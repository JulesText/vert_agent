
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
-- list all db tables and columns
--
/*
select tab.table_schema as database_schema,
    tab.table_name as table_name,
    col.ordinal_position as column_id,
    col.column_name as column_name,
    col.data_type as data_type,
    case when col.numeric_precision is not null
        then col.numeric_precision
        else col.character_maximum_length end as max_length,
    case when col.datetime_precision is not null
        then col.datetime_precision
        when col.numeric_scale is not null
        then col.numeric_scale
            else 0 end as 'precision'
from information_schema.tables as tab
    inner join information_schema.columns as col
        on col.table_schema = tab.table_schema
        and col.table_name = tab.table_name
where tab.table_type = 'BASE TABLE'
    and tab.table_schema not in ('information_schema','mysql',
        'performance_schema','sys')
    and tab.table_schema = database()
order by tab.table_name,
    col.ordinal_position;
*/

-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 12, 2022 at 06:47 PM
-- Server version: 10.2.41-MariaDB-cll-lve
-- PHP Version: 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `julesnet_vert_agent_dev`
--

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
  `source` varchar(32) NOT NULL,
  `class` enum('crypto','fiat','stock') NOT NULL,
  `investment_id` varchar(128) NOT NULL,
  `investment_proportion` decimal(6,4) NOT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `timestamp_dt` datetime DEFAULT NULL,
  `financial_year` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `asset_pairs`
--

CREATE TABLE `asset_pairs` (
  `pair_id` int(11) NOT NULL,
  `pair` varchar(64) NOT NULL,
  `exchange` varchar(64) NOT NULL,
  `collect` tinyint(1) NOT NULL DEFAULT 1,
  `analyse` tinyint(1) NOT NULL DEFAULT 1,
  `trade` tinyint(1) NOT NULL DEFAULT 0,
  `leverage` decimal(5,3) NOT NULL DEFAULT 1.000,
  `class` enum('crypto','fiat','stock') DEFAULT NULL,
  `period` int(11) NOT NULL,
  `refresh` int(11) NOT NULL,
  `currency_start` bigint(20) NOT NULL DEFAULT 0,
  `currency_start_dt` datetime DEFAULT NULL,
  `currency_end` bigint(20) NOT NULL DEFAULT 0,
  `currency_end_dt` datetime DEFAULT NULL,
  `history_start` bigint(20) NOT NULL,
  `history_start_dt` datetime DEFAULT NULL,
  `history_end` bigint(20) NOT NULL,
  `history_end_dt` datetime DEFAULT NULL,
  `reference` varchar(1056) DEFAULT NULL
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
  `pair_id` int(11) DEFAULT NULL,
  `imputed` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` bigint(20) NOT NULL,
  `timestamp_dt` datetime DEFAULT NULL,
  `pair_tmp` char(32) DEFAULT NULL,
  `source_tmp` varchar(32) DEFAULT NULL,
  `period_tmp` set('1m','5m','15m','30m','1h','2h','4h','6h','12h','1d','1w') DEFAULT NULL,
  `open` decimal(30,20) DEFAULT NULL,
  `close` decimal(30,20) DEFAULT NULL,
  `high` decimal(30,20) NOT NULL,
  `low` decimal(30,20) NOT NULL,
  `volume` decimal(30,10) NOT NULL,
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
  `rsi8` int(11) DEFAULT NULL,
  `rsi8ob` tinyint(1) DEFAULT NULL,
  `rsi8os` tinyint(1) DEFAULT NULL,
  `rsi12` int(11) DEFAULT NULL,
  `rsi12ob` tinyint(1) DEFAULT NULL,
  `rsi12os` tinyint(1) DEFAULT NULL,
  `rsi24` int(11) DEFAULT NULL,
  `rsi24ob` tinyint(1) DEFAULT NULL,
  `rsi24os` tinyint(1) DEFAULT NULL,
  `rsi36` int(11) DEFAULT NULL,
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
  `gain` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reflection` varchar(4112) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tactics`
--

CREATE TABLE `tactics` (
  `tactic_id` int(11) NOT NULL,
  `strategy_id` int(11) DEFAULT NULL,
  `status` enum('inactive','conditional','actionable','ordered','executed','failed') NOT NULL DEFAULT 'inactive',
  `refresh` int(11) NOT NULL DEFAULT 60,
  `currency` bigint(20) NOT NULL DEFAULT 0,
  `currency_dt` datetime DEFAULT NULL,
  `action_time_limit` int(11) NOT NULL DEFAULT 1440,
  `condition_time_test` tinyint(1) NOT NULL DEFAULT 1,
  `condition_time` bigint(20) DEFAULT NULL,
  `condition_time_dt` datetime DEFAULT NULL,
  `condition_tactic_test` tinyint(1) NOT NULL DEFAULT 1,
  `condition_tactic` int(11) DEFAULT NULL,
  `condition_pair_test` tinyint(1) DEFAULT 1,
  `condition_pair_id` int(11) DEFAULT NULL,
  `condition_pair_currency_min` int(11) DEFAULT 1440,
  `condition_pair_indicator` varchar(128) DEFAULT NULL,
  `condition_pair_value_operand` enum('>=','<=','=') DEFAULT NULL,
  `condition_pair_value` decimal(40,20) DEFAULT NULL,
  `action` enum('delete','limit','market','loan','repay','none','alert') NOT NULL DEFAULT 'none',
  `pair_id` int(11) DEFAULT NULL,
  `exchange_tmp` char(32) DEFAULT NULL,
  `pair_asset_del` varchar(64) DEFAULT NULL,
  `from_asset` char(16) DEFAULT NULL,
  `from_amount` decimal(30,20) DEFAULT NULL,
  `from_percent` decimal(23,20) DEFAULT NULL,
  `to_asset` char(16) DEFAULT NULL,
  `trade_price` decimal(30,20) DEFAULT NULL,
  `to_fee_max` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tactics_external`
--

CREATE TABLE `tactics_external` (
  `tactic_ext_id` int(11) NOT NULL,
  `status` enum('inactive','processing','actionable','included') NOT NULL DEFAULT 'inactive',
  `timestamp` bigint(20) NOT NULL,
  `timestamp_dt` datetime DEFAULT NULL,
  `action_time_limit` int(11) NOT NULL DEFAULT 60,
  `channel_post_id` int(11) NOT NULL,
  `channel` varchar(64) NOT NULL,
  `pair` varchar(64) NOT NULL,
  `side` enum('short','long') NOT NULL,
  `entry` decimal(40,20) NOT NULL,
  `target` decimal(40,20) NOT NULL,
  `stop` decimal(40,20) DEFAULT NULL,
  `leverage` decimal(23,20) DEFAULT NULL,
  `order_string` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `investment_id` varchar(128) DEFAULT NULL,
  `investment_proportion` decimal(40,20) DEFAULT NULL,
  `time_opened` bigint(20) DEFAULT NULL,
  `time_closed` bigint(20) DEFAULT NULL,
  `time_opened_dt` datetime DEFAULT NULL,
  `time_closed_dt` datetime DEFAULT NULL,
  `capital_amount` decimal(40,20) DEFAULT NULL,
  `capital_fee` decimal(40,20) DEFAULT NULL,
  `purpose` enum('','trade','transfer in','transfer out','loan','loan repay','pool','fee') DEFAULT NULL,
  `exchange` char(64) DEFAULT NULL,
  `exchange_transaction_id` char(64) DEFAULT NULL,
  `exchange_transaction_status` enum('trigger open','open','complete','cancelled','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
  `recorded` tinyint(1) NOT NULL DEFAULT 0,
  `pair` char(64) DEFAULT NULL,
  `from_asset` char(64) DEFAULT NULL,
  `from_amount` decimal(40,20) DEFAULT NULL,
  `to_asset` char(64) DEFAULT NULL,
  `to_amount` decimal(40,20) DEFAULT NULL,
  `to_fee` decimal(40,20) DEFAULT NULL,
  `pair_price` decimal(40,20) DEFAULT NULL,
  `from_price_usd` decimal(40,20) DEFAULT NULL,
  `to_price_usd` decimal(40,20) DEFAULT NULL,
  `price_reference` text DEFAULT NULL,
  `fee_amount_usd` decimal(40,20) DEFAULT NULL,
  `price_aud_usd` decimal(40,20) DEFAULT NULL,
  `aud_usd_reference` text DEFAULT NULL,
  `aud_usd_timestamp` bigint(20) NOT NULL DEFAULT 0,
  `from_wallet` char(128) DEFAULT NULL,
  `to_wallet` char(128) DEFAULT NULL,
  `tactic_id` int(11) DEFAULT NULL,
  `strategy_id` int(11) DEFAULT NULL,
  `strategy_result_usd` decimal(40,20) DEFAULT NULL,
  `percent_complete` decimal(23,20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `web_content`
--

CREATE TABLE `web_content` (
  `content_id` int(11) NOT NULL,
  `source` varchar(64) NOT NULL,
  `url` text DEFAULT NULL,
  `content` text NOT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `timestamp_dt` datetime DEFAULT NULL,
  `notified` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`);

--
-- Indexes for table `asset_pairs`
--
ALTER TABLE `asset_pairs`
  ADD PRIMARY KEY (`pair_id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Indexes for table `strategies`
--
ALTER TABLE `strategies`
  ADD PRIMARY KEY (`strategy_id`);

--
-- Indexes for table `tactics`
--
ALTER TABLE `tactics`
  ADD PRIMARY KEY (`tactic_id`);

--
-- Indexes for table `tactics_external`
--
ALTER TABLE `tactics_external`
  ADD PRIMARY KEY (`tactic_ext_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `web_content`
--
ALTER TABLE `web_content`
  ADD PRIMARY KEY (`content_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_pairs`
--
ALTER TABLE `asset_pairs`
  MODIFY `pair_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `strategies`
--
ALTER TABLE `strategies`
  MODIFY `strategy_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tactics`
--
ALTER TABLE `tactics`
  MODIFY `tactic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tactics_external`
--
ALTER TABLE `tactics_external`
  MODIFY `tactic_ext_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `web_content`
--
ALTER TABLE `web_content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
