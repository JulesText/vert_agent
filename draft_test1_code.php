<?php

include('system_includes.php');
include('functions.test1.php');
$sep = PHP_EOL . '-------------------------------' . PHP_EOL;
$sep2 = $sep . '-------------------------------' . PHP_EOL;
$sep .= PHP_EOL;
$config['sep'] = $sep;
echo "<pre>";

/* mysql connection */

$config['sql_link'] = mysqli_connect(
	'localhost',
	'julesnet_vert_agent_test',
	'X)i$oWulPR{x',
	'julesnet_vert_agent_test'
);

/* set reporting options */

$config['error_log'] = FALSE; # avoid saving to error_log during development
$hour = 36000000;
$config['match_threshold'] = 24 * $hour; # maximum time for transfer between exchanges

/* simluate completing form with initial capital investment values */

$asset_symbol = '"AUD"';
$amount_held = 100;
$timestamp_held_first = 1581018120000;
$proportion_held = 1;
$exchange_held = '"cba"';
$exchange = '"swyftx"';

/* prepare database for test */

$test = "initialise db";
$response = $config['response'];
$response['msg'] = $sep2 . $test . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] = 'empty tables ...' . PHP_EOL . PHP_EOL;
$tables = array('assets' => 20, 'asset_investment' => 30, 'asset_pairs' => 1, 'investments' => 10, 'transactions' => 131);
foreach ($tables as $table => $key) {
	$result = query("TRUNCATE TABLE {$table}", $config);
	if ($result === 0) {
		$response['msg'] .= "ok: ";
	} else {
		$response['msg'] .= "failed: ";
		$response['error'] = TRUE;
	}
	$response['msg'] .= "truncate {$table}" . PHP_EOL;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'set auto increment values ...' . PHP_EOL . PHP_EOL;
foreach ($tables as $table => $key) {
	$result = query("ALTER TABLE {$table} AUTO_INCREMENT = {$key}", $config);
	if ($result === 0) {
		$response['msg'] .= "ok: ";
	} else {
		$response['msg'] .= "failed: ";
		$response['error'] = TRUE;
	}
	$response['msg'] .= "auto increment {$table} to {$key}" . PHP_EOL;
}
process($response, $config);


/* create test transaction history */

$test = "creating transaction history";
$response = $config['response'];
$response['msg'] = $sep2 . $test . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] = 'inserting to transactions ...' . PHP_EOL . PHP_EOL;
$i = 	array(
				array(
					time_closed => $timestamp_held_first,
					purpose => '"transfer in"',
					from_asset => $asset_symbol,
					from_amount => $amount_held,
					to_asset => $asset_symbol,
					to_amount => $amount_held - 2,
					to_fee => 2,
					pair_price => 1
				),
				array(
					time_closed => $timestamp_held_first,
					purpose => '"trade"',
					from_asset => $asset_symbol,
					from_amount => $amount_held - 2,
					to_asset => '"USD"',
					to_amount => 62.09238474,
					to_fee => 0,
					pair_price => 0.63359576
				),
				array(
					time_closed => $timestamp_held_first + $hour,
					purpose => '"trade"',
					from_asset => '"USD"',
					from_amount => 62.09238474,
					to_asset => '"USDT"',
					to_amount => 61,
					to_fee => 0.305,
					pair_price => (61 + 0.305) / 62.09238474
				),
				array(
					time_closed => $timestamp_held_first + $hour * 24,
					purpose => '"trade"',
					from_asset => '"USDT"',
					from_amount => 61,
					to_asset => '"ETH"',
					to_amount => 0.1,
					to_fee => 0.0005,
					pair_price => (0.1 + 0.0005) / 61
				),
				array(
					time_closed => $timestamp_held_first + $hour * 48,
					purpose => '"trade"',
					from_asset => '"ETH"',
					from_amount => 0.1,
					to_asset => '"USDT"',
					to_amount => 80,
					to_fee => 0.4,
					pair_price => (80 + 0.4) / 0.1
				),
				array(
					time_closed => $timestamp_held_first + $hour * 72,
					purpose => '"trade"',
					from_asset => '"USDT"',
					from_amount => 80,
					to_asset => '"USD"',
					to_amount => 75,
					to_fee => 0.375,
					pair_price => (75 + 0.375) / 80
				),
				array(
					time_closed => $timestamp_held_first + $hour,
					purpose => '"trade"',
					from_asset => '"USD"',
					from_amount => 75,
					to_asset => '"AUD"',
					to_amount => 110,
					to_fee => 0.55,
					pair_price => (110 + 0.55) / 75
				)
			);
foreach ($i as $n) {
	$sql = "
	INSERT INTO transactions SET
	transaction_id = NULL
	, from_ass_inv_id = NULL
	, to_ass_inv_id = NULL
	, time_opened = NULL
	, time_closed = {$n['time_closed']}
	, capital_fee = NULL
	, purpose = {$n['purpose']}
	, exchange = {$exchange}
	, exchange_transaction_status = 'complete'
	, pair_id = NULL
	, from_asset = {$n['from_asset']}
	, from_amount = {$n['from_amount']}
	, to_asset = {$n['to_asset']}
	, to_amount = {$n['to_amount']}
	, to_fee = {$n['to_fee']}
	, pair_price = {$n['pair_price']}
	, from_price_usd = NULL
	, to_price_usd = NULL
	, fee_amount_usd = NULL
	, price_aud_usd = NULL
	, from_wallet = NULL
	, to_wallet = NULL
	, percent_complete = 100
	";
	$result = query($sql, $config);
	if ($result === 1) {
		$transaction_id = mysqli_insert_id($config['sql_link']);
		$response['msg'] .= "ok: insert transaction_id " . $transaction_id . PHP_EOL;
	} else {
		$response['msg'] .= "failed: insert transaction" . PHP_EOL;
		$response['error'] = TRUE;
	}
}
process($response, $config);


/* simluate completing form with initial capital investment values */

$test = "recording initial investment";
$response = $config['response'];
$response['msg'] = $sep2 . $test . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] = 'emulating manual form to insert to assets ...' . PHP_EOL . PHP_EOL;
$response['msg'] .= "manual: asset = " . $asset_symbol . PHP_EOL;
$response['msg'] .= "manual: amount_held = " . $amount_held . PHP_EOL;
$response['msg'] .= "manual: proportion_held = " . $proportion_held . PHP_EOL;
$response['msg'] .= "manual: exchange_held = " . $exchange_held . PHP_EOL;
$response['msg'] .= "manual: timestamp_held_first = " . $timestamp_held_first . PHP_EOL;
$response['msg'] .= "manual: exchange = " . $exchange . PHP_EOL;
$sql = "
INSERT INTO assets SET
asset_id = NULL
, purpose = 'capital'
, asset = {$asset_symbol}
, amount_held = {$amount_held}
, proportion_held = {$proportion_held}
, exchange_held = {$exchange_held}
, tmp_class = NULL
, timestamp_held_first = {$timestamp_held_first}
, tmp_timestamp_held_last = NULL
, tmp_date = NULL
, financial_year_held = NULL
";
$result = query($sql, $config);
if ($result === 1) {
	$asset_id = mysqli_insert_id($config['sql_link']);
	$response['msg'] .= "ok: inserted asset_id " . $asset_id;
	$sql = "SELECT * FROM assets WHERE asset_id = {$asset_id}";
	$asset = query($sql, $config);
	$asset = $asset[0];
} else {
	$response['msg'] .= "failed: insert asset";
	$response['error'] = TRUE;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'insert to investments ...' . PHP_EOL . PHP_EOL;
$sql = "
INSERT INTO investments SET
investment_id = NULL
, asset = '{$asset['asset']}'
, amount = '{$asset['amount_held']}'
, roi = ''
";
$result = query($sql, $config);
if ($result === 1) {
	$investment_id = mysqli_insert_id($config['sql_link']);
	$response['msg'] .= "ok: inserted investment_id " . $investment_id;
} else {
	$response['msg'] .= "failed: insert investment";
	$response['error'] = TRUE;
}
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'insert to asset_investment ...' . PHP_EOL . PHP_EOL;
$asset_proportion = 1;
$response['msg'] .= 'assume: asset_proportion = 100%' . PHP_EOL;
$investment_proportion = 1;
$response['msg'] .= 'assume: investment_proportion = 100%' . PHP_EOL;
$price_investment_asset = 1;
$response['msg'] .= 'assume: price_investment_asset is in capital currency' . PHP_EOL;
$sql = "
INSERT INTO asset_investment SET
ass_inv_id = NULL
, asset_id = {$asset['asset_id']}
, asset_proportion = {$asset_proportion}
, asset_parent_id = NULL
, asset_parent_proportion = NULL
, investment_id = {$investment_id}
, investment_proportion = {$investment_proportion}
, price_investment_asset = {$price_investment_asset}
";
$result = query($sql, $config);
if ($result === 1) {
	$ass_inv_id = mysqli_insert_id($config['sql_link']);
	$response['msg'] .= "ok: inserted ass_inv_id " . $ass_inv_id;
} else {
	$response['msg'] .= "failed: insert asset_investment";
	$response['error'] = TRUE;
}
process($response, $config);


$test = "map capital asset to transfer-in transaction";
$response = $config['response'];
$response['msg'] = $sep2 . $test . $sep2;
process($response, $config);


$asset['exchange'] = $exchange;
$response = investment_transaction($config, $asset);
if (!$response['error']) {
	$response['msg'] .= $sep . "ok: new asset_id {$response['result']['asset_id']} added and mapped";
} else {
	$response = investment_transaction_undo($config, $response);
}
process($response, $config);


$test = "mapping investment assets to transactions";
$response = $config['response'];
$response['msg'] = $sep2 . $test . $sep2;
process($response, $config);


$response = $config['response'];
$response['msg'] = $sep . 'select active assets ...' . PHP_EOL . PHP_EOL;
$sql = "
	SELECT DISTINCT asset, exchange_held as exchange FROM assets
	WHERE proportion_held > 0
	";
$assets = query($sql, $config);
$sql = "
	SELECT DISTINCT to_asset as asset, exchange FROM transactions
	WHERE ISNULL(from_ass_inv_id)
	AND ISNULL(to_ass_inv_id)
	AND time_closed > 0
	AND exchange_transaction_status = 'complete'
	ORDER BY time_closed ASC
	";
$t_assets = query($sql, $config);
$sql = "
	SELECT DISTINCT from_asset as asset, exchange FROM transactions
	WHERE ISNULL(from_ass_inv_id)
	AND ISNULL(to_ass_inv_id)
	AND time_closed > 0
	AND exchange_transaction_status = 'complete'
	ORDER BY time_closed ASC
	";
$f_assets = query($sql, $config);
$assets = array_merge($assets, $t_assets, $f_assets);
$assets = dedupe_array($assets);
if (empty($assets)) {
	$response['msg'] .= "ok: no active assets to process";
	process($response, $config);
} else
foreach ($assets as $asset) {


	$response = $config['response'];
	$response['msg'] = $sep . "select transactions mapable to asset {$asset['asset']} on {$asset['exchange']} exchange ..." . PHP_EOL . PHP_EOL;
	$sql = "
		SELECT * FROM transactions
		WHERE ISNULL(from_ass_inv_id)
		AND ISNULL(to_ass_inv_id)
		AND time_closed > 0
		AND exchange_transaction_status = 'complete'
		AND from_asset = '{$asset['asset']}'
		AND exchange = '{$asset['exchange']}'
		ORDER BY time_closed ASC
		";
	$result = query($sql, $config);
	if (!empty($result)) {
		$response['msg'] .= "ok: found " . count($result) . " new transactions to process ";
		process($response, $config);
	} else {
		$response['msg'] .= "ok: no new transactions to process ";
		process($response, $config);
		continue;
	}


	foreach ($result as $t) {
		$sql = "
			SELECT * FROM assets
			WHERE asset = '{$t['from_asset']}'
			AND exchange_held = '{$t['exchange']}'
			AND proportion_held > 0
			ORDER BY timestamp_held_first ASC
			LIMIT 1
			";
		$asset = query($sql, $config);
		if (empty($asset)) {
			$sql = "

				";
		} else
			$asset = $asset[0];
		$asset['exchange'] = '"' . $t['exchange'] . '"';
		$response = investment_transaction($config, $asset);
		if (!$response['error']) {
			$response['msg'] .= $sep . "ok: new asset_id {$response['result']['asset_id']} added and mapped";
		} else {
			$response = investment_transaction_undo($config, $response);
		}
		process($response, $config);
	}


}
