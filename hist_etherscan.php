<?php

# https://docs.etherscan.io/

# data load
$txn_hist_eth = json_decode(file_get_contents('db/eth_txn_hist.json'));
$txn_hist_eth = objectToArray($txn_hist_eth);
$contracts = json_decode(file_get_contents('db/contracts.json'));
$contracts = objectToArray($contracts);
echo runtime() . 'hist_etherscan.php loaded data' . PHP_EOL;

# If the Fee Asset is the same as Sell Asset, then the Sell Quantity must be the net amount (after fee deduction), not gross amount.
# If the Fee Asset is the same as Buy Asset, then the Buy Quantity must be the gross amount (before fee deduction), not net amount.

# etherscan transaction query

$config['exchange'] = 'etherscan';

$tables = array(
	 'txlist'
	,'txlistinternal'
	,'tokentx'
	,'tokennfttx'
);

$addresses = array();

# to not refresh transaction, or to
if (!$refresh_txn_hist_eth) {

	$txn_hist = $txn_hist_eth['txn_hist'];

} else {

	# set wallets to update records for

	$i = $refresh_wallets['i'];
	$n = $refresh_wallets['n'];
	$fro = ($i - 1) * $n;
	$refresh_wallets = array_column(array_slice($portfolios, $fro, $n), 'address');
	if (count($refresh_wallets) == 0) die('too many in $refresh_wallets');

	# set block range to query

	foreach ($refresh_wallets as $wallet) {
		$startblock = 0;
		$endblock = 99999999;
		if ($refresh_period == 'all') {} # do nothing
		if ($refresh_period == 'last') {
			foreach ($txn_hist_eth['txn_hist'] as $txn) {
				if (
					$txn['wallet_address'] == $wallet
					& (int) $txn['block'] > $startblock
					) $startblock = (int) $txn['block'];
			}
		}
		foreach ($portfolios as &$row) {
			if ($row['address'] == $wallet) {
				$row['startblock'] = $startblock;
				$row['endblock'] = $endblock;
			}
		}
		unset($row); # unset &$row as still accessible
	}

	# filter $txn_hist

	$txn_hist = array();
	foreach ($txn_hist_eth['txn_hist'] as $txn) {
		$wallet = $txn['wallet_address'];
		# only keep records not in our selected wallets (to query) or outside their block range
		if (!in_array($wallet, $refresh_wallets)) {
			$txn_hist[] = $txn;
		} else {
			foreach ($portfolios as $row) {
				if ($row['address'] == $wallet) {
					if ($txn['block'] < $row['startblock']) $txn_hist[] = $txn;
					break;
				}
			}
		}
	}

	# need to catch new addresses to check for spam

	$contracts_new = [];

	# commence queries

	foreach ($portfolios as $port) {

		if (!in_array($port['address'], $refresh_wallets)) continue; # only set wallets

		echo runtime() . 'start etherscan query ' . $port['alias'] . ' from block ' . $port['startblock'] . PHP_EOL;

		foreach ($tables as $table) {

			$config = config_exchange($config);
			$config['api_request'] =  'module=account&sort=asc'
					. '&startblock=' . $port['startblock']
					. '&endblock=' . $port['endblock']
					. '&apikey=' . $config['api_key']
					. '&address=' . $port['address']
					. '&action=' . $table;
			$config['url'] .= $config['api_request'];
			$result = query_api($config);
			$result = $result['result'];
			sleep(0.25); # rate limit 5/second (though is asynchronous and takes 1 sec anyway)

			# add any dust balance transactions
			foreach ($txn_balance_dust as $r)
				if ($table == $r['table'] && $port['address'] == $r['wallet_address']) $result[] = $r;

			# now process
			foreach ($result as $r) {

				# new array

				$row = array();
				foreach ($keys_txn as $key) $row[$key] = '';

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
					$r['contractAddress'] = '0xeth';
				}

				if (!isset($r['tokenID'])) $r['tokenID'] = '';

				$r['from'] = strtolower($r['from']);
				if (isset($r['contractAddress'])) $r['contractAddress'] = strtolower($r['contractAddress']);
				if (isset($r['address'])) $r['address'] = strtolower($r['address']);

				$txn_id = strtolower($r['hash']);

				# spam filter

				if (array_search($r['contractAddress'], $spam) !== FALSE) continue;

				# flag any nft nullifies

				$nftnull = FALSE;
				if ($table == 'txlist')
					foreach ($nftnulls as $key => $val)
						if ($key == $txn_id) $nftnull = TRUE;

				# correct token symbols

				if ($nftnull) $r['symbol'] = $nftnulls[$txn_id];

				if (in_array($table, ['tokentx', 'tokennfttx'])) {
					foreach ($tokens as $t) {
						if ($r['contractAddress'] == $t['address'] && $r['tokenID'] == $t['nftTokenId']) {
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
					if ($table == 'tokennfttx') $row['Buy Quantity'] = 1;
				}

				if ($row['Type'] == 'Withdrawal') {
					if ($nftnull) {
						$row['Sell Quantity'] = 1;
					} else if(!$r['isError']) {
						if ($r['value'] > 0)
							$row['Sell Quantity'] = bcdiv($r['value'], pow(10, $r['decimal']), $r['decimal']);
					} else {
						#$row['Sell Quantity'] = 0;
						$row['Note'] .= 'Transaction error, gas fee may apply. ';
					}
					if ($row['Sell Quantity'] > 0)
						$row['Sell Asset'] = $r['symbol'];
					$row['Fee Quantity'] = bcdiv($r['gasUsed'] * $r['gasPrice'], pow(10, 18), 18);
					$row['Fee Asset'] = 'ETH';
				}

				$row['Wallet'] = $port['alias'];

				$row['Timestamp'] = date('Y-m-d H:i:s', $r['timeStamp']);

				$row['time_unix'] = $r['timeStamp'];

				$row['block'] = $r['blockNumber'];

				$row['transaction_id'] = $txn_id;

				$row['portfolio'] = $port['portfolio'];

				$row['wallet_address'] = $port['address'];

				if ($row['Type'] == 'Deposit')
					$row['transfer_address'] = strtolower($r['from']);
				else
					$row['transfer_address'] = strtolower($r['to']);

				if ($row['Type'] == 'Deposit')
					$row['Buy_contract_address'] = $r['contractAddress'];
				else
					$row['Sell_contract_address'] = $r['contractAddress'];
				if ($row['Fee Asset'] == 'ETH')
					$row['Fee_contract_address'] = '0xeth';

				$row['error'] = $r['isError'];

				if ($table == 'tokentx') {
					if(!isset($contracts[$r['contractAddress']])) {
						$contracts_new[$r['contractAddress']]['tokenName'] = $r['tokenName'];
						$contracts_new[$r['contractAddress']]['tokenSymbol'] = $r['tokenSymbol'];
						$contracts_new[$r['contractAddress']]['symbol'] = $r['symbol'];
						$contracts_new[$r['contractAddress']]['nftTokenId'] = $r['tokenID'];
						$contracts_new[$r['contractAddress']]['decimal'] = $r['decimal'];
						$contracts_new[$r['contractAddress']]['coingecko'] = array();
					}
				} else if ($nftnull) {
					$row['Sell_contract_address'] = '0xnull';
				}

				# special case for wrapping ETH to WETH as we won't automatically see the WETH value deposited
				$addr_weth = '0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2';
				if (($r['to'] == $addr_weth || $r['from'] == $addr_weth) && $r['value'] > 0 && $r['symbol'] == 'ETH') {

					$row_weth = $row;
					if ($row['Type'] == 'Withdrawal') {
						$row_weth['Note'] = 'Wrapping ETH. ';
						$row_weth['Type'] = 'Deposit';
						$row_weth['Buy Quantity'] = $row['Sell Quantity'];
						$row_weth['Buy Asset'] = 'WETH';
						$row_weth['Sell Quantity'] = '';
						$row_weth['Sell Asset'] = '';
						$row_weth['Buy_contract_address'] = $addr_weth;
						$row_weth['Sell_contract_address'] = '0xeth';
					}
					if ($row['Type'] == 'Deposit') {
						$row_weth['Note'] = 'Unwrapping WETH';
						$row_weth['Type'] = 'Withdrawal';
						$row_weth['Buy Quantity'] = '';
						$row_weth['Buy Asset'] = '';
						$row_weth['Sell Quantity'] = $row['Buy Quantity'];
						$row_weth['Sell Asset'] = 'WETH';
						$row_weth['Buy_contract_address'] = '0xeth';
						$row_weth['Sell_contract_address'] = $addr_weth;
					}
					$row_weth['Fee Asset'] = '';
					$row_weth['Fee Quantity'] = '';
					$row_weth['Fee_contract_address'] = '';

					# create extra record
					$txn_hist[] = $row_weth;

				}

				# record

				$txn_hist[] = $row;

				# record addresses, contracts and dates for price
				# ? redundant, can delete ?

				$addresses[] = $r['from'];
				$addresses[] = $r['to'];

			}
		}
	}

	if (count($contracts_new) > 0) {
		if ($contracts_append) {
			$contracts = array_merge($contracts, $contracts_new);
			file_put_contents('db/contracts.json', json_encode($contracts, JSON_PRETTY_PRINT));
		} else {
			echo runtime() . 'new coins, check for spam https://etherscan.io/address/0xaa (add to $spam if necessary) then update $contracts_append = TRUE' . PHP_EOL;
			var_dump($contracts_new);
			die;
		}
	}

	# dedupe
	# $txn_balance_dust is one possible source of dupes
	$txn_hist_dupe = $txn_hist;
	$txn_hist = dedupe_array($txn_hist);
	$txn_dupe = count($txn_hist_dupe) - count($txn_hist);

	# save
	$a = array('updated' => $today, 'txn_hist' => $txn_hist);
	file_put_contents('db/eth_txn_hist.json', json_encode($a));
	unset($a);
	echo runtime() . count($txn_hist) . ' etherscan records saved to eth_txn_hist.json. ' . $txn_dupe . ' dupes removed.' . PHP_EOL;

	# interrupt before continuing
	if ($refresh_hist_die) {
		echo runtime() . 'Printing array ...' . PHP_EOL;
		var_dump($txn_hist);
		die;
	}

}

# populate transfer address info
foreach ($txn_hist as &$row) {

	foreach ($address_book as $a) {
		if ($row['transfer_address'] == $a['address']) {
			$row['transfer_alias'] = $a['alias'];
			break;
		}
	}

	if ($row['transfer_alias'] == '')
		foreach ($contracts as $c) {
			if ($row['transfer_address'] == $c['contractAddress']) {
				$row['transfer_alias'] = $c['symbol'] . ' contract';
				break;
			}
		}

	if ($row['transfer_alias'] !== '') {
		if ($row['Type'] == 'Deposit')
			$row['Note'] .= 'Receive from ';
		else
			$row['Note'] .= 'Send to ';
		$row['Note'] .= $row['transfer_alias'];
		if ($row['error'])
			$row['Note'] .= ' attempt failed';
		$row['Note'] .= '.';
	}

}
unset($row);

# count instances of transaction_id
# by wallet to not count transfers as trades

$t_id_sides = array();
foreach ($txn_hist as $row) {
	if ($row['Buy Quantity'] > 0) $t_id_sides[$row['transaction_id'].$row['Wallet']]['buy'] = 1;
	if ($row['Sell Quantity'] > 0) $t_id_sides[$row['transaction_id'].$row['Wallet']]['sell'] = 1;
}
# join to array
foreach ($txn_hist as &$row) {
	$row['t_id_sides'] = $t_id_sides[$row['transaction_id'].$row['Wallet']]['buy']
		+ $t_id_sides[$row['transaction_id'].$row['Wallet']]['sell'];
}
unset($row);

# correct transaction type record
# possible values (needed):
# Deposit, Airdrop, Withdrawal, Spend, Trade
# possible values (not used yet):
# Mining, Staking, Interest, Dividend, Income, Gift-Received, Gift-Sent, Gift-Spouse, Charity-Sent, Lost

foreach ($txn_hist as &$row) {

	$row['type.ori'] = $row['Type'];

	# Type: Trade (direct with person)
	# treat as Trade
	# would otherwise be incorrectly identified as deposit/withdrawal
	# taxable event: yes
	# identifiers: manually nominate transaction_id
	if (in_array($row['transaction_id'], $manualtrades)) {
		$row['Type'] = 'Trade';
		$row['Note'] = 'Manual trade with individual. ' . $row['Note'];
	}

	# Type: Spend (personal purchase transaction)
	# treat as Spend
	# would otherwise be incorrectly identified as withdrawal
	# taxable event: yes disposal
	# identifiers: manually nominate transaction_id
	else if (in_array($row['transaction_id'], $purchases)) {
		$row['Type'] = 'Spend';
		$row['Note'] = 'Personal purchase transaction. ' . $row['Note'];
	}

	# Type: Airdrop
	# if airdrop identified but type is withdrawal, this record needs to be set as Spend type
	# taxable event: no
	# identifiers:
	# manually nominate transaction_id
	# contract_id not clearly reliable
	# type is deposit
	else if (in_array($row['transaction_id'], $airdrops)) {
		if ($row['Type'] == 'Deposit') {
			$row['Type'] = 'Airdrop';
			# bittytax and rp2 price airdrops, and throw error without price
			# so counterbalance transaction with minor value
			$row['Buy Value in '.$fiat] = 1;
		}
	}

	# Type: Staking, Interest, Mining, Dividend, Income
	# income received from staking, interest, mining, dividend, or other income
	# if transaction_id identified but type is withdrawal, this record needs to be set as Spend type
	# taxable event: yes income
	# identifiers:
	# manually nominate transaction_id
	# type is deposit
	else if (in_array($row['transaction_id'], $interests)) {
		if ($row['Type'] == 'Deposit') {
			$row['Type'] = 'Interest';
			$row['Note'] = ' Received as interest/staking/mining from auto distributor.';
		} else {
			$row['Type'] = 'Spend';
			$row['Sell Value in '.$fiat] = '';
		}
	}

	# Type: Deposit
	# deposit is only for transfers between accounts I own
	# there should be a corresponding withdrawal for every deposit
	# taxable event: no
	# identifiers:
	# one transaction_id
	# type is deposit
	else if ($row['t_id_sides'] == 1 && $row['Type'] == 'Deposit') {
		# bittytax and rp2 price transfers, and throw error without price
		# $row['Buy Value in '.$fiat] = 0;
	}

	# Type: Withdrawal
	# withdrawal is only for transfers between accounts I own
	# there should be a corresponding withdrawal for every deposit
	# taxable event: no
	# identifiers:
	# one transaction_id
	# type is Withdrawal
	# withdrawal amount > 0
	# not failed transaction
	else if ($row['t_id_sides'] == 1 && $row['Type'] == 'Withdrawal' && $row['error'] == 0) {}

	# Type: Spend (approval/authorisation)
	# fees for wallet approvals, authorisations etc
	# set as type 'Spend'
	# taxable event: yes disposal
	# identifiers:
	# no transaction_id with buy value > 0 or sell value > 0 (sides = 0)
	# type is withdrawal
	else if (	$row['t_id_sides'] == 0
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
	# fiat-to-crypto: acquisition
	# crypto-to-crypto: disposal
	# crypto-to-fiat: disposal
	# identifiers:
	# transaction_id with amount > 0 on both sides (withdrawal, deposit)
	else if ($row['t_id_sides'] == 2) {
		// if ($row['Type'] == 'Withdrawal') {
		// 	$row['Buy Quantity'] = 0;
		// 	$row['Buy Asset'] = '#NULL';
		// } else {
		// 	$row['Sell Quantity'] = 0;
		// 	$row['Sell Asset'] = '#NULL';
		// }
		$row['Type'] = 'Trade';
	}

	# Type: Spend (failed transaction)
	# taxable event: yes disposal
	# identifiers:
	# one transaction_id
	# error is true
	# type is withdrawal
	else if ($row['t_id_sides'] == 0 && $row['Type'] == 'Withdrawal' && $row['error'] == 1) {
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
unset($row);


$sort_keys = sort_txns($txn_hist);
array_multisort($sort_keys, SORT_ASC, $txn_hist);

# deduplicate fees
# this must follow the chronological sort
# count instances of transaction_id ~ fee quantity

$t_id_fee_count = array();
foreach ($txn_hist as $row) {
	$key = $row['transaction_id'] . $row['Fee Quantity'];
	if ($row['Fee Quantity'] > 0) $t_id_fee_count[$key]++;
	else $t_id_fee_count[$key] = '';
}
# join to array
foreach ($txn_hist as &$row) {
	$key = $row['transaction_id'] . $row['Fee Quantity'];
	$row['t_id_fee_count'] = $t_id_fee_count[$key];
}
unset($row); # unset &$row as still accessible
# retain only first instance of fee in sorted order
$t_id_last = '';
foreach ($txn_hist as &$row) {
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
unset($row);

# correct zero fee transactions

foreach ($txn_hist as $key => &$row) {
	foreach ($fee_free as $txn_id) {
		if ($row['transaction_id'] == $txn_id) {
			if ($row['Fee Quantity'] > 0) $row['Fee Quantity'] = 0;
			continue;
		}
	}
}
unset($row);

# save

$a = array('updated' => $txn_hist_eth['updated'], 'txn_hist' => $txn_hist);
file_put_contents('db/eth_txn_hist_tidy.json', json_encode($a));
unset($a);

echo runtime() . count($txn_hist) . ' records saved to eth_txn_hist_tidy.json' . PHP_EOL;

if ($eth_die) die;
