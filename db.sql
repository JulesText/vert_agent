
--
-- Explanation of tactics table fields
--
/*
`strategy_id` is simply a backwards reference to the strategy the tactic belongs to
`status` will change from:
     conditional to actionable when all conditions are met (ie conditions = 1)
     actionable to ordered when order is placed
     ordered to executed when order is completed
`refresh` is set to x minutes, to check tactic status and conditions no more than every x minutes
`currency` is the last time the tactic status and conditions were checked
`action_time_limit` is set to default 1440, so error message is generated and tactic cancelled if it has remained actionable for more than 1440 minutes (1 day)
`condition_time_test` is set to 0 if waiting for time to arrive, otherwise 1
`condition_time` is set to a point in the future
`condition_tactic_test` is set to 0 if waiting for another tactic to execute, otherwise 1
`condition_tactic` indicates other tactic to wait for
`condition_pair_test` is set to 0 if waiting for a price indicator to pass a threshold, otherwise 1
`condition_pair_id`, `condition_pair_currency_min`, `condition_pair_indicator`, `condition_pair_value_operand`, `condition_pair_value`, these define the price indicator
`action`, `exchange`, `pair`, `from_asset`, `from_amount`, `from_percent`, `to_asset`, `trade_price`, `to_fee_max`, these define the order, though note order is optional, some tactics will not have any order and only serve as dependency for another tactic
`transaction_id` captures the transaction_id (order id) once it is placed
*/

--
-- Table structure for table `tactics`
--

CREATE TABLE `tactics` (
  `tactic_id` int(11) NOT NULL,
  `strategy_id` int(11) NOT NULL,
  `status` enum('inactive','conditional','actionable','ordered','executed','failed') NOT NULL DEFAULT 'inactive',
  `refresh` int(8) NOT NULL,
  `currency` bigint(20) NOT NULL,
  `action_time_limit` int(8) NOT NULL DEFAULT '1440',
  `condition_time_test` tinyint(1) NOT NULL DEFAULT '1',
  `condition_time` bigint(20) DEFAULT NULL,
  `condition_tactic_test` tinyint(1) NOT NULL DEFAULT '1',
  `condition_tactic` int(11) DEFAULT NULL,
  `condition_pair_test` tinyint(1) DEFAULT '1',
  `condition_pair_id` int(11) DEFAULT NULL,
  `condition_pair_currency_min` int(8) DEFAULT NULL,
  `condition_pair_indicator` varchar(128) DEFAULT NULL,
  `condition_pair_value_operand` enum('>=','<=','=') DEFAULT NULL,
  `condition_pair_value` decimal(40,20) DEFAULT NULL,
  `action` enum('delete','limit','market','none') NOT NULL DEFAULT 'none',
  `exchange` char(32) DEFAULT NULL,
  `pair` varchar(64) DEFAULT NULL,
  `from_asset` char(16) DEFAULT NULL,
  `from_amount` decimal(30,20) DEFAULT NULL,
  `from_percent` int(11) DEFAULT NULL,
  `to_asset` char(16) DEFAULT NULL,
  `trade_price` decimal(30,20) DEFAULT NULL,
  `to_fee_max` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` int(11) NOT NULL,
  `purpose` enum('capital','investment','return') NOT NULL,
  `asset` varchar(32) NOT NULL,
  `amount` decimal(40,20) NOT NULL,
  `price_aud` decimal(30,20) NOT NULL,
  `exchange` varchar(32) NOT NULL,
  `class` enum('crypto','fiat','stock') NOT NULL,
  `investment_id` varchar(128) NOT NULL,
  `investment_proportion` decimal(6,4) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `financial_year` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `asset_pairs`
--

CREATE TABLE `asset_pairs` (
  `pair_id` int(11) NOT NULL,
  `pair` varchar(64) NOT NULL,
  `collect` tinyint(1) NOT NULL DEFAULT '1',
  `analyse` tinyint(1) NOT NULL DEFAULT '1',
  `class` enum('crypto','fiat','stock') DEFAULT NULL,
  `period` int(8) NOT NULL,
  `refresh` int(8) NOT NULL,
  `currency` bigint(20) NOT NULL,
  `history_start` bigint(20) NOT NULL,
  `history_end` bigint(20) NOT NULL,
  `exchange` varchar(64) NOT NULL,
  `reference` varchar(1056) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `indicators`
--

CREATE TABLE `indicators` (
  `function` varchar(128) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `detail` varchar(2056) NOT NULL,
  `indication` varchar(128) NOT NULL,
  `reliability` varchar(256) NOT NULL,
  `class` varchar(32) NOT NULL,
  `parameters` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `history_id` int(11) NOT NULL,
  `pair` char(32) DEFAULT NULL,
  `exchange` varchar(32) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `period` set('1m','5m','15m','30m','1h','2h','4h','6h','12h','1d','1w') DEFAULT NULL,
  `open` decimal(30,20) DEFAULT NULL,
  `close` decimal(30,20) DEFAULT NULL,
  `high` decimal(30,20) NOT NULL,
  `low` decimal(30,20) NOT NULL,
  `volume` decimal(30,10) NOT NULL,
  `imputed` tinyint(1) NOT NULL DEFAULT '0',
  `ema6` decimal(20,10) DEFAULT NULL,
  `ema6cd` tinyint(1) DEFAULT NULL,
  `ema6cu` tinyint(1) DEFAULT NULL,
  `ema12` decimal(20,10) DEFAULT NULL,
  `ema12cd` tinyint(1) DEFAULT NULL,
  `ema12cu` tinyint(1) DEFAULT NULL,
  `ema26` decimal(20,10) DEFAULT NULL,
  `ema26cd` tinyint(1) DEFAULT NULL,
  `ema26cu` tinyint(1) DEFAULT NULL,
  `ema50` decimal(20,10) DEFAULT NULL,
  `ema50cd` tinyint(1) DEFAULT NULL,
  `ema50cu` tinyint(1) DEFAULT NULL,
  `ema100` decimal(20,10) DEFAULT NULL,
  `ema100cd` tinyint(1) DEFAULT NULL,
  `ema100cu` tinyint(1) DEFAULT NULL,
  `ema200` decimal(20,10) DEFAULT NULL,
  `ema200cd` tinyint(1) DEFAULT NULL,
  `ema200cu` tinyint(1) DEFAULT NULL,
  `roc1` decimal(20,10) DEFAULT NULL,
  `roc2` decimal(20,10) DEFAULT NULL,
  `roc4` decimal(20,10) DEFAULT NULL,
  `roc6` decimal(20,10) DEFAULT NULL,
  `roc12` decimal(20,10) DEFAULT NULL,
  `roc24` decimal(20,10) DEFAULT NULL,
  `rsi8` int(2) DEFAULT NULL,
  `rsi8ob` tinyint(1) DEFAULT NULL,
  `rsi8os` tinyint(1) DEFAULT NULL,
  `rsi12` int(2) DEFAULT NULL,
  `rsi12ob` tinyint(1) DEFAULT NULL,
  `rsi12os` tinyint(1) DEFAULT NULL,
  `rsi24` int(2) DEFAULT NULL,
  `rsi24ob` tinyint(1) DEFAULT NULL,
  `rsi24os` tinyint(1) DEFAULT NULL,
  `rsi36` int(2) DEFAULT NULL,
  `rsi36ob` tinyint(1) DEFAULT NULL,
  `rsi36os` tinyint(1) DEFAULT NULL,
  `corr50btc` decimal(3,2) DEFAULT NULL,
  `corr50eth` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `strategies`
--

CREATE TABLE `strategies` (
  `strategy_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `objective` varchar(4112) NOT NULL,
  `gain` decimal(5,2) NOT NULL DEFAULT '0.00',
  `reflection` varchar(4112) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `investment_id` varchar(128) NOT NULL,
  `investment_proportion` decimal(40,20) DEFAULT NULL,
  `time_opened` bigint(20) NOT NULL,
  `time_closed` bigint(20) NOT NULL,
  `capital_amount` decimal(40,20) DEFAULT NULL,
  `capital_fee` decimal(40,20) DEFAULT NULL,
  `purpose` char(64) DEFAULT NULL,
  `exchange` char(64) DEFAULT NULL,
  `exchange_transaction_id` char(64) NOT NULL,
  `exchange_transaction_status` enum('trigger open','open','complete','cancelled','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
  `pair` char(64) NOT NULL,
  `from_asset` char(64) DEFAULT NULL,
  `from_amount` decimal(40,20) DEFAULT NULL,
  `to_asset` char(64) DEFAULT NULL,
  `to_amount` decimal(40,20) DEFAULT NULL,
  `to_fee` decimal(40,20) DEFAULT NULL,
  `pair_price` decimal(40,20) NOT NULL,
  `from_price_usd` decimal(40,20) DEFAULT NULL,
  `to_price_usd` decimal(40,20) DEFAULT NULL,
  `price_reference` text,
  `fee_amount_usd` decimal(40,20) DEFAULT NULL,
  `price_aud_usd` decimal(40,20) DEFAULT NULL,
  `aud_usd_reference` text,
  `from_wallet` char(128) DEFAULT NULL,
  `to_wallet` char(128) DEFAULT NULL,
  `tactic_id` int(6) DEFAULT NULL,
  `strategy_id` int(6) NOT NULL,
  `strategy_result_usd` decimal(40,20) DEFAULT NULL,
  `percent_complete` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `web_content`
--

CREATE TABLE `web_content` (
  `content_id` int(11) NOT NULL,
  `source` varchar(64) NOT NULL,
  `url` text,
  `content` text NOT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `notified` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
