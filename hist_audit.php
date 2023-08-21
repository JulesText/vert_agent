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

	foreach ($balances_manual_dydx as $addr => $arr)
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
$string = runtime() . 'prepared audit data' . PHP_EOL . PHP_EOL;

if ($reset_balances) {
	$balances = $balances_dydx;
} else {
	$balances = json_decode(file_get_contents('db/balances.json'));
	$balances = objectToArray($balances);
}

if ($audit) foreach ($portfolios as $port) {

	if (strlen($port['alias']) == 4) $dydx = TRUE;
		else $dydx = FALSE;

	if ($reset_balances) $string .= runtime() . 'querying balances for ' . $port['alias'] . PHP_EOL;

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
			$contract = '0xeth';
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
				$nft = FALSE;
				foreach ($tokens as $t)
					if ($t['symbol'] == $asset) {
						if ($t['nftTokenId'] !== '') {
							$string .= PHP_EOL . 'warn:  ' . $port['alias'] . ': ' . $asset . ' no match in contracts.json (manually audit this NFT)' . PHP_EOL;
							$nft = TRUE;
						}
						break;
					}
				if (!$nft) $string .= PHP_EOL . 'error: ' . $port['alias'] . ': ' . $asset . ' no match in contracts.json' . PHP_EOL;
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
				$nft = FALSE;
				foreach ($tokens as $t)
					if ($t['symbol'] == $asset) {
						if ($t['nftTokenId'] !== '') {
							$string .= 'warn:  ' . $port['alias'] . ': ' . $asset . ' no match in balances.json (manually audit this NFT)' . PHP_EOL;
							$nft = TRUE;
						}
						break;
					}
				if (!$nft) $string .= 'error: ' . $port['alias'] . ': ' . $asset . ' no match in balances.json' . PHP_EOL;
				continue;
			}
		}

		$balance = bcdiv($balance, pow(10, $dec), $dec);
		$txn_fee = [];
		$txn_fee_free = [];
		$hist = '0';

		foreach ($txn_hist as $key => &$row) {
			if ($row['Wallet'] !== $port['alias']) continue;
			if ($row['Buy Asset'] == $asset) $hist = bcadd($hist, $row['Buy Quantity'], $dec);
			if ($row['Sell Asset'] == $asset) $hist = bcsub($hist, $row['Sell Quantity'], $dec);
			if ($row['Fee Asset'] == $asset) $hist = bcsub($hist, $row['Fee Quantity'], $dec);
			if ($row['Fee Quantity'] > 0) $txn_fee[$key] = (int) bcmul($row['Fee Quantity'], pow(10, $dec), 0);
		}
		# too difficult to get accurate match of block / date, so just take last txn date
		$time_unix = $row['time_unix'];
		$block = $row['block'];
		unset($row);

		$diff = bcsub($hist, $balance, $dec);
		$comp = bccomp($hist, $balance, $dec);
		if ($comp == 0) $pass[] = $asset; # balance equal
		if ($comp !== 0 && $dydx && $asset == 'USDC' && $diff < 5 && $diff > -5) { $comp = 0; $pass[] = $asset . '[dust<5]'; }
		if ($comp == 1)  $string .= PHP_EOL . 'error: ' . $port['alias'] . ': ' . $asset . ' '  . $contract . PHP_EOL . 'error: txn balance higher than actual balance' . PHP_EOL;
		if ($comp == -1) $string .= PHP_EOL . 'error: ' . $port['alias'] . ': ' . $asset . ' '  . $contract . PHP_EOL . 'error: txn balance lower than actual balance, checked fee-free txns ...' . PHP_EOL;
		if ($comp == -1 || $comp == 1) {
			$string .= $balance . ' actual balance' . PHP_EOL;
			$string .= $hist . ' txn_hist balance' . PHP_EOL;
			$string .= $diff . ' difference' . PHP_EOL;
			$target = (int) bcmul(bcsub($balance, $hist, $dec), pow(10, $dec), 0);
			$string .= 'target: ' . $target . PHP_EOL;
			if ($diff > 0) {
				$from = $port['address'];
				$to = ''; # leave blank for withdrawal
			} else {
				$from = ''; # leave blank for deposit
				$to = $port['address'];
			}
			$dust = FALSE;
			$i = 0;
			while (!$dust) {
				$i++;
				$hash = 'dust' . $i;
				$match_dust = FALSE;
				foreach ($txn_balance_dust as $txn_dust)
					if ($txn_dust['hash'] == $hash) $match_dust = TRUE;
				if (!$match_dust) $dust = TRUE;
			}

			$string .= "
may be preferable to balance dust in txn_balance_dust then rerun etherscan query
,[
'blockNumber' => " . $block . ",
'table' => 'txlist',
'wallet_address' => '" . $port['address'] . "',
'hash' => '" . $hash . "',
'tokenSymbol' => '" . $asset . "',
'tokenDecimal' => " . $dec . ",
'contractAddress' => '" . $contract . "',
'value' => " . abs($target) . ",
'timeStamp' => " . $time_unix . ",
'tokenID' => '',
'from' => '" . $from . "',
'to' => '" . $to . "',
'address' => 'NA'
]
			" . PHP_EOL;

		}

	}
$string .= runtime() . 'pass:  ' . $port['alias'] . ': ';
foreach ($pass as $asset) $string .= $asset . ' ';
$string .= PHP_EOL . PHP_EOL;
}

if ($reset_balances) file_put_contents('db/balances.json', json_encode($balances, JSON_PRETTY_PRINT));

die($string);
