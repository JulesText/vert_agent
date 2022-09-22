<?php

include('system_includes.php');

// $config['exchange'] = 'dydx';
// $config = config_exchange($config);
// $config['data'] = '';

// $config['api_request'] = $config['server_time'];
// $config['url'] .= $config['api_request'];
// $result = query_api($config);
// var_dump($result);

// $config['method'] = 'GET';
// $config['api_request'] = $config['api_keys'];
// $config['url'] .= $config['api_request'];
// $result = query_api($config);
// var_dump($result);

// $config = config_exchange($config);
// $config['api_request'] = $config['fills'];
// $config['url'] .= $config['api_request'];
// $result = query_api($config);
// var_dump($result);
// die;


$txn_data = json_decode(file_get_contents('db/dydx_positions.json'));
echo count($txn_data) . ' dydx_positions records' . PHP_EOL;
$txn_data = dedupe_array($txn_data);
echo count($txn_data) . ' dydx_positions records after dedupe' . PHP_EOL;
$assets = [];
foreach ($txn_data as $txn) {

	$t = [];
	foreach ($txn as $key => $val) $t[$key] = $val;

	$pair = explode('-', $t['market']);
	if ($pair[0] == 'BTC') $pair[0] = 'WBTC';
	$ass = $pair[0];
	$wal = strtolower($t['wallet']);

	if (!isset($assets[$wal][$ass])) $assets[$wal][$ass] = (float) 0;

	if ($t['status'] == 'CLOSED') continue;

	# long positions will show a positive value
	# short positions will show a negative value
	# this should reconcile with txn_hist
	$assets[$wal][$ass] += (float) $t['size'];

}
$txn_data = json_decode(file_get_contents('db/dydx_acc_status.json'));
foreach ($txn_data as $txn) {
	$wal = strtolower($txn[0]);
	$assets[$wal]['USDC'] = $txn[1];
}
file_put_contents('db/dydx_balance.json', json_encode($assets, JSON_PRETTY_PRINT));


$fiat = 'AUD';
$txn_hist = [];

$txn_hist['Type'] = '';
$txn_hist['Buy Quantity'] = '';
$txn_hist['Buy Asset'] = '';
$txn_hist['Buy Value in ' . $fiat] = '';
$txn_hist['Sell Quantity'] = '';
$txn_hist['Sell Asset'] = '';
$txn_hist['Sell Value in ' . $fiat] = '';
$txn_hist['Fee Quantity'] = '';
$txn_hist['Fee Asset'] = '';
$txn_hist['Fee Value in ' . $fiat] = '';
$txn_hist['Wallet'] = '';
$txn_hist['Timestamp'] = '';
$txn_hist['Note'] = '';
$txn_hist['time_unix'] = '';
$txn_hist['transaction_id'] = '';
$txn_hist['portfolio'] = '';
$txn_hist['transfer_address'] = '';

$txn_hist['wallet_address'] = '';
$txn_hist['transfer_alias'] = '';
$txn_hist['Buy_contract_address'] = '';
$txn_hist['Sell_contract_address'] = '';
$txn_hist['Fee_contract_address'] = '';
$txn_hist['error'] = '';
$txn_hist['type.ori'] = '';
$txn_hist['t_id_sides'] = '';
$txn_hist['t_id_fee_count'] = '';

$txn_hist_dydx = [];

$txn_data = json_decode(file_get_contents('db/dydx_txn_hist.json'));
echo count($txn_data) . ' dydx_txn_hist records' . PHP_EOL;
$txn_data = dedupe_array($txn_data);
echo count($txn_data) . ' dydx_txn_hist records after dedupe' . PHP_EOL;
foreach ($txn_data as $txn) {

	$t = [];
	foreach ($txn as $key => $val) $t[$key] = $val;

	$pair = explode('-', $t['market']);
	if ($pair[0] == 'BTC') $pair[0] = 'WBTC';
	$pair[1] = 'USDC';

	foreach ($pair as $asset) {

		$tx = ['Type' => 'Trade', 'Buy Quantity' => '', 'Buy Asset' => '', 'Sell Quantity' => '', 'Sell Asset' => '', 'Fee Quantity' => '', 'Fee Asset' => ''];

		$tx['side'] = $t['side'];
		if ($t['side'] == 'SELL') {
			if ($asset == 'USDC') {
				# If the Fee Asset is the same as Buy Asset, then the Buy Quantity must be the gross amount (before fee deduction), not net amount.
				$tx['Buy Quantity'] = (float) $t['size'] * $t['price'];
				$tx['Buy Asset'] = $asset;
				$tx['Fee Quantity'] = (float) $t['fee'];
				$tx['Fee Asset'] = $asset;
			} else {
				$tx['Sell Quantity'] = (float) $t['size'];
				$tx['Sell Asset'] = $asset;
			}
		} else {
			if ($asset == 'USDC') {
				# If the Fee Asset is the same as Sell Asset, then the Sell Quantity must be the net amount (after fee deduction), not gross amount.
				$tx['Sell Quantity'] = (float) $t['size'] * $t['price'] + (float) $t['fee'];
				$tx['Sell Asset'] = $asset;
				$tx['Fee Quantity'] = (float) $t['fee'];
				$tx['Fee Asset'] = $asset;
			} else {
				$tx['Buy Quantity'] = (float) $t['size'];
				$tx['Buy Asset'] = $asset;
			}
		}
		$tx['wallet_address'] = strtolower($t['wallet']);
		# we reduce timestamp to whole day to minimise number of records
		$tx['Timestamp'] = date('Y-m-d', strtotime($t['createdAt']));
		# transaction_id becomes redundant
		#$tx['transaction_id'] = $t['orderId'];
		$tx['transaction_id'] = $tx['Timestamp'] . '_' . $t['market'] . '_' . $tx['wallet_address'];
		$tx['Timestamp'] .= ' 23:59:59';

		$txn_hist_dydx[] = $tx;

	}

}

$txn_data = json_decode(file_get_contents('db/dydx_pay_hist.json'));
$txn_data = json_decode(json_encode($txn_data), true);
echo count($txn_data) . ' dydx_pay_hist records' . PHP_EOL;
$keys = ['rate', 'positionSize', 'price'];
foreach ($txn_data as &$row) foreach ($keys as $key) unset($row[$key]);
unset($row);
$txn_data = dedupe_array($txn_data);
echo count($txn_data) . ' dydx_pay_hist records after dedupe' . PHP_EOL;
foreach ($txn_data as $txn) {

	$t = [];
	foreach ($txn as $key => $val) $t[$key] = $val;

	$tx = ['Type' => '', 'Buy Quantity' => '', 'Buy Asset' => '', 'Sell Quantity' => '', 'Sell Asset' => '', 'Fee Quantity' => '', 'Fee Asset' => ''];

	if ($t['payment'] > 0) {
		$tx['Type'] = 'Interest';
		$tx['Buy Quantity'] = (float) $t['payment'];
		$tx['Buy Asset'] = 'USDC';
	} else {
		$tx['Type'] = 'Spend';
		$tx['Sell Quantity'] = abs((float) $t['payment']);
		$tx['Sell Asset'] = 'USDC';
	}

	$tx['wallet_address'] = strtolower($t['wallet']);
	# we reduce timestamp to whole day to minimise number of records
	$tx['Timestamp'] = date('Y-m-d', strtotime($t['effectiveAt']));
	# transaction_id becomes redundant
	#$tx['transaction_id'] = $t['orderId'];
	$tx['transaction_id'] = $tx['Timestamp'] . '_' . $t['market'] . '_' . $tx['wallet_address'];
	$tx['Timestamp'] .= ' 23:59:59';

	$txn_hist_dydx[] = $tx;

}

# merge fills/payments from same day/pair
$keys = [];
foreach ($txn_hist_dydx as $txn) {
	$keys[] = [
		 'Type' => $txn['Type']
		,'Buy Asset' => $txn['Buy Asset']
		,'Sell Asset' => $txn['Sell Asset']
		,'Fee Asset' => $txn['Fee Asset']
		,'wallet_address' => $txn['wallet_address']
		,'Timestamp' => $txn['Timestamp']
		,'transaction_id' => $txn['transaction_id']
	];
}
$keys = dedupe_array($keys);
$txn_hist_sum = [];
$counts = ['Buy Quantity', 'Sell Quantity', 'Fee Quantity'];
foreach ($keys as $key) {
	$tx = $txn_hist;
	foreach ($key as $k => $v) $tx[$k] = $v;
	foreach ($txn_hist_dydx as $txn) {
		if ($txn['Buy Asset'] == $key['Buy Asset']
			 && $txn['Sell Asset'] == $key['Sell Asset']
			 && $txn['Fee Asset'] == $key['Fee Asset']
			 && $txn['transaction_id'] == $key['transaction_id']) {
				 # must be a match, so add
				 foreach ($counts as $var) {
		 			if ($txn[$var] !== '') {
		 				$tx[$var] = (float) $tx[$var] + (float) $txn[$var];
		 			}
		 		}
			 }
	}
	$tx['Note'] = 'Trade or payment on dydx.';
	$txn_hist_sum[] = $tx;
}


$txn_data = json_decode(file_get_contents('db/dydx_tran_hist.json'));
echo count($txn_data) . ' dydx_tran_hist records' . PHP_EOL;
$txn_data = dedupe_array($txn_data);
echo count($txn_data) . ' dydx_tran_hist records after dedupe' . PHP_EOL;
foreach ($txn_data as $txn) {

	$t = [];
	foreach ($txn as $key => $val) $t[$key] = $val;

	$tx = $txn_hist;

	if ($t['status'] !== 'CONFIRMED') continue;

	if ($t['type'] == 'FAST_WITHDRAWAL') {
		# If the Fee Asset is the same as Sell Asset, then the Sell Quantity must be the net amount (after fee deduction), not gross amount.
		$tx['Type'] = 'Withdrawal';
		$tx['Sell Quantity'] = (float) $t['creditAmount'];
		$tx['Sell Asset'] = $t['creditAsset'];
		$tx['Fee Quantity'] = (float) $t['debitAmount'] - (float) $t['creditAmount'];
		$tx['Fee Asset'] = $t['debitAsset'];
		$tx['transfer_address'] = $t['fromAddress'];
		$tx['Note'] = 'Widthdrawal from dydx.';
	} else if ($t['type'] == 'DEPOSIT') {
		# If the Fee Asset is the same as Buy Asset, then the Buy Quantity must be the gross amount (before fee deduction), not net amount.
		$tx['Type'] = 'Deposit';
		$tx['Buy Quantity'] = (float) $t['debitAmount'];
		$tx['Buy Asset'] = $t['debitAsset'];
		$tx['transfer_address'] = strtolower($t['wallet']);
		$tx['Note'] = 'Deposit to dydx.';
	} else if ($t['type'] == 'TRANSFER_IN') {
		# special offer
		$tx['Type'] = 'Airdrop';
		$tx['Buy Quantity'] = (float) $t['creditAmount'];
		$tx['Buy Asset'] = $t['creditAsset'];
		$tx['transfer_address'] = $t['wallet'];
		$tx['Note'] = 'Bonus offer on dydx.';
	} else die('error');

	$tx['wallet_address'] = strtolower($t['wallet']);
	$tx['Timestamp'] = date('Y-m-d H:i:s', strtotime($t['confirmedAt']));
	$tx['transaction_id'] = $t['transactionHash'];

	$txn_hist_sum[] = $tx;

}

foreach ($txn_hist_sum as &$txn) {
	$txn['time_unix'] = strtotime($txn['Timestamp']);
	$txn['error'] = '0';
}

# var_dump($txn_hist_sum);
file_put_contents('db/dydx_txn_hist_tidy.json', json_encode($txn_hist_sum, JSON_PRETTY_PRINT));
