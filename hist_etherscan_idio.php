<?php

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
