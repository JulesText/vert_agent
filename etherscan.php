<?php

//check issue with balance history
// https://discord.com/channels/715804406842392586/717770762659954709/993155136920768582

// I: look for clues in raw data such as wallet address is neither to_address or from_address
// I: use covalenthq instead of etherscan if enough info is in it to identify fee-free
// I: step through txns chronologically, use covalent to query eth balance by day history of txn, compare txn_history balance to actual balance, for any block where difference noted it should be a fee-free txn so add to list in json file (and check first)
// I: use etherscan manual ETH balance tool https://etherscan.io/balancecheck-tool
// I: build local instance of geth and query with web3.js or similar



/*

commands to write to disk may need permissions, for instance:
	file_put_contents('db/txn_hist.json', json_encode($a));
so in terminal consider
	chmod 777 txn_hist.json

to do:

# split file into account query and price query, allow price query to take other records

# label addresses in crypto plan and import

# get eth balances
foreach ($portfolios as $port) {
	$config = config_exchange($config);
	$config['api_request'] =  'module=account&tag=latest'
			. '&apikey=' . $config['api_key']
			. '&address=' . $port['address']
			. '&action=balance';
	$config['url'] .= $config['api_request'];
	$result = query_api($config);
	var_dump($port);
	var_dump($result);
	sleep(0.25); # rate limit 5/second
}

# get token balances
$contracts = dedupe_array($contracts);
foreach ($contracts as $c) {
	$config = config_exchange($config);
	$config['api_request'] =  'module=account&tag=latest'
			. '&apikey=' . $config['api_key']
			. '&address=' . $c['address']
			. '&contractaddress=' . $c['contractAddress']
			. '&action=tokenbalance';
	$config['url'] .= $config['api_request'];
	$result = query_api($config);
	var_dump($c);
	var_dump($result);
	sleep(0.25); # rate limit 5/second
}

# addresses list
$addresses = dedupe_array($addresses);
var_dump($addresses);

# contracts list
foreach ($contracts as &$row) unset($row['address']);
$contracts = dedupe_array($contracts);
var_dump($contracts);

# known issue where correct amount is written to csv, but when opened with excel, the amount is truncated to 15 decimal places
when read in bittytax however the correct decimal value is used, but the output xlsx file will store some numbers as text to avoid loss of information, and if manually inspected these won't be included in arithmetic calculations

# known issue where this script correctly reads successful transaction but bittytax reads it as failed transaction
etherscan shows transaction was successful but contained an error
https://etherscan.io/tx/0xce6ee4ef0a93de2d51da361567a344405ff39adc75e787527316e1cf801f7976
hence bittytax sets ETH value = 0

*/

include('system_includes.php');

$fiat = $config['base_fiat'];
$today = date('Y-m-d', $config['timestamp'] / 1000);

# change object to array
function objectToArray ($object) {
	if(!is_object($object) && !is_array($object)) return $object;
	return array_map('objectToArray', (array) $object);
}

$keys = array('address', 'portfolio', 'alias');
# sorted oldest first
$portfolios = array(
	 ['0x7578af57e2970EDB7cB065b066C488bEce369C43', 'JK', 'J1']
	,['0xd898F6bfBe145d84526ec25700C2c52E04a6c240', 'JK', 'J2']
 	// ,['0x861afbf9a062d4c8b583140c1c884529ca21e503', 'JK', 'J3']
	// ,['0x4263891BC4469759ac035f1f3CCEb2eD87dEaA7e', 'AK', 'AK']
	// ,['0xd5c24396683c236452A898cE45A16553358A660b', 'JK', 'J4']
	// ,['0xE1688450ED79aD737755965c912447DF0D933b5A', 'JB', 'JB']
	// ,['0xa2F5F0d6b64Ba1acF54418FCccfB15b99ed349e7', 'Jx', 'Jx']
	// ,['0x5b5af4b5ab0ed2d39ea27d93e66e3366a01d7aa9', 'JK', 'J6']
	// ,['0xb351a776afbceb74d2d3747d05cf4c3b1cc539c7', 'JK', 'J7']
	// ,['0x4B50bfeA9c49d01616c73Edb9C73421530FfE096', 'JK', 'J8']
	// ,['0x56B8021aeB2315E03eA1c99C2BE81baF0a2CB283', 'JK', 'J9']
	// ,['0x139d2CE2a3f323B668E9F1f30812F762ec6AC1F0', 'AO', 'AO']
);
foreach ($portfolios as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row); # unset &$row as still accessible

# note known fee-free transactions
$fee_free = array(
	 '0x915dac65a19e8d6200dba77ab3a2c0945c3207590c59ab0955aca8d4e04fecd1'
	,'0xe5946ef81510bb885a83ca51ac72ed06ca3f42b9d16f4a43d2fbf2f5b9aee926'
	,'0x61e96c1025c4b33e15f9f39cceaa9608b971a0ac30adf79aeba1a507e356a89d'
	,'0x1a52855bcb57b356e8d6b4cc6c67a11fb304654bd4b248cdd12e47758688b051'
	,'0x1b18566d406e1e87f2e738a7cb5f259c5dc9cfb4f316a9505b7cfec9e08e5d6e'
	,'0xa2aae17cafba0f98f16f9bb71281da741b3798d378d0886afd8bb2801ac8e450'
	,''
);
$fee_free = array_map('strtolower', $fee_free);

# note aidrop transactions
$airdrops = array(
	 '0xf3dd1f2b7a86efec7f910aab83718c1b49aa76160c1402404ccff37039ac1605'
	,'0xef55c8c3d94745063eb2ebe303dd1ebea09c9f393db6c32cea2306a369587baf'
	,'0x5714d0d1a4b0206f98ebd6c542b49c0bff2582aee077b42134d102a685769627'
);
$airdrops = array_map('strtolower', $airdrops);

# note pooling nft disposal transactions
$nftnulls = array(
	 '0x05e065d43ffebb22b106b8f2026c49081a00927b5afad3875379a6021081d75d' => 'UNI-ETH-4144'
	,'0xe6dcc256e665ab0d3115d73bc85ae71519dee5c69fa8de5ce6412e80d9956f08' => 'RPL-ETH-4382'
	,'0x5ab42ca6fcef62df0034303e28262b7aa0a76616a4ab7d6b701161681bae58db' => 'LYXe-ETH-4414'
	,'0x5e35a0b6b9d20ca9b22e1768d9802f438947eedf14d662a98010f92523fe9b44' => 'ZRX-ETH-4426'
);

# tokens that respond to queries on token id but not contract address (not ethereum)
$tokensids = array(
	  '0x178c820f862b14f316509ec36b13123da19a6054' => 'energy-web-token'
	 ,'0xb4efd85c19999d84251304bda99e90b92300bd93' => 'rocket-pool'
);

# if all else fails, manually specify missing price histories
$price_manual = [
	'ETHRSIAPY2' => ['0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6' => ['AUD' => [
		1592991318 => ['date' => '2020-07-05 00:00:00', 'price'=>365.7769165565453, 'url'=>'https://api.coingecko.com/api/v3/coins/ethereum/contract/0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6/market_chart/range?vs_currency=aud&from=1593907200&to=1595721600'],
		1594903561 => ['date' => '2020-07-16 13:11:07', 'price'=>340.4949081360257, 'url'=>'https://api.coingecko.com/api/v3/coins/ethereum/contract/0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6/market_chart/range?vs_currency=aud&from=1593907200&to=1595721600']
	]]],
	'QACOL' => ['0xdcd441ca4689bde55add971afcc2330e7ad5c90f' => ['AUD' => [
		1598666134 => ['date' => '', 'price'=>0, 'url'=>'smart contract, no possible price value'],
		1598878681 => ['date' => '', 'price'=>0, 'url'=>'smart contract, no possible price value']
	]]]
];

# some tokens have ambiguous names, clarify them
$keys = array('address', 'nftTokenId', 'tksymbol', 'symbol');
$tokens = array(
	 ['0xb27de0ba2abfbfdf15667a939f041b52118af5ba', '',     'UNI-V2',     'UBT-ETH']
	,['0x3041cbd36888becc7bbcbc0045e3b1f144466f5f', '',     'UNI-V2',     'USDC-USDT']
	,['0x3b3d4eefdc603b232907a7f3d0ed1eea5c62b5f7', '',     'UNI-V2',     'STAKE-ETH']
	,['0x8973be4402bf0a39448f419c2d64bd3591dd2299', '',     'UNI-V2',     'YFII-ETH']
	,['0x12d4444f96c644385d8ab355f6ddf801315b6254', '',     'UNI-V2',     'CVP-ETH']
	,['0xed9fc98816817cf855eeed3cb2ab81887cb3fc71', '',     'UNI-V2',     'STAKE-USDT']
	,['0xae461ca67b15dc8dc81ce7615e0320da1a9ab8d5', '',     'UNI-V2',     'DAI-USDC']
	,['0xb20bd5d04be54f870d5c0d3ca85d82b34b836405', '',     'UNI-V2',     'DAI-USDT']
	,['0xa2107fa5b38d9bbd2c461d6edf11b11a50f6b974', '',     'UNI-V2',     'LINK-ETH']
	,['0xb4e16d0168e52d35cacd2c6185b44281ec28c9dc', '',     'UNI-V2',     'USDC-ETH']
	,['0xdc7d8cc3a22fe0ec69770e02931f43451b7b975e', '',     'UNI-V2',     'EWTB-ETH']
	,['0xbb2b8038a1640196fbe3e38816f3e67cba72d940', '',     'UNI-V2',     'WBTC-ETH']
	,['0x966ea83cf3170a309184bb742398c925249e407e', '',     'UNI-V2',     'LPL-ETH']
	,['0x0d4a11d5eeaac28ec3f61d100daf4d40471f1852', '',     'UNI-V2',     'ETH-USDT']
	,['0x2e81ec0b8b4022fac83a21b2f2b4b8f5ed744d70', '',     'UNI-V2',     'ETH-GRT']
	,['0x86fef14c27c78deaeb4349fd959caa11fc5b5d75', '',     'UNI-V2',     'ETH-RARI']
	,['0x1412eca9dc7daef60451e3155bb8dbf9da349933', '',     'A68.net',    'spam']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4144', 'UNI-V3-POS', 'UNI-ETH-4144']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4382', 'UNI-V3-POS', 'RPL-ETH-4382']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4414', 'UNI-V3-POS', 'LYXe-ETH-4414']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4426', 'UNI-V3-POS', 'ZRX-ETH-4426']
	,['0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6', '',     'ETHRSIAPY',  'ETHRSIAPY2']
);
foreach ($tokens as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row);

# note address purposes
$keys = array('address', 'alias');
$address_book = array(
	['0x8139e63f2969d21883ceb278372e3fc02d152185', '? tim']
	,['0x744fc208bf314fdf15a88198153121d16527996a', 'bitmax wallet']
	,['0x29bff390fc12c900aaf0f2e51c06675df691337a', 'CVP deployer']
	,['0x0000000000000000000000000000000000000000', 'default ethereum router']
	,['0x5b67871c3a857de81a1ca0f9f7945e5670d986dc', 'ETHRSIAPY token set vault']
	,['0x861afbf9a062d4c8b583140c1c884529ca21e503', 'hotbit wallet']
	,['0x33cc2c8376c7b79224b965d544029914200932e4', 'idex wallet']
	,['0xdbf328cc664ff89b446b1cb1c624a007ab209973', 'idex wallet']
	,['0x818e6fecd516ecc3849daf6845e3ec868087b755', 'kyber proxy router']
	,['0x9aab3f75489902f3a48495025729a0af77d4b11e', 'kyber proxy router 2']
	,['0xdf4b6fb700c428476bd3c02e6fa83e110741145b', 'liquid wallet']
	,['0x6cc5f688a315f3dc28a7781717a9a798a59fda7b', 'okex wallet']
	,['0x03f955ad67d0027e0baf3dccc3d1fb5f665e61f9', 'old wallet or cex/dex']
	,['0x3f5ce5fbfe3e9af3971dd833d26ba9b5c936f0be', 'swyftx wallet']
	,['0xd551234ae421e3bcba99a0da6d736074f22192ff', 'swyftx wallet']
	,['0x0681d8db095565fe8a346fa0277bffde9c0edbbf', 'swyftx wallet']
	,['0x564286362092d8e7936f0549571a803b203aaced', 'swyftx wallet']
	,['0x7a250d5630b4cf539739df2c5dacb4c659f2488d', 'uniswap router']
	,['0xde697ac56b1bd9c0987c08911020f2341e274813', 'token set rebalancer']
	,['0xda816e2122a8a39b0926bfa84edd3d42477e9efd', 'swyftx wallet']
	,['0xb76c291871b92a7c9e020b2511a3402a3bf0499d', 'uniswap exchange']
	,['0xe334c3bba644f1e6908324728d5a6e1c5b68eed2', 'hitbtc wallet']
	,['0xda6786379ff88729264d31d472fa917f5e561443', 'token set rebalancer']
	,['0x369a2020a287edfc12f39774318057073e4a40d6', 'liquid wallet']
	,['0x61bfb5e060a3f2092ccf80ecf412980c0385d537', 'liquid wallet']
	,['0x65bf64ff5f51272f729bdcd7acfb00677ced86cd', 'kyber defi exchange']
	,['0x52d35e8f0ffa18337b093aec3dfff40445d8f4f4', 'kyber limit order']
	,['0x37c8b60ec0be985cb47955a3228e4f8a8da63f92', 'okex wallet']
	,['0x7c66550c9c730b6fdd4c03bc2e73c5462c5f7acc', 'kyber defi exchange']
	,['0xe268807b0865f84b0e9aaac77328b739a0468618', 'swyftx wallet']
	,['0x2f577f053dde1e20f9b070e313a26ea49d6c6fd6', 'swyftx wallet']
	,['0x5041ed759dd4afc3a72b8192c143f72f4724081a', 'okex wallet']
	,['0xa7efae728d2936e78bda97dc267687568dd593f3', 'okex wallet']
	,['0x20312e96b1a0568ac31c6630844a962383cc66c2', 'uniswap router']
	,['0x09363887a4096b142f3f6b58a7eed2f1a0ff7343', 'uniswap router']
	,['0xe4b3dd9839ed1780351dc5412925cf05f07a1939', 'uniswap router']
	,['0xeb76b50807bfe587bb3dc88c75cb351bc49e3ab3', 'uniswap router']
	,['0x34fcb7f4edfd95761e6cbcf0fa34d19afd13089d', 'xdai distributor']
	,['0x274f3c32c90517975e29dfc209a23f315c1e5fc7', 'hotbit wallet']
	,['0x090d4613473dee047c3f2706764f49e0821d256e', 'uniswap UNI distributor']
	,['0xd3d2e2692501a5c9ca623199d38826e513033a17', 'uniswap UNI pool']
	,['0xa5407eae9ba41422680e2e00537571bcc53efbfd', 'curve sUSD pool']
	,['0xa90996896660decc6e997655e065b23788857849', 'curve sUSD gauge']
	,['0x3da1313ae46132a397d90d95b1424a9a7e3e0fce', 'uniswap CRV pool']
	,['0x5f3b5dfeb7b28cdbd7faba78963ee202a494e2a2', 'veCRV governance token']
	,['0x79a8c46dea5ada233abaffd40f3a0a2b1e5a4f27', 'curve BUSD pool']
	,['0x986a2fca9eda0e06fbf7839b89bfc006ee2a23dd', 'ascendex wallet']
	,['0xbebc44782c7db0a1a60cb6fe97d0b483032ff1c7', 'curve DAI/USDC/USDT pool']
	,['0x0d25efec9a4378b65dfd0457382bc2e20a864688', 'approve USDC']
	,['0xd061d61a4d941c39e5453435b6345dc261c2fce0', 'curve token minter']
	,['0xfcba3e75865d2d561be8d220616520c171f12851', 'curve staking pool']
	,['0x43ae24960e5534731fc831386c07755a2dc33d47', 'uniswap SNX pool']
	,['0x881d40237659c251811cec9c364ef91dc08d300c', 'metamask exchange']
	,['0x74de5d4fcbf63e00296fd95d33236b9794016631', 'sushiswap exchange']
	,['0xa478c2975ab1ea89e8196811f51a7b7ade33eb11', 'uniswap DAI pool']
	,['0x708396f17127c42383e3b9014072679b2f60b82f', 'swyftx wallet']
	,['0xdef1c0ded9bec7f1a1670819833240f027b25eff', '0xDefiCoded matcha exchange']
	,['0x8463c03c0c57ff19fa8b431e0d3a34e2df89888e', '0xDefiCoded matcha exchange']
	,['0x2f9ec37d6ccfff1cab21733bdadede11c823ccb0', 'matcha approval']
	,['0x225a6313e0d13d0f8c87a661ddc6923b53d0509a', '0xDefiCoded matcha exchange']
	,['0x983eb8f8c0f6902e1f078b1186500ef6602d7d54', '0xDefiCoded matcha exchange']
	,['0x0000000000007f150bd6f54c40a34d7c3d5e9f56', '0xDefiCoded matcha exchange']
	,['0x22f9dcf4647084d6c31b2765f6910cd85c178c18', '0xDefiCoded matcha exchange']
	,['0x58e327d64fc77fa36f698989623c3e4f058c8a89', '0xDefiCoded matcha exchange']
	,['0x09cf91d5e137cf047eb9e0551ceb18239b46d3dd', 'uniswap router']
	,['0x06da0fd433c1a5d7a4faa01111c044910a184553', '0xDefiCoded matcha exchange']
	,['0x0bc5ae46c32d99c434b7383183aca16dd6e9bdc8', '0xDefiCoded matcha exchange']
	,['0x1d42064fc4beb5f8aaf85f4617ae8b3b5b8bd801', 'uniswap v3 UNI-ETH pool']
	,['0x632e675672f2657f227da8d9bb3fe9177838e726', 'uniswap v3 RPL-ETH pool']
	,['0x80c7770b4399ae22149db17e97f9fc8a10ca5100', 'uniswap v3 LYXe-ETH pool']
	,['0x14424eeecbff345b38187d0b8b749e56faa68539', 'uniswap v3 ZRX-ETH pool']
	,['0xd583d0824ed78767e0e35b9bf7a636c81c665aa8', 'uniswap exchange']
	,['0x70ea56e46266f0137bac6b75710e3546f47c855d', 'uniswap exchange']
	,['0xaa04f1162899554fb66d06d1d60c68d46dce4d6a', '0xDefiCoded matcha exchange']
	,['0x0000000000a8fb09af944ab3baf7a9b3e1ab29d8', '0xDefiCoded matcha exchange']
	,['0x004375dff511095cc5a197a54140a24efef3a416', 'uniswap exchange']
	,['0x397ff1542f962076d0bfe58ea045ffa2d347aca0', '0xDefiCoded matcha exchange']
	,['0x2fa96250cf4eafe165ed7cd3f05e32a99e271566', '0xDefiCoded matcha exchange']
	,['0x56178a0d5f301baf6cf3e1cd53d9863437345bf9', '0xDefiCoded matcha exchange']
	,['0xe0f0cfde7ee664943906f17f7f14342e76a5cec7', 'swyftx wallet']
	,['0xe592427a0aece92de3edee1f18e0157c05861564', 'uniswap exchange']
	,['0xfaace66bd25abff62718abd6db97560e414ec074', 'uniswap exchange']
	,['0x88e6a0c2ddd26feeb64f039a2c41296fcb3f5640', 'uniswap exchange']
	,['0xd54f502e184b6b739d7d27a6410a67dc462d69c8', 'dydx wallet']
	,['0x44db449cbdea5cc143f9a3f4893c171821b2d756', 'dydx wallet']
	,['0xf42ed7184f3bdd07b0456952f67695683afd9044', 'dydx wallet']
	,['0x9696f59e4d72e237be84ffd425dcad154bf96976', 'swyftx wallet']
	,['0x0fd829c3365a225fb9226e75c97c3a114bd3199e', 'dydx token claims']
	,['0x639192d54431f8c816368d3fb4107bc168d0e871', 'dydx token distributor']
	,['0xabea9132b05a70803a4e85094fd0e1800777fbef', 'zksync wallet']
	,['0x7858e59e0c01ea06df3af3d20ac7b0003275d4bf', 'uniswap exchange']
	,['0x8e8bd01b5a9eb272cc3892a2e40e64a716aa2a40', 'dydx wallet']
	,['0x8b00ee8606cc70c2dce68dea0cefe632cca0fb7b', '0xDefiCoded matcha exchange']
	,['0x561b94454b65614ae3db0897b74303f4acf7cc75', '0xDefiCoded matcha exchange']
	,['0x00000000000e1d0dabf7b7c7b68866fc940d0db8', '0xDefiCoded matcha exchange']
);
foreach ($address_book as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row);
foreach ($portfolios as $port) {
	$address_book[] = array(
		'address' => $port['address'],
		'alias' => $port['alias'] . ' wallet'
	);
}
foreach ($tokens as $token) {
	if ($token['nftTokenId'] == '')
		$address_book[] = array(
			'address' => $token['address'],
			'alias' => $token['tksymbol'] . ' ' . $token['symbol'] . ' contract'
		);
	else
		$address_book[] = array(
			'address' => $token['address'],
			'alias' => $token['tksymbol'] . ' nft minter'
		);
}

# balance history record for auditing transactions, from covalent

$config['exchange'] = 'covalent';

$config = config_exchange($config);

$address_wallet = '0x7578af57e2970EDB7cB065b066C488bEce369C43';
$address_wallet = '0x56B8021aeB2315E03eA1c99C2BE81baF0a2CB283';
$address_eth = '0xeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
/*
# current token balances & ETH balance
$config['api_request'] =  '/1/' # blockchain
		. 'address/' . $address_wallet # reference address
		. '/balances_v2/' # module
		. '?key=' . $config['api_key']; # params

# all token holder changes by block range
$address_token = '0x136fae4333ea36a24bb751e2d505d6ca4fd9f00b';
$config['api_request'] =  '/1/' # blockchain
		. 'tokens/' . $address_token # reference address
		. '/token_holders_changes/' # module
		. '?key=' . $config['api_key'] # params
		. '&starting-block=10076005'
		. '&ending-block=10076006'
		. '&page-size=1000'
		. '&page-number=0';

# all transactions for token in wallet
# Fetching transfers for a chain's gas currency (i.e. ETH) is not currently supported
$config['api_request'] =  '/1/' # blockchain
		. 'address/' . $address_wallet # reference address
		. '/transfers_v2/' # module
		. '?key=' . $config['api_key'] # params
		. '&contract-address=' . $address_token;

# all transactions for wallet
$config['api_request'] =  '/1/' # blockchain
		. 'address/' . $address_wallet # reference address
		. '/transactions_v2/' # module
		. '?key=' . $config['api_key'] # params
		. '&page-size=1000'
		. '&page-number=0'
		. '&block-signed-at-asc=true'; # sort chronologically
*/
# wallet value by eth/token over time
$config['api_request'] =  '/1/' # blockchain
		. 'address/' . $address_wallet # reference address
			. '/portfolio_v2/' # module
		. '?key=' . $config['api_key'] # params
		. '&quote-currency=' . $fiat
		. '&days=100';
#		. '&days=900';
		# limits query to x seconds before times out, 1400 in about 60 seconds, see api_functions CURLOPT_CONNECTTIMEOUT

$config['url'] .= $config['api_request'];
$balances = query_api($config);
var_dump($config['url']);
#$result = $result['data']['items'];

foreach ($balances['data']['items'] as $asset) {
	if ($asset['contract_ticker_symbol'] == 'ETH') var_dump($asset);
}

file_put_contents('db/balance_history.json', json_encode($balances));
$balances = json_decode(file_get_contents('db/balance_history.json'));
$balances = objectToArray($balances);

die;

# etherscan transaction query

$config['exchange'] = 'etherscan';

$tables = array(
	 'txlist'
	,'txlistinternal'
	,'tokentx'
	,'tokennfttx'
);

$addresses = array();

# this will be an object not array
$contracts = json_decode(file_get_contents('db/contracts.json'));
$contracts = objectToArray($contracts);

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
$keys = array_merge($keys_out, [
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
$txn_hist = json_decode(file_get_contents('db/txn_hist.json'));
$txn_hist = objectToArray($txn_hist);
if ($txn_hist['updated'] == $today) {
	$price_dates = $txn_hist['price_dates'];
	$txn_hist = $txn_hist['txn_hist'];
} else {
	$price_dates = array();
	$txn_hist = array();
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
					$r['contractAddress'] = '0xeth';
				}

				if (!isset($r['tokenID'])) $r['tokenID'] = '';

				$r['from'] = strtolower($r['from']);
				if (isset($r['contractAddress'])) $r['contractAddress'] = strtolower($r['contractAddress']);
				if (isset($r['address'])) $r['address'] = strtolower($r['address']);

				$txn_id = strtolower($r['hash']);

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
						$contracts[$r['contractAddress']]['tokenName'] = $r['tokenName'];
						$contracts[$r['contractAddress']]['tokenSymbol'] = $r['tokenSymbol'];
						$contracts[$r['contractAddress']]['symbol'] = $r['symbol'];
						$contracts[$r['contractAddress']]['nftTokenId'] = $r['tokenID'];
						$contracts[$r['contractAddress']]['decimal'] = $r['decimal'];
						$contracts[$r['contractAddress']]['coingecko'] = array();
					}
					$price_dates[$r['contractAddress']][] = $r['timeStamp'];
				} else if ($nftnull) {
					$row['Sell_contract_address'] = '0xnull';
				}

				$price_dates['0xeth'][] = $r['timeStamp'];

				# record

				$txn_hist[] = $row;

				# record addresses, contracts and dates for price

				$addresses[] = $r['from'];
				$addresses[] = $r['to'];

			}
		}
	}

	$price_dates = dedupe_array($price_dates);
	$a = array('updated' => $today, 'txn_hist' => $txn_hist, 'price_dates' => $price_dates);
	file_put_contents('db/txn_hist.json', json_encode($a));
	unset($a);
	file_put_contents('db/contracts.json', json_encode($contracts));
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

$taxable = array('Spend', 'Trade', 'Staking', 'Interest', 'Mining', 'Dividend', 'Income');
foreach ($txn_hist as &$row) {

	$row['type.ori'] = $row['Type'];

	# Type: Airdrop
	# if airdrop identified but type is withdrawal, this record needs to be set as Spend type
	# taxable event: no
	# identifiers:
	# manually nominate transaction_id
	# contract_id not clearly reliable
	# type is deposit
	if (in_array($row['transaction_id'], $airdrops)) {
		if ($row['Type'] == 'Deposit') {
			$row['Type'] = 'Airdrop';
			$row['Buy Value in '.$fiat] = 0;
		} else {
			$row['Type'] = 'Spend';
			$row['Sell Value in '.$fiat] = 0;
		}
		# bittytax incorrectly prices airdrops

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
	else if ($row['t_id_sides'] == 1 && $row['Type'] == 'Deposit') {
		$row['Buy Value in '.$fiat] = 0;
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

# sort chronologically, and by tran_id/type/amount for equal times

$sort_keys = array();
foreach ($txn_hist as $row) {
	$s = (string) $row['Timestamp'] . $row['transaction_id'];
	if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] == 0) $s .= '0';
	else if ($row['Buy Quantity'] == 0 && $row['Sell Quantity'] > 0) $s .= '1';
	else if ($row['Buy Quantity'] > 0 && $row['Sell Quantity'] == 0) $s .= '2';
	$sort_keys[] = $s;
}
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

# check if ETH balances matches records

foreach ($portfolios as $port) {

	$config['exchange'] = 'etherscan';
	$config = config_exchange($config);
	$config['api_request'] = 'module=account&action=balance&tag=latest'
			. '&apikey=' . $config['api_key']
			. '&address=' . $port['address'];
	$config['url'] .= $config['api_request'];
	$result = query_api($config);
	sleep(0.25); # rate limit 5/second

	$eth_balance = bcdiv($result['result'], pow(10, 18), 18);
	$txn_fee = [];
	$txn_fee_free = [];
	$eth_hist = '0.000000000000000000';
	echo $port['alias'] . PHP_EOL;

	foreach ($txn_hist as $key => &$row) {
		if ($row['Wallet'] !== $port['alias']) continue;
		if ($row['Buy Asset'] == 'ETH') $eth_hist = bcadd($eth_hist, $row['Buy Quantity'], 18);
		if ($row['Sell Asset'] == 'ETH') $eth_hist = bcsub($eth_hist, $row['Sell Quantity'], 18);
		if ($row['Fee Asset'] == 'ETH') {

			$known_free = FALSE;
			foreach ($fee_free as $txn_id)
				if ($row['transaction_id'] == $txn_id) { $known_free = TRUE; break; }

			if ($known_free) {
				$row['Fee Quantity'] = 0;
			} else {
				$eth_hist = bcsub($eth_hist, $row['Fee Quantity'], 18);
				$txn_fee[$key] = (int) bcmul($row['Fee Quantity'], pow(10, 18), 0);
			}
		}
	}
	unset($row);

	echo $eth_balance . ' actual balance' . PHP_EOL;
	echo $eth_hist . ' txn balance' . PHP_EOL;
	echo bcsub($eth_hist, $eth_balance, 18) . ' difference' . PHP_EOL;
	$comp = bccomp($eth_hist, $eth_balance, 18);
	if ($comp == 0) echo 'Okay: balances equal';
	if ($comp == 1) die('Error: txn balance higher than actual balance');
	if ($comp == -1) echo 'txn balance lower than actual balance, checked fee-free txns ...';
	echo PHP_EOL . PHP_EOL;

	if ($comp == -1) {

		echo count($txn_fee) . ' possible fee records' . PHP_EOL;
		$target =  (int) bcmul(bcsub($eth_balance, $eth_hist, 18), pow(10, 18), 0);
		echo 'target: ' . $target . PHP_EOL;

		# get max/min num of records
		sort($txn_fee);
		$target_bal = $target;
		$max = 1;
		foreach ($txn_fee as $fee) {
			$target_bal = $target_bal - $fee;
			if ($target_bal < 0) break;
			$max++;
		}
		rsort($txn_fee);
		$target_bal = $target;
		$min = 1;
		foreach ($txn_fee as $fee) {
			$target_bal = $target_bal - $fee;
			if ($target_bal < 0) break;
			$min++;
		}
		echo $min . ' - ' . $max . ' fee records needed';

		for ($i = 0; $i < count($txn_fee); $i++) {

		}
	}

}

// $result = get_all_combinations($txn_fee, 2);
// echo '('.count($result).' combination subsets found)';
// var_dump($result);

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

file_put_contents('db/contracts.json', json_encode($contracts));


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

	$running = T;

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

// if (count($keys_out) <> 17) die('error');
// const cols = 17;
// foreach ($txn_hist as &$row)
// 	$row = array_slice($row, 0, cols);
// unset($row);

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
