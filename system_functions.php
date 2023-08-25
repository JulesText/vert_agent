<?php

/* heart beat counter */
$starttime = milliseconds();
function runtime() {

	global $starttime;
	$runtime = milliseconds() / 1000 - $starttime / 1000;
	$runtime = sprintf('%05.1f', $runtime);
	$runtime = $runtime . 's: ';
	return($runtime);

}

/* function response handling */

function process($response, $config) {

	/* if error, initiate error report and die */
	if ($response['error'] !== FALSE) error($response['msg'], $config['error_log']);

	/* print notification on screen */
	if ($response['msg'] !== '') echo $response['msg'] . PHP_EOL;

	/* send notification message to alert bot */
	if ($response['alert']) {
		// html output is not developed yet
		// if api returns html, this will throw an error
		// so use strip_tags to remove html code
		$response['msg'] = strip_tags($response['msg']);
		// and also replace < and > symbols within plain text
		$response['msg'] = str_replace('<=', 'LE', $response['msg']);
		$response['msg'] = str_replace('<', 'LT', $response['msg']);
		$response['msg'] = str_replace('>=', 'GE', $response['msg']);
		$response['msg'] = str_replace('>', 'GT', $response['msg']);
		$config['url'] =
			$config['chat_url'] .
			'/sendMessage?chat_id=' . $config['chat_id'] .
			'&parse_mode=html&text=' . urlencode($response['msg']) # urlencode handles line breaks
		;
		query_api($config, $default_headers = TRUE);
	}

}


/* get timestamp */

function milliseconds() {

	list($msec, $sec) = explode(' ', microtime());
	return (int) ($sec . substr($msec, 2, 3));

}

/*
unix timestamp to date and time
milliseconds in 13-digits
*/

function unixtime_datetime($unixtime_ms, $format = 'print') {

	$unixtime_ms = (int)$unixtime_ms;
	$unixtime = number_format($unixtime_ms / 1000, 3, '.', '');
	$ms = number_format($unixtime - floor($unixtime), 3, '.', '');
	$ms = ltrim($ms, '0');

	switch ($format) {

		case 'iso':
			# to ISO8601 date and time format
			# 'T' denotes time
			# 'Z' denotes UTC timezone (zero offset)
			$datetime = date('Y-m-d\TH:i:s\\' . $ms . '\Z', $unixtime);
		break;

		case 'sql':
			# MySQL format
			$datetime = date('Y-m-d H:i:s\\' . $ms, $unixtime);
		break;

		case 'print':
			# print format
			$unixtime = floor($unixtime);
 			$unixtime_ms = $unixtime * 1000;
			$datetime = date('Y-m-d H:i:s', $unixtime);
		break;

	}

	$check = datetime_unixtime($datetime);
	if ($check == $unixtime_ms) {
		return $datetime;
	} else {
		error(
			'error in unixtime_datetime, ' .
			$check . ' !== ' .
			$unixtime_ms .
			' for datetime ' . $datetime
		);
	}

}

/*
date and time (must have 3 trailing decimal places including zeros)
to unix timestamp (milliseconds in 13-digits)
$datetime in MySQL + milliseconds as decimal of seconds
*/

function datetime_unixtime($datetime) {

	$unixtime = strtotime($datetime);
	$pos = strpos($datetime, '.');
	if ($pos) {
		$ms = substr($datetime, $pos);
	} else {
		$ms = '.000';
	}
	if (substr($ms, 0, 1) !== '.') error('error in datetime_unixtime, $datetime does not have 3 trailing decimal places');
	$unixtime_ms = ($unixtime + $ms) * 1000;

	return $unixtime_ms;

}

/* array depth */

function countdim($array) {

	if (is_array(reset($array))) {
		$count = countdim(reset($array)) + 1;
	} else {
		$count = 1;
	}
	return $count;

}

/* deduplicate multidimensional array */

function dedupe_array($array) {

	return array_map('unserialize', array_unique(array_map('serialize', $array)));

}

/*
asset pair only reported on weekdays
expect some asset pairs are not recorded on weekends, for instance fiat currencies like $AUD to $USD
*/

function weekends($class) {

	if ($class == 'fiat' || $class == 'stock') {
		$weekend = FALSE;
	} else {
		$weekend = TRUE;
	}
	return $weekend;

}

/* count number of observations falling on weekends */

function nobs_weekends($from, $to, $period_ms) {

	$nobs = 0;
	for ($j = $from; $j <= $to; $j += $period_ms) {
		$day = date('l', $j / 1000);
		if ($day == 'Saturday' || $day == 'Sunday') $nobs++;
	}
	return $nobs;

}

/* calculate the smallest multiple of x closest to a given number n, always rounding down
useful for calculating closest price/amount that fits exchange parameters */

function lower_multiple($n, $x) {

	$n = $n - fmod($n, $x);
	$n = number_format($n, decimals($x), '.', '');

	return $n;

}

/* count number of decimal places */

function decimals($d) {

	$i = strlen(substr(strrchr($d, "."), 1));

	return $i;

}

/* write error to log, display error and terminate */

function error($msg, $error_log = TRUE) {

	if ($error_log) {
		$log = fopen('error_log', 'a');
		fwrite($log, $msg . PHP_EOL);
		fclose($log);
	}
	$sep = '-------------------------------' . PHP_EOL;
	$sep = PHP_EOL . $sep . $sep;
	die($sep . 'error' . $sep . $msg);

}

/* convert unix timestamps to date time format */

function convert_timestamps($config) {

	$response = $config['response'];

	$array = array(
		array('table' => 'assets', 'from' => 'timestamp', 'to' => 'timestamp_dt', 'replace' => 1),
		array('table' => 'asset_pairs', 'from' => 'currency_start', 'to' => 'currency_start_dt', 'replace' => 1),
		array('table' => 'asset_pairs', 'from' => 'currency_end', 'to' => 'currency_end_dt', 'replace' => 1),
		array('table' => 'asset_pairs', 'from' => 'history_start', 'to' => 'history_start_dt', 'replace' => 1),
		array('table' => 'asset_pairs', 'from' => 'history_end', 'to' => 'history_end_dt', 'replace' => 1),
		array('table' => 'price_history', 'from' => 'timestamp', 'to' => 'timestamp_dt', 'replace' => 0),
		array('table' => 'tactics', 'from' => 'currency', 'to' => 'currency_dt', 'replace' => 1),
		array('table' => 'tactics', 'from' => 'condition_time', 'to' => 'condition_time_dt', 'replace' => 1),
		array('table' => 'tactics_external', 'from' => 'timestamp', 'to' => 'timestamp_dt', 'replace' => 0),
		array('table' => 'transactions', 'from' => 'time_opened', 'to' => 'time_opened_dt', 'replace' => 0),
		array('table' => 'transactions', 'from' => 'time_closed', 'to' => 'time_closed_dt', 'replace' => 0),
		array('table' => 'web_content', 'from' => 'timestamp', 'to' => 'timestamp_dt', 'replace' => 1)
	);

	$response['count'] = 0;
	foreach ($array as $a) {
		$query = "
			UPDATE " . $a['table'] . "
			SET " . $a['to'] . " =
				CASE
				WHEN " . $a['from'] . " = 0 THEN NULL
				ELSE from_unixtime((" . $a['from'] . " / 1000))
				END
		";
		if (!$a['replace']) $query .= " WHERE ISNULL(" . $a['to'] . ") ";
		$response['count'] += query($query, $config);
	}

	$response['msg'] .= 'refreshed: ' . $response['count'] . ' datetime records from timestamps' . PHP_EOL;

	return $response;

}

# change object to array

function objectToArray ($object) {
	if(!is_object($object) && !is_array($object)) return $object;
	return array_map('objectToArray', (array) $object);
}

# populate prices

function fiat_value($address, $timestamp, $quantity, $fiat, $fiat_decimal, $price_records) {

	$result = '';

	# check if price record exists
	if (isset($price_records[$address][$fiat][$timestamp])) {
		$price_fiat = $price_records[$address][$fiat][$timestamp]['price'];
		// $result = round($price_fiat * $quantity, 6);
		$price_fiat *= pow(10, $fiat_decimal);
		$price_fiat = ceil($price_fiat) / pow(10, $fiat_decimal);
		$result = $price_fiat * $quantity;
	} else {
		die(
			'fatal error: fiat price missing from price_records.json at ' . $timestamp . ' for ' . $address . PHP_EOL
			. 'try rerunning script to allow prices to update further'
		);
	}

	return $result;

}

# sort transaction history chronologically, and by tran_id/type/amount for equal times

function sort_txns($txn_hist) {

	$sort_keys = array();
	foreach ($txn_hist as $row) {
		$s = (string) $row['Timestamp'] . $row['transaction_id'];
		if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] == 0) $s .= '0';
		else if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] > 0) $s .= '1';
		else if ($row['Buy Quantity'] > 0 && $row['Sell Quantity'] == 0) $s .= '2';
		$sort_keys[] = $s;
	}

	return $sort_keys;

}
