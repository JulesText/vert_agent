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
$limittime = (int) 60; # script can use long time
set_time_limit($limittime);
	#echo ini_get('max_execution_time');die;
ini_set('memory_limit', '512M'); # script can use high memory
	#echo ini_get('memory_limit');die;
$fiat = $config['base_fiat'];
$dydx = TRUE; # hist_dydx.php to run dydx txn processing functions, hist_dydx.py used to generate:
	# dydx_txn_hist_tidy.json
	# dydx_balance.json
	$dedupe_dydx = FALSE; # hist_dydx.py has its own dedupe function
	$dy_dec = 10; # number of decimal places for dydx txn calculations
	$track = [FALSE,FALSE]; #track wallet/asset output on screen, can use high memory/time
	# $track = ['0x7578af57e2970edb7cb065b066c488bece369c43','MKR'];
	$dydx_die = FALSE; # stop after running hist_dydx.php
$etherscan = TRUE; # hist_etherscan.php to run eth txn processing functions
	$refresh_txn_hist_eth = FALSE; # refresh transaction history, to generate:
		# eth_txn_hist.json
		$refresh_period = 'last'; # if refreshing, 'last' to exclude previously-queried blocks, or 'all' for rebuild
		$refresh_wallets = ['i' => 1, 'n' => 12]; # workaround timeout limit - i) current iteration, n) max wallets per refresh
		$contracts_append = FALSE; # set contracts_append false by default
			# if FALSE, as new contracts identified, critical stop occurs and prints on screen
			# if seem like spam then add address to $spam list in hist_etherscan.php
			# if not spam then allow, and set $contracts_append = TRUE, then rerun to generate:
			# contracts.json if any changes
		$today = date('Y-m-d', $config['timestamp'] / 1000); # record run date
		$refresh_hist_die = TRUE; # show on screen refresh is complete and stop further processing
	# flow through to generate:
	# eth_txn_hist_tidy.json
	$eth_die = FALSE; # stop after end of running hist_etherscan.php
$audit = TRUE; # hist_audit.php to run audit and print results
	$reset_balances = FALSE; # for audit, re-query wallet balances to generate:
		# balances.json
		# balance_history.json is not operational
	# always die() after audit to ensure correct output data file is being produced
$secondary_process = TRUE; # hist_secondary.php to update contracts, calculate fiat prices and output data file
	# if $*_die = FALSE & $audit = FALSE then  will flow through to generate:
	# price_history.json
	# price_records.json
	$restrict_fiat_value = FALSE; # restrict fiat value calculations to taxable events (rp2/bittytax may throw error)

# portfolios and idiosyncratic records
require('hist_idio.php');

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
	'block',
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

echo runtime() . 'bring in all data' . PHP_EOL;

# audit report
if ($audit) require('hist_audit.php');

# update contracts, calculate fiat prices and output data file
require('hist_secondary.php');
