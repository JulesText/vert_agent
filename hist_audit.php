<?php

$sort_keys = array();
foreach ($txn_hist as $row) {
	$s = (string) $row['Timestamp'] . $row['transaction_id'];
	if ($row['Type'] == 'Trade') $s .= '0';
	else if ($row['Type'] == 'Spend') $s .= '1';
	else if ($row['Type'] == 'Interest') $s .= '2';
	$sort_keys[] = $s;
}
array_multisort($sort_keys, SORT_ASC, $txn_hist);

# dydx balances
if ($reset_balances) {

	$balances_manual = [
		'J1dy' => ['LUNA' => 0]
		,'J6dy' => ['LUNA' => 0]
	];

	$arr = $balances_dydx;
	$balances_dydx = [];

	foreach ($arr as $key => $a) {
		# convert address to alias
		foreach ($portfolios as $p)
			if ($p['address'] == $key && substr($p['alias'], -2) == 'dy') {
				$key = $p['alias'];
				break;
			}
		# apply decimal multiplier & check is in contracts
		foreach ($a as $k => &$v) {
			foreach ($contracts as $ck) {
				if ($ck['symbol'] == $k) {
					$v = (float) $v * (int) pow(10, $ck['decimal']);
					break;
				}
			}
		}
		unset($v);
		$balances_dydx[$key] = $a;
	}

	foreach ($balances_manual as $addr => $arr)
		foreach ($arr as $k => $v)
			if (!isset($balances_dydx[$addr][$key]))
				$balances_dydx[$addr][$k] = (float) $v;

}

/*
# functions to reconcile asset fee balances
function sumCombinations($arr, $r, $target) {
	# all combinations of size r in arr[], returning imploded keys and sum of vals for arr[]
	$n = sizeof($arr);
	$keys = array();
	foreach ($arr as $key => $val) $keys[] = $key;
	$combo = array();
	$haystack = array();

	$haystack = combinationUtil($arr, $keys, $combo, $target, $haystack, 0, $n - 1, 0, $r);
	return $haystack;
}
function combinationUtil($arr, $keys, $combo, $target, &$haystack, $start, $end, $index, $r) {
	# current combination is ready
	if ($index == $r) {
		$sum = $target;
		foreach ($combo as $key) $sum -= $arr[$key];
		# tolerance to < 0.01 eth	is
		#								10000000000000000
		if (abs($sum) < 100) {
			#$keys = implode('|', $combo);
			#$haystack[$keys] = $sum;
			$a = [$combo, $sum];
			$haystack[] = $a;
		}
		return $haystack;
	}
	// replace index with all possible elements. The condition "end-i+1 >= r-index"
	// makes sure that including one element at index will make a combination with remaining elements at remaining positions
	for ($i = $start; $i <= $end && $end - $i + 1 >= $r - $index; $i++) {
		$combo[$index] = $keys[$i];
		combinationUtil($arr, $keys, $combo, $target, $haystack, $i + 1, $end, $index + 1, $r);
	}

	return $haystack;
}
*/

# check if asset balances match records

#$string = PHP_EOL . PHP_EOL . 'audit results' . PHP_EOL . PHP_EOL;
$string = '';

if ($reset_balances) {
	$balances = $balances_dydx;
} else {
	$balances = json_decode(file_get_contents('db/balances.json'));
	$balances = objectToArray($balances);
}

if ($audit) foreach ($portfolios as $port) {

	#if ($port['alias'] !== 'Jxdy') continue;
	if (strlen($port['alias']) == 4) $dydx = TRUE;
		else $dydx = FALSE;

	# assemble asset list
	$assets = [];
	foreach ($txn_hist as $row) {
		if ($row['Wallet'] !== $port['alias']) continue;
		$arr = ['Buy Asset', 'Sell Asset', 'Fee Asset'];
		foreach ($arr as $ass) {
			if ($row[$ass] !== '') $assets[] = $row[$ass];
			#if ($row[$ass] == NULL) { echo 'missing value ' . $ass . PHP_EOL; var_dump($row); die; }
		}
	}
	$assets = dedupe_array($assets);

	$pass = [];

	foreach ($assets as $asset) {

		#if (!in_array($asset, ['ETH','USDC'])) continue;
		if ($asset == 'ETH') {
			$dec = 18;
		} else {
			$match = FALSE;
			foreach ($contracts as $key => $c) {
				if ($c['symbol'] == $asset) {
					$contract = $key;
					$dec = $c['decimal'];
					$match = TRUE;
					break;
				}
			}
			if (!$match) {
				$string .= $port['alias'] . ' ' . $asset . ' error: no match in contracts.json (manually audit if NFT)' . PHP_EOL . PHP_EOL;
				continue;
			}
		}

		if ($reset_balances && !$dydx) {
			$config['exchange'] = 'etherscan';
			$config = config_exchange($config);
			$config['api_request'] = 'module=account&tag=latest'
					. '&apikey=' . $config['api_key']
					. '&address=' . $port['address'];
			if ($asset == 'ETH') $config['api_request'] .= '&action=balance';
				else $config['api_request'] .= '&action=tokenbalance&contractaddress=' . $contract;
			$config['url'] .= $config['api_request'];
			$result = query_api($config);
			$balance = $result['result'];
			$balances[$port['alias']][$asset] = $balance;
			sleep(0.25); # rate limit 5/second
		} else {
			$match = FALSE;
			foreach ($balances as $key => $arr) {
				if ($key !== $port['alias'] || !isset($arr[$asset])) continue;
				$balance = $arr[$asset];
				$match = TRUE;
				break;
			}
			if (!$match) {
				$string .= $port['alias'] . ' ' . $asset . ' error: no match in balances.json' . PHP_EOL . PHP_EOL;
				continue;
			}
		}

#var_dump($txn_hist);die;
		$balance = bcdiv($balance, pow(10, $dec), $dec);
		$txn_fee = [];
		$txn_fee_free = [];
		$hist = '0';

		foreach ($txn_hist as $key => &$row) {
			if ($row['Wallet'] !== $port['alias']) continue;
			if ($row['Buy Asset'] == $asset) $hist = bcadd($hist, $row['Buy Quantity'], $dec);
			if ($row['Sell Asset'] == $asset) $hist = bcsub($hist, $row['Sell Quantity'], $dec);
			if ($row['Fee Asset'] == $asset) $hist = bcsub($hist, $row['Fee Quantity'], $dec);
			if ($row['Fee Quantity'] > 0)
				$txn_fee[$key] = (int) bcmul($row['Fee Quantity'], pow(10, $dec), 0);
		}
		unset($row);

		$diff = bcsub($hist, $balance, $dec);
		$comp = bccomp($hist, $balance, $dec);
		if ($comp == 0) $pass[] = $asset; # balance equal
		if ($comp !== 0 && $dydx && $asset == 'USDC' && $diff < 5 && $diff > -5) { $comp = 0; $pass[] = $asset . '[dust<5]'; }
		if ($comp == 1) $string .= $port['alias'] . ' ' . $asset . ' '  . $contract . PHP_EOL . 'error: txn balance higher than actual balance' . PHP_EOL;
		if ($comp == -1) $string .= $port['alias'] . ' ' . $asset . ' '  . $contract . PHP_EOL . 'error: txn balance lower than actual balance, checked fee-free txns ...' . PHP_EOL;
		if ($comp == -1 || $comp == 1) {
			$string .= $balance . ' actual balance' . PHP_EOL;
			$string .= $hist . ' txn_hist balance' . PHP_EOL;
			$string .= $diff . ' difference' . PHP_EOL;
		}
		if ($comp == 1) $string .= PHP_EOL;

		if ($comp == -1) {

			$string .= count($txn_fee) . ' possible fee records' . PHP_EOL;
			$target = (int) bcmul(bcsub($balance, $hist, $dec), pow(10, $dec), 0);
			$string .= 'target: ' . $target . PHP_EOL;

			# get max/min num of records
			$arr = [];
			foreach ($txn_fee as $key => $val) {
				#if (substr($val, -4) !== '0000') $arr[$key] = $val;
				if ($val < 10000000000000000) $arr[$key] = $val;
			}
			asort($arr);
			$target_bal = $target;
			$max = 1;
			foreach ($arr as $fee) {
				$target_bal = $target_bal - $fee;
				if ($target_bal < 0) break;
				$max++;
			}
			$arr = array_reverse($arr, TRUE);
			$target_bal = $target;
			$min = 1;
			foreach ($arr as $fee) {
				$target_bal = $target_bal - $fee;
				if ($target_bal < 0) break;
				$min++;
			}
			$string .= 'somewhere between ' . $min . ' and ' . $max . ' fee records needed' . PHP_EOL;

			// $r = $min;
			// $arr = array_slice($arr, 0, 138, TRUE);
			// $start = milliseconds();
			// $haystack = sumCombinations($arr, $r, $target);
			// $duration = (milliseconds() - $start) / 1000;
			// echo $duration . ' seconds' . PHP_EOL;
			// var_dump($haystack);
			// die;

			$string .= PHP_EOL;
		}

	}
$string .= $port['alias'] . ' pass: ';
foreach ($pass as $asset) $string .= $asset . ' ';
$string .= PHP_EOL . PHP_EOL;
}

if ($reset_balances) file_put_contents('db/balances.json', json_encode($balances, JSON_PRETTY_PRINT));

die($string);
