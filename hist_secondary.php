<?php

# parameters

$taxable = array('Spend', 'Trade', 'Staking', 'Interest', 'Mining', 'Dividend', 'Income');

# tokens that respond to coingecko queries on token id but not contract address (not ethereum)
$tokensids = array(
	  '0x178c820f862b14f316509ec36b13123da19a6054' => 'energy-web-token'
	 ,'0xb4efd85c19999d84251304bda99e90b92300bd93' => 'rocket-pool'
);

# some contracts not captured through etherscan

$keys = array('address', 'tokenName', 'tokenSymbol', 'symbol', 'nftTokenId', 'decimal', 'coingecko');
$con = array(
	  ['0x04Fa0d235C4abf4BcF4787aF4CF447DE572eF828', 'UMA', 'UMA', 'UMA', '', '18', []]
	 ,['0x7fc66500c84a76ad7e9c93437bfc5ac33e2ddae9', 'AAVE', 'AAVE', 'AAVE', '', '18', []]
	 ,['0x9f8f72aa9304c8b593d555f12ef6589cc3a579a2', 'MKR', 'MKR', 'MKR', '', '18', []]
	 ,['0x4206931337dc273a630d328da6441786bfad668f', 'DOGE', 'DOGE', 'DOGE', '', '8', []]
	 ,['0xluna', 'LUNA', 'LUNA', 'LUNA', '', '18', []]
	 ,['0xada', 'ADA', 'ADA', 'ADA', '', '18', []]
	 ,['0xc36442b4a4522e871399cd717abdd847ab11fe88', 'uniswap router', 'null', 'null', '', '18', ['match' => 'pool']]
	 ,['0xuni-eth-4144', 'UNI-ETH-4144', 'UNI-ETH-4144', 'UNI-ETH-4144', '4144', '18', ['match' => 'pool']]
	 ,['0xrpl-eth-4382', 'RPL-ETH-4382', 'RPL-ETH-4382', 'RPL-ETH-4382', '4382', '18', ['match' => 'pool']]
	 ,['0xlyxe-eth-4414', 'LYXe-ETH-4414', 'LYXe-ETH-4414', 'LYXe-ETH-4414', '4414', '18', ['match' => 'pool']]
	 ,['0xzrx-eth-4426', 'ZRX-ETH-4426', 'ZRX-ETH-4426', 'ZRX-ETH-4426', '4426', '18', ['match' => 'pool']]
);
foreach ($con as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row);
foreach ($con as $c) {
	if (isset($contracts[$c['address']])) continue;
	$contracts[$c['address']]['tokenName'] = $c['tokenName'];
	$contracts[$c['address']]['tokenSymbol'] = $c['tokenSymbol'];
	$contracts[$c['address']]['symbol'] = $c['symbol'];
	$contracts[$c['address']]['nftTokenId'] = $c['nftTokenId'];
	$contracts[$c['address']]['decimal'] = $c['decimal'];
	$contracts[$c['address']]['coingecko'] = $c['coingecko'];
}

# populate contract address info

$config['exchange'] = 'coingecko';
$config = config_exchange($config);
$url = $config['url'];
$cgecko = 0;
foreach ($contracts as $address => &$c) {

	if (isset($c['coingecko']['match'])) continue;

	$cgecko++;
	if($cgecko > 20) break; # rate limit 10-50/minute

	$contract_query = TRUE;

	foreach ($tokensids as $addr => $id)
		if ($addr == $address) {
			$contract_query = FALSE;
			$config['api_request'] = '/coins/' . $id;
			break;
		}

	if ($contract_query)
		$config['api_request'] = '/coins/ethereum/contract/' . $address;
	$config['url'] = $url . $config['api_request'];
	$result = query_api($config);

	if (isset($result['id'])) {
		if ($contract_query)
			$c['coingecko']['match'] = 'contract';
		else
			$c['coingecko']['match'] = 'id';
		$c['coingecko']['id'] = $result['id'];
		$c['coingecko']['symbol'] = $result['symbol'];
		$c['coingecko']['name'] = $result['name'];
	} else {
		$c['coingecko']['match'] = 'false';
	}

}
unset($c);

file_put_contents('db/contracts.json', json_encode($contracts, JSON_PRETTY_PRINT));


# check if price record exists
# and set range for price info to query if not

$price_history = json_decode(file_get_contents('db/price_history.json'));
$price_history = objectToArray($price_history);

$price_records = json_decode(file_get_contents('db/price_records.json'));
$price_records = objectToArray($price_records);

# in case price queries fail, manually specify missing price histories
$price_manual = [
	'ETHRSIAPY2' => ['0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6' => ['AUD' => [
		1592991318 => ['date' => '2020-07-05 00:00:00', 'price'=>365.7769165565453, 'url'=>'https://api.coingecko.com/api/v3/coins/ethereum/contract/0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6/market_chart/range?vs_currency=aud&from=1593907200&to=1595721600'],
		1594903561 => ['date' => '2020-07-16 13:11:07', 'price'=>340.4949081360257, 'url'=>'https://api.coingecko.com/api/v3/coins/ethereum/contract/0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6/market_chart/range?vs_currency=aud&from=1593907200&to=1595721600']
	]]]
	,'QACOL' => ['0xdcd441ca4689bde55add971afcc2330e7ad5c90f' => ['AUD' => [
		1598666134 => ['date' => '', 'price'=>0, 'url'=>'smart contract, no possible price value'],
		1598878681 => ['date' => '', 'price'=>0, 'url'=>'smart contract, no possible price value']
	]]]
];

foreach ($price_manual as $s)
	foreach ($s as $ass => $k)
 		foreach ($k as $fiat => $t)
			foreach ($t as $ts => $p)
			$price_records[$ass][$fiat][$ts] = array(
				'date' => $p['date'],
				'price' => $p['price'],
				'url' => $p['url']
			);


# collate price dates

$price_dates = [];
foreach ($txn_hist as $txn) {
	foreach (array('Buy', 'Sell', 'Fee') as $side) {
		$ass = $txn[$side.' Asset'];
		if ($ass !== '') {
			$match = FALSE;
			foreach ($contracts as $key => $arr) {
				if ($ass == $arr['symbol']) {
					$price_dates[$key][] = (int) $txn['time_unix'];
					$match = TRUE;
					break;
				}
			}
			if (!$match) {
				echo 'error: asset ' . $ass . ' ' . $key . ' not found in contracts.json' . PHP_EOL;
				// var_dump($txn);
				// die;
			}
		}
	}
}
foreach ($price_dates as &$dates) $dates = dedupe_array($dates);
unset($dates);

# check if price record exists and delete price date to not query
$i = 0;

foreach ($price_dates as $ass => $tsx) $i = $i + count($tsx);
echo $i . ' price dates' . PHP_EOL;
$i = 0;
foreach ($price_dates as $ass => &$tsx)
	foreach ($tsx as $k => $ts)
		if (isset($price_records[$ass][$fiat][$ts])) {
			unset($price_dates[$ass][$k]);
			$i++;
		}
unset($tsx);
echo $i . ' price record matches' . PHP_EOL;

# check if existing price history match
# if so add to $price_records
# if not flag for subsequent query as per $price_query

$price_query = array();

# older than 90 days up to 1h or 1d candles
# older than 1 day up to 1h candles
# less than 1 day up to 5m candles
$h = 3600;
$d = $h * 24;
$d0 = $config['timestamp'] / 1000;
$d90 = $d0 - 90 * $d;

foreach ($price_dates as $ass => &$tsx) {

	foreach ($tsx as $k => $ts) {

		if ($ts > $d90)
			$seconds = $h;
		else if ($ts < $d90)
			$seconds = $d;
		else continue;

		# search for match in existing price history
		foreach ($price_history[$ass][$fiat] as $tsp => $p) {
			if (
				$ts <= $tsp
				&& $tsp - $ts < $seconds /* we use the period end time in UTC */
				&& $p['price'] !== ''
				&& $p['url'] !== ''
			) {
				unset($price_dates[$ass][$k]);
				$price_records[$ass][$fiat][$ts] = array(
					'date' => $p['date'],
					'price' => $p['price'],
					'url' => $p['url']
				);
				continue;
			}
		}

		# if no match found flag for subsequent query by setting params
		if (!isset($price_query[$ass]['from'])) {
			$price_query[$ass]['from'] = $ts;
			$price_query[$ass]['to'] = $ts + $seconds + 1;
		} else {
			if ($ts < $price_query[$ass]['from']) $price_query[$ass]['from'] = $ts;
			if ($ts > $price_query[$ass]['to']) $price_query[$ass]['to'] = $ts;
		}

	}
}
unset($tsx);

# we save $price_records expecting to rerun full script multiple times

file_put_contents('db/price_records.json', json_encode($price_records));

# price info query from coingecko

$config['exchange'] = 'coingecko';
$config = config_exchange($config);
$url = $config['url'];

# flag possible timeout
$running = TRUE;

# loop each asset (timestamps set as range from / to)
# will only run if there are unfilled price query requests
foreach ($price_query as $address => $query) {

	# get coingecko query type
	$process = $contracts[$address]['coingecko']['match'];

	# contracts.json value indicates not possible to query from coingecko
	if ($process == 'false') {
		echo 'error: contract coingecko match (price query) is false for ' . $address . PHP_EOL;
		$running = FALSE;
		break;
	}
	if ($process == 'pool' || $process == 'ignore') continue;

	# coingecko endpoint to call
	if ($process == 'contract') {
		$config['api_request'] = '/coins/ethereum';
		if ($address !== '0xeth')
			$config['api_request'] .= '/contract/' . $address;
	} else if ($process == 'id')
		$config['api_request'] = '/coins/' . $contracts[$address]['coingecko']['id'];

	$config['api_request'] .=
		'/market_chart/range?'
		. 'vs_currency=' . $fiat
		. '&from=' . $query['from']
		. '&to=' . $query['to'];

	$config['url'] = $url . $config['api_request'];
	$result = query_api($config);

	# error check
	$cgecko++;
	# if hit rate limit 10-50 / minute
	if($cgecko > 20) {
		echo 'possible rate limit reached (10-50 / minute), access blocked until next 1 minute window';
		$running = FALSE;
		break;
	}
	# if no result returned
	if(!isset($result)) {
		echo 'error: no $result for query: ' . $config['url'] . PHP_EOL;
		$running = FALSE;
		break;
	} else {
		echo 'ok: $result received for ' . $config['url'] . PHP_EOL;
	}

	# process result to $price_history format
	foreach ($result['prices'] as $r) {
		$timestamp = round($r[0] / 1000, 0);
		$date = date('Y-m-d H:i:s', $timestamp);
		$price_history[$address][$fiat][$timestamp] = array(
			'date' => $date
			,'price' => $r[1]
			#,'url' => $config['url']
		);
	}

}


# update $price_history
$json = json_encode($price_history, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$json = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json); # indent = 2 spaces
file_put_contents('db/price_history.json', $json);
#file_put_contents('db/price_history.json', json_encode($price_history, JSON_PRETTY_PRINT));

if (!$running) die('invalid query OR price query timed out [wait 60 seconds]');

# populate prices

foreach ($txn_hist as &$row) {

	$timestamp = strtotime($row['Timestamp']);

	# we just allow different fiat valuations on each side of a trade
	# differences are balanced out in the audit anyway

	foreach (array('Buy', 'Sell', 'Fee') as $side) {

		if (!$restrict_fiat_value || in_array($row['Type'], $taxable) || $side == 'Fee') {

			if ($row[$side.' Value in '.$fiat] == '') {

				if ($row[$side.' Quantity'] > 0) {
				// if ($row['transaction_id'] == '0x871fb67d42f036d5dfb596dcf9fdd0d87858736e8243befea2ed65ca435baba5' && $side == 'Buy') {
				// 	echo '_contract_address: ' . $row[$side.'_contract_address'] . PHP_EOL
				// 	. '$timestamp: ' . $timestamp . PHP_EOL
				// 	. 'Quantity: ' . $row[$side.' Quantity'] . PHP_EOL
				// 	. '$fiat: '	. $fiat	. PHP_EOL;
				// }

				$query = TRUE;
				foreach ($contracts as $key => $arr) {
					if ($arr['symbol'] == $row[$side.' Asset']) {
						if ($arr['coingecko']['match'] == 'pool') {
							$query = FALSE;
							break;
						}
					}
				}

				# query price
				# but don't try querying uniswap pools (V2 or V3) as they don't have prices
				if ($query)
					$row[$side.' Value in '.$fiat] = fiat_value(
						$row[$side.'_contract_address'],
						$timestamp,
						$row[$side.' Quantity'],
						$fiat,
						$price_records
					);

			} else if ($row[$side.' Quantity'] == '0') $row[$side.' Value in '.$fiat] = 0;
			}
		} else if (
				$row[$side.' Quantity'] > 0
				&& $row[$side.' Value in '.$fiat] == ''
			) $row[$side.' Value in '.$fiat] = 0;

	}
}
unset($row);

# unusual token exceptions

# for uniswap pools, balance values in fiat to other sides

$txn_ids = [];

foreach ($tokens as $t)
	if (in_array($t['tksymbol'], ['UNI-V2','UNI-V3-POS'])) {
		foreach ($txn_hist as $row) {
			if ($row['Buy Asset'] == $t['symbol'])
				$txn_ids[$row['transaction_id']][$t['symbol']] = 'Sell'; # if Buy we want Sell
			if ($row['Sell Asset'] == $t['symbol'])
				$txn_ids[$row['transaction_id']][$t['symbol']] = 'Buy'; # vice versa
		}
	}

foreach ($txn_ids as $txn_id => $arr)
	foreach ($arr as $symbol => $side) {
		# tally the fiat value(s) on the token's opposite side
		$value_fiat = 0;
		foreach ($txn_hist as $row)
			if ($row['transaction_id'] == $txn_id)
				$value_fiat = $value_fiat + $row[$side.' Value in '.$fiat];
		# insert the tally as the token's fiat value
		$side = ($side == 'Buy') ? 'Sell' : 'Buy';
		foreach ($txn_hist as &$row)
			if (
				$row['transaction_id'] == $txn_id
				&& $row[$side.' Asset'] == $symbol
			) {
				$row[$side.' Value in AUD'] = $value_fiat;
				break;
			}
		unset($row);
	}

# trim fields

if (count($keys_out) <> 17) die('error');
const cols = 17;
foreach ($txn_hist as &$row)
	$row = array_slice($row, 0, cols);
unset($row);

# generate csv file

# we can't have anything printing to screen or it gets inserted in the csv file
# this empties the screen output buffer
ob_end_clean();

# open output
$output = fopen("php://output",'w') or die("Can't open php://output");
header("Content-Type:application/csv");
header("Content-Disposition:attachment;filename=dat_etherscan.csv");

# column headers
fputcsv($output, array_keys($txn_hist[0]));

# write content
foreach ($txn_hist as $row) fputcsv($output, $row);

# close output
fclose($output) or die("Can't close php://output");

# we can't allow anything further to be printed to screen
die;
