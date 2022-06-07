<?php

include('system_includes.php');

$config['exchange'] = 'etherscan';

$keys = array('address', 'portfolio', 'alias');
$portfolios = array(

	['0x7578af57e2970EDB7cB065b066C488bEce369C43', 'JK', 'hbot6']
	,['0x4263891BC4469759ac035f1f3CCEb2eD87dEaA7e', 'AK', 'AK']
	,['0xd5c24396683c236452A898cE45A16553358A660b', 'JK', 'JK2']
	,['0xa2F5F0d6b64Ba1acF54418FCccfB15b99ed349e7', 'Joint', 'hbot5']
	,['0xE1688450ED79aD737755965c912447DF0D933b5A', 'JB', 'JB']
	,['0x5b5af4b5ab0ed2d39ea27d93e66e3366a01d7aa9', 'JK', 'hbot1']
	,['0xb351a776afbceb74d2d3747d05cf4c3b1cc539c7', 'JK', 'hbot2']
	,['0xd898F6bfBe145d84526ec25700C2c52E04a6c240', 'JK', 'JK metamask']
	,['0x4B50bfeA9c49d01616c73Edb9C73421530FfE096', 'JK', 'hbot3']
	,['0x56B8021aeB2315E03eA1c99C2BE81baF0a2CB283', 'JK', 'hbot4']
	,['0x139d2CE2a3f323B668E9F1f30812F762ec6AC1F0', 'AO', 'Adea']

);
foreach ($portfolios as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}

# note aidrop transactions
$airdrops = array(
	'0xf3dd1f2b7a86efec7f910aab83718c1b49aa76160c1402404ccff37039ac1605' /* dydx */
	,'0xef55c8c3d94745063eb2ebe303dd1ebea09c9f393db6c32cea2306a369587baf' /* spam */
	,''
);
$airdrops = array_map('strtolower', $airdrops);

# note address purposes
# unclear if/how functionality needed
$keys = array('address', 'purpose', 'exchange');
$address_book = array(

	['0x0000000000000000000000000000000000000000', '', '']
	,['0x5b67871c3a857de81a1ca0f9f7945e5670d986dc', '', '']
	,['', '', '']

);
foreach ($address_book as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}


# some tokens have ambiguous names, clarify them
$keys = array('address', 'nftTokenId', 'tksymbol', 'symbol');
$tokens = array(

	['0xb27de0ba2abfbfdf15667a939f041b52118af5ba', '', 'UNI-V2', 'UBT-ETH'],
	['0x3041cbd36888becc7bbcbc0045e3b1f144466f5f', '', 'UNI-V2', 'USDC-USDT'],
	['0x3b3d4eefdc603b232907a7f3d0ed1eea5c62b5f7', '', 'UNI-V2', 'STAKE-ETH'],
	['0x8973be4402bf0a39448f419c2d64bd3591dd2299', '', 'UNI-V2', 'YFII-ETH'],
	['0x12d4444f96c644385d8ab355f6ddf801315b6254', '', 'UNI-V2', 'CVP-ETH'],
	['0xed9fc98816817cf855eeed3cb2ab81887cb3fc71', '', 'UNI-V2', 'STAKE-USDT'],
	['0xae461ca67b15dc8dc81ce7615e0320da1a9ab8d5', '', 'UNI-V2', 'DAI-USDC'],
	['0xb20bd5d04be54f870d5c0d3ca85d82b34b836405', '', 'UNI-V2', 'DAI-USDT'],
	['0xa2107fa5b38d9bbd2c461d6edf11b11a50f6b974', '', 'UNI-V2', 'LINK-ETH'],
	['0xb4e16d0168e52d35cacd2c6185b44281ec28c9dc', '', 'UNI-V2', 'USDC-ETH'],
	['0xdc7d8cc3a22fe0ec69770e02931f43451b7b975e', '', 'UNI-V2', 'EWTB-ETH'],
	['0xbb2b8038a1640196fbe3e38816f3e67cba72d940', '', 'UNI-V2', 'WBTC-ETH'],
	['0x966ea83cf3170a309184bb742398c925249e407e', '', 'UNI-V2', 'LPL-ETH'],
	['0x0d4a11d5eeaac28ec3f61d100daf4d40471f1852', '', 'UNI-V2', 'ETH-USDT'],
	['0x2e81ec0b8b4022fac83a21b2f2b4b8f5ed744d70', '', 'UNI-V2', 'ETH-GRT'],
	['0x86fef14c27c78deaeb4349fd959caa11fc5b5d75', '', 'UNI-V2', 'ETH-RARI'],
	['0x1412eca9dc7daef60451e3155bb8dbf9da349933', '', 'A68.net', 'spam'],
	['0x178c820f862b14f316509ec36b13123da19a6054', '', 'EWTB', 'EWT'],
	['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4144', 'UNI-V3-POS', 'UNI-ETH-4144'],
	['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4382', 'UNI-V3-POS', 'RPL-ETH-4382'],
	['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4414', 'UNI-V3-POS', 'LYXe-ETH-4414'],
	['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4426', 'UNI-V3-POS', 'ZRX-ETH-4426'],
	['', '', '']

);
foreach ($tokens as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}

$tables = array(
	'txlist'
	,'txlistinternal'
	,'tokentx'
	,'tokennfttx'
);

$addresses = array();
$contracts = array();

$data = array();
$keys = array(
	'Type',
	'Buy Quantity',
	'Buy Asset',
	'Buy Value in AUD',
	'Sell Quantity',
	'Sell Asset',
	'Sell Value in AUD',
	'Fee Quantity',
	'Fee Asset',
	'Fee Value in AUD',
	'Wallet',
	'Timestamp',
	'Note',
	'transaction_id',
	'portfolio',
	'alias',
	'error',
	'type.ori',
	't_id_count',
	't_id_fee_count'
);

foreach ($tables as $table) {

	foreach ($portfolios as $port) {

		$config = config_exchange($config);
		$config['api_request'] =  'module=account&startblock=0&endblock=99999999&sort=asc'
				. '&apikey=' . $config['api_key']
				. '&address=' . $port['address']
				. '&action=' . $table;
		$config['url'] .= $config['api_request'];
		$result = query_api($config);
		sleep(0.25); # rate limit 5/second

		foreach ($result['result'] as $r) {

			# new array

			$row = array();
			foreach ($keys as $key) $row[$key] = '';

			# defaults, because some fields not included in some tables

			if (!isset($r['isError'])) $r['isError'] = 0;

			if (isset($r['tokenDecimal'])) {
				$r['decimal'] = $r['tokenDecimal'];
			} else {
				$r['decimal'] = 18;
			}

			if (isset($r['tokenSymbol'])) {
				$r['symbol'] = $r['tokenSymbol'];
			} else {
				$r['symbol'] = 'ETH';
			}

			if (!isset($r['tokenID'])) $r['tokenID'] = '';

			$r['from'] = strtolower($r['from']);
			if (isset($r['contractAddress'])) $r['contractAddress'] = strtolower($r['contractAddress']);
			if (isset($r['address'])) $r['address'] = strtolower($r['address']);

			# correct token symbols

			if (in_array($table, ['tokentx', 'tokennfttx'])) {
				foreach ($tokens as $t) {
					if ($r['contractAddress'] == $t['address'] & $r['tokenID'] == $t['nftTokenId']) {
						$r['symbol'] = $t['symbol'];
						break;
					}
				}
			}

			# process data

			if ($r['from'] == $port['address']) {
				$row['Type'] = 'Withdrawal';
			} else {
				$row['Type'] = 'Deposit';
			}

			if ($row['Type'] == 'Deposit' && !$r['isError']) {
				$row['Buy Quantity'] = bcdiv($r['value'], pow(10, $r['decimal']), $r['decimal']);
				$row['Buy Asset'] = $r['symbol'];
			}

			if ($row['Type'] == 'Withdrawal') {
				if(!$r['isError']) {
					$row['Sell Quantity'] = bcdiv($r['value'], pow(10, $r['decimal']), $r['decimal']);
				} else {
					$row['Sell Quantity'] = 0;
					$row['Note'] .= 'Transaction error, gas fee may apply.';
				}
				$row['Sell Asset'] = $r['symbol'];
				$row['Fee Quantity'] = bcdiv($r['gasUsed'] * $r['gasPrice'], pow(10, 18), 18);
				$row['Fee Asset'] = 'ETH';
			}

			$row['Wallet'] = $port['address'];

			$row['Timestamp'] = date('Y-m-d H:i:s', $r['timeStamp']);

			$row['transaction_id'] = strtolower($r['hash']);

			$row['portfolio'] = $port['portfolio'];

			$row['alias'] = $port['alias'];

			$row['error'] = $r['isError'];

			# record

			$data[] = $row;

			# record addresses and contracts
			$addresses[] = $r['from'];
			$addresses[] = $r['to'];
			if (in_array($table, ['tokentx', 'tokennfttx']))
				$contracts[] = array(
					'contractAddress' => $r['contractAddress'],
					'tokenName' => $r['tokenName'],
					'tokenSymbol' => $r['tokenSymbol'],
					'symbol' => $r['symbol'],
					'nftTokenId' => $r['tokenID'],
					'decimal' => $r['decimal'],
					'address' => $port['address']
				);

		}

	}

}

# count instances of transaction_id
$t_id_count = array();
foreach ($data as $row) $t_id_count[$row['transaction_id']]++;
# join to array
foreach ($data as &$row) $row['t_id_count'] = $t_id_count[$row['transaction_id']];
unset($row); # unset &$row as still accessible

# correct transaction type record
# trades, spends, airdrops, transfers, etc should all be set to correct type
foreach ($data as &$row) {

	$row['type.ori'] = $row['Type'];

	# Type: Airdrop
	# if airdrop identified but type is withdrawal, this record needs to be set as Spend type
	# taxable event: no
	# identifiers:
	# manually nominate transaction_id
	# contract_id not clearly reliable
	# type is deposit
	if (in_array($row['transaction_id'], $airdrops)) {
		if ($row['Type'] == 'Deposit') $row['Type'] = 'Airdrop';
		else $row['Type'] = 'Spend';
	}

	# Type: Staking, Interest, Mining, Dividend, Income
	# income received from staking, interest, mining, dividend, or other income
	# if transaction_id identified but type is withdrawal, this record needs to be set as Spend type
	# taxable event: yes income
	# identifiers:
	# manually nominate transaction_id
	# type is deposit
	else if (FALSE) {}

	# Type: Deposit
	# deposit is only for transfers between accounts I own
	# there should be a corresponding withdrawal for every deposit
	# taxable event: no
	# identifiers:
	# one transaction_id
	# type is deposit
	else if ($row['t_id_count'] == 1 && $row['Type'] == 'Deposit') {}

	# Type: Withdrawal
	# withdrawal is only for transfers between accounts I own
	# there should be a corresponding withdrawal for every deposit
	# taxable event: no
	# identifiers:
	# one transaction_id
	# type is Withdrawal
	# withdrawal amount > 0
	# not failed transaction
	else if ($row['t_id_count'] == 1 && $row['Type'] == 'Withdrawal' && $row['Sell Quantity'] > 0 && $row['error'] == 0) {}

	# Type: Spend (approval/authorisation)
	# fees for wallet approvals, authorisations etc
	# set as type 'Spend'
	# taxable event: yes disposal
	# identifiers:
	# one transaction_id
	# buy value + sell value = 0
	# type is withdrawal
	else if (	$row['t_id_count'] == 1
						&& $row['Type'] == 'Withdrawal'
						&& ($row['Sell Quantity'] == 0 || $row['Sell Quantity'] == '')
						&& $row['error'] == 0
					) { $row['Type'] = 'Spend'; }

	# Type: Trade
	# to avoid the complication of matching etherscan withdrawals to deposits
	# where there are 1-many relationships, we instead do not merge rows into single trade record
	# for a withdrawal type trade set the buy qty = 0 and buy asset = ETH
	# and conversely for a deposit type trade set the sell qty = 0 and sell asset = ETH
	# these 'zero' fills are required for bittytax to run its calculations
	# taxable event: yes
	# fiat-to-crypto: acquisition [to do: check if bittytax distinguishes]
	# crypto-to-crypto: disposal
	# crypto-to-fiat: disposal
	# identifiers:
	# multiple transaction_id
	else if ($row['t_id_count'] > 1) {
		if ($row['Type'] == 'Withdrawal') {
			$row['Buy Quantity'] = 0;
			$row['Buy Asset'] = 'ETH';
		} else {
			$row['Sell Quantity'] = 0;
			$row['Sell Asset'] = 'ETH';
		}
		$row['Type'] = 'Trade';
	}

	# Type: Spend (failed transaction)
	# taxable event: yes disposal
	# identifiers:
	# one transaction_id
	# error is true
	# type is withdrawal
	else if ($row['t_id_count'] == 1 && $row['Type'] == 'Withdrawal' && $row['error'] == 1) {
		$row['Type'] = 'Spend';
	}

	# Type: unmapped
	else $row['Type'] = 'Unmapped';

	# Type: Fee (Trade)
	# taxable event: yes disposal
	# identifiers:
	# any/all fees where type is trade

	# Type: Fee (Withdrawal), Fee (Deposit)
	# deducted from the deposit or withdrawal total amount
	# taxable event: yes disposal
	# identifiers:
	# any/all fees where type is deposit or withdrawal

	# Type: Fee (failed transaction)
	# taxable event: yes disposal
	# identifiers:
	# one transaction_id
	# error is true
	# type is withdrawal

	# Type: Fork
	# fork - see https://github.com/BittyTax/BittyTax#other-types

}
unset($row); # unset &$row as still accessible


//
// # get eth balances
// foreach ($portfolios as $port) {
//
// 	$config = config_exchange($config);
// 	$config['api_request'] =  'module=account&tag=latest'
// 			. '&apikey=' . $config['api_key']
// 			. '&address=' . $port['address']
// 			. '&action=balance';
// 	$config['url'] .= $config['api_request'];
// 	$result = query_api($config);
// 	var_dump($port);
// 	var_dump($result);
// 	sleep(0.25); # rate limit 5/second
//
// }
//
# get token balances
// $contracts = dedupe_array($contracts);
// foreach ($contracts as $c) {
//
// 	$config = config_exchange($config);
// 	$config['api_request'] =  'module=account&tag=latest'
// 			. '&apikey=' . $config['api_key']
// 			. '&address=' . $c['address']
// 			. '&contractaddress=' . $c['contractAddress']
// 			. '&action=tokenbalance';
// 	$config['url'] .= $config['api_request'];
// 	$result = query_api($config);
// 	var_dump($c);
// 	var_dump($result);
// 	sleep(0.25); # rate limit 5/second
//
// }
// die;
//
// addresses list
// $addresses = dedupe_array($addresses);
// var_dump($addresses);
//
// contracts list
// foreach ($contracts as &$row) unset($row['address']);
// $contracts = dedupe_array($contracts);
// var_dump($contracts);
// die;
//
// get eth balance
// $config = config_exchange($config);
// $config['api_request'] =  'module=account&tag=latest'
// 		. '&apikey=' . $config['api_key']
// 		. '&address=' . $port['address']
// 		. '&action=balance';
// $config['url'] .= $config['api_request'];
// $result = query_api($config);
// var_dump($result);
//
// get token balance
// $config = config_exchange($config);
// $config['api_request'] =  'module=account&tag=latest'
// 		. '&apikey=' . $config['api_key']
// 		. '&address=' . $port['address']
// 		. '&contractaddress=0xa47c8bf37f92abed4a126bda807a7b7498661acd'
// 		. '&action=tokenbalance';
// $config['url'] .= $config['api_request'];
// $result = query_api($config);
// var_dump($result);
//
// die;
//
// show trans
// foreach ($data as $row) {
// if(in_array($row['transaction_id'], [
// 	'0xdbacb0ec89931103a6fae44aacf943ee64d90101a61416e3fe68622739ebfb0f',
// 	'0xb895677a5a624f16c0bef78c07303d65955b91ba92672cb91864c1e21cb66292'
// 	])) var_dump($row);
// }
// die;

# sort chronologically, and by tran_id/type/amount for equal times
$sort_keys = array();
foreach ($data as $row) {
	$s = (string) $row['Timestamp'] . $row['transaction_id'];
	if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] == 0) $s .= '0';
	else if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] > 0) $s .= '1';
	else if ($row['Buy Quantity'] > 0 && $row['Sell Quantity'] == 0) $s .= '2';
	$sort_keys[] = $s;
}
array_multisort($sort_keys, SORT_ASC, $data);

# deduplicate fees
# this must follow the chronological sort
# count instances of transaction_id ~ fee quantity
$t_id_fee_count = array();
foreach ($data as $row) {
	$key = $row['transaction_id'] . $row['Fee Quantity'];
	if ($row['Fee Quantity'] > 0) $t_id_fee_count[$key]++;
	else $t_id_fee_count[$key] = '';
}
# join to array
foreach ($data as &$row) {
	$key = $row['transaction_id'] . $row['Fee Quantity'];
	$row['t_id_fee_count'] = $t_id_fee_count[$key];
}
unset($row); # unset &$row as still accessible
# retain only first instance of fee in sorted order
$t_id_last = '';
foreach ($data as &$row) {
	if ($row['transaction_id'] !== $t_id_last) $included = F;
	if ($row['t_id_fee_count'] > 1) {
		if ($included == T) {
			$row['Fee Quantity'] = '';
			$row['Fee Asset'] = '';
		} else {
			$included = T;
		}
	}
	$t_id_last = $row['transaction_id'];
}
unset($row); # unset &$row as still accessible

# generate csv file

# we can't have anything printing to screen or it gets inserted in the csv file
# this empties the screen output buffer
ob_end_clean();

# open output
$output = fopen("php://output",'w') or die("Can't open php://output");
header("Content-Type:application/csv");
header("Content-Disposition:attachment;filename=dat_etherscan.csv");

# write content
fputcsv($output, $keys);
foreach ($data as $row) fputcsv($output, $row);

# known issue where correct amount is written to csv, but when opened with excel, the amount is truncated to 15 decimal places
# when read in bittytax however the correct decimal value is used, but the output xlsx file will store some numbers as text to avoid loss of information, and if manually inspected these won't be included in arithmetic calculations

# known issue where this script correctly reads successful transaction but bittytax reads it as failed transaction
# etherscan shows transaction was successful but contained an error
# https://etherscan.io/tx/0xce6ee4ef0a93de2d51da361567a344405ff39adc75e787527316e1cf801f7976
# hence bittytax sets ETH value = 0

# close output
fclose($output) or die("Can't close php://output");

# we can't allow anything further to be printed to screen
die;
