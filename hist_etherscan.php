<?php

# set contracts_append false by default
# if FALSE, as new contracts identified, critical stop occurs and prints on screen
# if seem like spam then add address to spam list
# if not then allow, and set TRUE, then rerun to add
$contracts_append = FALSE;

# data load
$txn_hist_eth = json_decode(file_get_contents('db/eth_txn_hist.json'));
$txn_hist_eth = objectToArray($txn_hist_eth);
$contracts = json_decode(file_get_contents('db/contracts.json'));
$contracts = objectToArray($contracts);

# If the Fee Asset is the same as Sell Asset, then the Sell Quantity must be the net amount (after fee deduction), not gross amount.
# If the Fee Asset is the same as Buy Asset, then the Buy Quantity must be the gross amount (before fee deduction), not net amount.


//check issue with balance history
// https://discord.com/channels/715804406842392586/717770762659954709/993155136920768582

// I: look for clues in raw data such as wallet address is neither to_address or from_address
// I: use covalenthq instead of etherscan if enough info is in it to identify fee-free
// I: step through txns chronologically, use covalent to query eth balance by day history of txn, compare txn_history balance to actual balance, for any block where difference noted it should be a fee-free txn so add to list in json file (and check first)
// I: use etherscan manual ETH balance tool https://etherscan.io/balancecheck-tool
// I: build local instance of geth and query with web3.js or similar
// I: just make dummy record to fill unknowns



/*

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

# note suspected fee-free transactions
$fee_free_sus = array(
	 /* note is 'Send to kyber limit order.' */
	 '0x51263f7bf90127d6ce97ddea54f730ae26d062ca31d1e668147fd3faf47e5312'
	,'0xfdc70b7142c44e7a642f8c1f38b314f85ac1a540d56c7b338a8850b2da33da27'
	,'0xbed3814644b8a2d2e1a8241e887df92da8186d0e3b9452821b619675ddf0ef13'
	,'0x353efeaa292d7f48bfa2c95f5797152885e24e4b01c3bf5ee424befee6600376'
	,'0xd0f949605b83b93e865b98ae722313e3a869d546c0f61416ca8a867063806a17'
	,'0x6216ea5d793bfa2c4b1cc8afc1607c3c6d94c8ce3dc8210d58d6dee2bb2c133b'
	,'0x09e044570edc34cab06ceab9ec2369e624cd89b8c6a8efac583f2619c5e78e22'
	,'0xe1b84313635f1ce8bd96ac3af2983cb6dd81c09a1fc0c13230e0b6eb4fcc2bdf'
	,'0x6d27ca72a18aa6673479933db81037cad5a47511b905f75451a6f1fce888eef1'
	,'0x9ce5ee7390fd3c9da59456ed25ffc15b43f6442ee4fc129a7d8bd7f6b94bf8fc'
	,'0xe3806e1ed760670d892d29a1582f014457254134ad0d2a0ed0a0db7145e9d1ea'
	,'0x8038e485ac66b8ef6085c10090f3c28d60ea9ccda928f400b69ae8fced44951d'
	,'0x31b88b90b910a4e519bc4f1021fe5daad48c8dd3fc67af1b8e4720ce6887571f'
	/* note is 'Send to kyber proxy router 2.' */
	,'0xcb44a5f1a5ac849cc3ae4e614af5549a0c53b89cc660f84531049fc308c3cebb'
	,'0x8246c99b1afeeec245a3684c4de12e5b531b31107459a1dabf366f3596ee8eb9'
	,'0xd7df8954017750000e44d48d710dee79b3f65702cb945d7e8c08e4a464875d0b'
	,'0xf7f574512e8c8976e225717e714b9c076dfc631c248a3c03653572beceeadc17'
	,'0xec30b4fba566ac1a4b8edd12ec6683d5d3c19b10e389f1923b5e464d982c82b2'
	,'0x05657bb142791dd746a592d9fa38d0eab12adea1348bcbc3e9a9eb2f50ce9765'
	,'0x57ea8155924f935bc1296c6831b31f2b19ccd28d859f0a0bdb313da4e1836ea1'
	,'0x536a3060c7d162d592107925e590be3c1a01e2163c56a67ab1183d4c0366bcc2'
	,'0xe5f4b73c5d078b42df4efe2c9bf30a51ee4367b70870d554ac0535c7d9116ece'
	,'0x293acab4e4d28dc0391fd6c5e4b2751c93bea1276e5b9f6f6687adc7ef488215'
	/* note is 'Transaction error, gas fee may apply. Send to token set rebalancer attempt failed.' */
	,'0x510bef55f9d3e0535999f45472961a52c496fb9379f29f73dfa0115f7446d65c'
	,'0x39cbfc54bd8e344d874a6f545b3068ae6cf9377708bb18851c04fc3292f214c6'
	,'0x42f8a87250bb104696e0d5388198d7e49dd835ce2901281fa883cf060e8f2fb7'
	,'0x8f2adcd428c96cda51070ebc39c0f09f8588321e8cb299b0c600649ef243b20c'
	,'0x8040b735bcc2c548a324c1da55e413e45ab6fd34daaae19fb61bd0c3745eba0a'
	,'0x2d7b03e0ad72286dc57337444223b8cdc455084b319ee0a2ab733e3f4c0b42e9'
);
$fee_free_sus = array_map('strtolower', $fee_free_sus);
$fee_free = array_merge($fee_free, $fee_free_sus);

# balance tiny amounts that throw out audit
$txn_balance_dust = array(
	[
	'table' => 'txlist',
	'wallet_address' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'hash' => 'dust1',
	'tokenSymbol' => 'ETH',
	'tokenDecimal' => 18,
	'contractAddress' => '0xeth',
	'value' => 512981262955731,
	'timeStamp' => 1589441549,
	'tokenID' => '',
	'from' => '', /* leave blank for deposit */
	'to' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'address' => ''
	]
 ,[
	'table' => 'txlist',
	'wallet_address' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'hash' => 'dust2',
	'tokenSymbol' => 'ETH',
	'tokenDecimal' => 18,
	'contractAddress' => '0xeth',
	'value' => 5777196593981831,
	'timeStamp' => 1664760344,
	'tokenID' => '',
	'from' => '', /* leave blank for deposit */
	'to' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'address' => ''
	]
	,[
 	'table' => 'txlist',
 	'wallet_address' => '0x4263891bc4469759ac035f1f3cceb2ed87deaa7e',
 	'hash' => 'dust3',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1321999000156219,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '', /* leave blank for deposit */
 	'to' => '0x4263891bc4469759ac035f1f3cceb2ed87deaa7e',
 	'address' => ''
 	]
	,[
 	'table' => 'txlist',
 	'wallet_address' => '0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7',
 	'hash' => 'dust4',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1527178377227244,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '', /* leave blank for deposit */
 	'to' => '0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7',
 	'address' => ''
 	]
	,[
 	'table' => 'txlist',
 	'wallet_address' => '0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0',
 	'hash' => 'dust5',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1520443714979164,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '', /* leave blank for deposit */
 	'to' => '0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0',
 	'address' => ''
 	]

);

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
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4144', 'UNI-V3-POS', 'UNI-ETH-4144']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4382', 'UNI-V3-POS', 'RPL-ETH-4382']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4414', 'UNI-V3-POS', 'LYXe-ETH-4414']
	,['0xc36442b4a4522e871399cd717abdd847ab11fe88', '4426', 'UNI-V3-POS', 'ZRX-ETH-4426']
	,['0x9f49ed43c90a540d1cf12f6170ace8d0b88a14e6', '',     'ETHRSIAPY',  'ETHRSIAPY2']
	,['0x39aa39c021dfbae8fac545936693ac917d5e7563', '',     'cUSDC',  'cUSDC']
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
	,['0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2', 'wrapped eth']
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

/*
# balance history record for auditing transactions, from covalent
# known bug, does not work for ETH but does for other tokens
$config['exchange'] = 'covalent';

$config = config_exchange($config);

$address_wallet = '0x7578af57e2970EDB7cB065b066C488bEce369C43';
$address_wallet = '0x56B8021aeB2315E03eA1c99C2BE81baF0a2CB283';
$address_eth = '0xeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';

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
*/

# etherscan transaction query

$config['exchange'] = 'etherscan';

$tables = array(
	 'txlist'
	,'txlistinternal'
	,'tokentx'
	,'tokennfttx'
);

$addresses = array();

# note spam contracts
$spam = [
	 '0x1412eca9dc7daef60451e3155bb8dbf9da349933'
	,'0x6cf0b5a20b2d4b55e6b752d7016275b892035652'
	,'0x2d262c7faa8e03a86d6634bdc6178b3e06cd690b'
	,'0xdeefe7215fb3aff0b6bad252430b12be53f6f4a4'
	,'0xcf39b7793512f03f2893c16459fd72e65d2ed00c'
	,'0x71fdf1bbb9f166fbcc4fdc73d696999fa4d8e4a2'
	,'0x2f87445c3c81a5b268adab1915235a94aa27bf54'
	,'0x67542502245eb5df64ef7ea776199ceb79401058'
	,'0x64e39084fce774b6e892e5a4da5a9032d7436871'
	,'0xb688d06d858e092ebb145394a1ba08c7a10e1f56'
	,'0x54fd62228c6e1234fd5fded28555ca963dcf6d26'
	,'0xc9da518db3abdb90a82c4d1838b7cf9b0c945e7e'
];
foreach ($spam as &$row) {
	$row = strtolower($row);
}
unset($row);

# need to catch new addresses to check for spam
$contracts_new = [];

if (!$reset_txn_hist_eth) {
	#$price_dates = $txn_hist_eth['price_dates'];
	$txn_hist = $txn_hist_eth['txn_hist'];
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
			$result = $result['result'];
			sleep(0.25); # rate limit 5/second

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
					$price_dates[$r['contractAddress']][] = $r['timeStamp'];
				} else if ($nftnull) {
					$row['Sell_contract_address'] = '0xnull';
				}

				$price_dates['0xeth'][] = $r['timeStamp'];

				# special case for wrapping ETH to WETH as we won't automatically see the WETH value deposited
				$addr_weth = '0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2';
				if (($r['to'] == $addr_weth || $r['from'] == $addr_weth) && $r['value'] > 0 && $r['symbol'] == 'ETH') {

					$row_weth = $row;
					if ($row['Type'] == 'Withdrawal') {
						$row_weth['Note'] = 'Wrapping ETH';
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

				$addresses[] = $r['from'];
				$addresses[] = $r['to'];

			}
		}
	}

	$price_dates = dedupe_array($price_dates);
	$a = array('updated' => $today, 'txn_hist' => $txn_hist, 'price_dates' => $price_dates);
	file_put_contents('db/eth_txn_hist.json', json_encode($a));
	unset($a);

	if (count($contracts_new) > 0) {
		if ($contracts_append) {
			$contracts = array_merge($contracts, $contracts_new);
			file_put_contents('db/contracts.json', json_encode($contracts, JSON_PRETTY_PRINT));
		} else {
			echo 'new coins, check for spam (add to $spam if necessary) then update $contracts_append' . PHP_EOL;
			var_dump($contracts_new);
			die;
		}
	}
}

#var_dump($txn_hist);die;

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

$a = array('updated' => $txn_hist_eth['updated'], 'txn_hist' => $txn_hist, 'price_dates' => $txn_hist_eth['price_dates']);
file_put_contents('db/eth_txn_hist_tidy.json', json_encode($a));
unset($a);
