<?php

include('system_includes.php');

// commands to write to disk may need permissions, for instance:
// 	file_put_contents('db/txn_hist.json', json_encode($a));
// so in terminal consider
// 	chmod 777 txn_hist.json

# parameters
set_time_limit(60);
ini_set('memory_limit', '512M');
#echo ini_get('memory_limit');die;
$fiat = $config['base_fiat'];
$etherscan = FALSE;
	$reset_txn_hist_eth = FALSE;
$dydx = FALSE;
$audit = FALSE;
	$reset_balances = FALSE;
$today = date('Y-m-d', $config['timestamp'] / 1000);

# portfolios
$keys = array('address', 'portfolio', 'alias');
$portfolios = array(
	 ['0x7578af57e2970edb7cb065b066c488bece369c43', 'JK', 'J1']
	,['0xd898f6bfbe145d84526ec25700c2c52e04a6c240', 'JK', 'J2']
 	,['0x861afbf9a062d4c8b583140c1c884529ca21e503', 'JK', 'J3']
	,['0x4263891bc4469759ac035f1f3cceb2ed87deaa7e', 'AK', 'AK']
	,['0xd5c24396683c236452a898ce45a16553358a660b', 'JK', 'J4']
	,['0xe1688450ed79ad737755965c912447df0d933b5a', 'JB', 'JB']
	,['0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7', 'Jx', 'Jx']
	,['0x5b5af4b5ab0ed2d39ea27d93e66e3366a01d7aa9', 'JK', 'J6']
	,['0xb351a776afbceb74d2d3747d05cf4c3b1cc539c7', 'JK', 'J7']
	,['0x4b50bfea9c49d01616c73edb9c73421530ffe096', 'JK', 'J8']
	,['0x56b8021aeb2315e03ea1c99c2be81baf0a2cb283', 'JK', 'J9']
	,['0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0', 'AO', 'AO']
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
if ($etherscan) require('hist_etherscan.php');

# bring in exchange data

$txn_hist_eth = json_decode(file_get_contents('db/eth_txn_hist_tidy.json'));
$txn_hist_eth = objectToArray($txn_hist_eth);
$txn_eth = $txn_hist_eth['txn_hist'];

$txn_hist_dydx = json_decode(file_get_contents('db/dydx_txn_hist_tidy.json'));
$txn_hist_dydx = objectToArray($txn_hist_dydx);
$txn_dydx = $txn_hist_dydx['txn_hist'];

$price_dates = array_merge($txn_hist_eth['price_dates'], $txn_hist_dydx['price_dates']);

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
	foreach ($portfolios as $p)
		if ($p['address'] == $txn['wallet_address']) {
			$txn['Wallet'] = $p['alias'];
			$txn['portfolio'] = $p['portfolio'];
			continue;
		}
}
unset($txn);

$txn_hist = array_merge($txn_eth, $txn_dydx);

# audit report
if ($audit || $reset_balances) require('hist_audit.php');

# update contracts, calculate fiat prices and output data file
require('hist_secondary.php');