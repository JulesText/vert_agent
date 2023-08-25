<?php

#
# portfolios
#

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

#
# account balances
#

# dydx balances
$balances_manual_dydx = [
	'J1dy' => ['LUNA' => 0]
	,'J6dy' => ['LUNA' => 0]
];

#
# tokens
#

# some tokens have ambiguous names or idiosyncracies
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

#
# transaction types
#

# note aidrop transactions
$airdrops = array(
	 '0xf3dd1f2b7a86efec7f910aab83718c1b49aa76160c1402404ccff37039ac1605' /* DYDX */
	,'0x5714d0d1a4b0206f98ebd6c542b49c0bff2582aee077b42134d102a685769627' /* UNI */
);
$airdrops = array_map('strtolower', $airdrops);

# note contract approval spend transactions
# in case not identified by existing logic
$approvals = array(
	'0xb1663ca442aba925f4dcce35cdd1615212b0c5f0047c08e60d5f476cc52ab272'
	,'0x3e3d37cf70cd652a69b209dbf2c5c184bf97f5a75bd1fc2a2453a47a08c56602'
	,'0x98564cad8c575ebda6d992acccbb331a95da6cfd3e4b980ab2e01b5de21311e4'
	,'0x09af5475635ee3a84d7f267c851cea76382e322d7831ce4a4ad556b2271e9b5d'
);
$approvals = array_map('strtolower', $approvals);

# note interest transactions
$interests = array(
	 '0x2e5d24eb68ac4edcb5e39751825aa1f7868544171d2cb9b22ba5978c19925184' /* CVP */
	,'0xc897ab69077a3fc4ada1b75cbdce719841bbb36dd70eb86c512a4441bcce00cc' /* CVP */
	,'0x0b1c459488d42f0b062151ad88257b9bf9039c0e08f098d3224838af7c9d052d' /* CVP */
	,'0xdbacb0ec89931103a6fae44aacf943ee64d90101a61416e3fe68622739ebfb0f' /* CRV */
	,'0xb8aa820874d05d88b7f87d5346b96537d0a339371af33ca917de375054193109' /* SNX */
);
$interests = array_map('strtolower', $interests);

# note direct trades with individuals, not through exchange
$manualtrades = array(
	 '0x9faa03e14f9a201a57e12121685dda1d296a24caf2c4237e42acc35a116ba00c'
	,'0xcc1954dcd5312388cc3384b03d034c3b15f91dd5c2d67cffbd3dcc7f179f508c'
	,'0xa2d9cb852e74211e5b9df2a4f744c3ab08d7d6741573d272c277c1362f093d49'
	,'0x14e7f971fcb5f4f86448a2beeb7a720f0a7ea2a159f880b7b36f99c5465b41c1'
	,'0x5239c3c12cf5e65a3646e0c6a362dfcb9dc908206f15684d996710865b2b6d1b'
	,'0x931378f547e34cd0338b19588d5ee1552ad6b40d75ecfe4c939d64023ada5123'
	,'0xf9068f139bb2798411b260a73f7baee1d8133b714c12c89d1517498dad83c5c4'
	,'0xd615c4647741ab97d94aa021d1935a9d4136e5c99f2757e300040ff02ea11c51'
	,'0x1251e48d40dec9c498e6a04c44d92fec01103031f3a7e064eeae72ca10a5b6fd'
	,'0x369430005c5e0a5862d5da6a1e5c06746d324b62c40422a66ed2714f8668245f'
	,'0xaeea4f15fb7d8b9bdb64f8adfcbb624284f0c2d305670ea4cf84de50dc149486'
	,'0x0655329cda9fb0af0af45f070bf1710c7007dde38d14673596b1a86ab0c9890f'
);
$manualtrades = array_map('strtolower', $manualtrades);

# note personal purchase transactions, to set as spends not withdrawals
$purchases = array(
	 '0x5a8ef1f166a763ce995f5ac994bad6e3e3d9c1aeff654ad671559716c628aa4f'
	,'0x47c3634486f84b27089722dc7fab9e8111e0c6a9c49663bae8b83ff8a42c4dbc'
	,'0x2b11aaa2654f63c666fdd1425fa0239a14c72e4c48ac01e558e05d22a189c0b3'
	,'0x43b41450cade5a94cd6bfe882e698d37f9611f5dae59e3e5eaecfba73d8d56cb'
);
$purchases = array_map('strtolower', $purchases);

# note pooling nft disposal transactions
$nftnulls = array(
	 '0x05e065d43ffebb22b106b8f2026c49081a00927b5afad3875379a6021081d75d' => 'UNI-ETH-4144'
	,'0xe6dcc256e665ab0d3115d73bc85ae71519dee5c69fa8de5ce6412e80d9956f08' => 'RPL-ETH-4382'
	,'0x5ab42ca6fcef62df0034303e28262b7aa0a76616a4ab7d6b701161681bae58db' => 'LYXe-ETH-4414'
	,'0x5e35a0b6b9d20ca9b22e1768d9802f438947eedf14d662a98010f92523fe9b44' => 'ZRX-ETH-4426'
);

#
# transaction balances
#

# manual enter missing dydx trades
$txn_manual_dydx = [
	['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USDC'
	,'price' => 5.4
	,'size' => 62.5
	,'fee' => 0.160312
	]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USDC'
	,'price' => 5.38
	,'size' => 62.5
	,'fee' => 0.159718
	]
	,['wallet' => '0x7578af57e2970edb7cb065b066c488bece369c43'
	,'createdAt' => '2022-04-29T17:43:09.075Z'
	,'side' => 'SELL'
	,'market' => 'UMA-USDC'
	,'price' => 5.35
	,'size' => 62.5
	,'fee' => 0.158828
	]
];

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
	'blockNumber' => 10062960,
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
	'to' => '0x7578af57e2970edb7cb065b066c488bece369c43', /* leave blank for withdrawal */
	'address' => 'NA'
	]
 ,[
	'blockNumber' => 15664332,
	'table' => 'txlist',
	'wallet_address' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'hash' => 'dust2',
	'tokenSymbol' => 'ETH',
	'tokenDecimal' => 18,
	'contractAddress' => '0xeth',
	'value' => 5777196593981831,
	'timeStamp' => 1664760344,
	'tokenID' => '',
	'from' => '',
	'to' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'address' => 'NA'
	]
	,[
	'blockNumber' => 15664332,
 	'table' => 'txlist',
 	'wallet_address' => '0x4263891bc4469759ac035f1f3cceb2ed87deaa7e',
 	'hash' => 'dust3',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1321999000156219,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '',
 	'to' => '0x4263891bc4469759ac035f1f3cceb2ed87deaa7e',
 	'address' => 'NA'
 	]
	,[
	'blockNumber' => 15664332,
 	'table' => 'txlist',
 	'wallet_address' => '0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7',
 	'hash' => 'dust4',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1527178377227244,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '',
 	'to' => '0xa2f5f0d6b64ba1acf54418fcccfb15b99ed349e7',
 	'address' => 'NA'
 	]
	,[
	'blockNumber' => 15664332,
 	'table' => 'txlist',
 	'wallet_address' => '0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0',
 	'hash' => 'dust5',
 	'tokenSymbol' => 'ETH',
 	'tokenDecimal' => 18,
 	'contractAddress' => '0xeth',
 	'value' => 1520443714979164,
 	'timeStamp' => 1664760344,
 	'tokenID' => '',
 	'from' => '',
 	'to' => '0x139d2ce2a3f323b668e9f1f30812f762ec6ac1f0',
 	'address' => 'NA'
 	]
	,[
	'blockNumber' => 17947374,
	'table' => 'txlist',
	'wallet_address' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'hash' => 'dust7',
	'tokenSymbol' => 'ETH',
	'tokenDecimal' => 18,
	'contractAddress' => '0xeth',
	'value' => 4065402739635462,
	'timeStamp' => 1692430571,
	'tokenID' => '',
	'from' => '',
	'to' => '0x7578af57e2970edb7cb065b066c488bece369c43',
	'address' => 'NA'
	]

);
$txn_balance_dust_ids = [];
foreach ($txn_balance_dust as $txn) $txn_balance_dust_ids[] = $txn['hash'];

#
# asset pricing anomalies
#

# tokens that respond to coingecko queries on token id but not contract address (not ethereum)
$tokens_id_only = array(
	  '0x178c820f862b14f316509ec36b13123da19a6054' => 'energy-web-token'
	 ,'0xb4efd85c19999d84251304bda99e90b92300bd93' => 'rocket-pool'
);

# some contracts not captured through etherscan
$keys = array('address', 'tokenName', 'tokenSymbol', 'symbol', 'nftTokenId', 'decimal', 'coingecko');
$con_not_eth = array(
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
foreach ($con_not_eth as &$row) {
	$row = array_combine($keys, $row);
	$row['address'] = strtolower($row['address']);
}
unset($row);

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

#
# address book (at end)
#

# note address purposes
$keys = array('address', 'alias');
$address_book = array(
	['0x8139e63f2969d21883ceb278372e3fc02d152185', ' Tim']
	,['0xdef1c0ded9bec7f1a1670819833240f027b25eff', '0xDefiCoded matcha exchange']
	,['0x8463c03c0c57ff19fa8b431e0d3a34e2df89888e', '0xDefiCoded matcha exchange']
	,['0x0000000000007f150bd6f54c40a34d7c3d5e9f56', '0xDefiCoded matcha exchange']
	,['0x22f9dcf4647084d6c31b2765f6910cd85c178c18', '0xDefiCoded matcha exchange']
	,['0x58e327d64fc77fa36f698989623c3e4f058c8a89', '0xDefiCoded matcha exchange']
	,['0x06da0fd433c1a5d7a4faa01111c044910a184553', '0xDefiCoded matcha exchange']
	,['0x0bc5ae46c32d99c434b7383183aca16dd6e9bdc8', '0xDefiCoded matcha exchange']
	,['0xaa04f1162899554fb66d06d1d60c68d46dce4d6a', '0xDefiCoded matcha exchange']
	,['0x0000000000a8fb09af944ab3baf7a9b3e1ab29d8', '0xDefiCoded matcha exchange']
	,['0x397ff1542f962076d0bfe58ea045ffa2d347aca0', '0xDefiCoded matcha exchange']
	,['0x2fa96250cf4eafe165ed7cd3f05e32a99e271566', '0xDefiCoded matcha exchange']
	,['0x56178a0d5f301baf6cf3e1cd53d9863437345bf9', '0xDefiCoded matcha exchange']
	,['0x8b00ee8606cc70c2dce68dea0cefe632cca0fb7b', '0xDefiCoded matcha exchange']
	,['0x561b94454b65614ae3db0897b74303f4acf7cc75', '0xDefiCoded matcha exchange']
	,['0x00000000000e1d0dabf7b7c7b68866fc940d0db8', '0xDefiCoded matcha exchange']
	,['0x225a6313e0d13d0f8c87a661ddc6923b53d0509a', '0xDefiCoded matcha exchange']
	,['0x983eb8f8c0f6902e1f078b1186500ef6602d7d54', '0xDefiCoded matcha exchange']
	,['0x0d25efec9a4378b65dfd0457382bc2e20a864688', 'approve USDC']
	,['0x986a2fca9eda0e06fbf7839b89bfc006ee2a23dd', 'ascendex/bitmax wallet']
	,['0x2f9ec37d6ccfff1cab21733bdadede11c823ccb0', 'bancor approval']
	,['0x225a6313e0d13d0f8c87a661ddc6923b53d0509a', 'bancor exchange']
	,['0x983eb8f8c0f6902e1f078b1186500ef6602d7d54', 'bancor exchange']
	,['0x744fc208bf314fdf15a88198153121d16527996a', 'bitmax wallet']
	,['0x79a8c46dea5ada233abaffd40f3a0a2b1e5a4f27', 'curve BUSD pool']
	,['0xbebc44782c7db0a1a60cb6fe97d0b483032ff1c7', 'curve DAI/USDC/USDT pool']
	,['0xfcba3e75865d2d561be8d220616520c171f12851', 'curve staking pool']
	,['0xa90996896660decc6e997655e065b23788857849', 'curve sUSD gauge']
	,['0xa5407eae9ba41422680e2e00537571bcc53efbfd', 'curve sUSD pool']
	,['0xd061d61a4d941c39e5453435b6345dc261c2fce0', 'curve token minter']
	,['0x29bff390fc12c900aaf0f2e51c06675df691337a', 'CVP deployer']
	,['0x0000000000000000000000000000000000000000', 'default ethereum router']
	,['0x0fd829c3365a225fb9226e75c97c3a114bd3199e', 'dydx token claims']
	,['0x639192d54431f8c816368d3fb4107bc168d0e871', 'dydx token distributor']
	,['0xd54f502e184b6b739d7d27a6410a67dc462d69c8', 'dydx wallet']
	,['0x44db449cbdea5cc143f9a3f4893c171821b2d756', 'dydx wallet']
	,['0xf42ed7184f3bdd07b0456952f67695683afd9044', 'dydx wallet']
	,['0x8e8bd01b5a9eb272cc3892a2e40e64a716aa2a40', 'dydx wallet']
	,['0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2', 'ETH wrapper']
	,['0x5b67871c3a857de81a1ca0f9f7945e5670d986dc', 'ETHRSIAPY token set vault']
	,['0xe334c3bba644f1e6908324728d5a6e1c5b68eed2', 'hitbtc wallet']
	,['0x274f3c32c90517975e29dfc209a23f315c1e5fc7', 'hotbit wallet']
	,['0x33cc2c8376c7b79224b965d544029914200932e4', 'idex wallet']
	,['0xdbf328cc664ff89b446b1cb1c624a007ab209973', 'idex wallet']
	,['0x65bf64ff5f51272f729bdcd7acfb00677ced86cd', 'kyber defi exchange']
	,['0x7c66550c9c730b6fdd4c03bc2e73c5462c5f7acc', 'kyber defi exchange']
	,['0x52d35e8f0ffa18337b093aec3dfff40445d8f4f4', 'kyber limit order']
	,['0x818e6fecd516ecc3849daf6845e3ec868087b755', 'kyber proxy router']
	,['0x9aab3f75489902f3a48495025729a0af77d4b11e', 'kyber proxy router 2']
	,['0xdf4b6fb700c428476bd3c02e6fa83e110741145b', 'liquid wallet']
	,['0x369a2020a287edfc12f39774318057073e4a40d6', 'liquid wallet']
	,['0x61bfb5e060a3f2092ccf80ecf412980c0385d537', 'liquid wallet']
	,['0x2f9ec37d6ccfff1cab21733bdadede11c823ccb0', 'matcha approval']
	,['0xb3c839dbde6b96d37c56ee4f9dad3390d49310aa', 'matcha exchange']
	,['0x881d40237659c251811cec9c364ef91dc08d300c', 'metamask exchange']
	,['0x5fdaeb2280a2ed4c7776ff90cb31325cfb75bc34', 'mistaken wallet']
	,['0x6cc5f688a315f3dc28a7781717a9a798a59fda7b', 'okex wallet']
	,['0x37c8b60ec0be985cb47955a3228e4f8a8da63f92', 'okex wallet']
	,['0x5041ed759dd4afc3a72b8192c143f72f4724081a', 'okex wallet']
	,['0xa7efae728d2936e78bda97dc267687568dd593f3', 'okex wallet']
	,['0x03f955ad67d0027e0baf3dccc3d1fb5f665e61f9', 'old wallet or cex/dex']
	,['0x7a250d5630b4cf539739df2c5dacb4c659f2488d', 'router']
	,['0x20312e96b1a0568ac31c6630844a962383cc66c2', 'router']
	,['0x09363887a4096b142f3f6b58a7eed2f1a0ff7343', 'router']
	,['0xe4b3dd9839ed1780351dc5412925cf05f07a1939', 'router']
	,['0xeb76b50807bfe587bb3dc88c75cb351bc49e3ab3', 'router']
	,['0x09cf91d5e137cf047eb9e0551ceb18239b46d3dd', 'router']
	,['0x74de5d4fcbf63e00296fd95d33236b9794016631', 'sushiswap exchange']
	,['0x3f5ce5fbfe3e9af3971dd833d26ba9b5c936f0be', 'swyftx wallet']
	,['0xd551234ae421e3bcba99a0da6d736074f22192ff', 'swyftx wallet']
	,['0x0681d8db095565fe8a346fa0277bffde9c0edbbf', 'swyftx wallet']
	,['0x564286362092d8e7936f0549571a803b203aaced', 'swyftx wallet']
	,['0xda816e2122a8a39b0926bfa84edd3d42477e9efd', 'swyftx wallet']
	,['0xe268807b0865f84b0e9aaac77328b739a0468618', 'swyftx wallet']
	,['0x2f577f053dde1e20f9b070e313a26ea49d6c6fd6', 'swyftx wallet']
	,['0x708396f17127c42383e3b9014072679b2f60b82f', 'swyftx wallet']
	,['0xe0f0cfde7ee664943906f17f7f14342e76a5cec7', 'swyftx wallet']
	,['0x9696f59e4d72e237be84ffd425dcad154bf96976', 'swyftx wallet']
	,['0x4976a4a02f38326660d17bf34b431dc6e2eb2327', 'swyftx wallet']
	,['0x28c6c06298d514db089934071355e5743bf21d60', 'swyftx wallet']
	,['0x5fbaeb2280a2ed4c7776ff90cb31325cfb75bc34', 'swyftx wallet']
	,['0xde697ac56b1bd9c0987c08911020f2341e274813', 'token set rebalancer']
	,['0xda6786379ff88729264d31d472fa917f5e561443', 'token set rebalancer']
	,['0x3da1313ae46132a397d90d95b1424a9a7e3e0fce', 'uniswap CRV pool']
	,['0xa478c2975ab1ea89e8196811f51a7b7ade33eb11', 'uniswap DAI pool']
	,['0xb1608e16609a7ff3ac5b0da49a0539bb0c3c3d9d', 'uniswap EWTB pool']
	,['0xb76c291871b92a7c9e020b2511a3402a3bf0499d', 'uniswap exchange']
	,['0xd583d0824ed78767e0e35b9bf7a636c81c665aa8', 'uniswap exchange']
	,['0x70ea56e46266f0137bac6b75710e3546f47c855d', 'uniswap exchange']
	,['0x004375dff511095cc5a197a54140a24efef3a416', 'uniswap exchange']
	,['0xe592427a0aece92de3edee1f18e0157c05861564', 'uniswap exchange']
	,['0xfaace66bd25abff62718abd6db97560e414ec074', 'uniswap exchange']
	,['0x88e6a0c2ddd26feeb64f039a2c41296fcb3f5640', 'uniswap exchange']
	,['0x7858e59e0c01ea06df3af3d20ac7b0003275d4bf', 'uniswap exchange']
	,['0x1d22397edfc4edf622d692050635bfc1febf1404', 'uniswap exchange']
	,['0xa6cc3c2531fdaa6ae1a3ca84c2855806728693e8', 'uniswap LINK pool']
	,['0x3cf3d5b9061fac75fc66bc33035803fb067cb4f8', 'uniswap match USTC exchange']
	,['0x68b3465833fb72a70ecdf485e0e4c7bd8665fc45', 'uniswap matcha exchange']
	,['0x43ae24960e5534731fc831386c07755a2dc33d47', 'uniswap SNX pool']
	,['0x090d4613473dee047c3f2706764f49e0821d256e', 'uniswap UNI distributor']
	,['0xd3d2e2692501a5c9ca623199d38826e513033a17', 'uniswap UNI pool']
	,['0x80c7770b4399ae22149db17e97f9fc8a10ca5100', 'uniswap v3 LYXe-ETH pool']
	,['0x632e675672f2657f227da8d9bb3fe9177838e726', 'uniswap v3 RPL-ETH pool']
	,['0x1d42064fc4beb5f8aaf85f4617ae8b3b5b8bd801', 'uniswap v3 UNI-ETH pool']
	,['0x14424eeecbff345b38187d0b8b749e56faa68539', 'uniswap v3 ZRX-ETH pool']
	,['0x5f3b5dfeb7b28cdbd7faba78963ee202a494e2a2', 'veCRV governance token']
	,['0x34fcb7f4edfd95761e6cbcf0fa34d19afd13089d', 'xdai distributor']
	,['0xabea9132b05a70803a4e85094fd0e1800777fbef', 'zksync wallet']
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

# note spam contracts
$spam = [
	'0xdcd441ca4689bde55add971afcc2330e7ad5c90f' /* this QACOL not really spam */
	,'0x1412eca9dc7daef60451e3155bb8dbf9da349933'
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
	,'0x208a9c9d8e1d33a4f5b371bf1864aa125379ba1b'
	,'0x70c18f2fdcb00d27494f767503874788e35c9940'
	,'0x622651529bda465277cb890ef9176c442f42b338'
	,'0x0bf377fb3b5f1dd601e693b8faf6b0bd249f37d3'
	,'0x956f824b5a37673c6fc4a6904186cb3ba499349b'
	,'0x68ca006db91312cd60a2238ce775be5f9f738bba'
	,'0x71b9d914eb26a2e634aff696a809988b69e1a5d1'
	,'0xc1c8c49b0405f6cffba5351179befb2d8a2c776c'
	,'0xa51a8578052edeb4ced5333a5e058860d9e7a35b'
	,'0xe65af2c8be6def2a8b5fbd5b382741fac4e80fd9'
	,'0x643695d282f6ba237afe27ffe0acd89a86b50d3e'
	,'0xb8d566044b68b5c304f3a81c56fd2cad29c1507e'
	,'0xe89c2dccfa045f3538ae3ebbfff3daec6a40a73a'
	,'0xac5ceab2eda779f48e9c16baedf0dc44992c88aa'
	,'0xcb6ba52f93e85b1089e2592c35696c1e7111cf85'
	,'0xb1977364642e0dcbc17fa655a3505a3f08c382c1'
	,'0x498b1165514f170cd1c84689a83f8ba5d5f41f11'
];
foreach ($spam as &$row) {
	$row = strtolower($row);
}
unset($row);
