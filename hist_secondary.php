<?php

# parameters

$taxable = array('Spend', 'Trade', 'Staking', 'Interest', 'Mining', 'Dividend', 'Income');

# tokens that respond to queries on token id but not contract address (not ethereum)
$tokensids = array(
	  '0x178c820f862b14f316509ec36b13123da19a6054' => 'energy-web-token'
	 ,'0xb4efd85c19999d84251304bda99e90b92300bd93' => 'rocket-pool'
);

# some contracts not captured through etherscan

$keys = array('address', 'tokenName', 'tokenSymbol', 'symbol', 'nftTokenId', 'decimal', 'coingecko');
$con = array(
	  ['0x04Fa0d235C4abf4BcF4787aF4CF447DE572eF828', 'UMA', 'UMA', 'UMA', '', '18', []]
	 ,['0xLUNA000000000000000000000000000000000000', 'LUNA', 'LUNA', 'LUNA', '', '18', []]
	 ,['0xADA0000000000000000000000000000000000000', 'ADA', 'ADA', 'ADA', '', '18', []]
	 ,['0x7fc66500c84a76ad7e9c93437bfc5ac33e2ddae9', 'AAVE', 'AAVE', 'AAVE', '', '18', []]
	 ,['0x9f8f72aa9304c8b593d555f12ef6589cc3a579a2', 'MKR', 'MKR', 'MKR', '', '18', []]
	 ,['0x4206931337dc273a630d328da6441786bfad668f', 'DOGE', 'DOGE', 'DOGE', '', '8', []]
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
	if($cgecko > 50) break; # rate limit 50/minute

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

$price_query = array();

$price_history = json_decode(file_get_contents('db/price_history.json'));
$price_history = objectToArray($price_history);

$price_records = json_decode(file_get_contents('db/price_records.json'));
$price_records = objectToArray($price_records);

foreach ($price_manual as $s)
	foreach ($s as $kx => $k)
 		foreach ($k as $fiat => $t)
			foreach ($t as $ts => $p)
			$price_records[$kx][$fiat][$ts] = array(
				'date' => $p['date'],
				'price' => $p['price'],
				'url' => $p['url']
			);

# check if price record exists and delete
foreach ($price_dates as $kx => &$tsx) {
	foreach ($tsx as $k => $ts)
		if (isset($price_records[$kx][$fiat][$ts]))
			unset($price_dates[$kx][$k]);
}
unset($tsx);

# check if price history match

# older than 90 days up to 1h or 1d candles
# older than 1 day up to 1h candles
# less than 1 day up to 5m candles
$h = 3600;
$d = $h * 24;
$d0 = $config['timestamp'] / 1000;
$d90 = $d0 - 90 * $d;

foreach ($price_dates as $kx => &$tsx) {

	foreach ($tsx as $k => $ts) {

		$ts = (int) $ts;

		if ($ts > $d90)
			$seconds = $h;
		else if ($ts < $d90)
			$seconds = $d;
		else continue;

		# search for match in price history
		# for coingecko price we use the period end time in UTC
		foreach ($price_history[$kx][$fiat] as $tsp => $p) {
			if (
				$ts <= $tsp
				&& $tsp - $ts < $seconds
				&& $p['price'] !== ''
				&& $p['url'] !== ''
			) {
				unset($price_dates[$kx][$k]);
				$price_records[$kx][$fiat][$ts] = array(
					'date' => $p['date'],
					'price' => $p['price'],
					'url' => $p['url']
				);
				continue;
			}
		}

		# if no match found flag for query
		if (!isset($price_query[$kx]['from'])) {
			$price_query[$kx]['from'] = $ts;
			$price_query[$kx]['to'] = $ts + $seconds + 1;
		} else {
			if ($ts < $price_query[$kx]['from']) $price_query[$kx]['from'] = $ts;
			if ($ts > $price_query[$kx]['to']) $price_query[$kx]['to'] = $ts;
		}

	}
}
unset($tsx);

file_put_contents('db/price_records.json', json_encode($price_records));

# price info query

if (count($price_query)) {

	$config['exchange'] = 'coingecko';
	$config = config_exchange($config);
	$url = $config['url'];

	$running = TRUE;

	foreach ($price_query as $address => $query) {

		$process = $contracts[$address]['coingecko']['match'];

		if ($process == 'false') continue;

		if ($process == 'contract') {
			$config['api_request'] = '/coins/ethereum';
			if ($address !== '0xeth')
				$config['api_request'] .= '/contract/' . $address;
		}

		if ($process == 'id')
			$config['api_request'] = '/coins/' . $contracts[$address]['coingecko']['id'];

		$config['api_request'] .=
			'/market_chart/range?'
			. 'vs_currency=' . $fiat
			. '&from=' . $query['from']
			. '&to=' . $query['to'];

		$config['url'] = $url . $config['api_request'];
		$result = query_api($config);

		$cgecko++;
		if($cgecko > 30 || !isset($result)) { $running = F; break; } # rate limit 10-50 / minute

		foreach ($result['prices'] as $r) {
			$timestamp = round($r[0] / 1000, 0);
			$date = date('Y-m-d H:i:s', $timestamp);
			$price_history[$address][$fiat][$timestamp] = array(
				'date' => $date,
				'price' => $r[1],
				'url' => $config['url']
			);
			#echo var_dump($price_history[$address][$fiat][$timestamp]);
		}

	}

	file_put_contents('db/price_history.json', json_encode($price_history));

	if (!$running) die('price query timed out, possible rate limit reached (10-50 / minute), wait 60 seconds');

}

# populate prices

function fiat_value($address, $timestamp, $quantity, $fiat, $price_records) {

	$result = '';

	# check if price record exists
	if (isset($price_records[$address][$fiat][$timestamp])) {
		$price_fiat = $price_records[$address][$fiat][$timestamp]['price'];
		$result = round($price_fiat * $quantity, 6);
	}

	return $result;

}

foreach ($txn_hist as &$row) {

	$timestamp = strtotime($row['Timestamp']);

	# we just allow different fiat valuations on each side of a trade
	# differences are balanced out in the audit anyway

	foreach (array('Buy', 'Sell', 'Fee') as $side) {

		if (in_array($row['Type'], $taxable) || $side == 'Fee') {

			if (
				$row[$side.' Quantity'] > 0
				&& $row[$side.' Value in '.$fiat] == ''
			) {

				$row[$side.' Value in '.$fiat] = fiat_value(
					$row[$side.'_contract_address'],
					$timestamp,
					$row[$side.' Quantity'],
					$fiat,
					$price_records
				);

			}
		}
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
