<?php

/* query exchange for price history for given time period and save in database */

function price_query($config, $pq) {

	$response = $config['response'];

	# make query string
	switch ($config['exchange']) {
		case 'twelve':
			$config['price_history'] =
				$config['url'] .
				$config['price_history'] .
				'?symbol=' . $pq['pair'] .
				'&apikey=' . $config['api_key'] .
				'&interval=' . $config['period_exchange'][$pq['period']] .
				'&outputsize=' . $pq['obs_iter'] .
				'&format=JSON&timezone=UTC'
			;
		break;
		case 'ascendex':
			$config['api_request'] = $config['price_history_hash'];
			$config['price_history'] =
				$config['url'] .
				$config['price_history'] .
				'?symbol=' . $pq['pair'] .
				'&n=' . $pq['obs_iter'] .
				'&interval=' . $config['period_exchange'][$pq['period']]
			;
		break;
		case 'okex_spot':
		case 'okex_margin':
			$config['price_history'] .= str_replace('/', '-', $pq['pair']);
			if ($pq['hist_long']) $config['price_history'] .= '/history';
			$config['price_history'] = $config['url'] . $config['price_history'];
		break;
	}

	# set start time; stop at current time; add number of periods minutes;
	$j = ($pq['period_ms'] * $pq['obs_iter']);
	$i = ceil(($pq['stop'] - $pq['start']) / $j);
	if ($i > 1) {
		$to = $pq['start'] + $j - $pq['period_ms']; # we need 1 period less than the maximum for looping
	} else {
		$i = 1; # in case $i = 0
		$j = $pq['stop'] - $pq['start']; # unless there's only 1 loop
		$to = $pq['stop'];
	}
	$from = $pq['start'];

	# count records inserted
	$inserted = 0;

	# split the query into separate API requests if the number of records exceeds the maximum allowed
	for ($i; $i > 0; $i--) {

		$response['msg'] .=
			'query: ' .
			'pair_id ' . $pq['pair_id'] . ', ' .
			'from "' . unixtime_datetime($from) . '" ' .
			'to "' . unixtime_datetime($to) . '"' .
			PHP_EOL .
			'expect: up to ' . (floor(($to - $from) / $pq['period_ms']) + 1) . ' records ' .
			PHP_EOL
		;

		# create the API request string
		switch ($config['exchange']) {

			case 'twelve':
				$config['url'] =
					$config['price_history'] .
					'&start_date=' . date('Y-m-d H:i:s', $from / 1000) .
					'&end_date=' . date('Y-m-d H:i:s', $to / 1000)
				;
				$config['url'] = str_replace(' ', '&nbsp;', $config['url']);
			break;

			case 'ascendex':
				$config['url'] =
					$config['price_history'] .
					'&from=' . $from .
					'&to=' . $to
				;
				break;

			case 'okex_spot':
			case 'okex_margin':
				if ($pq['hist_long'])
					$config['api_request'] =
						'/candles' .
						# okex dates in reverse order for /history
						# seems like a bug in their code
						'?start=' . date('Y-m-d\TH:i:s.000', $to / 1000) . 'Z' .
						'&end=' . date('Y-m-d\TH:i:s.000', $from / 1000) . 'Z' .
						'&granularity=' . $config['period_exchange'][$pq['period']]
					;
				else
					$config['api_request'] =
						'/candles' .
						'?granularity=' . $config['period_exchange'][$pq['period']] .
						# okex dates in normal order for not /history
						'&start=' . date('Y-m-d\TH:i:s.000', $from / 1000) . 'Z' .
						'&end=' . date('Y-m-d\TH:i:s.000', $to / 1000) . 'Z'
					;
				$config['url'] = $config['price_history'] . $config['api_request'];
			break;

		}

		if ($config['debug']) $response['msg'] .= 'url: ' . $config['url'] . PHP_EOL;

		$result = query_api($config);

		if (empty($result)) $response['msg'] .= 'result: 0 records' . PHP_EOL;

		# process response
		if (!empty($result)) {

			# read the response in the array format for the exchange
			# okex already in correct format
			if ($config['exchange'] == 'twelve') {
				#$values['pair'] = $result['meta']['symbol'];
				$result = $result['values'];
			}
			if ($config['exchange'] == 'ascendex') {
				$array = array();
				foreach ($result['data'] as $data) array_push($array, $data['data']);
				$result = $array;
			}

			# get timestamp range
			$start = 1999999999999;
			$stop = 0;

			# process result one row (price bar) at a time because it will be
			# easier to check the database if the record already exists using a single row
			$i = 0;
			if (!empty($result))
			foreach ($result as $price) {

				$values = array();
				$values['pair_id'] = $pq['pair_id'];
				$values['imputed'] = 0;

				if ($config['exchange'] == 'twelve') {
					# server seems to expect requested data on separate dates
					$values['timestamp'] = strtotime($price['datetime'])  * 1000; # bar start time
					$values['open'] = $price['open'];
					$values['high'] = $price['high'];
					$values['low'] = $price['low'];
					$values['close'] = $price['close'];
					$values['volume'] = isset($price['volume']) ? $price['volume'] : 0;
				}

				if ($config['exchange'] == 'ascendex') {
					# possible that server returns missing value for bar
					$values['timestamp'] = $price['ts']; # bar start time
					$values['open'] = $price['o'];
					$values['high'] = $price['h'];
					$values['low'] = $price['l'];
					$values['close'] = $price['c'];
					$values['volume'] = $price['v'];
				}

				if ($config['exchange'] == 'okex_spot' || $config['exchange'] == 'okex_margin') {
					#$values['pair'] = str_replace('-', '/', $values['pair']);
					$values['timestamp'] = strtotime($price[0]) * 1000; # convert to unix time
					$values['open'] = $price[1];
					$values['high'] = $price[2];
					$values['low'] = $price[3];
					$values['close'] = $price[4];
					$values['volume'] = $price[5];
				}


				/*
				new code snippet
				if delete from ana_price_history on query,
				first check if price has changed
				if so delete from ana_price first
				*/


				# save row
				$inserted += query('insert_price_history', $config, $values);
				# get timestamp range
				if ($values['timestamp'] > $stop) $stop = $values['timestamp'];
				if ($values['timestamp'] < $start) $start = $values['timestamp'];
				# option to skip deduplication within iteration
				# would still need to call price_history_dedupe after all iterations
				# not currently used, but could be for performance improvement
				if (!$pq['dedupe_hist']) continue 1;
				# check if duplicate price history record and remove oldest ones
				$values['filterquery'] = "
					AND a.pair_id = '" . $pq['pair_id'] . "'
					AND a.timestamp = " . $values['timestamp'] . "
				";
				query('deduplicate_history', $config, $values);
			}

			$response['msg'] .= 'result: ' . $inserted . ' records inserted';
			if (!empty($result)) $response['msg'] .=
				', ' .
				'from "' . unixtime_datetime($start) . '" ' .
				'to "' . unixtime_datetime($stop) . '"'
				;
			$response['msg'] .= PHP_EOL;
			if ($config['debug']) $response['msg'] .= var_export($result, TRUE);
			$response['count'] += $inserted;

		}

		$from += $j;
		$to += $j;
		sleep(0.2); # use delay to reduce server load
	}

	return $response;

}

/* check for all possible duplicate price history record and remove oldest ones */

function price_history_dedupe($config) {

	$response = $config['response'];

	# quick check if all records are unique
	$query_all = "
		SELECT COUNT(*) AS count FROM price_history
	";
	$total = query($query_all, $config);
	$query_dis = "
		SELECT COUNT(
			DISTINCT
			pair_id,
			timestamp
		) AS count
		FROM price_history
	";
	$dedupes = query($query_dis, $config);
	$dupes = $total[0]['count'] - $dedupes[0]['count'];
	$response['msg'] .= $dupes . ' duplicates' . PHP_EOL;
	if (!$dupes) return $response;

	$response['msg'] .= 'deduplicating ...' . PHP_EOL;

	# list all possible asset pairs and periods
	$query_pair = "SELECT DISTINCT pair_id FROM price_history";
	$permutations = query($query_pair, $config);

	# process each possible pair
	$query_ded = "
		SELECT DISTINCT
		pair_id,
		timestamp
		FROM price_history
	";
	foreach ($permutations as $p) {

		$filter = "
			WHERE pair_id = '" . $p['pair_id'] . "'
		";
		$total = query($query_all . $filter, $config);
		$dedupes = query($query_ded . $filter, $config);
		$dupes = $total[0]['count'] - count($dedupes);

		if (!$dupes) continue 1;
		$response['msg'] .= $dupes . ' dupes ' . $filter . PHP_EOL;
		$response['count'] += $dupes;

		foreach ($dedupes as $record) {
			$values['filterquery'] = "
				AND a.pair_id = '" . $record['pair_id'] . "'
				AND a.timestamp = " . $record['timestamp'] . "
			";
			query('deduplicate_history', $config, $values);
		}
	}

	return $response;

}

/* check for all possible price history records that do not belong to an asset pair and delete */

function price_history_trim($config) {

	$response = $config['response'];

	# get all asset pairs
	$query = "SELECT pair_id FROM asset_pairs";
	$pairs = query($query, $config);
	$sep = '';
	$filter = '';
	foreach ($pairs as $pair) {
		$filter .= $sep . $pair['pair_id'];
		$sep = ',';
	}

	# delete price_history without asset pairs
	$query = "
		DELETE FROM price_history
		WHERE pair_id NOT IN (" . $filter . ")
		OR ISNULL(pair_id)";
	$result = query($query, $config);

	$response['msg'] = 'deleted: ' . $result . ' history_id without asset pairs from price_history' . PHP_EOL;

	return $response;

}

/* record the time of the earliest missing price history record */

function history_currency($config, $pq) {

	$response = $config['response'];
	$query = "
		SELECT timestamp
		FROM price_history
		WHERE pair_id = '{$pq['pair_id']}'
		ORDER BY timestamp
	";
	$history = query($query, $config);
	if (empty($history)) {
		$response['msg'] .= 'currency: no records found' . PHP_EOL;
		return $response;
	}

	$count = count($history);
	$currency_start = $history[0]['timestamp'];
	$currency = $history[$count - 1]['timestamp'];

	$hist = array();
	if (!empty($history)) {

		foreach ($history as $array) {
			array_push($hist, $array['timestamp']);
		}

		for ($i = 1; $i < $count; $i++) {
			if ($hist[$i] - $hist[$i - 1] !== $pq['period_ms']) {
				# expect some asset pairs are not recorded on weekends
				if (!$pq['weekends']) {
					$missing = 0;
					for (
						$j = $hist[$i - 1] + $pq['period_ms'];
						$j < $hist[$i];
						$j += $pq['period_ms']
					) {
						$day = date('l', $j / 1000);
						if (!($day == 'Sunday' || $day == 'Monday')) $missing++;
					}
					if (!$missing) continue 1;
				} else {
					$currency = $hist[$i - 1];
					break 1;
				}
			}
		}
	}

	$query = "
		UPDATE asset_pairs
		SET
		currency_start = " . $currency_start . ",
		currency_end = " . $currency . "
		WHERE pair_id = " . $pq['pair_id']
	;
	query($query, $config);

	$response['msg'] .=
		'currency: records complete ' .
		'from "' . date('Y-m-d H:i:s', $currency_start / 1000) . '" ' .
		'to "' . date('Y-m-d H:i:s', $currency / 1000) . '" ' .
		'for pair_id ' . $pq['pair_id'] .
		PHP_EOL
	;

	return $response;

}

/* check if historical price data is current, and update */

function price_history($config, $pair_id = FALSE) {

	$response = $config['response'];

	# select asset_pairs due for refreshing
	$query = "
		SELECT * FROM asset_pairs
		WHERE collect
		AND (
			(
				history_end > " . milliseconds() . "
				AND currency_end < (" . milliseconds() . " - refresh * 60000)
			) OR (
				history_end < " . milliseconds() . "
				AND currency_end < (history_end - period * 60000)
			)
		)
	";
	if ($pair_id) $query .= " AND pair_id = " . $pair_id;
	$pairs = query($query, $config);

	# check if any results
	if (empty($pairs)) $response['msg'] .= 'asset pair(s) current';
	else
	# process each result
	foreach ($pairs as $pair) {

		if ($config['debug']) $response['msg'] .= var_export($pair, TRUE);

		# define exchange parameters
		$config['exchange'] = $pair['exchange'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair_id'] = $pair['pair_id'];
		$pq['pair'] = $pair['pair'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];
		$pq['start'] = $pair['currency_end'];
		$pq['stop'] = milliseconds();

		# define time parameters
		if ($pair['history_start'] > $pq['start']) $pq['start'] = $pair['history_start'];
		if ($pair['history_end'] < $pq['stop']) $pq['stop'] = $pair['history_end'];

		# exit if time period incorrect
		if ($pq['start'] >= $pq['stop']) continue 1;

		# ignore weekends for certain asset classes
		# and set start - 3 days
		$pq['weekends'] = weekends($pair['class']);
		if (!$pq['weekends']) $pq['start'] = $pq['start'] - 1440 * 60000 * 3;

		# some APIs limit available history for some coins, check if longer history needed
		switch ($config['exchange']) {

			case 'okex_spot':
			case 'okex_margin':
				$obs_request = (milliseconds() - $pq['start']) / $pq['period_ms'];
				if ($obs_request > $config['obs_hist_max']) {
					# check if longer history available
					if (in_array($pair['pair'], $config['hist_pairs'])) {
						$pq['hist_long'] = TRUE;
					} else {
						$response['msg'] .=
							'requested price history too long for pair_id ' . $pair['pair_id'] .
							', ' . $obs_request . ' observations requested, only ' .
							$config['obs_hist_max'] . ' provided by API, ' .
							'you need to disable collection flag in asset_pairs table' .
							PHP_EOL .
							'(' . milliseconds() . ' - ' . $pq['start'] . ') / ' . $pq['period_ms'] . ' > ' . $config['obs_hist_max'] .
							PHP_EOL
						;
						$response['alert'] = TRUE;
						continue 2; # continue to next iteration in nearest containing loop (value is +1 when inside 'switch' but not 'if' statement, as of php 7.3)
					}
				}
			break;

		}

		# select recorded timestamps
		$query_part = "
			AND pair_id = '" . $pair['pair_id'] . "'
			AND timestamp >= " . $pq['start'] . "
			AND timestamp <= " . $pq['stop'] . "
			ORDER BY timestamp
		";
		$query = "
			SELECT timestamp
			FROM price_history
			WHERE (
				timestamp = (SELECT MIN(timestamp) FROM price_history WHERE 1 " . $query_part . ")
				OR timestamp = (SELECT MAX(timestamp) FROM price_history WHERE 1 " . $query_part . ")
			)
		";
		$history = query($query . $query_part, $config);
		if (!empty($history)) {
			$count = count($history);
		} else {
			$count = 0;
		}

		# define query result parameters
		if ($count <= 1) {
			# handle exception of small result
			$oldest = milliseconds();
			$newest = milliseconds();
		} else {
			$oldest = $history[0]['timestamp'];
			$newest = $history[1]['timestamp'];
		}

		# print parameters
		if ($config['debug']) {
			$response['msg'] .=
				'history_start: ' . date('Y-m-d H:i:s', $pair['history_start'] / 1000) .
				' history_end: ' . date('Y-m-d H:i:s', $pair['history_end'] / 1000) . PHP_EOL
			;
			$response['msg'] .=
				'$count: ' . $count .
				' $currency: ' . date('Y-m-d H:i:s', $pq['start'] / 1000) .
				' $pq[start]: ' . date('Y-m-d H:i:s', $pq['start'] / 1000) .
				' $oldest: ' . date('Y-m-d H:i:s', $oldest / 1000) . PHP_EOL
			;
		}

		#var_dump($pq);
		#echo $oldest . PHP_EOL;
		#echo $newest . PHP_EOL;
		#var_dump($history);die;

		# check if the earliest required record is in the database, and if not then query price history
		if ($oldest > $pq['start'] + $pq['period_ms']) {
			$pqa = $pq;
			$pqa['stop'] = $oldest + $pq['period_ms'];
			$response_x = price_query($config, $pqa);
			$response['msg'] .= $response_x['msg'];
			$response['count'] += $response_x['count'];
		}

		# we need to query the existing history again
		$query = "SELECT timestamp FROM price_history WHERE 1";
		$history = query($query . $query_part, $config);

		# if there is no existing records, there is a problem
		if (empty($history)) {
			$response['msg'] .=
				'unknown problem with price history update for pair_id ' . $pair['pair_id'] .
				', unable to query data'
			;
			$response['alert'] = TRUE;
			continue 1;
		}

		# impute missing price history records
		$response_x = price_missing($config, $history, $pq);
		$response['msg'] .= $response_x['msg'];
		$response['count'] += $response_x['count'];

		# update asset history summary
		$response_x = history_currency($config, $pq);
		$response['msg'] .= $response_x['msg'];

	}

	return $response;

}

/* check if recent price history data is current, and update */

function price_recent($config, $pair_id = FALSE) {

	$response = $config['response'];

	$query = "
		SELECT * FROM asset_pairs
		WHERE collect
		AND history_end > " . milliseconds() . "
		AND currency_end + refresh * 60000 < " . milliseconds()
	;
	if ($pair_id) $query .= " AND pair_id = " . $pair_id;
	$pairs = query($query, $config);

	# check if any results
	if (empty($pairs)) $response['msg'] .= 'asset pair(s) current';
	else
	foreach ($pairs as $pair) {

		if ($config['debug']) $response['msg'] .= var_export($pair, TRUE);

		# define exchange parameters
		$config['exchange'] = $pair['exchange'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair_id'] = $pair['pair_id'];
		$pq['pair'] = $pair['pair'];
		#$pq['exchange'] = $pair['exchange'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];
		$pq['start'] = $pair['currency_end'];

		# define time parameters
		$i = floor((milliseconds() - $pq['start']) / $pq['period_ms']);
		if ($i > $config['obs_curr_max']) $i = $config['obs_curr_max'];
		$pq['stop'] = intval($pq['start'] + $i * $pq['period_ms']);

		# exit if time period incorrect or currency equals next interval
		if ($pq['start'] >= $pq['stop']) continue 1;

		# query price history first
		# if the history is complete it will not execute
		$response_x = price_query($config, $pq);
		$response['msg'] .= $response_x['msg'];
		$response['count'] += $response_x['count'];

		# ignore weekends for certain asset classes
		$pq['weekends'] = weekends($pair['class']);

		# update asset history summary
		$response_x = history_currency($config, $pq);
		$response['msg'] .= $response_x['msg'];

	}

	return $response;

}

/* impute missing price history records */

function price_missing($config, $history, $pq) {

	$response = $config['response'];

	$count = count($history);
	$oldest = $history[0]['timestamp'];
	$newest = $history[$count - 1]['timestamp'];

	# create array of timestamps
	$hist = array();
	foreach ($history as $array) array_push($hist, $array['timestamp']);

	# process each timestamp
	for ($i = 1; $i < $count; $i++) {
		# check if it indicates a broken sequence of timestamps
		# otherwise resume loop
		if ($hist[$i] - $hist[$i - 1] !== $pq['period_ms']) {
			# define the first missing record in the sequence
			$from = $hist[$i - 1] + $pq['period_ms'];
			# define the last missing record in the sequence, could be the same as the first
			$to = $hist[$i] - $pq['period_ms'];
			# print number of missing records
			$missing = floor(($to - $from) / $pq['period_ms']) + 1;
			$response['msg'] .=
				'missing: ' . $missing . ' records, ' .
				'"' . unixtime_datetime($from) . '" to ' .
				'"' . unixtime_datetime($to) . '"' . PHP_EOL
			;
			# query the API for the missing records
			$pq['start'] = min($from, $to);
			$pq['stop'] = max($to, $from);
			$response_x = price_query($config, $pq);
			$response['msg'] .= $response_x['msg'];
			$response['count'] += $response_x['count'];
			# check if missing record is returned
			# query both existing records and any new in between
			$query_sub = "
				SELECT
				timestamp,
				open,
				close,
				high,
				low,
				volume
				FROM price_history
				WHERE pair_id = '" . $pq['pair_id'] . "' " .
				/* query records from either side of the missing records */
				"AND timestamp >= " . ($from - $pq['period_ms']) . "
				AND timestamp <= " . ($to + $pq['period_ms']) .
				/* option to not impute values from today or yesterday */
				/* " AND timestamp < " . (milliseconds() - 1440 * 2) . */
				" ORDER BY timestamp
			";
			$sub_hist = query($query_sub, $config);
			# if any missing records have now been updated with price_query
			# then we will skip imputing them below
			# otherwise impute remaining missing records
			# create array of timestamps
			$timestamps = array();
			foreach ($sub_hist as $sub) array_push($timestamps, $sub['timestamp']);
			# count imputations
			$imputed = 0;
			# process each timestamp
			for ($j = 1; $j < count($timestamps); $j++) {
				if ($config['debug']) $response['msg'] .= 'timestamp pair ' . $j . ' of ' . (count($timestamps) - 1) . PHP_EOL;
				$from = $timestamps[$j - 1];
				# check timestamp array is valid
				if (!isset($timestamps[$j])) {
					$response['msg'] = 'failed: imputing timestamps for pair_id ' . $pq['pair_id'];
					$response['alert'] = TRUE;
					$response['error'] = TRUE;
					return $response;
				}
				$to = $timestamps[$j];
				# count missing records
				$iter = (($to - $from) / $pq['period_ms']) - 1;
				if ($iter == 0) continue 1;
				if ($config['debug']) $response['msg'] .= $iter . ' to impute for this timestamp pair' . PHP_EOL;
				# if there will be a long string of missing records, exit
				# accounting for weekends
				if ($pq['weekends']) $wmiss = 0;
				else $wmiss = nobs_weekends($from, $to, $pq['period_ms']);
				if ($iter - $wmiss > $config['obs_imp_max']) {
					$response['msg'] .= 'imputed: no possible, ' . ($iter - $wmiss) . ' missing, max allowed is ' . $config['obs_imp_max'] . PHP_EOL;
					continue 1;
				}
				# iterate for each missing record
				$imputed = 0;
				for ($k = 1; $k <= $iter; $k++) {
					# define imputed values using last known record
					$values = array();
					$values['pair_id'] = $pq['pair_id'];
					#$values['pair'] = $pq['pair'];
					#$values['exchange'] = $pq['exchange'];
					$values['timestamp'] = $from + $k * $pq['period_ms'];
					#$values['period'] = $sub_hist[$j - 1]['period'];
					$values['open'] = $sub_hist[$j - 1]['open'];
					$values['close'] = $sub_hist[$j - 1]['close'];
					$values['high'] = $sub_hist[$j - 1]['high'];
					$values['low'] = $sub_hist[$j - 1]['low'];
					$values['volume'] = $sub_hist[$j - 1]['volume'];
					$values['imputed'] = 1;
					if ($config['debug']) $response['msg'] .= var_export($values, TRUE);
					# update database
					$imputed += query('insert_price_history', $config, $values);
				}
				$response['msg'] .= 'imputed: ' . $imputed . ' records ' . PHP_EOL;
				$response['count'] += $imputed;
			}
		}
	}

	return $response;

}

/* for any imputed records, try to retrieve actual records */

function price_history_imputed($config, $pair_id = FALSE) {

	$response = $config['response'];

	$replaceable = FALSE;

	$query = "SELECT * FROM asset_pairs WHERE collect";
	if ($pair_id) $query .= " AND pair_id = '" . $pair_id . "'";
	$pairs = query($query, $config);

	if (!empty($pairs))
	foreach ($pairs as $pair) {

		if ($config['debug']) $response['msg'] .= var_export($pair, TRUE);

		# define exchange parameters
		$config['exchange'] = $pair['exchange'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair_id'] = $pair['pair_id'];
		$pq['pair'] = $pair['pair'];
		#$pq['exchange'] = $pair['exchange'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];
		$pq['weekends'] = weekends($pair['class']);

		# query imputed records
		$query_sub = "
			SELECT history_id, timestamp
			FROM price_history
			WHERE pair_id = '" . $pq['pair_id'] . "'
			AND imputed = 1
		";
		# if not reported weekends, limit query to mon (0) to fri (4)
		if (!$pq['weekends']) $query_sub .= "
			AND weekday(from_unixtime((timestamp / 1000))) < 5
		";
		$query_sub .= "
			ORDER BY timestamp
		";
		$sub_hist = query($query_sub, $config);

		# check if imputed records exist
		if (empty($sub_hist)) continue 1;
		$replaceable = TRUE;

		# create array of timestamps
		$timestamps = array();
		foreach ($sub_hist as $sub) array_push($timestamps, $sub['timestamp']);

		# count replace records
		$inserted = 0;

		# attempt to replace records
		$pq['start'] = $timestamps[0];
		$count = count($timestamps);

		if ($count == 1) {
			$pq['start'] = $timestamps[0];
			$pq['stop'] = $timestamps[0];
			$response_x = price_query($config, $pq);
			$response['msg'] .= $response_x['msg'];
			$inserted += $response_x['count'];
		} else

		for ($j = 1; $j < $count; $j++) {
			# check if timestamp is not a sequence with the previous one
			if ($timestamps[$j] - $timestamps[$j - 1] !== $pq['period_ms']) {
				# if it is a new sequence then query the previous sequence
				$pq['stop'] = $timestamps[$j - 1];
				# query the API for the missing records
				$response_x = price_query($config, $pq);
				$response['msg'] .= $response_x['msg'];
				$inserted += $response_x['count'];
				# define the new sequence
				$pq['start'] = $timestamps[$j];
			}
			# check if the final timestamp
			if ($j == ($count - 1)) {
				# query the final sequence
				$pq['stop'] = $timestamps[$j];
				$response_x = price_query($config, $pq);
				$response['msg'] .= $response_x['msg'];
				$inserted += $response_x['count'];
			}
		}
		$response['msg'] .=
			'query: found ' . $inserted .
			' actual records to replace ' .
			$count . ' imputed records for pair_id ' .
			$pq['pair_id'] .
			PHP_EOL
		;
		$response['count'] += $inserted;

		# if records were replaced, reclaculate the technical analysis
		if ($inserted) {
			$response_x = technical_analysis($config, $pq['pair_id'], $timestamps[0]);
			$response['msg'] .= $response_x['msg'];
		}
	}

	if (!$replaceable) $response['msg'] .= 'result: no replaceable imputed records';

	return $response;

}

/* get last price for pair */

function price_last($config, $pair) {

	$response = $config['response'];

	switch($config['exchange']) {

		case 'okex_spot':
		case 'okex_margin':
			$config['api_request'] = $config['pairs_info'] . str_replace('/', '-', $pair) . '/ticker';
			$config['url'] .= $config['api_request'];
			$result = query_api($config);
			$response['result']['lowest_sell'] = $result['best_ask'];
			$response['result']['highest_buy'] = $result['best_bid'];
		break;

	}

	return $response;

}
