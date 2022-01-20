<?php

/*
default: system_diagnostic.php
	execute via browser, prints results on screen
automated: system_diagnostic.php?alert=TRUE
	execute via cron job, sends error messages to alert bot
trade: system_diagnostic.php?trade=TRUE
	execute live trades, from USDT to ETH and back with market order
	expects 20 USDT in account
exchange: system_diagnostic.php?exchange=name
	limit connection test to single exchange
die: system_diagnostic.php?die=FALSE
	ignore fatal errors
convert_timestamps: system_diagnostic.php?convert_timestamps=TRUE
	update timestamps from unix times in database
debug: system_diagnostic.php?debug=TRUE
	do not delete records inserted into database, to allow manual inspection
truncate_tactics: system_diagnostic.php?truncate_tactics=TRUE
	delete all tactics in database, use with caution
truncate_pairs: system_diagnostic.php?truncate_pairs=TRUE
	delete all asset_pairs in database, use with caution
	option to retain asset pairs, for instance
	system_diagnostic.php?truncate_pairs=TRUE&retain_pairs=1,17,26
*/

include('system_includes.php');
if (isset($_GET['alert']) && $_GET['alert'] == 'TRUE') $alert = TRUE;
else $alert = FALSE;
if (isset($_GET['die']) && $_GET['die'] == 'FALSE') $die = FALSE;
else $die = TRUE;
if (isset($_GET['exchange'])) $exchanges = array($_GET['exchange']);
else $exchanges = $config['exchanges_trade'];


$sep = PHP_EOL . '-------------------------------' . PHP_EOL;
$sep2 = $sep . '-------------------------------' . PHP_EOL;
$sep .= PHP_EOL;

$response = $config['response'];
$response['msg'] = $sep2 . 'checking for errors' . $sep2;
process($response, $config);


$test = 'php timezone set to ' . $config['timezone_php'];
$response = $config['response'];
if (date_default_timezone_get() == $config['timezone_php']) {
	$response['msg'] = 'ok: ' . $test;
} else {
	$response['msg'] = 'failed: ' . $test . ', currently ' . date_default_timezone_get();
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);

$test = 'php version set to known working version 7.x.x, running ' . phpversion();
$response = $config['response'];
if (version_compare(phpversion(),"8.0.0",'<')
&& version_compare(phpversion(),"7.0.0",'>=')) {
	$response['msg'] = 'ok: ' . $test;
} else {
	$response['msg'] = 'failed: ' . $test;
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);


$test = 'server memory_limit set to ' . $config['memory_limit'] . ' in ini_set';
$response = $config['response'];
if (ini_get('memory_limit') == $config['memory_limit']) {
	$response['msg'] =  'ok: ' . $test;
} else {
	$response['msg'] = 'failed: ' . $test . ', currently ' . ini_get('memory_limit');
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);


$test = 'MySQL connection';
$response = $config['response'];
if ($config['sql_link']) {
	$response['msg'] = 'ok: ' . $test;
} else {
	$response['msg'] = 'failed: ' . $test;
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);

$test = 'sql timezone set to ' . $config['timezone_sql'];
$response = $config['response'];
$result = query("SELECT @@session.time_zone", $config);
if ($result[0]['@@session.time_zone'] == $config['timezone_sql']) {
	$response['msg'] = 'ok: ' . $test;
} else {
	$response['msg'] = 'failed: ' . $test . ', currently ' . $result[0]['@@session.time_zone'];
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);


$test = 'alert bot exists';
$response = $config['response'];
$config['url'] = $config['chat_url'] . '/getMe';
$response_x = query_api($config, $default_headers = TRUE);
if ($response_x['ok'] == TRUE) {
	$response['msg'] = 'ok: ' . $test . ' "' . $response_x['result']['username'] . '"';
} else {
	$response['msg'] = 'failed: ' . $test;
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);


$test = 'alert bot chat initiated with user';
$response = $config['response'];
$config['url'] = $config['chat_url'] . '/getChat?chat_id=' . $config['chat_id'];
$response_x = query_api($config, $default_headers = TRUE);
if ($response_x['ok'] == TRUE) {
	$response['msg'] = 'ok: ' . $test . ' "' . $response_x['result']['username'] . '"';
} else {
	$response['msg'] = 'failed: ' . $test;
	$response['alert'] = $alert;
	$response['error'] = $die;
}
process($response, $config);


$test = 'connection to exchange/repository ';
foreach ($exchanges as $config['exchange']) {
	$response = $config['response'];
	$connection = FALSE;
	$config = config_exchange($config);
	switch ($config['exchange']) {
		case 'ascendex':
			$config['api_request'] = $config['account_info_hash'];
			$config['url'] .= $config['account_info'];
			$result = query_api($config);
			if ($result['code'] == '0') $connection = TRUE;
	 	break;
		case 'okex_spot':
			$config['api_request'] = $config['account_info'];
			$config['url'] .= $config['api_request'];
			$result = query_api($config);
			if (!is_null($result)) $connection = TRUE;
		break;
		case 'twelve':
			$config['url'] =
			$config['url'] .
			$config['price_history'] .
			'?symbol=TSLA' .
			'&apikey=' . $config['api_key'] .
			'&interval=1h' .
			'&outputsize=1' .
			'&format=JSON&timezone=UTC';
			$result = query_api($config);
			if ($result['status'] == 'ok') $connection = TRUE;
	 	break;
	}
	if ($connection) {
		$response['msg'] = 'ok: ' . $test . '"' . $config['exchange'] . '"';
	} else {
		$response['msg'] =
			'failed: ' . $test .
			'"' . $config['exchange'] . '"' . PHP_EOL .
			var_export($result, TRUE)
		;
		$response['alert'] = $alert;
		$response['error'] = $die;
	}
	process($response, $config);
}


$test = 'inserting into asset_pairs table';
$history_start = $config['timestamp'] - 7 * 86400000; # 7 days prior
$queries_values = array(
	/* example crypto to run test trade */
	array(
		'pair' => 'ETH/USDT',
		'collect' => 1,
		'analyse' => 1,
		'class' => 'crypto',
		'period' => 240,
		'refresh' => 5,
		'history_start' => $history_start,
		'history_end' => 1999999999999,
		'exchange' => 'okex_spot',
		'reference' => ''
	),
	/* example crypto */
	array(
		'pair' => 'BTC/USDT',
		'collect' => 1,
		'analyse' => 1,
		'class' => 'crypto',
		'period' => 240,
		'refresh' => 5,
		'history_start' => $history_start,
		'history_end' => 1999999999999,
		'exchange' => 'okex_spot',
		'reference' => ''
	),
	/* example crypto */
	array(
		'pair' => 'BTC/USDT',
		'collect' => 1,
		'analyse' => 1,
		'class' => 'crypto',
		'period' => 240,
		'refresh' => 5,
		'history_start' => $history_start,
		'history_end' => 1999999999999,
		'exchange' => 'ascendex',
		'reference' => ''
	)/* ,
	AUD/USD necessary for calculating price at time of trade
	array(
		'pair' => 'AUD/USD',
		'collect' => 1,
		'analyse' => 1,
		'class' => 'fiat',
		'period' => 1440,
		'refresh' => 5,
		'history_start' => $history_start,
		'history_end' => 1999999999999,
		'exchange' => 'twelve',
		'reference' => 'https://twelvedata.com/'
	) */
);
$pair_ids = array();
foreach ($queries_values as $values) {
	$response = $config['response'];
	query("SELECT @@session.time_zone", $config); # reset sql insert_id
	query('insert_asset_pair', $config, $values);
	$id = mysqli_insert_id($config['sql_link']);
	if (!is_numeric($id) || !$id) {
		$response['msg'] = 'error: ' . $config['sql_link']->error;
		$response['msg'] .= PHP_EOL . 'failed: ' . $test . var_export($values, TRUE);
		$response['alert'] = $alert;
		$response['error'] = $die;
		process($response, $config);
	}
	array_push($pair_ids, $id);
}
$pair_ids_str = ', pair_ids ' . implode(', ', $pair_ids);
$response['msg'] = 'ok: ' . $test . $pair_ids_str;
process($response, $config);


# to do: post trade signal to telegram channel,
# test bot ability to read and convert to tactic


/* to do: test ability to identify duplicate post trade signals
foreach ($tactics as $t) {
	$t['channel_post_id'] = $t['channel_post_id'] + 10;
	$t['channel'] = 'test';
	#array_push($tactics, $t);
}
*/


$test = 'inserting into tactics table';
$condition_time = $config['timestamp'] - 60 * 60000; # 1 hour prior
$config['exchange'] = 'okex_spot';
$config = config_exchange($config);
$symbol = 'ETH/USDT';
$response = check_pairs($config, $symbol);
$amount = $response['result'][$symbol]['amount_minimum'];
$response = price_last($config, $symbol);
$trade_price = 2 * $response['result']['lowest_sell'];
$queries_values = array(
	/* tactic waits for the time $condition_time to pass */
	array(
		'status' => 'conditional',
		'refresh' => 5,
		'currency' => $condition_time,
		'action_time_limit' => 1440,
		'condition_time_test' => 0,
		'condition_time' => $condition_time,
		'action' => 'none'
	),
	/* tactic waits for previous tactic to execute */
	array(
		'status' => 'conditional',
		'refresh' => 5,
		'currency' => $condition_time,
		'action_time_limit' => 1440,
		'condition_tactic_test' => 0,
		'condition_tactic' => 'prev_tactic_id',
		'action' => 'none'
	),
	/*
		tactic waits until the indicator rsi8 to pass above 0,
		for the asset_pair created earlier for ETH/USDT
	*/
	array(
		'status' => 'conditional',
		'refresh' => 5,
		'currency' => $condition_time,
		'action_time_limit' => 1440,
		'condition_pair_test' => 0,
		'condition_pair_id' => $pair_ids[0],
		'condition_pair_currency_min' => 480,
		'condition_pair_indicator' => 'rsi8',
		'condition_pair_value_operand' => '>=',
		'condition_pair_value' => 0,
		'action' => 'none'
	),
	/*
		tactic waits for all of:
			time $condition_time to pass
			previous tactic to execute
			indicator rsi8 to pass below 100 for the asset_pair created earlier (ETH/USDT)
		then places order:
			min amount of ETH to USDT, market order
	*/
	array(
		'status' => 'conditional',
		'refresh' => 5,
		'currency' => $condition_time,
		'action_time_limit' => 1440,
		'condition_time_test' => 0,
		'condition_time' => $condition_time,
		'condition_tactic_test' => 0,
		'condition_tactic' => 'prev_tactic_id',
		'condition_pair_test' => 0,
		'condition_pair_id' => $pair_ids[0],
		'condition_pair_currency_min' => 480,
		'condition_pair_indicator' => 'rsi8',
		'condition_pair_value_operand' => '<=',
		'condition_pair_value' => 100,
		'action' => 'market',
		'pair_id' => $pair_ids[0],
		'from_asset' => 'ETH',
		'from_amount' => $amount,
		'to_asset' => 'USDT'
	),
	/*
		tactic waits for previous tactic to order:
			then places limit order
	*/
	array(
		'status' => 'conditional',
		'refresh' => 5,
		'currency' => $condition_time,
		'action_time_limit' => 1440,
		'condition_tactic_test' => 0,
		'condition_tactic' => 'prev_tactic_id',
		'action' => 'limit',
		'pair_id' => $pair_ids[0],
		'from_asset' => 'ETH',
		'from_amount' => $amount,
		'to_asset' => 'USDT',
		'trade_price' => $trade_price
	),
	/*
		tactic waits for previous tactic to order:
			then deletes limit order
	*/
	array(
		'status' => 'inactive',
		'refresh' => 5,
		'currency' => $condition_time,
		'action' => 'delete',
		'exchange' => 'okex_spot'
	)
);
$tactic_ids = array();
foreach ($queries_values as $values) {
	$response = $config['response'];
	query("SELECT @@session.time_zone", $config); # reset sql insert_id
	if ($values['condition_tactic'] == 'prev_tactic_id') # use last insert_id if requested
		$values['condition_tactic'] = $id;
	query('insert_tactic', $config, $values);
	$id = mysqli_insert_id($config['sql_link']);
	if (!is_numeric($id) || !$id) {
		$response['msg'] = 'error: ' . $config['sql_link']->error;
		$response['msg'] .= PHP_EOL . 'failed: ' . $test . var_export($values, TRUE);
		$response['alert'] = $alert;
		$response['error'] = $die;
		process($response, $config);
	}
	array_push($tactic_ids, $id);
}
$tactic_ids_str = ', tactic_ids ' . implode(', ', $tactic_ids);
$response['msg'] = 'ok: ' . $test . $tactic_ids_str;
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep2 . 'running price history queries' . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] = 'querying and saving price_history() ...' . PHP_EOL . PHP_EOL;
foreach ($pair_ids as $pair_id) {
	$response_x = price_history($config, $pair_id);
	$response['count'] += $response_x['count'];
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	if ($response_x['die']) {
		$response['error'] = $die;
		$response['alert'] = $alert;
		process($response, $config);
	}
}
if ($response['count']) {
	$response['msg'] .= 'ok: ' . $response['count'] . ' records inserted or imputed';
} else {
	$response['msg'] .= 'failed: expecting > 0 records inserted or imputed';
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'querying and saving price_recent() ... ' . PHP_EOL . PHP_EOL;
foreach ($pair_ids as $pair_id) {
	$query = "
		UPDATE asset_pairs
		SET currency_end = currency_end - 5 * period * 60000
		WHERE pair_id = '" . $pair_id . "'
	";
	$result = query($query, $config);
	if ($result) {
		$response['msg'] .= 'reset: wound back currency by 5 periods for pair_id ' . $pair_id . PHP_EOL;
	} else {
		$response['msg'] .= 'failed: unable to wind back currency for pair_id ' . $pair_id . PHP_EOL;
		$response['error'] = $die;
		$response['alert'] = $alert;
		process($response, $config);
	}
	$response_x = price_recent($config, $pair_id);
	$response['count'] += $response_x['count'];
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	if ($response_x['die']) {
		$response['error'] = $die;
		$response['alert'] = $alert;
		process($response, $config);
	}
}
if ($response['count']) {
	$response['msg'] .= 'ok: ' . $response['count'] . ' records inserted';
} else {
	$response['msg'] .= 'failed: expecting > 0 records inserted';
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$i = 2;
$response = $config['response'];
$response['msg'] .= $sep . 'deleting ' . $i . ' price history records ...' . PHP_EOL . PHP_EOL;
$query = "SELECT MIN(history_id) FROM price_history WHERE pair_id = " . $pair_ids[0];
$result = query($query, $config);
$history_id = $result[0]['MIN(history_id)'];
if (!is_numeric($history_id) || $history_id == 0) {
	$response['msg'] .= 'failed: ' . PHP_EOL . $query;
	$response['error'] = $die;
	$response['alert'] = $alert;
	process($response, $config);
}
$query = "SELECT timestamp FROM price_history";
$query_sub = " WHERE history_id IN (" . ($history_id + 1) . "," . ($history_id + 2) . ")";
$missing = query($query . $query_sub, $config);
$test .=
	'"' . unixtime_datetime($missing[1]['timestamp']) . '"' .
	' to "' . unixtime_datetime($missing[0]['timestamp']) . '"';
$query = "DELETE FROM price_history";
$result = query($query . $query_sub, $config);
if ($result == $i) {
	$response['msg'] .= 'ok: ' . $i . ' deleted';
} else {
	$response['msg'] .= 'failed: ' . PHP_EOL . $query . $query_sub;
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'querying ' . $i . ' history records with price_missing() ...' . PHP_EOL . PHP_EOL;
$query = "
	SELECT timestamp
	FROM price_history
	WHERE pair_id = '" . $pair_ids[0] . "'
	ORDER BY timestamp
";
$history = query($query, $config);
$query = "
	SELECT *
	FROM asset_pairs
	WHERE pair_id = '" . $pair_ids[0] . "'
";
$pair = query($query, $config);
$pair = $pair[0];
$config['exchange'] = $pair['exchange'];
$config = config_exchange($config);
$pq = $config['price_query'];
$pq['pair_id'] = $pair_ids[0];
$pq['pair'] = $pair['pair'];
$pq['period'] = $pair['period'];
$pq['period_ms'] = $pair['period'] * 60000;
$pq['obs_iter'] = $config['obs_iter_max'];
$pq['start'] = $pair['history_start'];
$pq['stop'] = milliseconds();
$response_x = price_missing($config, $history, $pq);
$response['msg'] .= $response_x['msg'] . PHP_EOL;
if ($response_x['count'] == $i) {
	$response['msg'] .= "ok: {$i} missing records inserted";
} else {
	$response['msg'] .= "failed: expecting {$i} missing records inserted";
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$i = 10;
$response = $config['response'];
$response['msg'] = $sep . "imputing {$i} history records with price_missing() ..." . PHP_EOL . PHP_EOL;
$query = "SELECT MAX(timestamp) FROM price_history WHERE pair_id = " . $pair_ids[0];
$result = query($query, $config);
$timestamp = $result[0]['MAX(timestamp)'];
$query = "
	SELECT
	pair_id,
	imputed,
	timestamp,
	open,
	close,
	low,
	high,
	volume
	FROM price_history
	WHERE
	pair_id = '" . $pair_ids[0] . "'
	AND timestamp = " . $timestamp . "
";
$values = query($query, $config);
$values = $values[0];
$values['imputed'] = 1;
$values['timestamp'] += ($i + 1) * $pq['period_ms'];
$result = query('insert_price_history', $config, $values);
if ($result === 1) {
	$response['msg'] .= "ok: wound latest history record forward {$i} periods" . PHP_EOL;
} else {
	$response['msg'] .= "failed: unable to wind latest history record forward {$i} periods" . PHP_EOL;
}
$query = "
	SELECT timestamp
	FROM price_history
	WHERE pair_id = '" . $pair_ids[0] . "'
	ORDER BY timestamp
";
$history = query($query, $config);
$pq['stop'] = milliseconds() + ($i + 1) * $pq['period_ms'];
$response_x = price_missing($config, $history, $pq);
$response['msg'] .= $response_x['msg'] . PHP_EOL;
if ($response_x['count'] == $i) {
	$response['msg'] .= "ok: {$i} records imputed";
} else {
	$response['msg'] .= "failed: expecting {$i} records imputed";
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'testing query to replace records with price_history_imputed() ...' . PHP_EOL . PHP_EOL;
$query = "UPDATE price_history SET imputed = 1 WHERE pair_id = '" . $pair_ids[0] . "' AND imputed = '0'";
$i = query($query, $config);
$response['msg'] .= 'altered: ' . $i . ' records to mimick imputed' . PHP_EOL;
$response_x = price_history_imputed($config, $pair_ids[0]);
$response['msg'] .= $response_x['msg'] . PHP_EOL;
if ($response_x['count'] == $i) {
	$response['msg'] .= "ok: {$i} records updated with actual figures";
} else {
	$response['msg'] .= "failed: expecting {$i} records updated";
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep2 . 'calculating technical_analysis()' . $sep2 . PHP_EOL;
$response_x = technical_analysis($config, implode(',', $pair_ids));
$response['msg'] .= $response_x['msg'] . PHP_EOL;
if ($response_x['count'] > 0) {
	$response['msg'] .= "ok: analysis calculated on {$response_x['count']} cells";
} else {
	$response['msg'] .= "failed: expecting records updated";
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


# unless a live trade is requested, disable any trades for test tactics
if (isset($_GET['trade']) && $_GET['trade'] == 'TRUE') $trade = TRUE;
else $trade = FALSE;
$response = $config['response'];
$response['msg'] .= $sep2;
if ($trade) $response['msg'] .= 'testing';
else $response['msg'] .= 'ignoring';
$response['msg'] .= ' trade tactics' . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] .= 'processing tactics ...' . PHP_EOL . PHP_EOL;
if (!$trade) {
	$values = array();
	$values['action'] = 'none';
	$i = 0;
	foreach ($tactic_ids as $values['tactic_id'])
		$i += query('update_tactic', $config, $values);
	if ($i) $response['msg'] .= "ok: nullified {$i} tactics with actions";
	else $response['msg'] .= "warning: nullified no tactics with actions";
} else {
	$values['filterquery'] = " WHERE t.action IN ('market','limit') AND t.tactic_id IN (" . implode(',', $tactic_ids) . ")";
	$result = query('select_tactics', $config, $values);
	if (empty($result)) {
		$response['msg'] .= "failed: select_tactics {$values['filterquery']}, expecting tactic with action for market/limit order" . PHP_EOL;
		$response['error'] = $die;
		$response['alert'] = $alert;
	} else {
		$count = count($result);
		$response['msg'] .= "ok: found {$count} tactics with action for market/limit order" . PHP_EOL . PHP_EOL;
		$tactic_id = $result[0]['tactic_id']; # assumes first order only is being placed for now
		$tactic_id_limit = $result[1]['tactic_id']; # assumes second order will be placed after the first is executed
	}
	$values['filterquery'] = " WHERE t.action IN ('delete') AND t.tactic_id IN (" . implode(',', $tactic_ids) . ")";
	$result = query('select_tactics', $config, $values);
	if (empty($result)) {
		$response['msg'] .= "failed: select_tactics {$values['filterquery']}, expecting tactic with action for delete order";
		$response['error'] = $die;
		$response['alert'] = $alert;
	} else {
		$count = count($result);
		$response['msg'] .= "ok: found {$count} tactics with action for delete order";
		$tactic_id_delete = $result[0]['tactic_id']; # assumes only one delete order
	}
}
process($response, $config);


if ($trade) {
	$response = $config['response'];
	$response['msg'] .= $sep . 'processing conditional tactics ...';
	process($response, $config);
	# test conditions for tactics and update status if conditions met
	foreach ($tactic_ids as $id) {
		if ($id == $tactic_id_limit || $id == $tactic_id_delete) continue 1;
		$response = $config['response'];
		$response_x = conditional_tactics($config, $id);
		$response['msg'] .= PHP_EOL . $response_x['msg'] . PHP_EOL;
		if (array_search($response_x['result'][$id], array('actionable', 'executed')) === FALSE) {
			$response['msg'] .= 'failed: conditions did not satisfy for tactic_id ' . $id;
			$response['error'] = $die;
			$response['alert'] = $alert;
		} else {
			$response['msg'] .= 'ok: conditions satisfied for tactic_id ' . $id;
		}
		process($response, $config);
	}
}


$ordered = FALSE;
if ($trade) {
	$response = $config['response'];
	$response['msg'] .= $sep . 'testing actionable_tactics() order placement for tactic with actionable order ...' . PHP_EOL . PHP_EOL;
	$time = 0;
	# keep trying for 30 seconds, every 5 seconds
	$stop = milliseconds() + 30000;
	while ($time < $stop && !$ordered) {
		sleep(5);
		$response_x = actionable_tactics($config, $tactic_id);
		if (!empty($response_x['result']) && $response_x['result'][$tactic_id] == 'ordered') $ordered = TRUE;
		$time = milliseconds();
	}
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	if ($ordered) {
		$response['msg'] .= 'ok: order placed';
	} else {
		$response['msg'] .= 'failed: order not placed';
		$response['error'] = $die;
		$response['alert'] = $alert;
	}
	process($response, $config);
}


$executed = FALSE;
if ($trade && $ordered) {
	$response = $config['response'];
	$response['msg'] .= $sep . 'testing ordered_tactics() execution for tactic with order instruction ...' . PHP_EOL . PHP_EOL;
	$time = 0;
	# keep trying for 30 seconds, every 5 seconds
	$stop = milliseconds() + 30000;
	while ($time < $stop && !$executed) {
		sleep(5);
		$response_x = ordered_tactics($config, $tactic_id);
		if (!empty($response_x['result']) && $response_x['result'][$tactic_id] == 'executed') $executed = TRUE;
		$time = milliseconds();
	}
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	if ($executed) {
		$response['msg'] .= 'ok: order executed ';
	} else {
		$response['msg'] .= 'failed: order not executed';
		$response['error'] = $die;
		$response['alert'] = $alert;
	}
	process($response, $config);
}

$recorded = FALSE;
if ($trade && $ordered && $executed) {
	$response = $config['response'];
	$response['msg'] .= $sep . 'testing update_transactions() for executed live trade ... ' . PHP_EOL . PHP_EOL;
	$values['filterquery'] = " WHERE tactic_id = {$tactic_id}";
	$t = query('select_transactions', $config, $values);
	if (empty($t)) {
		$response['msg'] .= "failed: transaction not found" . PHP_EOL;
	} else if (array_search($t[0]['exchange_transaction_status'], array('unconfirmed','complete')) === FALSE) {
		$response['msg'] .= "failed: transaction status not 'unconfirmed' or 'complete'" . PHP_EOL;
	} else {
		$response_x = update_transactions($config, $t[0]['exchange'], $t[0]['pair']);
		$action = $response_x['result'][$t[0]['exchange']][$t[0]['exchange_transaction_id']];
		if (array_search($action, array('updated','ignored')) === FALSE) {
			$response['msg'] .= "failed: transaction not updated" . PHP_EOL;
		} else {
			$t = query('select_transactions', $config, $values);
			if ($t[0]['exchange_transaction_status'] !== 'complete') {
				$response['msg'] .= "failed: transaction not complete" . PHP_EOL;
			} else if (is_null($t[0]['time_opened'])) {
				$response['msg'] .= "failed: transaction time_opened not recorded" . PHP_EOL;
			} else if ($t[0]['purpose'] !== 'trade') {
				$response['msg'] .= "failed: transaction purpose not recorded" . PHP_EOL;
			} else if (is_null($t[0]['pair'])) {
				$response['msg'] .= "failed: transaction pair not recorded" . PHP_EOL;
			} else if (is_null($t[0]['from_asset'])) {
				$response['msg'] .= "failed: transaction from_asset not recorded" . PHP_EOL;
			} else if (is_null($t[0]['from_amount'])) {
				$response['msg'] .= "failed: transaction from_amount not recorded" . PHP_EOL;
			} else if (is_null($t[0]['to_asset'])) {
				$response['msg'] .= "failed: transaction to_asset not recorded" . PHP_EOL;
			} else if (is_null($t[0]['to_amount'])) {
				$response['msg'] .= "failed: transaction to_amount not recorded" . PHP_EOL;
			} else if (is_null($t[0]['to_fee'])) {
				$response['msg'] .= "failed: transaction to_fee not recorded" . PHP_EOL;
			} else if (is_null($t[0]['pair_price'])) {
				$response['msg'] .= "failed: transaction pair_price not recorded" . PHP_EOL;
			} else if (is_null($t[0]['from_wallet'])) {
				$response['msg'] .= "failed: transaction from_wallet not recorded" . PHP_EOL;
			} else if (is_null($t[0]['to_wallet'])) {
				$response['msg'] .= "failed: transaction to_wallet not recorded" . PHP_EOL;
			} else if (is_null($t[0]['percent_complete'])) {
				$response['msg'] .= "failed: transaction percent_complete not recorded" . PHP_EOL;
			} else if (!$t[0]['recorded']) {
				$response['msg'] .= "failed: transaction not recorded" . PHP_EOL;
			} else {
				$response['msg'] .= "ok: transaction complete and recorded for tactic_id {$tactic_id}";
				$recorded = TRUE;
			}
		}
	}
	if (!$recorded) {
		$response['error'] = $die;
		$response['alert'] = $alert;
	}
	process($response, $config);
}


if ($trade && $ordered && $executed) {
	$deleted = FALSE;
	$response = $config['response'];
	$response['msg'] .= $sep . 'testing tactics for limit order and deletion ... ' . PHP_EOL . PHP_EOL;
	$response_x = conditional_tactics($config, $tactic_id_limit);
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	if ($response_x['result'][$tactic_id_limit] !== 'actionable') {
		$response['msg'] .= 'failed: conditions did not satisfy for tactic_id ' . $tactic_id_limit . PHP_EOL;
		$response['error'] = $die;
		$response['alert'] = $alert;
	} else {
		$response['msg'] .= 'ok: conditions satisfied for tactic_id ' . $tactic_id_limit . PHP_EOL . PHP_EOL;
		# keep trying for 30 seconds, every 5 seconds
		$ordered = FALSE;
		$stop = milliseconds() + 30000;
		while ($time < $stop && !$ordered) {
			sleep(5);
			$response_x = actionable_tactics($config, $tactic_id_limit);
			if (!empty($response_x['result']) && $response_x['result'][$tactic_id_limit] == 'ordered') $ordered = TRUE;
			$time = milliseconds();
		}
		$response['msg'] .= $response_x['msg'] . PHP_EOL;
		if (!$ordered) {
			$response['msg'] .= 'failed: order not placed';
			$response['error'] = $die;
			$response['alert'] = $alert;
		} else {
			$response['msg'] .= 'ok: order placed ' . PHP_EOL . PHP_EOL;
			$values['filterquery'] = " WHERE tactic_id = {$tactic_id_limit}";
			$t = query('select_transactions', $config, $values);
			if (empty($t)) {
				$response['msg'] .= "failed: transaction not found" . PHP_EOL;
			} else {
				$values = array();
				$values['tactic_id'] = $tactic_id_delete;
				$values['status'] = 'actionable';
				$values['transaction_id'] = $t[0]['transaction_id'];
				query('update_tactic', $config, $values);
				$placed = FALSE;
				$stop = milliseconds() + 30000;
				while ($time < $stop && !$placed) {
					sleep(5);
					$response_x = actionable_tactics($config, $tactic_id_delete);
					if (
						!empty($response_x['result']) &&
						($response_x['result'][$tactic_id_delete] == 'inactive' ||
						$response_x['result'][$tactic_id_delete] == 'ordered')
					) $placed = TRUE;
					$time = milliseconds();
				}
				$response['msg'] .= $response_x['msg'] . PHP_EOL;
				if (!$placed) {
					$response['msg'] .= "failed: order deletion tactic failed" . PHP_EOL;
				} else {
					$response_x = check_transaction($config, $t[0]['transaction_id']);
					$values['filterquery'] = " WHERE transaction_id = {$t[0]['transaction_id']}";
					$t = query('select_transactions', $config, $values);
					if ($t[0]['exchange_transaction_status'] !== 'cancelled') {
						$response['msg'] .= "failed: transaction status not cancelled";
					} else {
						$deleted = TRUE;
						$response['msg'] .= "ok: transaction cancelled tactic_id {$tactic_id_limit}";
					}
				}
			}
		}
	}
	if (!$deleted) {
		$response['error'] = $die;
		$response['alert'] = $alert;
	}
	process($response, $config);
}


# option to update datetimes
if (isset($_GET['convert_timestamps']) && $_GET['convert_timestamps'] == 'TRUE') {
	$response = $config['response'];
	$response['msg'] = $sep . "updating datetimes in database ..." . PHP_EOL . PHP_EOL;
	$response_x = convert_timestamps($config);
	$response['msg'] .= $response_x['msg'] . PHP_EOL;
	process($response, $config);
}


# option to not delete records inserted into database, to allow manual inspection
if (isset($_GET['debug']) && $_GET['debug'] == 'TRUE') {
	$response = $config['response'];
	$response['msg'] = $sep . "not deleting records inserted into database, to allow manual inspection ..." . PHP_EOL . PHP_EOL;
	$response['msg'] .= "completed";
	process($response, $config);
	die;
}


$response = $config['response'];
$response['msg'] = $sep2 . 'testing data cleaning ' . $sep2;
process($response, $config);


$response = $config['response'];
$query = "DELETE FROM asset_pairs WHERE pair_id IN (" . implode(',', $pair_ids) . ")";
$result = query($query, $config);
if ($result == count($pair_ids)) {
	$response['msg'] = 'ok: deleted the ' . $result .
		' records from asset_pairs table' .
		$pair_ids_str;
} else {
	$response['msg'] = 'failed: unable to delete all test records from assets_pairs table' . $pair_ids_str;
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


# option to truncate asset_pairs table, use with caution
if (isset($_GET['truncate_pairs']) && $_GET['truncate_pairs'] == 'TRUE') {
	if (isset($_GET['retain_pairs'])) {
		$retain = "where pair_id not in ({$_GET['retain_pairs']})";
	} else {
		$retain = '';
	}
	$response = $config['response'];
	$response['msg'] = $sep . "deleting all remaining asset_pairs {$retain} ..." . PHP_EOL . PHP_EOL;
	$query = "delete from asset_pairs {$retain}";
	$count = query($query, $config);
	$response['msg'] .= "deleted: {$count} asset_pairs" . PHP_EOL;
	process($response, $config);
}


$response = $config['response'];
$response['msg'] = $sep . "testing price history deduplication with price_history_dedupe() ..." . PHP_EOL . PHP_EOL;
price_history_dedupe($config);
$values['filterquery'] = " WHERE pair_id = {$pair_ids[0]}";
$history = query('select_history', $config, $values);
$i = 0;
foreach ($history as $h) {
	unset($h['history_id']);
	$i += query('insert_price_history', $config, $h);
}
$response['msg'] .= "inserted {$i} duplicates" . PHP_EOL;
$response_x = price_history_dedupe($config);
$j = $response_x['count'];
$response['msg'] .= "price_history_dedupe() found {$j} duplicates" . PHP_EOL;
$response_x = price_history_dedupe($config);
$k = $j - $response_x['count'];
if ($i == $k) {
	$response['msg'] .= "ok: price_history_dedupe() removed {$k} duplicates";
} else {
	$response['msg'] .= "failed: number of duplicates {$i} does not match number removed {$k}";
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . "deleting price history with no asset_pair with price_history_trim() ..." . PHP_EOL . PHP_EOL;
$response_x = price_history_trim($config);
$response['msg'] .= $response_x['msg'];
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . "deleting test tactics ..." . PHP_EOL . PHP_EOL;
$query = "DELETE FROM tactics WHERE tactic_id IN (" . implode(',', $tactic_ids) . ")";
$result = query($query, $config);
if ($result == count($tactic_ids)) {
	$response['msg'] .= 'ok: deleted the ' . $result .
		' records from tactics table' .
		$tactic_ids_str .
		PHP_EOL;
} else {
	$response['msg'] .= 'failed: unable to delete all test records from tactics table' . $tactic_ids_str;
	$response['error'] = $die;
	$response['alert'] = $alert;
}
process($response, $config);


# option to truncate tactics table, use with caution
if (isset($_GET['truncate_tactics']) && $_GET['truncate_tactics'] == 'TRUE') {
	$response = $config['response'];
	$response['msg'] = $sep . "deleting all remaining tactics ..." . PHP_EOL . PHP_EOL;
	$query = "delete from tactics";
	$count = query($query, $config);
	$response['msg'] .= "deleted: {$count} tactics" . PHP_EOL;
	process($response, $config);
}


$response = $config['response'];
$response['msg'] = $sep2 . 'result' . $sep2 . PHP_EOL;
$response['msg'] .= 'error checks passed' . PHP_EOL . PHP_EOL;
$response['msg'] .= 'manually inspect any warnings' . PHP_EOL;
process($response, $config);

include('system_speed.php');
