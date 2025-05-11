<?

/* query exchange for price history for given time period and save in database */

function price_query($config, $pq) {

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
		case 'bitmax':
			$config['api_request'] = 'barhist';
			$config['price_history'] =
				$config['url'] .
				$config['price_history'] .
				'?symbol=' . $pq['pair'] .
				'&n=' . $pq['obs_iter'] .
				'&interval=' . $config['period_exchange'][$pq['period']]
			;
		break;
		case 'okex':
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

	# split the query into separate API requests if the number of records exceeds the maximum allowed
	for ($i; $i > 0; $i--) {

		echo
			'$from: ' . $from .
			' $to: ' . $to .
			' units: ' . floor(($to - $from) / $pq['period_ms']) .
			' of ' . floor(($pq['stop'] - $pq['obs_iter']) / $pq['period_ms']) .
			PHP_EOL
		;
		echo
			'$i:  ' . $i .
			' $j:   ' . $j .
			' $period_ms: ' . $pq['period_ms'] .
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

			case 'bitmax':
				$config['url'] =
					$config['price_history'] .
					'&from=' . $from .
					'&to=' . $to
				;
				break;

			case 'okex':
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

		echo 'url: ' . $config['url'] . PHP_EOL;

		$result = info($config);

		# process response
		if (!empty($result)) {

			$values = array();
			$values['pair'] = $pq['pair'];
			$values['source'] = $config['exchange'];
			$values['period'] = $config['period'][$pq['period']];
			$values['imputed'] = 0;

			# read the response in the array format for the exchange
			# okex already in correct format
			if ($config['exchange'] == 'twelve') {
				$values['pair'] = $result['meta']['symbol'];
				$result = $result['values'];
			}
			if ($config['exchange'] == 'bitmax') {
				$array = array();
				foreach ($result['data'] as $data) array_push($array, $data['data']);
				$result = $array;
			}

			echo 'result: ' . count((array)$result) . PHP_EOL;
			if ($config['debug']) print_r($result);

			# process result one row (price bar) at a time because it will be
			# easier to check the database if the record already exists using a single row
			if (!empty($result))
			foreach ($result as $price) {

				if ($config['exchange'] == 'twelve') {
					# server seems to expect requested data on separate dates
					$values['timestamp'] = strtotime($price['datetime'])  * 1000; # bar start time
					$values['open'] = $price['open'];
					$values['high'] = $price['high'];
					$values['low'] = $price['low'];
					$values['close'] = $price['close'];
					$values['volume'] = $price['volume'];
				}

				if ($config['exchange'] == 'bitmax') {
					# possible that server returns missing value for bar
					$values['timestamp'] = $price['ts']; # bar start time
					$values['open'] = $price['o'];
					$values['high'] = $price['h'];
					$values['low'] = $price['l'];
					$values['close'] = $price['c'];
					$values['volume'] = $price['v'];
				}

				if ($config['exchange'] == 'okex') {
					$values['pair'] = str_replace('-', '/', $values['pair']);
					$values['timestamp'] = strtotime($price[0]) * 1000; # convert to unix time
					$values['open'] = $price[1];
					$values['high'] = $price[2];
					$values['low'] = $price[3];
					$values['close'] = $price[4];
					$values['volume'] = $price[5];
				}

				# save row
				query('update_history', $config, $values);
				#var_dump($values);break 2;
				# option to skip deduplication within iteration
				# would still need to call price_history_dedupe after all iterations
				# not currently used, but could be for performance improvement
				if (!$pq['dedupe_hist']) continue 1;
				# check if duplicate price history record and remove oldest ones
				$values['filterquery'] = "
					AND a.pair = '" . $values['pair'] . "'
					AND a.source = '" . $values['source'] . "'
					AND a.timestamp = " . $values['timestamp'] . "
					AND a.period = '" . $values['period'] . "'
				";
				query('deduplicate_history', $config, $values);
			}
		}

		$from += $j;
		$to += $j;
		sleep(0.2); # use delay to reduce server load
	}
}

/* check for all possible duplicate price history record and remove oldest ones */

function price_history_dedupe($config) {

	# quick check if all records are unique
	$query_all = "
		SELECT COUNT(*) AS count FROM `price_history`
	";
	$total = query($query_all, $config);
	$query_dis = "
		SELECT COUNT(
			DISTINCT
			`pair`,
			`source`,
			`timestamp`,
			`period`
		) AS count
		FROM `price_history`
	";
	$dedupes = query($query_dis, $config);
	$dupes = $total[0]['count'] - $dedupes[0]['count'];
	echo $dupes . ' duplicates' . PHP_EOL;
	if (!$dupes) return;

	echo 'deduplicating ...';

	# list all possible asset pairs and periods
	$query_pair = "
		SELECT DISTINCT
		`pair`,
		`source`,
		`period`
		FROM `price_history`
	";
	$permutations = query($query_pair, $config);

	# process each possible pair
	$query_ded = "
		SELECT DISTINCT
		`pair`,
		`source`,
		`timestamp`,
		`period`
		FROM `price_history`
	";
	foreach ($permutations as $p) {

		$filter = "
			WHERE pair = '" . $p['pair'] . "'
			AND source =  '" . $p['source'] . "'
			AND period =  '" . $p['period'] . "'
		";
		$total = query($query_all . $filter, $config);
		$dedupes = query($query_ded . $filter, $config);
		$dupes = $total[0]['count'] - count($dedupes);

		if (!$dupes) continue 1;
		echo $dupes . ' dupes ' . $filter . PHP_EOL;

		foreach ($dedupes as $record) {
			$values['filterquery'] = "
				AND a.pair = '" . $record['pair'] . "'
				AND a.source = '" . $record['source'] . "'
				AND a.timestamp = " . $record['timestamp'] . "
				AND a.period = '" . $record['period'] . "'
			";
			query('deduplicate_history', $config, $values);
		}
	}
}

/* check for all possible price history records that do not belong to an asset pair and delete */

function price_history_trim($config) {

	# get all asset pairs
	$query = "
		SELECT DISTINCT
		pair,
		source,
		period
		FROM asset_pairs
	";
	$history = query($query, $config);

	# get history_id without asset pairs
	$query = "SELECT history_id FROM price_history WHERE 1=1";
	foreach ($history as $hist) {
		if ($config['debug'])
			foreach ($hist as $key => $val) {
				echo $key . ': ' . $val . ' ';
			}
		echo PHP_EOL;
		$query .= "
			AND (
				pair != '" . $hist['pair'] . "'
				AND source != '" . $hist['source'] . "'
				AND period != '" . $hist['period'] . "'
			)
		";
	}
	$drop_ids = query($query, $config);

	# delete those history_id if any
	echo PHP_EOL;
	if (!empty($drop_ids)) {
		$filter = "
			FROM price_history
			WHERE history_id
			IN (0
		";
		foreach ($drop_ids as $array) $filter .= ", " . $array['history_id'];
		$filter .= ")";
		$query = "
			SELECT DISTINCT
			pair,
			source,
			period
		";
		$result = query($query . $filter, $config);
		echo 'deleting history_id without asset pairs: ' . PHP_EOL;
		var_dump($result);
		$query = "DELETE ";
		query($query . $filter, $config);
	} else {
		echo 'no history_id without asset pairs';
	}

}

/* record the time of the earliest missing price history record */

function history_currency($config, $query, $pq) {

	$history = query($query, $config);
	$count = count($history);
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
	echo '$currency: UTC ' . date('Y-m-d H:i:s', $currency / 1000) . PHP_EOL;
	$query = "
		UPDATE asset_pairs
		SET currency = " . $currency . "
		WHERE pair_id = " . $pq['pair_id']
	;
	query($query, $config);

}

/* check if historical price data is current, and update */

function price_history($config, $pair_id = FALSE) {

	# select asset_pairs due for refreshing
	$query = "
		SELECT * FROM asset_pairs
		WHERE collect
		AND (
			(
				history_end > " . milliseconds() . "
				AND currency < (" . milliseconds() . " - refresh * 60000)
			) OR (
				history_end < " . milliseconds() . "
				AND currency < (history_end - period * 60000)
			)
		)
	";
	if ($pair_id) $query .= " AND pair_id = " . $pair_id;
	$pairs = query($query, $config);

	# check if any results
	if (empty($pairs)) echo 'price_history current' . PHP_EOL;
	else
	# process each result
	foreach ($pairs as $pair) {

		print_r($pair);

		# define exchange parameters
		$config['exchange'] = $pair['source'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair'] = $pair['pair'];
		$pq['source'] = $pair['source'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];
		$pq['start'] = $pair['currency'];
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

			case 'okex':
				if ((milliseconds() - $pq['start']) / $pq['period_ms'] > $config['obs_hist_max']) {
					# check if longer history available
					if (in_array($pair['pair'], $config['hist_pairs'])) {
						$pq['hist_long'] = TRUE;
					} else {
						$config['chatText'] =
							'requested price history too long for pair_id ' . $pair['pair_id'] .
							', you need to manually define earliest query in asset_pairs table'
						;
						telegram($config);
						echo $config['chatText'] . PHP_EOL;
						continue 2; # continue to next iteration in nearest containing loop (value is +1 when inside 'switch' but not 'if' statement, as of php 7.3)
					}
				}
			break;

		}

		# select recorded timestamps
		$query_part = "
			AND pair = '" . $pair['pair'] . "'
			AND source = '" . $pair['source'] . "'
			AND period = '" . $config['period'][$pq['period']] . "'
			AND timestamp >= " . $pq['start'] . "
			AND timestamp <= " . $pq['stop'] . "
			ORDER BY timestamp
		";
		$query = "
			SELECT timestamp
			FROM price_history
			WHERE (
				timestamp = (SELECT MIN(timestamp) FROM price_history WHERE 1=1 " . $query_part . ")
				OR timestamp = (SELECT MAX(timestamp) FROM price_history WHERE 1=1 " . $query_part . ")
			)
		";
		$history = query($query . $query_part, $config);
		$count = count($history);

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
			echo
				'history_start: ' . date('Y-m-d H:i:s', $pair['history_start'] / 1000) .
				' history_end: ' . date('Y-m-d H:i:s', $pair['history_end'] / 1000) . PHP_EOL
			;
			echo
				'$count: ' . $count .
				' $currency: ' . date('Y-m-d H:i:s', $pq['start'] / 1000) .
				' $pq[start]: ' . date('Y-m-d H:i:s', $pq['start'] / 1000) .
				' $oldest: ' . date('Y-m-d H:i:s', $oldest / 1000) . PHP_EOL
			;
		}

		# check if the earliest required record is in the database, and if not then query price history
		if ($oldest > $pq['start'] + $pq['period_ms']) {
			$pqa = $pq;
			$pqa['stop'] = $oldest + $pq['period_ms'];
			price_query($config, $pqa);
		}

		# we need to query the existing history again
		$query = "SELECT timestamp FROM price_history WHERE 1=1";
		$history = query($query . $query_part, $config);

		# if there is no existing records, there is a problem
		if (empty($history)) {
			$config['chatText'] =
				'unknown problem with price history update for pair_id ' . $pair['pair_id'] .
				', unable to query data'
			;
			telegram($config);
			echo $config['chatText'] . PHP_EOL;
			continue 1;
		}

		# impute missing price history records
		price_missing($config, $history, $pq);

		# update asset history summary
		$query_part = "
			AND pair = '" . $pair['pair'] . "'
			AND source = '" . $pair['source'] . "'
			AND period = '" . $config['period'][$pq['period']] . "'
			AND timestamp >= " . $pair['history_start'] . "
			AND timestamp <= " . $pair['history_end'] . "
			ORDER BY timestamp
		";
		history_currency($config, $query . $query_part, $pq);

	}

}

/* check if recent price history data is current, and update */

function price_recent($config, $pair_id = FALSE) {

	$query = "
		SELECT * FROM asset_pairs
		WHERE collect
		AND history_end > " . milliseconds() . "
		AND currency + refresh * 60000 < " . milliseconds()
	;
	if ($pair_id) $query .= " AND pair_id = " . $pair_id;
	$pairs = query($query, $config);

	if ($config['debug']) echo $query . PHP_EOL;

	if (empty($pairs)) echo 'price_history current' . PHP_EOL;
	else
	foreach ($pairs as $pair) {

		print_r($pair);

		# define exchange parameters
		$config['exchange'] = $pair['source'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair_id'] = $pair['pair_id'];
		$pq['pair'] = $pair['pair'];
		$pq['source'] = $pair['source'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];
		$pq['start'] = $pair['currency'];

		# define time parameters
		$i = floor((milliseconds() - $pq['start']) / $pq['period_ms']);
		if ($i > $config['obs_curr_max']) $i = $config['obs_curr_max'];
		$pq['stop'] = $pq['start'] + $i * $pq['period_ms'];

		# exit if time period incorrect
		if ($pq['start'] >= $pq['stop']) continue 1;

		# query price history first
		# if the history is complete it will not execute
		price_query($config, $pq);

		# ignore weekends for certain asset classes
		$pq['weekends'] = weekends($pair['class']);

		$query = "
			SELECT timestamp FROM price_history
			WHERE pair = '" . $pq['pair'] . "'
			AND source = '" . $pq['source'] . "'
			AND period = '" . $config['period'][$pq['period']] . "'
			AND timestamp >= " . $pq['start'] . "
			ORDER BY timestamp
		";

		history_currency($config, $query, $pq);

	}

}

/* impute missing price history records */

function price_missing($config, $history, $pq) {

	$count = count($history);
	$oldest = $history[0]['timestamp'];
	$newest = $history[$count - 1]['timestamp'];

	# create array of timestamps
	$hist = array();
	foreach ($history as $array) array_push($hist, $array['timestamp']);

	# print any missing records
	echo 'missing: ' . PHP_EOL;

	# process each timestamp
	for ($i = 1; $i < $count; $i++) {
		# check if it indicates a broken sequence of timestamps
		# otherwise resume loop
		if ($hist[$i] - $hist[$i - 1] !== $pq['period_ms']) {
			# define the first missing record in the sequence
			$from = $hist[$i - 1] + $pq['period_ms'];
			# define the last missing record in the sequence, could be the same as the first
			$to = $hist[$i] - $pq['period_ms'];
			# query the API for the missing record(s)
			$pq['start'] = $from;
			$pq['stop'] = $to;
			price_query($config, $pq);
			# check if missing record is returned
			$query_sub = "
				SELECT * FROM price_history
				WHERE pair = '" . $pq['pair'] . "'
				AND source = '" . $pq['source'] . "'
				AND period = '" . $config['period'][$pq['period']] . "'" .
				/* query records from either side of the missing record(s) */
				"AND timestamp >= " . ($from - $pq['period_ms']) . "
				AND timestamp <= " . ($to + $pq['period_ms']) .
				/* option to not impute values from today or yesterday */
				/* " AND timestamp < " . (milliseconds() - 1440 * 2) . */
				" ORDER BY timestamp
			";
			$sub_hist = query($query_sub, $config);
			# check if missing records have now been updated
			if (empty($sub_hist) || count($sub_hist) < 2) break 1;
			# otherwise impute missing records
			# create array of timestamps
			$timestamps = array();
			foreach ($sub_hist as $sub) array_push($timestamps, $sub['timestamp']);
			# process each timestamp
			for ($j = 1; $j < count($timestamps); $j++) {
				$from = $timestamps[$j - 1];
				# check timestamp array is valid
				if (!isset($timestamps[$j])) {
					echo 'ERROR $j: ' . $j . PHP_EOL;
					var_dump($timestamps);
					die;
				}
				$to = $timestamps[$j];
				# count missing records
				$iter = ($to - $from) / $pq['period_ms'];
				# if there will be a long string of missing records, exit
				# accounting for weekends
				if ($pq['weekends']) {
					if ($iter > $config['obs_imp_max']) continue 1;
				} else {
					$wmiss = count_on_weekends($from, $to, $pq['period_ms']);
					if ($iter - $wmiss > $config['obs_imp_max']) continue 1;
				}
				# iterate for each missing record
				for ($k = 1; $k < $iter; $k++) {
					# define imputed values using last known record
					$values = array();
					$values['pair'] = $pq['pair'];
					$values['source'] = $pq['source'];
					$values['timestamp'] = $from + $k * $pq['period_ms'];
					$values['period'] = $sub_hist[$j - 1]['period'];
					$values['open'] = $sub_hist[$j - 1]['open'];
					$values['close'] = $sub_hist[$j - 1]['close'];
					$values['high'] = $sub_hist[$j - 1]['high'];
					$values['low'] = $sub_hist[$j - 1]['low'];
					$values['volume'] = $sub_hist[$j - 1]['volume'];
					$values['imputed'] = 1;
					if ($config['debug']) var_dump($values);
					# update database
					query('update_history', $config, $values);
				}
			}
		}
	}

}

/* for any imputed records, try to retrieve actual records */

function price_history_imputed($config) {

	$query = "SELECT * FROM asset_pairs WHERE collect";
	$pairs = query($query, $config);

	if (!empty($pairs))
	foreach ($pairs as $pair) {

		print_r($pair);

		# define exchange parameters
		$config['exchange'] = $pair['source'];
		$config = config_exchange($config);

		# array format defined in config.php
		$pq = $config['price_query'];
		$pq['pair_id'] = $pair['pair_id'];
		$pq['pair'] = $pair['pair'];
		$pq['source'] = $pair['source'];
		$pq['period'] = $pair['period'];
		$pq['period_ms'] = $pq['period'] * 60000;
		$pq['obs_iter'] = $config['obs_iter_max'];

		# query imputed records
		$query_sub = "
			SELECT * FROM price_history
			WHERE pair = '" . $pq['pair'] . "'
			AND source = '" . $pq['source'] . "'
			AND period = '" . $config['period'][$pq['period']] . "'
			AND imputed = 1
			ORDER BY timestamp
		";
		$sub_hist = query($query_sub, $config);

		# check if imputed records exist
		if (empty($sub_hist)) continue 1;

		# check if the asset pair is only reported on weekends
		/*
		if (!$pq['weekends']) {
			$missing = count_on_weekends($from, $to, $period_ms);
			if (!$missing) continue 1;
		}
		*/

		# create array of timestamps
		$timestamps = array();
		foreach ($sub_hist as $sub) array_push($timestamps, $sub['timestamp']);

		$count = count($timestamps);
		if ($count == 1) $count = 2;

		$pq['start'] = $timestamps[0];
		for ($j = 1; $j < $count; $j++) {
			# check if timestamp is not a sequence with the previous one
			if ($timestamps[$j] - $timestamps[$j - 1] !== $pq['period_ms']) {
				# if it is a new sequence then query the previous sequence
				$pq['stop'] = $timestamps[$j - 1];
				# query the API for the missing record(s)
				price_query($config, $pq);
				# define the new sequence
				$pq['start'] = $timestamps[$j];
			}
			# check if the final timestamp
			if ($j == $count) {
				# query the final sequence
				$pq['stop'] = $timestamps[$j];
				price_query($config, $pq);
			}
		}
	}
}

function info_uniswap($config) {

	# https://uniswap.org/docs/v2/API/queries/#pair-data
	# create as array, then json_encode
	/*
	$query = '
{
pair(id: "0xa478c2975ab1ea89e8196811f51a7b7ade33eb11"){
		 token0 {
			 id
			 symbol
			 name
			 derivedETH
		 }
		 token1 {
			 id
			 symbol
			 name
			 derivedETH
		 }
		 reserve0
		 reserve1
		 reserveUSD
		 trackedReserveETH
		 token0Price
		 token1Price
		 volumeUSD
		 txCount
 }
}';

		$config['url'] = $config['url_uniswap_graph'] . $query;
*/

}
