<?php

include('system_includes.php');

// to populate price queries, need to rerun script multiple times until die stops or timeouts cease
// may need to directly edit contracts.json to work with coingecko
// all prices from coingecko unless otherwise stated in $price_manual
// https://www.coingecko.com/en/api/documentation
// https://api.coingecko.com/api/v3/coins/list

// commands to write to disk may need permissions, for instance:
// 	file_put_contents('db/txn_hist.json', json_encode($a));
// so in terminal consider
// 	chmod 777 txn_hist.json

# parameters
set_time_limit(60);
ini_set('memory_limit', '512M');
$dy_dec = 10; # number of decimal places for dydx txn calculations
#echo ini_get('memory_limit');die;
$fiat = $config['base_fiat'];
$query_period = 'last'; # 'last' to exclude previously-queried periods, or 'all' for all
$dydx = FALSE; # run dydx txn processing functions, hist_dydx.py used to generate new txn history
	$track = [FALSE,FALSE]; #track output on screen
	#$track = ['0x5b5af4b5ab0ed2d39ea27d93e66e3366a01d7aa9','ETH'];
$etherscan = TRUE; # run eth txn processing functions
	$reset_txn_hist_eth = TRUE; # refresh transaction history
	$reset_hist_die = TRUE; # show on screen refresh is complete
	$contracts_append = FALSE; # set contracts_append false by default
		# if FALSE, as new contracts identified, critical stop occurs and prints on screen
		# if seem like spam then add address to $spam list in hist_etherscan.php
		# if not spam then allow, and set $contracts_append = TRUE, then rerun to add contracts to db
$audit = FALSE; # run audit and print results, no txn file output
	$reset_balances = FALSE; # for audit, reset wallet balances history
$restrict_fiat_value = FALSE; # restrict fiat value calculations to taxable events (rp2/bittytax may throw error)
$today = date('Y-m-d', $config['timestamp'] / 1000);

# portfolios
$keys = array('address', 'portfolio', 'alias');
$portfolios = array(
	 ['0x7578af57e2970edb7cb065b066c488bece369c43', 'JK', 'J1'] /* trezor */
	,['0xd898f6bfbe145d84526ec25700c2c52e04a6c240', 'JK', 'J2'] /* metamask */
 	,['0x861afbf9a062d4c8b583140c1c884529ca21e503', 'JK', 'J3'] /* metamask */
	,['0x4263891bc4469759ac035f1f3cceb2ed87deaa7e', 'AK', 'AK'] /* trezor */
	,['0xd5c24396683c236452a898ce45a16553358a660b', 'JK', 'J4'] /* trezor */
	,['0xe1688450ed79ad737755965c912447df0d933b5a', 'JB', 'JB'] /* trezor */
	,['0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7', 'JK', 'J5'] /* trezor */
	,['0x5b5af4b5ab0ed2d39ea27d93e66e3366a01d7aa9', 'JK', 'J6'] /* trezor */
	,['0xb351a776afbceb74d2d3747d05cf4c3b1cc539c7', 'JK', 'J7'] /* trezor */
	,['0x4b50bfea9c49d01616c73edb9c73421530ffe096', 'JK', 'J8'] /* trezor */
	,['0x56b8021aeb2315e03ea1c99c2be81baf0a2cb283', 'JK', 'J9'] /* trezor */
	,['0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0', 'AO', 'AO'] /* trezor */
);
foreach ($portfolios as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row); # unset &$row as still accessible

# contracts
$contracts = json_decode(file_get_contents('db/contracts.json'));
$contracts = objectToArray($contracts);

# format
$keys_out = array(
	'Type',
	'Buy Quantity',
	'Buy Asset',
	'Buy Value in ' . $fiat,
	'Sell Quantity',
	'Sell Asset',
	'Sell Value in ' . $fiat,
	'Fee Quantity',
	'Fee Asset',
	'Fee Value in ' . $fiat,
	'Wallet',
	'Timestamp',
	'Note',
	'time_unix',
	'transaction_id',
	'portfolio',
	'transfer_address'
);
$keys_txn = array_merge($keys_out, [
	'wallet_address',
	'transfer_alias',
	'Buy_contract_address',
	'Sell_contract_address',
	'Fee_contract_address',
	'error',
	'type.ori',
	't_id_sides',
	't_id_fee_count'
]);

# dydx
if ($dydx) require('hist_dydx.php');

# etherscan
require('hist_etherscan_idio.php');
if ($etherscan) require('hist_etherscan.php');

# bring in exchange data

$txn_hist_eth = json_decode(file_get_contents('db/eth_txn_hist_tidy.json'));
$txn_hist_eth = objectToArray($txn_hist_eth);
$txn_eth = $txn_hist_eth['txn_hist'];

$txn_hist_dydx = json_decode(file_get_contents('db/dydx_txn_hist_tidy.json'));
$txn_hist_dydx = objectToArray($txn_hist_dydx);
$txn_dydx = $txn_hist_dydx['txn_hist'];

# update portfolios
$balances_dydx = json_decode(file_get_contents('db/dydx_balance.json'));
$balances_dydx = objectToArray($balances_dydx);
$arr = [];
foreach ($balances_dydx as $key => $a)
	$arr[] = $key;
foreach ($arr as $a) {
	foreach ($portfolios as $p) {
		if ($p['address'] == $a) {
			$r['address'] = $a;
			$r['portfolio'] = $p['portfolio'];
			$r['alias'] = $p['alias'] . 'dy';
			$portfolios[] = $r;
			continue;
		}
	}
}

# update transactions and merge
foreach ($txn_dydx as &$txn) {
	# portfolio / wallet
	foreach ($portfolios as $p)
		if ($p['address'] == $txn['wallet_address']) {
			$txn['Wallet'] = $p['alias'];
			$txn['portfolio'] = $p['portfolio'];
			continue;
		}
	# asset contract
	foreach (array('Buy', 'Sell', 'Fee') as $side) {
		if ($txn[$side.' Asset'] !== '') {
			$ass = $txn[$side.' Asset'];
			foreach ($contracts as $add => $c) {
				if ($ass == $c['symbol']) {
					$txn[$side.'_contract_address'] = $add;
					continue;
				}
			}
		}
	}
}
unset($txn);

$txn_hist = array_merge($txn_eth, $txn_dydx);

$sort_keys = sort_txns($txn_hist);
array_multisort($sort_keys, SORT_ASC, $txn_hist);

# audit report
if ($audit || $reset_balances) require('hist_audit.php');

# update contracts, calculate fiat prices and output data file
require('hist_secondary.php');
