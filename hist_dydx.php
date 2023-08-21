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
echo runtime() . count($txn_data) . ' dydx_positions records read' . PHP_EOL;
if ($dedupe_dydx) {
	$txn_data = dedupe_array($txn_data);
	echo count($txn_data) . ' dydx_positions records after dedupe' . PHP_EOL;
}
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
	$assets[$wal][$ass] = bcadd($assets[$wal][$ass], $t['size'], $dy_dec);

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
	,'market' => 'UMA-USDC'
	,'price' => 5.4
	,'size' => 62.5
	,'fee' => 0.160312
	]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USDC'
	,'price' => 5.38
	,'size' => 62.5
	,'fee' => 0.159718
	]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USDC'
	,'price' => 5.35
	,'size' => 62.5
	,'fee' => 0.158828
	]
];

# trades/fills

#$test = [];
$txn_data = json_decode(file_get_contents('db/dydx_txn_hist.json'));
$txn_data = array_merge($txn_manual, $txn_data);
echo runtime() . count($txn_data) . ' dydx_txn_hist records read' . PHP_EOL;
if ($dedupe_dydx) {
	$txn_data = dedupe_array($txn_data);
	echo count($txn_data) . ' dydx_txn_hist records after dedupe' . PHP_EOL;
}
foreach ($txn_data as $txn) {

	$t = [];
	foreach ($txn as $key => $val) $t[$key] = $val;

	$pair = explode('-', $t['market']);
	if ($pair[0] == 'BTC') $pair[0] = 'WBTC';
	$pair[1] = 'USDC';

	foreach ($pair as $asset) {

		$tx = ['Type' => 'Trade', 'Buy Quantity' => '', 'Buy Asset' => '', 'Sell Quantity' => '', 'Sell Asset' => '', 'Fee Quantity' => '', 'Fee Asset' => '', 'transfer_address' => 'NA'];

		$tx['side'] = $t['side'];
		if ($t['side'] == 'SELL') {
			if ($asset == 'USDC') {
				# If the Fee Asset is the same as Buy Asset, then the Buy Quantity must be the gross amount (before fee deduction), not net amount.
				$tx['Buy Quantity'] = (float) bcmul($t['size'], $t['price'], $dy_dec);
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
				$tx['Sell Quantity'] = (float) bcmul($t['size'], $t['price'], $dy_dec);
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
		$tx['market'] = $pair[0] . '-' . $pair[1];

		# exception where fee quantity is negative due to liquidation in a volatile market
		# counts as interest rather than fee
		if ($tx['Fee Quantity'] < 0) {
			# fees on dydx are only ever in USDC
			if ($tx['Buy Asset'] == 'USDC') $tx['Buy Quantity'] = (float) bcsub($tx['Buy Quantity'], $tx['Fee Quantity'], $dy_dec);
			else die('unexplained transaction');
			$tx['Fee Quantity'] = 0;
		}

		# record
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
echo runtime() . count($txn_data) . ' dydx_pay_hist records read' . PHP_EOL;
$keys = ['rate', 'positionSize', 'price'];
foreach ($txn_data as &$row) foreach ($keys as $key) unset($row[$key]);
unset($row);
if ($dedupe_dydx) {
	$txn_data = dedupe_array($txn_data);
	echo count($txn_data) . ' dydx_pay_hist records after dedupe' . PHP_EOL;
}
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
	#$tx['market'] = $t['market'];
	$pair = explode('-', $t['market']);
	if ($pair[0] == 'BTC') $pair[0] = 'WBTC';
	$pair[1] = 'USDC';
	$tx['market'] = $pair[0] . '-' . $pair[1];

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
echo runtime() . count($keys) . ' transaction and payment records read' . PHP_EOL;
$keys = dedupe_array($keys); # must be deduped, can use high memory, use ini_set('memory_limit') in parameters above
echo runtime() . 'expect ' . count($keys) . ' transaction and payment records once merged by day' . PHP_EOL;

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
						$tx[$var] = (float) bcadd($tx[$var], $txn[$var], $dy_dec);
		 			}
		 		}
				$i++;
		 }
	}
	$txn_hist_sum[] = $tx;
}
echo runtime() . $i . ' transaction and payment records processed by day' . PHP_EOL;
echo runtime() . 'result ' . count($txn_hist_sum) . ' transaction and payment records once merged by day' . PHP_EOL;

foreach ($txn_hist_sum as &$txn) {
	$txn['transaction_id'] = $txn['market'] . '_' . $txn['Timestamp'] . '_' . $txn['wallet_address']; # do not change without tracing dependents
	if ($txn['Type'] == 'Trade') {
		$txn['Note'] = 'Trade on dydx ' . $txn['market'] . ' perpetual.';
		#if ($txn['Buy Quantity'] !== '') $txn['Timestamp'] .= ' 23:59:10'; # timestamp used for sort order later on
		#else $txn['Timestamp'] .= ' 23:59:30';
	}
	if ($txn['Type'] == 'Interest') {
		$txn['Note'] = 'Interest earned on dydx ' . $txn['market'] . ' perpetual.';
		#$txn['Timestamp'] .= ' 23:59:50';
	}
	if ($txn['Type'] == 'Spend') {
		$txn['Note'] = 'Interest paid on dydx ' . $txn['market'] . ' perpetual.';
		#$txn['Timestamp'] .= ' 23:59:55';
	}
	$txn['Timestamp'] .= ' 23:59:59';
}
unset($txn);

$txn_data = json_decode(file_get_contents('db/dydx_tran_hist.json'));
echo runtime() . count($txn_data) . ' dydx_tran_hist records read' . PHP_EOL;
if ($dedupe_dydx) {
	$txn_data = dedupe_array($txn_data);
	echo count($txn_data) . ' dydx_tran_hist records after dedupe' . PHP_EOL;
}
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
		$tx['Fee Quantity'] = (float) bcsub($t['debitAmount'], $t['creditAmount'], $dy_dec);
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
		if ($t['transactionHash'] == '') $t['transactionHash'] = 'layer2_' . $t['fromAddress'];
	} else die('error');

	$tx['wallet_address'] = strtolower($t['wallet']);

	if ($t['confirmedAt'] == NULL)
		$tx['Timestamp'] = date('Y-m-d H:i:s', strtotime($t['createdAt']));
	else
		$tx['Timestamp'] = date('Y-m-d H:i:s', strtotime($t['confirmedAt']));
	$tx['transaction_id'] = $t['transactionHash'];

	$txn_hist_sum[] = $tx;

}

# track positions open/close to avoid negative asset value on txn sorted by timestamp
# avoid rp2 error by having all day trades at same second

# get all wallet_address | market combinations
$combos = array();
foreach ($txn_hist_sum as $txn) {
	foreach (['Buy', 'Sell', 'Fee'] as $side)
		if ($txn[$side.' Asset'] !== '')
			$combos[] = array('wallet_address' => $txn['wallet_address'], 'asset' => $txn[$side.' Asset']);
}
$combos = dedupe_array($combos);

# for each, iterate txn_hist and check if daily total trades in asset becomes negative
$short_positions = [];
foreach ($combos as $combo) {

	$asset = $combo['asset'];

	$txn_hist_pos = [];
	foreach ($txn_hist_sum as $txn)
		if ($txn['wallet_address'] == $combo['wallet_address'])
			if ($txn['Buy Asset'] == $asset || $txn['Sell Asset'] == $asset || $txn['Fee Asset'] == $asset)
				$txn_hist_pos[] = $txn;

	$sort_keys = array();
	foreach ($txn_hist_pos as $txn) {
		if ($txn['Buy Asset'] == $asset) {
			$size = ceil($txn['Buy Quantity']); # round up
			$size = sprintf('%08d', $size); # add up to 8 leading zeros, sorts ascending
			$sort_keys[] = $txn['Timestamp'] . ' 1 ' . $size;
		} else {
			$size = ceil($txn['Sell Quantity']+$txn['Fee Quantity']); # round up
			$size = sprintf('%08d', $size); # add up to 8 leading zeros
			$size = 100000000 - $size; # sorts descending
			$sort_keys[] = $txn['Timestamp'] . ' 2 ' . $size;
		}
	}
	array_multisort($sort_keys, SORT_ASC, $txn_hist_pos);

	$running_total = (float) 0;
	$running_negative = (float) 0;
	$skip_ahead = FALSE;

	foreach ($txn_hist_pos as $key => $txn) {

		# skip if we've already accounted for this txn
		if ($skip_ahead) {
			$skip_ahead = FALSE;
			continue;
		}

		$day = substr($txn['Timestamp'], 0, 10);

		# get amount
		$amount = 0;
		if ($txn['Buy Asset'] == $asset) $amount = (float) $txn['Buy Quantity'];
		if ($txn['Sell Asset'] == $asset) $amount = (float) bcsub($amount, $txn['Sell Quantity'], $dy_dec);
		if ($txn['Fee Asset'] == $asset) $amount = (float) bcsub($amount, $txn['Fee Quantity'], $dy_dec);
		if (!$amount) continue; # not logically possible but here in case of unexpected data
		# trade fee always in USDC, no need to check

		# track
		if ($track[0] == $txn['wallet_address'] && $track[1] == $asset)
			echo runtime() . $asset . ' $day ' . $day . ' $running_negative ' . $running_negative . ' $running_total ' . $running_total . PHP_EOL;

		# process amount
		$running_total = (float) bcadd($running_total, $amount, $dy_dec);

		if ($track[0] == $txn['wallet_address'] && $track[1] == $asset)
			echo ' + $amount ' . $amount . ' = $running_total ' . $running_total . PHP_EOL;

		# case 0: no open/extension of short position
		# occurs: upon buy trade
		if ($amount > 0 && $running_negative == 0) continue;

		# case 1: open/extend a short position
		# occurs: upon sell trade
		# cases: 	1a open completely new short position
		# 				1b extend existing short position
		# conditions: side is sell
		#							running_total < 0
		# conditn 1a: running_negative = 0
		# conditn 1b:	running_negative < 0
		if ($amount < 0 && $running_total < 0) {
			# distinction btw cases 1a, 1b
			# calculate change in position
			if ($running_negative == 0) {
				$case = 'Open';
				$pos_amount = (float) abs($running_total);
			} else {
				$case = 'Extend';
				$pos_amount = (float) abs($amount);
			}
			# record
			$txn_position = [];
			foreach ($keys_txn as $k) $txn_position[$k] = '';
			$txn_position['Type'] = 'Trade';
			$txn_position['Buy Quantity'] = $pos_amount;
			$txn_position['Buy Asset'] = $asset;
			#$txn_position['Timestamp'] = $day . ' 23:59:20';
			$txn_position['Timestamp'] = $day . ' 23:59:59';
			$txn_position['Note'] = $case . ' short position on dydx ' . $txn['market'] . ' market.';
			$txn_position['transaction_id'] = $txn['transaction_id'];
			$txn_position['wallet_address'] = $txn['wallet_address'];
			$txn_position['market'] = $txn['market'];
			$short_positions[] = $txn_position;
			# update running negative amount (i.e. position size)
			$running_negative = bcsub($running_negative, $pos_amount, $dy_dec);
			if ($track[0] == $txn['wallet_address'] && $track[1] == $asset)
				echo '  ' . $txn_position['Note'] . ' $pos_amount ' . $pos_amount . PHP_EOL . '  $running_negative ' . $running_negative . PHP_EOL;
		}

		# case 2: close/reduce short position
		# occurs: upon buy trade
		# exceptions:	if next txn is sell on same day, need to account
		#							if only reducing short position by small amount, ignore
		# cases:	2a close position
		#					2b reduce position
		#					2c extend position
		#					2d static position
		# conditions: side is buy
		#							running_negative < 0
		# conditn 2a: running_negative >= 0 (after any look ahead adjustment)
		# conditn 2b:	running_negative < 0 (after any look ahead adjustment)
		#							$amount > look ahead amount (if exists)
		# conditn 2c:	running_negative < 0
		#							$amount < look ahead amount (must exist)
		# conditn 2d: $amount = look ahead amount (must exist)
		if ($amount > 0 && $running_negative < 0) {

			# look ahead that day to check if sell trade is subsequent and process
			# and if found to then change get amount for correct position closure/reduction, or no change
			$ahead = 0;
			if ($key + 1 < count($txn_hist_pos)) # check if this is last txn, then do not check for next
				# buy/sell/payment trades have same txn id if same day, market and wallet
				if ($txn_hist_pos[$key+1]['transaction_id'] == $txn['transaction_id']) {
					if ($txn_hist_pos[$key+1]['Sell Asset'] == $asset)
						$ahead = (float) bcsub($ahead, $txn_hist_pos[$key+1]['Sell Quantity'], $dy_dec);
					if ($txn_hist_pos[$key+1]['Fee Asset'] == $asset)
						$ahead = (float) bcsub($ahead, $txn_hist_pos[$key+1]['Fee Quantity'], $dy_dec);
				}

			# no look ahead - no following txn with sell that day
			if ($ahead == 0) {
				# case 2a - close
				if ($amount >= abs($running_negative)) {
					$case = 'Close';
					$pos_amount = (float) abs($running_negative);
					$running_negative = 0;
				}
				# case 2b - reduce
				else {
					# exception, don't bother reducing position if its a small txn, i.e. interest/payment
					if ($asset == 'USDC' && $amount < 50) continue;
					# otherwise capture
					$case = 'Reduce';
					$pos_amount = (float) $amount;
					$running_negative = bcadd($running_negative, $amount, $dy_dec);
				}
			}

			# look ahead - there is a following txn with sell that day
			if ($ahead < 0) {

				$running_negative_ahead = (float) bcadd($running_negative, $amount, $dy_dec); # this trade
				$running_negative_ahead = (float) bcadd($running_negative_ahead, $ahead, $dy_dec); # next trade

				# case 2a - close
				if ($running_negative_ahead >= 0) {
					$case = 'Close';
					$pos_amount = (float) abs($running_negative);
					$running_negative = 0;
				}
				# case 2b - reduce
				else if ($amount > abs($ahead)) {
					# exception, don't bother reducing position if its a small txn, i.e. interest/payment
					if ($asset == 'USDC' && $amount < 50) continue;
					# otherwise capture
					$case = 'Reduce';
					$pos_amount = (float) abs(bcadd($amount, $ahead, $dy_dec));
					$running_negative = $running_negative_ahead;
				}
				# case 2c - extend
				else if ($amount < abs($ahead)) {
					$case = 'Extend';
					$pos_amount = (float) abs(bcadd($amount, $ahead, $dy_dec));
					$running_negative = $running_negative_ahead;
				}
				# case 2d - static
				else if ($amount == abs($ahead)) {
					continue;
				}
				else die('look ahead error');

				$skip_ahead = TRUE;
				$running_total = bcadd($running_total, $ahead, $dy_dec);

			}

			# record
			$txn_position = [];
			foreach ($keys_txn as $k) $txn_position[$k] = '';
			$txn_position['Type'] = 'Trade';
			if ($case == 'Close' || $case == 'Reduce') {
				$txn_position['Sell Quantity'] = $pos_amount;
				$txn_position['Sell Asset'] = $asset;
			}
			if ($case == 'Extend') {
				$txn_position['Buy Quantity'] = $pos_amount;
				$txn_position['Buy Asset'] = $asset;
			}
			#$txn_position['Timestamp'] = $day . ' 23:59:40';
			$txn_position['Timestamp'] = $day . ' 23:59:59';
			$txn_position['Note'] = $case . ' short position on dydx ' . $txn['market'] . ' market.';
			$txn_position['transaction_id'] = $txn['transaction_id'];
			$txn_position['wallet_address'] = $txn['wallet_address'];
			$txn_position['market'] = $txn['market'];
			$short_positions[] = $txn_position;
			if ($track[0] == $txn['wallet_address'] && $track[1] == $asset)
				echo '  ' . $txn_position['Note'] . ' $pos_amount ' . $pos_amount . PHP_EOL . '   $running_negative ' . $running_negative . ' $skip_ahead ' . ($skip_ahead ? 'TRUE $ahead '.$ahead : 'FALSE') . PHP_EOL;
		}
	}
}
if ($track[0]) die;

$txn_hist_sum = array_merge($txn_hist_sum, $short_positions);

$recent = 0;

foreach ($txn_hist_sum as &$txn) {
	$txn['time_unix'] = strtotime($txn['Timestamp']);
	if ($recent < $txn['time_unix']) $recent = $txn['time_unix'];
	$txn['error'] = '0';
	unset($txn['market']);
}
$recent = date('Y-m-d', $recent);

# save

$a = array('updated' => $recent, 'txn_hist' => $txn_hist_sum);
file_put_contents('db/dydx_txn_hist_tidy.json', json_encode($a, JSON_PRETTY_PRINT));
unset($a);
unset($txn_hist_sum);
unset($txn_data);

echo runtime() . 'dydx calcs complete' . PHP_EOL . PHP_EOL;
if ($dydx_die) die;
