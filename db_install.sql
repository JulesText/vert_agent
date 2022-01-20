
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
-- Generation Time: Jan 21, 2022 at 12:30 AM
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
-- Table structure for table `ana_price`
--

CREATE TABLE `ana_price` (
  `ana_price_id` bigint(20) NOT NULL,
  `price_id` int(11) NOT NULL,
  `indicator` varchar(16) NOT NULL,
  `value` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ana_tactic_external`
--

CREATE TABLE `ana_tactic_external` (
  `tactic_ext_id` int(11) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'inactive',
  `timestamp` bigint(20) NOT NULL,
  `timestamp_debug` datetime DEFAULT NULL,
  `action_time_limit` varchar(8) NOT NULL,
  `channel_post_id` int(11) NOT NULL,
  `channel` varchar(64) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `side` enum('short','long') NOT NULL,
  `entry` decimal(40,20) NOT NULL,
  `target` decimal(40,20) NOT NULL,
  `stop` decimal(40,20) DEFAULT NULL,
  `leverage` decimal(23,20) DEFAULT NULL,
  `order_string` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dat_content`
--

CREATE TABLE `dat_content` (
  `content_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `timestamp_debug` datetime DEFAULT NULL,
  `url` text DEFAULT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dat_price`
--

CREATE TABLE `dat_price` (
  `price_id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `imputed` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` bigint(20) NOT NULL,
  `timestamp_debug` datetime DEFAULT NULL,
  `open` decimal(40,20) DEFAULT NULL,
  `close` decimal(40,20) DEFAULT NULL,
  `high` decimal(40,20) DEFAULT NULL,
  `low` decimal(40,20) DEFAULT NULL,
  `volume` decimal(40,20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inv_cycle_assets`
--

CREATE TABLE `inv_cycle_assets` (
  `cyc_ass_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `ass_inv_id` int(11) NOT NULL,
  `user` varchar(32) NOT NULL,
  `open_base_amount` decimal(40,20) NOT NULL,
  `close_base_amount` decimal(40,20) NOT NULL,
  `base_amount_cgtd` decimal(40,20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `inv_investment`
--

CREATE TABLE `inv_investment` (
  `investment_id` int(11) NOT NULL,
  `user` varchar(32) DEFAULT NULL,
  `open_transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `met_action`
--

CREATE TABLE `met_action` (
  `action` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_action`
--

INSERT INTO `met_action` (`action`) VALUES
('alert'),
('delete'),
('limit'),
('loan'),
('market'),
('none'),
('repay');

-- --------------------------------------------------------

--
-- Table structure for table `met_channel`
--

CREATE TABLE `met_channel` (
  `channel` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_channel`
--

INSERT INTO `met_channel` (`channel`) VALUES
('email'),
('telegram');

-- --------------------------------------------------------

--
-- Table structure for table `met_cycle`
--

CREATE TABLE `met_cycle` (
  `cycle_id` int(11) NOT NULL,
  `time_open` bigint(20) NOT NULL,
  `time_close` bigint(20) NOT NULL,
  `label` varchar(32) DEFAULT NULL,
  `time_zone` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_cycle`
--

INSERT INTO `met_cycle` (`cycle_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES
(19, 1561903200000, 1593525599999, '2019-20-FY', '+10:00'),
(20, 1593525600000, 1625061599999, '2020-21-FY', '+10:00'),
(21, 1625061600000, 1656597599999, '2021-22-FY', '+10:00'),
(22, 1656597600000, 1688133599999, '2022-23-FY', '+10:00'),
(23, 1688133600000, 1719755999999, '2023-24-FY', '+10:00'),
(24, 1719756000000, 1751291999999, '2024-25-FY', '+10:00'),
(25, 1751292000000, 1782827999999, '2025-26-FY', '+10:00'),
(26, 1782828000000, 1814363999999, '2026-27-FY', '+10:00'),
(27, 1814364000000, 1845986399999, '2027-28-FY', '+10:00'),
(28, 1845986400000, 1877522399999, '2028-29-FY', '+10:00'),
(29, 1877522400000, 1909058399999, '2029-30-FY', '+10:00'),
(30, 1909058400000, 1940594399999, '2030-31-FY', '+10:00'),
(31, 1940594400000, 1972216799999, '2031-32-FY', '+10:00'),
(32, 1972216800000, 2003752799999, '2032-33-FY', '+10:00'),
(33, 2003752800000, 2035288799999, '2033-34-FY', '+10:00'),
(34, 2035288800000, 2066824799999, '2034-35-FY', '+10:00'),
(35, 2066824800000, 2098447199999, '2035-36-FY', '+10:00'),
(36, 2098447200000, 2129983199999, '2036-37-FY', '+10:00'),
(37, 2129983200000, 2161519199999, '2037-38-FY', '+10:00'),
(38, 2161519200000, 2193055199999, '2038-39-FY', '+10:00'),
(39, 2193055200000, 2224677599999, '2039-40-FY', '+10:00'),
(40, 2224677600000, 2256213599999, '2040-41-FY', '+10:00'),
(41, 2256213600000, 2287749599999, '2041-42-FY', '+10:00'),
(42, 2287749600000, 2319285599999, '2042-43-FY', '+10:00'),
(43, 2319285600000, 2350907999999, '2043-44-FY', '+10:00'),
(44, 2350908000000, 2382443999999, '2044-45-FY', '+10:00'),
(45, 2382444000000, 2413979999999, '2045-46-FY', '+10:00'),
(46, 2413980000000, 2445515999999, '2046-47-FY', '+10:00'),
(47, 2445516000000, 2477138399999, '2047-48-FY', '+10:00'),
(48, 2477138400000, 2508674399999, '2048-49-FY', '+10:00'),
(49, 2508674400000, 2540210399999, '2049-50-FY', '+10:00');

-- --------------------------------------------------------

--
-- Table structure for table `met_event`
--

CREATE TABLE `met_event` (
  `event` varchar(16) NOT NULL,
  `assets_investments` varchar(16) DEFAULT NULL,
  `transaction` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_event`
--

INSERT INTO `met_event` (`event`, `assets_investments`, `transaction`) VALUES
('asset merge', 'asset merge', NULL),
('asset split', 'asset split', NULL),
('fee', 'fee', 'fee'),
('investment close', 'investment close', NULL),
('investment open', 'investment open', NULL),
('loan', 'loan', 'loan'),
('loan repay', 'loan repay', 'loan repay'),
('pool', 'pool', 'pool'),
('pool close', 'pool close', 'pool close'),
('trade', 'trade', 'trade'),
('transfer', 'transfer', NULL),
('transfer in', NULL, 'transfer in'),
('transfer out', NULL, 'transfer out');

-- --------------------------------------------------------

--
-- Table structure for table `met_indicator`
--

CREATE TABLE `met_indicator` (
  `indicator` varchar(16) NOT NULL,
  `decimals` int(2) NOT NULL,
  `code_function` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_indicator`
--

INSERT INTO `met_indicator` (`indicator`, `decimals`, `code_function`) VALUES
('corr50btc', 2, NULL),
('corr50eth', 2, NULL),
('ema100', 20, NULL),
('ema100cd', 0, NULL),
('ema100cu', 0, NULL),
('ema12', 20, NULL),
('ema12cd', 0, NULL),
('ema12cu', 0, NULL),
('ema200', 20, NULL),
('ema200cd', 0, NULL),
('ema200cu', 0, NULL),
('ema26', 20, NULL),
('ema26cd', 0, NULL),
('ema26cu', 0, NULL),
('ema50', 20, NULL),
('ema50cd', 0, NULL),
('ema50cu', 0, NULL),
('ema6', 20, NULL),
('ema6cd', 0, NULL),
('ema6cu', 0, NULL),
('roc1', 20, NULL),
('roc12', 20, NULL),
('roc2', 20, NULL),
('roc24', 20, NULL),
('roc4', 20, NULL),
('roc6', 20, NULL),
('rsi12', 2, NULL),
('rsi12ob', 0, NULL),
('rsi12os', 0, NULL),
('rsi24', 2, NULL),
('rsi24ob', 0, NULL),
('rsi24os', 0, NULL),
('rsi36', 2, NULL),
('rsi36ob', 0, NULL),
('rsi36os', 0, NULL),
('rsi8', 2, NULL),
('rsi8ob', 0, NULL),
('rsi8os', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `met_network`
--

CREATE TABLE `met_network` (
  `network` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_network`
--

INSERT INTO `met_network` (`network`) VALUES
('bank'),
('centralised'),
('decentralised'),
('ethereum');

-- --------------------------------------------------------

--
-- Table structure for table `met_period`
--

CREATE TABLE `met_period` (
  `minutes` int(11) NOT NULL,
  `period` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_period`
--

INSERT INTO `met_period` (`minutes`, `period`) VALUES
(0, '0m'),
(720, '12h'),
(131040, '13w'),
(15, '15m'),
(1080, '18h'),
(1440, '1d'),
(60, '1h'),
(1, '1m'),
(10080, '1w'),
(262080, '26w'),
(2880, '2d'),
(120, '2h'),
(20160, '2w'),
(30, '30m'),
(5220, '3d'),
(3, '3m'),
(30240, '3w'),
(45, '45m'),
(240, '4h'),
(40320, '4w'),
(524160, '52w'),
(5, '5m'),
(360, '6h'),
(480, '8h'),
(80640, '8w');

-- --------------------------------------------------------

--
-- Table structure for table `met_purpose`
--

CREATE TABLE `met_purpose` (
  `purpose_id` int(11) NOT NULL,
  `purpose` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_purpose`
--

INSERT INTO `met_purpose` (`purpose_id`, `purpose`) VALUES
(1, 'asset'),
(2, 'automation'),
(3, 'calculation'),
(4, 'goal'),
(5, 'indicator'),
(6, 'model'),
(7, 'prediction'),
(8, 'principle'),
(9, 'strategy'),
(10, 'tactic'),
(11, 'trade');

-- --------------------------------------------------------

--
-- Table structure for table `met_resource`
--

CREATE TABLE `met_resource` (
  `resource_id` int(11) NOT NULL,
  `resource` varchar(16) NOT NULL,
  `abstract` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `met_status`
--

CREATE TABLE `met_status` (
  `status` varchar(16) NOT NULL,
  `tactic` varchar(16) DEFAULT NULL,
  `tactic_external` varchar(16) DEFAULT NULL,
  `transaction` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_status`
--

INSERT INTO `met_status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES
('actionable', 'actionable', 'actionable', NULL),
('cancelled', NULL, NULL, 'cancelled'),
('complete', NULL, NULL, 'complete'),
('conditional', 'conditional', NULL, NULL),
('executed', 'executed', NULL, NULL),
('failed', 'failed', NULL, NULL),
('inactive', 'inactive', 'inactive', NULL),
('included', NULL, 'included', NULL),
('open', NULL, NULL, 'open'),
('ordered', 'ordered', NULL, NULL),
('processing', NULL, 'processing', NULL),
('trigger open', NULL, NULL, 'trigger open'),
('unconfirmed', NULL, NULL, 'unconfirmed');

-- --------------------------------------------------------

--
-- Table structure for table `met_sym_class`
--

CREATE TABLE `met_sym_class` (
  `class` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `met_sym_class`
--

INSERT INTO `met_sym_class` (`class`) VALUES
('crypto'),
('fiat'),
('stock');

-- --------------------------------------------------------

--
-- Table structure for table `str_condition_content`
--

CREATE TABLE `str_condition_content` (
  `condition_content_id` int(11) NOT NULL,
  `tactic_id` int(11) NOT NULL,
  `refresh` varchar(8) NOT NULL,
  `recency` bigint(20) NOT NULL DEFAULT 0,
  `recency_debug` datetime NOT NULL,
  `content_id` int(11) NOT NULL,
  `condition_met` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_condition_indicator`
--

CREATE TABLE `str_condition_indicator` (
  `condition_indicator_id` int(11) NOT NULL,
  `tactic_id` int(11) NOT NULL,
  `refresh` varchar(8) NOT NULL,
  `recency` bigint(20) NOT NULL DEFAULT 0,
  `recency_debug` datetime DEFAULT NULL,
  `recency_min` varchar(8) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `indicator` varchar(16) NOT NULL,
  `value_operand` enum('>=','<=','=') NOT NULL,
  `value` decimal(40,20) NOT NULL,
  `condition_met` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_condition_tactic`
--

CREATE TABLE `str_condition_tactic` (
  `str_condition_tactic_id` int(11) NOT NULL,
  `tactic_id` int(11) NOT NULL,
  `refresh` varchar(8) NOT NULL,
  `recency` bigint(20) NOT NULL DEFAULT 0,
  `recency_debug` datetime DEFAULT NULL,
  `condition_tactic_id` int(11) NOT NULL,
  `condition_met` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_condition_time`
--

CREATE TABLE `str_condition_time` (
  `condition_time_id` int(11) NOT NULL,
  `tactic_id` int(11) NOT NULL,
  `refresh` varchar(8) NOT NULL,
  `recency` bigint(20) NOT NULL DEFAULT 0,
  `recency_debug` datetime NOT NULL,
  `condition_time` bigint(20) NOT NULL,
  `condition_time_debug` datetime NOT NULL,
  `condition_met` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_evaluation`
--

CREATE TABLE `str_evaluation` (
  `strategy_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `objective` varchar(4112) NOT NULL,
  `gain` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reflection` varchar(4112) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `str_evaluation`
--

INSERT INTO `str_evaluation` (`strategy_id`, `name`, `objective`, `gain`, `reflection`) VALUES
(0, 'undefined', '', 0.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `str_plan`
--

CREATE TABLE `str_plan` (
  `plan_id` int(11) NOT NULL,
  `purpose` varchar(16) NOT NULL,
  `detail` varchar(2056) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `indication` varchar(128) NOT NULL,
  `reliability` varchar(256) NOT NULL,
  `class` varchar(32) NOT NULL,
  `code_function` varchar(128) NOT NULL,
  `parameters` varchar(256) NOT NULL,
  `to_do` varchar(2056) DEFAULT NULL,
  `dev_simple_order` int(4) DEFAULT NULL,
  `dev_simple_hrs_rem` int(4) DEFAULT NULL,
  `provided_commercially` int(4) DEFAULT NULL,
  `dev_advanced` int(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_tactic`
--

CREATE TABLE `str_tactic` (
  `tactic_id` int(11) NOT NULL,
  `strategy_id` int(11) DEFAULT NULL,
  `refresh` varchar(8) NOT NULL,
  `status` varchar(16) NOT NULL,
  `status_currency` bigint(20) DEFAULT NULL,
  `status_currency_debug` datetime DEFAULT NULL,
  `action` varchar(16) NOT NULL DEFAULT '''none''',
  `action_time_limit` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `str_tactic_alert`
--

CREATE TABLE `str_tactic_alert` (
  `alert_id` int(11) NOT NULL,
  `tactic_id` int(11) NOT NULL,
  `user` varchar(32) NOT NULL,
  `channel` varchar(16) NOT NULL,
  `description` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sym_exchange`
--

CREATE TABLE `sym_exchange` (
  `exchange` varchar(16) NOT NULL,
  `network` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sym_pair`
--

CREATE TABLE `sym_pair` (
  `pair_id` int(11) NOT NULL,
  `symbol_buy` varchar(16) NOT NULL,
  `symbol_sell` varchar(16) NOT NULL,
  `class` varchar(8) NOT NULL,
  `period` varchar(8) NOT NULL DEFAULT '''0m''',
  `exchange` varchar(16) NOT NULL,
  `collect` tinyint(1) NOT NULL DEFAULT 0,
  `analyse` tinyint(1) NOT NULL DEFAULT 0,
  `trade` tinyint(1) NOT NULL DEFAULT 0,
  `transfer` tinyint(1) NOT NULL DEFAULT 0,
  `leverage` decimal(5,3) NOT NULL DEFAULT 0.000,
  `refresh` varchar(8) NOT NULL DEFAULT '0m',
  `recency_start` bigint(20) NOT NULL DEFAULT 0,
  `recency_end` bigint(20) NOT NULL DEFAULT 0,
  `history_start` bigint(20) NOT NULL DEFAULT 0,
  `history_end` bigint(20) NOT NULL DEFAULT 1999999999999
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sym_unit`
--

CREATE TABLE `sym_unit` (
  `symbol` varchar(16) NOT NULL,
  `class` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `txn_assets_investments`
--

CREATE TABLE `txn_assets_investments` (
  `ass_inv_id` int(11) NOT NULL,
  `ass_inv_par_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `event` varchar(16) DEFAULT NULL,
  `symbol` varchar(16) DEFAULT NULL,
  `open_amount` decimal(40,20) DEFAULT NULL,
  `time_opened` bigint(20) DEFAULT NULL,
  `time_closed` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `txn_history`
--

CREATE TABLE `txn_history` (
  `transaction_id` int(11) NOT NULL,
  `user` varchar(32) NOT NULL,
  `time_opened` bigint(20) DEFAULT NULL,
  `time_closed` bigint(20) DEFAULT NULL,
  `event` varchar(16) DEFAULT NULL,
  `exchange` varchar(16) DEFAULT NULL,
  `exchange_transaction_id` varchar(64) DEFAULT NULL,
  `exchange_transaction_status` varchar(16) DEFAULT 'unconfirmed',
  `percent_complete` decimal(23,20) DEFAULT NULL,
  `record_complete` tinyint(1) NOT NULL DEFAULT 0,
  `pair_id` int(11) DEFAULT NULL,
  `from_symbol` varchar(16) DEFAULT NULL,
  `from_amount` decimal(40,20) DEFAULT NULL,
  `to_symbol` varchar(16) DEFAULT NULL,
  `to_amount` decimal(40,20) DEFAULT NULL,
  `to_fee` decimal(40,20) DEFAULT NULL,
  `pair_price` decimal(40,20) DEFAULT NULL,
  `from_price_denom` decimal(40,20) DEFAULT NULL,
  `to_price_denom` decimal(40,20) DEFAULT NULL,
  `price_ref_exch` varchar(16) DEFAULT NULL,
  `fee_amount_denom` decimal(40,20) DEFAULT NULL,
  `price_base_denom` decimal(40,20) DEFAULT NULL,
  `price_base_denom_ref_exch` varchar(16) DEFAULT NULL,
  `from_wallet_id` int(11) DEFAULT NULL,
  `to_wallet_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `txn_order`
--

CREATE TABLE `txn_order` (
  `order_id` int(11) NOT NULL,
  `tactic_id` int(11) DEFAULT NULL,
  `pair_id` int(11) DEFAULT NULL,
  `from_symbol` varchar(16) DEFAULT NULL,
  `from_amount` decimal(40,20) DEFAULT NULL,
  `from_percent` decimal(23,20) DEFAULT NULL,
  `to_symbol` varchar(16) DEFAULT NULL,
  `trade_price` decimal(40,20) DEFAULT NULL,
  `to_fee_max` date DEFAULT NULL,
  `user` varchar(32) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `usr_settings`
--

CREATE TABLE `usr_settings` (
  `user` varchar(32) NOT NULL,
  `base_symbol` varchar(16) NOT NULL,
  `denom_symbol` varchar(16) NOT NULL,
  `telegram_id` varchar(128) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `usr_wallet`
--

CREATE TABLE `usr_wallet` (
  `wallet_id` int(11) NOT NULL,
  `address` varchar(128) DEFAULT NULL,
  `label` varchar(16) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `exchange` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ana_price`
--
ALTER TABLE `ana_price`
  ADD PRIMARY KEY (`ana_price_id`),
  ADD KEY `FK__ana_price__price_id` (`price_id`),
  ADD KEY `FK__ana_price__indicator` (`indicator`);

--
-- Indexes for table `ana_tactic_external`
--
ALTER TABLE `ana_tactic_external`
  ADD PRIMARY KEY (`tactic_ext_id`),
  ADD KEY `FK__ana_tactics_external__pair_id` (`pair_id`),
  ADD KEY `FK__ana_tactics_external__status` (`status`),
  ADD KEY `FK__ana_tactic_external__action_time_limit` (`action_time_limit`);

--
-- Indexes for table `dat_content`
--
ALTER TABLE `dat_content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `FK__dat_content__resource_id` (`resource_id`);

--
-- Indexes for table `dat_price`
--
ALTER TABLE `dat_price`
  ADD PRIMARY KEY (`price_id`) USING BTREE,
  ADD KEY `FK__dat_price__pair_id` (`pair_id`);

--
-- Indexes for table `inv_cycle_assets`
--
ALTER TABLE `inv_cycle_assets`
  ADD PRIMARY KEY (`cyc_ass_id`),
  ADD KEY `FK__inv_cycle_assets__ass_inv_id` (`ass_inv_id`),
  ADD KEY `FK__inv_cycle_assets__cycle_id` (`cycle_id`),
  ADD KEY `FK__inv_cycle_assets__user` (`user`);

--
-- Indexes for table `inv_investment`
--
ALTER TABLE `inv_investment`
  ADD PRIMARY KEY (`investment_id`),
  ADD KEY `FK__inv_investment__open_transaction_id` (`open_transaction_id`),
  ADD KEY `FK__inv_investment__user` (`user`);

--
-- Indexes for table `met_action`
--
ALTER TABLE `met_action`
  ADD PRIMARY KEY (`action`);

--
-- Indexes for table `met_channel`
--
ALTER TABLE `met_channel`
  ADD PRIMARY KEY (`channel`);

--
-- Indexes for table `met_cycle`
--
ALTER TABLE `met_cycle`
  ADD PRIMARY KEY (`cycle_id`);

--
-- Indexes for table `met_event`
--
ALTER TABLE `met_event`
  ADD PRIMARY KEY (`event`),
  ADD UNIQUE KEY `asset_investment` (`assets_investments`),
  ADD UNIQUE KEY `transaction` (`transaction`);

--
-- Indexes for table `met_indicator`
--
ALTER TABLE `met_indicator`
  ADD PRIMARY KEY (`indicator`);

--
-- Indexes for table `met_network`
--
ALTER TABLE `met_network`
  ADD PRIMARY KEY (`network`);

--
-- Indexes for table `met_period`
--
ALTER TABLE `met_period`
  ADD PRIMARY KEY (`minutes`),
  ADD KEY `period` (`period`);

--
-- Indexes for table `met_purpose`
--
ALTER TABLE `met_purpose`
  ADD PRIMARY KEY (`purpose_id`),
  ADD UNIQUE KEY `purpose` (`purpose`);

--
-- Indexes for table `met_resource`
--
ALTER TABLE `met_resource`
  ADD PRIMARY KEY (`resource_id`);

--
-- Indexes for table `met_status`
--
ALTER TABLE `met_status`
  ADD PRIMARY KEY (`status`),
  ADD UNIQUE KEY `transaction` (`transaction`) USING BTREE,
  ADD UNIQUE KEY `tactic` (`tactic`),
  ADD UNIQUE KEY `tactic_external` (`tactic_external`);

--
-- Indexes for table `met_sym_class`
--
ALTER TABLE `met_sym_class`
  ADD PRIMARY KEY (`class`);

--
-- Indexes for table `str_condition_content`
--
ALTER TABLE `str_condition_content`
  ADD PRIMARY KEY (`condition_content_id`),
  ADD KEY `FK__str_condition_content__tactic_id` (`tactic_id`),
  ADD KEY `FK__str_condition_content__refresh` (`refresh`),
  ADD KEY `FK__str_condition_content__content_id` (`content_id`);

--
-- Indexes for table `str_condition_indicator`
--
ALTER TABLE `str_condition_indicator`
  ADD PRIMARY KEY (`condition_indicator_id`),
  ADD KEY `FK__str_condition_indicator__tactic_id` (`tactic_id`) USING BTREE,
  ADD KEY `FK__str_condition_indicator__pair_id` (`pair_id`) USING BTREE,
  ADD KEY `FK__str_condition_indicator__refresh` (`refresh`) USING BTREE,
  ADD KEY `FK__str_condition_indicator__indicator` (`indicator`) USING BTREE,
  ADD KEY `FK__str_condition_indicator__recency_min` (`recency_min`) USING BTREE;

--
-- Indexes for table `str_condition_tactic`
--
ALTER TABLE `str_condition_tactic`
  ADD PRIMARY KEY (`str_condition_tactic_id`),
  ADD KEY `FK__str_condition_tactic__tactic_id` (`tactic_id`),
  ADD KEY `FK__str_condition_tactic__refresh` (`refresh`),
  ADD KEY `FK__str_condition_tactic__condition_tactic` (`condition_tactic_id`);

--
-- Indexes for table `str_condition_time`
--
ALTER TABLE `str_condition_time`
  ADD PRIMARY KEY (`condition_time_id`),
  ADD KEY `FK__str_condition_time__tactic_id` (`tactic_id`),
  ADD KEY `FK__str_condition_time__refresh` (`refresh`);

--
-- Indexes for table `str_evaluation`
--
ALTER TABLE `str_evaluation`
  ADD PRIMARY KEY (`strategy_id`);

--
-- Indexes for table `str_plan`
--
ALTER TABLE `str_plan`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `FK__str_plan__purpose` (`purpose`);

--
-- Indexes for table `str_tactic`
--
ALTER TABLE `str_tactic`
  ADD PRIMARY KEY (`tactic_id`),
  ADD KEY `FK__str_tactics__action` (`action`),
  ADD KEY `FK__str_tactics__action_time_limit` (`action_time_limit`),
  ADD KEY `FK__str_tactics__refresh` (`refresh`),
  ADD KEY `FK__str_tactics__status` (`status`),
  ADD KEY `FK__str_tactics__strategy_id` (`strategy_id`);

--
-- Indexes for table `str_tactic_alert`
--
ALTER TABLE `str_tactic_alert`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `FK__str_tactic_alert__tactic_id` (`tactic_id`),
  ADD KEY `FK__str_tactic_alert__channel` (`channel`),
  ADD KEY `FK__str_tactic_alert__user` (`user`);

--
-- Indexes for table `sym_exchange`
--
ALTER TABLE `sym_exchange`
  ADD PRIMARY KEY (`exchange`),
  ADD KEY `FK__sym_exchange__network` (`network`);

--
-- Indexes for table `sym_pair`
--
ALTER TABLE `sym_pair`
  ADD PRIMARY KEY (`pair_id`),
  ADD KEY `FK__sym_pair__class` (`class`),
  ADD KEY `FK__sym_pair__exchange` (`exchange`),
  ADD KEY `FK__sym_pair__symbol_buy` (`symbol_buy`),
  ADD KEY `FK__sym_pair__symbol_sell` (`symbol_sell`),
  ADD KEY `FK__sym_pair__period` (`period`),
  ADD KEY `FK__sym_pair__refresh` (`refresh`);

--
-- Indexes for table `sym_unit`
--
ALTER TABLE `sym_unit`
  ADD PRIMARY KEY (`symbol`),
  ADD KEY `FK__sym_unit__class` (`class`);

--
-- Indexes for table `txn_assets_investments`
--
ALTER TABLE `txn_assets_investments`
  ADD PRIMARY KEY (`ass_inv_id`),
  ADD KEY `FK__txn_assets_investments__ass_inv_par_id` (`ass_inv_par_id`),
  ADD KEY `FK__txn_assets_investments__event` (`event`),
  ADD KEY `FK__txn_assets_investments__investment_id` (`investment_id`),
  ADD KEY `FK__txn_assets_investments__symbol` (`symbol`),
  ADD KEY `FK__txn_assets_investments__transaction_id` (`transaction_id`);

--
-- Indexes for table `txn_history`
--
ALTER TABLE `txn_history`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `FK__txn_history__event` (`event`),
  ADD KEY `FK__txn_history__exchange` (`exchange`),
  ADD KEY `FK__txn_history__exchange_transaction_status` (`exchange_transaction_status`),
  ADD KEY `FK__txn_history__from_symbol` (`from_symbol`),
  ADD KEY `FK__txn_history__from_wallet_id` (`from_wallet_id`),
  ADD KEY `FK__txn_history__pair_id` (`pair_id`),
  ADD KEY `FK__txn_history__price_base_denom_ref_exch` (`price_base_denom_ref_exch`),
  ADD KEY `FK__txn_history__price_ref_exch` (`price_ref_exch`),
  ADD KEY `FK__txn_history__to_symbol` (`to_symbol`),
  ADD KEY `FK__txn_history__to_wallet_id` (`to_wallet_id`),
  ADD KEY `FK__txn_history__user` (`user`);

--
-- Indexes for table `txn_order`
--
ALTER TABLE `txn_order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `FK__txn_order__tactic_id` (`tactic_id`),
  ADD KEY `FK__txn_order__pair_id` (`pair_id`),
  ADD KEY `FK__txn_order__from_symbol` (`from_symbol`),
  ADD KEY `FK__txn_order__to_symbol` (`to_symbol`),
  ADD KEY `FK__txn_order__transaction_id` (`transaction_id`),
  ADD KEY `FK__txn_order__user` (`user`);

--
-- Indexes for table `usr_settings`
--
ALTER TABLE `usr_settings`
  ADD PRIMARY KEY (`user`),
  ADD KEY `FK__usr_id__sym_base` (`base_symbol`),
  ADD KEY `FK__usr_id__sym_denom` (`denom_symbol`);

--
-- Indexes for table `usr_wallet`
--
ALTER TABLE `usr_wallet`
  ADD PRIMARY KEY (`wallet_id`),
  ADD KEY `FK__usr_wallet__exchange` (`exchange`),
  ADD KEY `FK__usr_wallet__user` (`user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ana_price`
--
ALTER TABLE `ana_price`
  MODIFY `ana_price_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dat_price`
--
ALTER TABLE `dat_price`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_cycle_assets`
--
ALTER TABLE `inv_cycle_assets`
  MODIFY `cyc_ass_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_investment`
--
ALTER TABLE `inv_investment`
  MODIFY `investment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `met_cycle`
--
ALTER TABLE `met_cycle`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `met_purpose`
--
ALTER TABLE `met_purpose`
  MODIFY `purpose_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `str_condition_content`
--
ALTER TABLE `str_condition_content`
  MODIFY `condition_content_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `str_condition_indicator`
--
ALTER TABLE `str_condition_indicator`
  MODIFY `condition_indicator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `str_condition_tactic`
--
ALTER TABLE `str_condition_tactic`
  MODIFY `str_condition_tactic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `str_condition_time`
--
ALTER TABLE `str_condition_time`
  MODIFY `condition_time_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `str_plan`
--
ALTER TABLE `str_plan`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `str_tactic_alert`
--
ALTER TABLE `str_tactic_alert`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sym_pair`
--
ALTER TABLE `sym_pair`
  MODIFY `pair_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `txn_assets_investments`
--
ALTER TABLE `txn_assets_investments`
  MODIFY `ass_inv_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `txn_history`
--
ALTER TABLE `txn_history`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usr_wallet`
--
ALTER TABLE `usr_wallet`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ana_price`
--
ALTER TABLE `ana_price`
  ADD CONSTRAINT `FK__ana_price__indicator` FOREIGN KEY (`indicator`) REFERENCES `met_indicator` (`indicator`),
  ADD CONSTRAINT `FK__ana_price__price_id` FOREIGN KEY (`price_id`) REFERENCES `dat_price` (`price_id`);

--
-- Constraints for table `ana_tactic_external`
--
ALTER TABLE `ana_tactic_external`
  ADD CONSTRAINT `FK__ana_tactic_external__action_time_limit` FOREIGN KEY (`action_time_limit`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__ana_tactics_external__pair_id` FOREIGN KEY (`pair_id`) REFERENCES `sym_pair` (`pair_id`),
  ADD CONSTRAINT `FK__ana_tactics_external__status` FOREIGN KEY (`status`) REFERENCES `met_status` (`tactic_external`);

--
-- Constraints for table `dat_content`
--
ALTER TABLE `dat_content`
  ADD CONSTRAINT `FK__dat_content__resource_id` FOREIGN KEY (`resource_id`) REFERENCES `met_resource` (`resource_id`);

--
-- Constraints for table `dat_price`
--
ALTER TABLE `dat_price`
  ADD CONSTRAINT `FK__dat_price__pair_id` FOREIGN KEY (`pair_id`) REFERENCES `sym_pair` (`pair_id`);

--
-- Constraints for table `inv_cycle_assets`
--
ALTER TABLE `inv_cycle_assets`
  ADD CONSTRAINT `FK__inv_cycle_assets__ass_inv_id` FOREIGN KEY (`ass_inv_id`) REFERENCES `txn_assets_investments` (`ass_inv_id`),
  ADD CONSTRAINT `FK__inv_cycle_assets__cycle_id` FOREIGN KEY (`cycle_id`) REFERENCES `met_cycle` (`cycle_id`),
  ADD CONSTRAINT `FK__inv_cycle_assets__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);

--
-- Constraints for table `inv_investment`
--
ALTER TABLE `inv_investment`
  ADD CONSTRAINT `FK__inv_investment__open_transaction_id` FOREIGN KEY (`open_transaction_id`) REFERENCES `txn_history` (`transaction_id`),
  ADD CONSTRAINT `FK__inv_investment__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);

--
-- Constraints for table `str_condition_content`
--
ALTER TABLE `str_condition_content`
  ADD CONSTRAINT `FK__str_condition_content__content_id` FOREIGN KEY (`content_id`) REFERENCES `dat_content` (`content_id`),
  ADD CONSTRAINT `FK__str_condition_content__refresh` FOREIGN KEY (`refresh`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_condition_content__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`);

--
-- Constraints for table `str_condition_indicator`
--
ALTER TABLE `str_condition_indicator`
  ADD CONSTRAINT `FK__str_condition_indicator__indicator` FOREIGN KEY (`indicator`) REFERENCES `met_indicator` (`indicator`),
  ADD CONSTRAINT `FK__str_condition_indicator__pair_id` FOREIGN KEY (`pair_id`) REFERENCES `sym_pair` (`pair_id`),
  ADD CONSTRAINT `FK__str_condition_indicator__recency_min` FOREIGN KEY (`recency_min`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_condition_indicator__refresh` FOREIGN KEY (`refresh`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_condition_indicator__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`);

--
-- Constraints for table `str_condition_tactic`
--
ALTER TABLE `str_condition_tactic`
  ADD CONSTRAINT `FK__str_condition_tactic__condition_tactic_id` FOREIGN KEY (`condition_tactic_id`) REFERENCES `str_tactic` (`tactic_id`),
  ADD CONSTRAINT `FK__str_condition_tactic__refresh` FOREIGN KEY (`refresh`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_condition_tactic__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`);

--
-- Constraints for table `str_condition_time`
--
ALTER TABLE `str_condition_time`
  ADD CONSTRAINT `FK__str_condition_time__refresh` FOREIGN KEY (`refresh`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_condition_time__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`);

--
-- Constraints for table `str_plan`
--
ALTER TABLE `str_plan`
  ADD CONSTRAINT `FK__str_plan__purpose` FOREIGN KEY (`purpose`) REFERENCES `met_purpose` (`purpose`);

--
-- Constraints for table `str_tactic`
--
ALTER TABLE `str_tactic`
  ADD CONSTRAINT `FK__str_tactics__action` FOREIGN KEY (`action`) REFERENCES `met_action` (`action`),
  ADD CONSTRAINT `FK__str_tactics__action_time_limit` FOREIGN KEY (`action_time_limit`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__str_tactics__status` FOREIGN KEY (`status`) REFERENCES `met_status` (`tactic`),
  ADD CONSTRAINT `FK__str_tactics__strategy_id` FOREIGN KEY (`strategy_id`) REFERENCES `str_evaluation` (`strategy_id`);

--
-- Constraints for table `str_tactic_alert`
--
ALTER TABLE `str_tactic_alert`
  ADD CONSTRAINT `FK__str_tactic_alert__channel` FOREIGN KEY (`channel`) REFERENCES `met_channel` (`channel`),
  ADD CONSTRAINT `FK__str_tactic_alert__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`),
  ADD CONSTRAINT `FK__str_tactic_alert__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);

--
-- Constraints for table `sym_exchange`
--
ALTER TABLE `sym_exchange`
  ADD CONSTRAINT `FK__sym_exchange__network` FOREIGN KEY (`network`) REFERENCES `met_network` (`network`);

--
-- Constraints for table `sym_pair`
--
ALTER TABLE `sym_pair`
  ADD CONSTRAINT `FK__sym_pair__class` FOREIGN KEY (`class`) REFERENCES `met_sym_class` (`class`),
  ADD CONSTRAINT `FK__sym_pair__exchange` FOREIGN KEY (`exchange`) REFERENCES `sym_exchange` (`exchange`),
  ADD CONSTRAINT `FK__sym_pair__period` FOREIGN KEY (`period`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__sym_pair__refresh` FOREIGN KEY (`refresh`) REFERENCES `met_period` (`period`),
  ADD CONSTRAINT `FK__sym_pair__symbol_buy` FOREIGN KEY (`symbol_buy`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__sym_pair__symbol_sell` FOREIGN KEY (`symbol_sell`) REFERENCES `sym_unit` (`symbol`);

--
-- Constraints for table `sym_unit`
--
ALTER TABLE `sym_unit`
  ADD CONSTRAINT `FK__sym_unit__class` FOREIGN KEY (`class`) REFERENCES `met_sym_class` (`class`);

--
-- Constraints for table `txn_assets_investments`
--
ALTER TABLE `txn_assets_investments`
  ADD CONSTRAINT `FK__txn_assets_investments__ass_inv_par_id` FOREIGN KEY (`ass_inv_par_id`) REFERENCES `txn_assets_investments` (`ass_inv_id`),
  ADD CONSTRAINT `FK__txn_assets_investments__event` FOREIGN KEY (`event`) REFERENCES `met_event` (`assets_investments`),
  ADD CONSTRAINT `FK__txn_assets_investments__investment_id` FOREIGN KEY (`investment_id`) REFERENCES `inv_investment` (`investment_id`),
  ADD CONSTRAINT `FK__txn_assets_investments__symbol` FOREIGN KEY (`symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__txn_assets_investments__transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `txn_history` (`transaction_id`);

--
-- Constraints for table `txn_history`
--
ALTER TABLE `txn_history`
  ADD CONSTRAINT `FK__txn_history__event` FOREIGN KEY (`event`) REFERENCES `met_event` (`transaction`),
  ADD CONSTRAINT `FK__txn_history__exchange` FOREIGN KEY (`exchange`) REFERENCES `sym_exchange` (`exchange`),
  ADD CONSTRAINT `FK__txn_history__exchange_transaction_status` FOREIGN KEY (`exchange_transaction_status`) REFERENCES `met_status` (`transaction`),
  ADD CONSTRAINT `FK__txn_history__from_symbol` FOREIGN KEY (`from_symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__txn_history__from_wallet_id` FOREIGN KEY (`from_wallet_id`) REFERENCES `usr_wallet` (`wallet_id`),
  ADD CONSTRAINT `FK__txn_history__pair_id` FOREIGN KEY (`pair_id`) REFERENCES `sym_pair` (`pair_id`),
  ADD CONSTRAINT `FK__txn_history__price_base_denom_ref_exch` FOREIGN KEY (`price_base_denom_ref_exch`) REFERENCES `sym_exchange` (`exchange`),
  ADD CONSTRAINT `FK__txn_history__price_ref_exch` FOREIGN KEY (`price_ref_exch`) REFERENCES `sym_exchange` (`exchange`),
  ADD CONSTRAINT `FK__txn_history__to_symbol` FOREIGN KEY (`to_symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__txn_history__to_wallet_id` FOREIGN KEY (`to_wallet_id`) REFERENCES `usr_wallet` (`wallet_id`),
  ADD CONSTRAINT `FK__txn_history__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);

--
-- Constraints for table `txn_order`
--
ALTER TABLE `txn_order`
  ADD CONSTRAINT `FK__txn_order__from_symbol` FOREIGN KEY (`from_symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__txn_order__pair_id` FOREIGN KEY (`pair_id`) REFERENCES `sym_pair` (`pair_id`),
  ADD CONSTRAINT `FK__txn_order__tactic_id` FOREIGN KEY (`tactic_id`) REFERENCES `str_tactic` (`tactic_id`),
  ADD CONSTRAINT `FK__txn_order__to_symbol` FOREIGN KEY (`to_symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__txn_order__transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `txn_history` (`transaction_id`),
  ADD CONSTRAINT `FK__txn_order__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);

--
-- Constraints for table `usr_settings`
--
ALTER TABLE `usr_settings`
  ADD CONSTRAINT `FK__usr_setting__base_symbol` FOREIGN KEY (`base_symbol`) REFERENCES `sym_unit` (`symbol`),
  ADD CONSTRAINT `FK__usr_setting__denom_symbol` FOREIGN KEY (`denom_symbol`) REFERENCES `sym_unit` (`symbol`);

--
-- Constraints for table `usr_wallet`
--
ALTER TABLE `usr_wallet`
  ADD CONSTRAINT `FK__usr_wallet__exchange` FOREIGN KEY (`exchange`) REFERENCES `sym_exchange` (`exchange`),
  ADD CONSTRAINT `FK__usr_wallet__user` FOREIGN KEY (`user`) REFERENCES `usr_settings` (`user`);
COMMIT;
