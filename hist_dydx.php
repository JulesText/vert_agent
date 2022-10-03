<?php

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

# data load
$contracts = json_decode(file_get_contents('db/contracts.json'));
$contracts = objectToArray($contracts);

# open positions
$txn_data = json_decode(file_get_contents('db/dydx_positions.json'));
echo count($txn_data) . ' dydx_positions records read' . PHP_EOL;
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


$txn_hist = [];
foreach ($keys_txn as $key) $txn_hist[$key] = '';
$txn_hist_dydx = [];

# manual enter missing trades
$txn_manual = [
	['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USD'
	,'price' => 5.4
	,'size' => 62.5
	,'fee' => 0.160312]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USD'
	,'price' => 5.38
	,'size' => 62.5
	,'fee' => 0.159718]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USD'
	,'price' => 5.35
	,'size' => 62.5
	,'fee' => 0.158828]
];

# trades/fills

#$test = [];
$txn_data = json_decode(file_get_contents('db/dydx_txn_hist.json'));
$txn_data = array_merge($txn_manual, $txn_data);
echo count($txn_data) . ' dydx_txn_hist records read' . PHP_EOL;
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
		} else if ($t['side'] == 'BUY') {
			if ($asset == 'USDC') {
				# If the Fee Asset is the same as Sell Asset, then the Sell Quantity must be the net amount (after fee deduction), not gross amount. [this is already how dydx calculates transactions]
				$tx['Sell Quantity'] = (float) $t['size'] * $t['price'];
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
		#$tx['transaction_id'] = $tx['Timestamp'] . '_' . $t['market'] . '_' . $tx['wallet_address'];
		#$tx['Timestamp'] .= ' 23:59:59';
		$tx['market'] = $t['market'];

		$txn_hist_dydx[] = $tx;

		// if ($tx['wallet_address'] == '0x7578af57e2970edb7cb065b066c488bece369c43' && $tx['market'] == 'UMA-USD' && $tx['Timestamp'] == '2022-04-29')
		// 		$test[] = $tx;

	}

}
// ob_end_clean();
// $output = fopen("php://output",'w') or die("Can't open php://output");
// header("Content-Type:application/csv");
// header("Content-Disposition:attachment;filename=dat_etherscan.csv");
// fputcsv($output, array_keys($txn_hist_dydx[0]));
// foreach ($txn_hist_dydx as $row) fputcsv($output, $row);
// fclose($output) or die("Can't close php://output");
// die;

# payment history (interest/perpetual)

$txn_data = json_decode(file_get_contents('db/dydx_pay_hist.json'));
$txn_data = json_decode(json_encode($txn_data), true);
echo count($txn_data) . ' dydx_pay_hist records read' . PHP_EOL;
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
	#$tx['transaction_id'] = $tx['Timestamp'] . '_' . $t['market'] . '_' . $tx['wallet_address'];
	#$tx['Timestamp'] .= ' 23:59:59';
	$tx['market'] = $t['market'];

	$txn_hist_dydx[] = $tx;

}

# merge trades/fills/payments from same day/pair/type
$keys = [];
foreach ($txn_hist_dydx as $txn) {
	$keys[] = [
		 'Type' => $txn['Type']
		,'Buy Asset' => $txn['Buy Asset']
		,'Sell Asset' => $txn['Sell Asset']
		,'Fee Asset' => $txn['Fee Asset']
		,'wallet_address' => $txn['wallet_address']
		,'Timestamp' => $txn['Timestamp']
		,'market' => $txn['market']
		// ,'transaction_id' => $txn['transaction_id']
	];
}
echo count($keys) . ' transaction and payment records read' . PHP_EOL;
$keys = dedupe_array($keys); # can use high memory, use ini_set('memory_limit') in parameters above
echo count($keys) . ' transaction and payment records once merged by day' . PHP_EOL;

$i = 0;
$txn_hist_sum = [];
$counts = ['Buy Quantity', 'Sell Quantity', 'Fee Quantity'];
foreach ($keys as $key) {
	$tx = $txn_hist;
	foreach ($key as $k => $v) $tx[$k] = $v;
	foreach ($txn_hist_dydx as $txn) {
		if (
			$txn['Type'] == $key['Type']
			&& $txn['Buy Asset'] == $key['Buy Asset']
			&& $txn['Sell Asset'] == $key['Sell Asset']
			&& $txn['Fee Asset'] == $key['Fee Asset']
			&& $txn['wallet_address'] == $key['wallet_address']
			&& $txn['Timestamp'] == $key['Timestamp']
			&& $txn['market'] == $key['market']
		 ) {
				 # must be a match, so add
				 foreach ($counts as $var) {
		 			if ($txn[$var] !== '') {
		 				$tx[$var] = (float) $tx[$var] + (float) $txn[$var];
		 			}
		 		}
				$i++;
		 }
	}
	$txn_hist_sum[] = $tx;
}
echo $i . ' transaction and payment records calculated by day' . PHP_EOL;

foreach ($txn_hist_sum as &$txn) {
	if ($txn['Type'] == 'Trade')
		$txn['Note'] = 'Trade on dydx.';
	if ($txn['Type'] == 'Interest')
		$txn['Note'] = 'Interest earned on dydx perpetual.';
	if ($txn['Type'] == 'Spend')
		$txn['Note'] = 'Interest paid on dydx perpetual.';
	$txn['transaction_id'] = $txn['market'] . '_' . $txn['Timestamp'] . '_' . $txn['wallet_address'];
	$txn['Timestamp'] .= ' 23:59:59';
}
unset($txn);

$txn_data = json_decode(file_get_contents('db/dydx_tran_hist.json'));
echo count($txn_data) . ' dydx_tran_hist records read' . PHP_EOL;
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

	if ($t['confirmedAt'] == NULL)
		$tx['Timestamp'] = date('Y-m-d H:i:s', strtotime($t['createdAt']));
	else
		$tx['Timestamp'] = date('Y-m-d H:i:s', strtotime($t['confirmedAt']));
	$tx['transaction_id'] = $t['transactionHash'];

	$txn_hist_sum[] = $tx;

}

$price_dates = array();
$recent = 0;

foreach ($txn_hist_sum as &$txn) {
	$txn['time_unix'] = strtotime($txn['Timestamp']);
	if ($recent < $txn['time_unix']) $recent = $txn['time_unix'];
	$txn['error'] = '0';
	if ($txn['Buy Asset'] !== '') $price_dates[$txn['Buy Asset']][] = $txn['time_unix'];
	if ($txn['Sell Asset'] !== '') $price_dates[$txn['Sell Asset']][] = $txn['time_unix'];
	if ($txn['Fee Asset'] !== '') $price_dates[$txn['Fee Asset']][] = $txn['time_unix'];
	unset($txn['market']);
}
$recent = date('Y-m-d', $recent);

foreach ($price_dates as $key => $a) {
	$match = FALSE;
	foreach ($contracts as $ckey => $c) {
		if ($c['symbol'] == $key) {
			$price_dates[$ckey] = dedupe_array($a);
			$match = TRUE;
			unset($price_dates[$key]);
			continue;
		}
	}
	if (!$match) die('fatal error: asset ' . $key . ' not found in contracts.json');
}

# save

$a = array('updated' => $recent, 'txn_hist' => $txn_hist_sum, 'price_dates' => $price_dates);
file_put_contents('db/dydx_txn_hist_tidy.json', json_encode($a, JSON_PRETTY_PRINT));
unset($a);
unset($txn_hist_sum);
unset($txn_data);

echo 'dydx calcs complete' . PHP_EOL . PHP_EOL;
