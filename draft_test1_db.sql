-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 10, 2021 at 04:33 PM
-- Server version: 10.2.40-MariaDB-cll-lve
-- PHP Version: 7.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `julesnet_vert_agent_test2`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets_investments`
--

CREATE TABLE `assets_investments` (
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
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `event` varchar(16) NOT NULL,
  `assets_investments` varchar(16) DEFAULT NULL,
  `transaction` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('asset merge', 'asset merge', NULL);
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('asset split', 'asset split', NULL);
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('fee', 'fee', 'fee');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('investment close', 'investment close', NULL);
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('investment open', 'investment open', NULL);
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('loan', 'loan', 'loan');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('loan repay', 'loan repay', 'loan repay');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('pool', 'pool', 'pool');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('pool close', 'pool close', 'pool close');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('trade', 'trade', 'trade');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('transfer', 'transfer', NULL);
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('transfer in', NULL, 'transfer in');
INSERT INTO `event` (`event`, `assets_investments`, `transaction`) VALUES('transfer out', NULL, 'transfer out');

-- --------------------------------------------------------

--
-- Table structure for table `exchange`
--

CREATE TABLE `exchange` (
  `exchange` varchar(16) NOT NULL,
  `network` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `exchange`
--

INSERT INTO `exchange` (`exchange`, `network`) VALUES('cba', 'bank');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('ascendex', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('bitmax', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('hotbit', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('liquid', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('okex', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('okex_margin', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('okex_spot', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('swyftx', 'centralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('coingecko', 'decentralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('idex', 'decentralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('twelve', 'decentralised');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('curve', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('ethereum', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('etherscan', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('friend', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('kyber', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('metamask', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('myetherwallet', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('token sets', 'ethereum');
INSERT INTO `exchange` (`exchange`, `network`) VALUES('uniswap', 'ethereum');

-- --------------------------------------------------------

--
-- Table structure for table `interval`
--

CREATE TABLE `interval` (
  `interval` int(11) NOT NULL,
  `code` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `interval`
--

INSERT INTO `interval` (`interval`, `code`) VALUES(0, NULL);
INSERT INTO `interval` (`interval`, `code`) VALUES(1, '1m');
INSERT INTO `interval` (`interval`, `code`) VALUES(3, '3m');
INSERT INTO `interval` (`interval`, `code`) VALUES(5, '5m');
INSERT INTO `interval` (`interval`, `code`) VALUES(15, '15m');
INSERT INTO `interval` (`interval`, `code`) VALUES(30, '30m');
INSERT INTO `interval` (`interval`, `code`) VALUES(45, '45m');
INSERT INTO `interval` (`interval`, `code`) VALUES(60, '1h');
INSERT INTO `interval` (`interval`, `code`) VALUES(120, '2h');
INSERT INTO `interval` (`interval`, `code`) VALUES(240, '4h');
INSERT INTO `interval` (`interval`, `code`) VALUES(360, '6h');
INSERT INTO `interval` (`interval`, `code`) VALUES(720, '12h');
INSERT INTO `interval` (`interval`, `code`) VALUES(1440, '1d');
INSERT INTO `interval` (`interval`, `code`) VALUES(10080, '1w');
INSERT INTO `interval` (`interval`, `code`) VALUES(40320, '4w');

-- --------------------------------------------------------

--
-- Table structure for table `investment`
--

CREATE TABLE `investment` (
  `investment_id` int(11) NOT NULL,
  `user` varchar(32) DEFAULT NULL,
  `open_transaction_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `investment`
--

INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(1, 'JK', 208);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(2, 'JK', 209);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(3, 'JK', 214);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(4, 'JK', 220);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(5, 'JK', 228);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(6, 'JK', 229);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(7, 'JK', 241);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(8, 'JK', 242);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(9, 'JK', 304);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(10, 'JK', 455);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(11, 'JK', 456);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(12, 'JK', 457);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(13, 'JK', 458);
INSERT INTO `investment` (`investment_id`, `user`, `open_transaction_id`) VALUES(14, 'JK', 459);

-- --------------------------------------------------------

--
-- Table structure for table `network`
--

CREATE TABLE `network` (
  `network` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `network`
--

INSERT INTO `network` (`network`) VALUES('bank');
INSERT INTO `network` (`network`) VALUES('centralised');
INSERT INTO `network` (`network`) VALUES('decentralised');
INSERT INTO `network` (`network`) VALUES('ethereum');

-- --------------------------------------------------------

--
-- Table structure for table `period`
--

CREATE TABLE `period` (
  `period_id` int(11) NOT NULL,
  `time_open` bigint(20) NOT NULL,
  `time_close` bigint(20) NOT NULL,
  `label` varchar(32) DEFAULT NULL,
  `time_zone` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `period`
--

INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(19, 1561903200000, 1593525599999, '2019-20-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(20, 1593525600000, 1625061599999, '2020-21-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(21, 1625061600000, 1656597599999, '2021-22-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(22, 1656597600000, 1688133599999, '2022-23-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(23, 1688133600000, 1719755999999, '2023-24-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(24, 1719756000000, 1751291999999, '2024-25-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(25, 1751292000000, 1782827999999, '2025-26-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(26, 1782828000000, 1814363999999, '2026-27-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(27, 1814364000000, 1845986399999, '2027-28-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(28, 1845986400000, 1877522399999, '2028-29-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(29, 1877522400000, 1909058399999, '2029-30-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(30, 1909058400000, 1940594399999, '2030-31-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(31, 1940594400000, 1972216799999, '2031-32-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(32, 1972216800000, 2003752799999, '2032-33-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(33, 2003752800000, 2035288799999, '2033-34-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(34, 2035288800000, 2066824799999, '2034-35-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(35, 2066824800000, 2098447199999, '2035-36-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(36, 2098447200000, 2129983199999, '2036-37-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(37, 2129983200000, 2161519199999, '2037-38-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(38, 2161519200000, 2193055199999, '2038-39-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(39, 2193055200000, 2224677599999, '2039-40-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(40, 2224677600000, 2256213599999, '2040-41-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(41, 2256213600000, 2287749599999, '2041-42-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(42, 2287749600000, 2319285599999, '2042-43-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(43, 2319285600000, 2350907999999, '2043-44-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(44, 2350908000000, 2382443999999, '2044-45-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(45, 2382444000000, 2413979999999, '2045-46-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(46, 2413980000000, 2445515999999, '2046-47-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(47, 2445516000000, 2477138399999, '2047-48-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(48, 2477138400000, 2508674399999, '2048-49-FY', '+10:00');
INSERT INTO `period` (`period_id`, `time_open`, `time_close`, `label`, `time_zone`) VALUES(49, 2508674400000, 2540210399999, '2049-50-FY', '+10:00');

-- --------------------------------------------------------

--
-- Table structure for table `periods_assets`
--

CREATE TABLE `periods_assets` (
  `per_ass_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `ass_inv_id` int(11) NOT NULL,
  `user` varchar(32) NOT NULL,
  `open_base_amount` decimal(40,20) NOT NULL,
  `close_base_amount` decimal(40,20) NOT NULL,
  `base_amount_cgtd` decimal(40,20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `status` varchar(16) NOT NULL,
  `tactic` varchar(16) DEFAULT NULL,
  `tactic_external` varchar(16) DEFAULT NULL,
  `transaction` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('actionable', 'actionable', 'actionable', NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('cancelled', NULL, NULL, 'cancelled');
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('complete', NULL, NULL, 'complete');
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('conditional', 'conditional', NULL, NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('executed', 'executed', NULL, NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('failed', 'failed', NULL, NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('inactive', 'inactive', 'inactive', NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('included', NULL, 'included', NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('open', NULL, NULL, 'open');
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('ordered', 'ordered', NULL, NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('processing', NULL, 'processing', NULL);
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('trigger open', NULL, NULL, 'trigger open');
INSERT INTO `status` (`status`, `tactic`, `tactic_external`, `transaction`) VALUES('unconfirmed', NULL, NULL, 'unconfirmed');

-- --------------------------------------------------------

--
-- Table structure for table `symbol`
--

CREATE TABLE `symbol` (
  `symbol` varchar(16) NOT NULL,
  `class` enum('fiat','crypto') DEFAULT NULL,
  `base` tinyint(1) NOT NULL DEFAULT 0,
  `denom` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `symbol`
--

INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ADA', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ALGO', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('AUD', 'fiat', 1, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('BTC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('CRV', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('CVP', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('CVP-ETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('DAI', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('DAI-USDC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('DAI-USDT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ERG', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ETHMACOAPY', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ETHRSI6040', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ETHRSIAPY', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('EWT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('HNS', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('IOTA', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('KDA', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('KNC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('LINK', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('LTC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('MATIC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ONT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('QASH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('SHIP', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('STAKE', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('STAKE-ETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('STAKE-USDT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('SUSDV2', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('UBT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('UBT-ETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('UNI', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('USD', 'fiat', 0, 1);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('USDC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('USDC-USDT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('USDT', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('veCRV', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('WBTC', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('WETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('XRP', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('YFII', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('YFII-ETH', 'crypto', 0, 0);
INSERT INTO `symbol` (`symbol`, `class`, `base`, `denom`) VALUES('ZRX', 'crypto', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `symbol_class`
--

CREATE TABLE `symbol_class` (
  `class` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `symbol_class`
--

INSERT INTO `symbol_class` (`class`) VALUES('crypto');
INSERT INTO `symbol_class` (`class`) VALUES('fiat');
INSERT INTO `symbol_class` (`class`) VALUES('stock');

-- --------------------------------------------------------

--
-- Table structure for table `symbol_pairs`
--

CREATE TABLE `symbol_pairs` (
  `pair_id` int(11) NOT NULL,
  `symbol_buy` varchar(16) NOT NULL,
  `symbol_sell` varchar(16) NOT NULL,
  `exchange` varchar(16) NOT NULL,
  `collect` tinyint(1) NOT NULL DEFAULT 0,
  `analyse` tinyint(1) NOT NULL DEFAULT 0,
  `trade` tinyint(1) NOT NULL DEFAULT 0,
  `transfer` tinyint(1) NOT NULL DEFAULT 0,
  `leverage` decimal(5,3) NOT NULL DEFAULT 0.000,
  `class` varchar(8) NOT NULL,
  `interval` int(8) NOT NULL DEFAULT 1440,
  `refresh` int(8) NOT NULL DEFAULT 1440,
  `currency_start` bigint(20) NOT NULL DEFAULT 0,
  `currency_end` bigint(20) NOT NULL DEFAULT 0,
  `history_start` bigint(20) NOT NULL DEFAULT 0,
  `history_end` bigint(20) NOT NULL DEFAULT 1999999999999
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `symbol_pairs`
--

INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(1, 'AUD', 'USD', 'twelve', 1, 1, 0, 0, 0.000, 'fiat', 1440, 10080, 1135900800000, 1626998400000, 1136077200000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(26, 'ETH', 'USDT', 'okex_margin', 1, 1, 1, 0, 0.000, 'crypto', 60, 30, 1569888000000, 1629010800000, 1569888000000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(651, 'ETH', 'USDT', 'okex_spot', 1, 1, 1, 0, 0.000, 'crypto', 1440, 30, 1569945600000, 1628956800000, 1569888000000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(653, 'MATIC', 'USDT', 'okex_spot', 1, 1, 1, 0, 0.000, 'crypto', 1440, 30, 1616947200000, 1628956800000, 1587052800000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(654, 'BTC', 'USDT', 'okex_spot', 1, 1, 1, 0, 0.000, 'crypto', 1440, 30, 1587052800000, 1628956800000, 1587052800000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(655, 'ETH', 'USDT', 'okex_spot', 1, 1, 0, 0, 0.000, 'crypto', 240, 5, 1625284800000, 1629000000000, 1625282637340, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(656, 'BTC', 'USDT', 'okex_margin', 1, 1, 1, 0, 0.000, 'crypto', 240, 5, 1625284800000, 1629000000000, 1625282637340, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(657, 'BTC', 'USDT', 'ascendex', 1, 1, 0, 0, 0.000, 'crypto', 240, 5, 1625284800000, 1629000000000, 1625282637340, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(661, 'IOTA', 'USDT', 'okex_spot', 1, 1, 1, 0, 0.000, 'crypto', 1440, 1440, 1587052800000, 1628956800000, 1587052800000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(665, 'ADA', 'USDT', 'okex_margin', 1, 1, 1, 0, 2.000, 'crypto', 1440, 1440, 1587052800000, 1628956800000, 1587052800000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(668, 'LINK', 'USDT', 'okex_margin', 1, 1, 1, 0, 1.000, 'crypto', 1440, 1440, 1587052800000, 1628956800000, 1587052800000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(669, 'ETH', 'USDT', 'okex_spot', 1, 1, 0, 0, 0.000, 'crypto', 240, 5, 1628409600000, 1629000000000, 1628401993264, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(670, 'BTC', 'USDT', 'okex_spot', 1, 1, 0, 0, 0.000, 'crypto', 240, 5, 1628409600000, 1629000000000, 1628401993264, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(671, 'BTC', 'USDT', 'ascendex', 1, 1, 0, 0, 0.000, 'crypto', 240, 5, 1628409600000, 1629000000000, 1628401993264, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(674, 'ETH', 'USDT', 'okex_spot', 1, 1, 0, 0, 0.000, 'crypto', 1440, 30, 0, 0, 1569888000000, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(676, 'MATIC', 'USDT', 'okex_margin', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(677, 'STAKE', 'USDT', 'bitmax', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(678, 'ETH', 'USD', 'swyftx', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(679, 'veCRV', 'CRV', 'curve', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(680, 'susdv2', 'USDT', 'curve', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(681, 'DAI', 'USDC', 'curve', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(682, 'DAI', 'USDT', 'curve', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(683, 'STAKE', 'ETH', 'friend', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(684, 'KDA', 'CVP', 'friend', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(685, 'KDA', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(686, 'HNS', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(687, 'ETH', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(688, 'BTC', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(689, 'ERG', 'BTC', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(690, 'YFII', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(691, 'MATIC', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(692, 'STAKE', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(693, 'MATIC', 'BTC', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(694, 'UBT', 'USDT', 'hotbit', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(695, 'SHIP', 'ETH', 'idex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(696, 'ETH', 'USDC', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(697, 'KNC', 'ETH', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(698, 'KNC', 'USDC', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(699, 'ETH', 'USDT', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(700, 'WETH', 'ETH', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(701, 'UBT', 'USDT', 'kyber', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(702, 'ETH', 'USDC', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(703, 'EWT', 'ETH', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(704, 'BTC', 'USDC', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(705, 'ETH', 'USD', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(706, 'QASH', 'ETH', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(707, 'QASH', 'USD', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(708, 'XRP', 'USD', 'liquid', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(709, 'ETH', 'USDC', 'myetherwallet', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(710, 'WBTC', 'USDC', 'myetherwallet', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(717, 'LINK', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(718, 'ONT', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(719, 'IOTA', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(720, 'ALGO', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(721, 'LTC', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(722, 'ETH', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(723, 'UNI', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(724, 'CVP', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(725, 'WBTC', 'ETH', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(726, 'ZRX', 'ETH', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(727, 'UNI', 'ETH', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(728, 'MATIC', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(729, 'BTC', 'USDT', 'okex', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(734, 'LTC', 'USDT', 'okex_margin', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(740, 'USDC', 'USD', 'swyftx', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(741, 'BTC', 'USD', 'swyftx', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(742, 'USD', 'USDC', 'swyftx', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(743, 'USDT', 'USD', 'swyftx', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(744, 'ETHRSIAPY', 'ETH', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(745, 'ETHRSIAPY', 'USDC', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(746, 'ETHMACOAPY', 'ETH', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(747, 'ETHMACOAPY', 'USDC', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(748, 'ETHRSIAPY', 'LINK', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(749, 'ETHRSI6040', 'ETH', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(750, 'ETHRSI6040', 'USDC', 'token sets', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(751, 'USDC-USDT', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(752, 'UBT-ETH', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(753, 'UBT-ETH', 'UBT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(754, 'STAKE-ETH', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(755, 'STAKE-ETH', 'STAKE', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(756, 'YFII-ETH', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(757, 'YFII-ETH', 'YFII', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(758, 'CVP-ETH', 'CVP', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(759, 'CVP-ETH', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(760, 'STAKE-USDT', 'STAKE', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(761, 'STAKE-USDT', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(762, 'CVP', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(763, 'DAI-USDC', 'DAI', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(764, 'DAI-USDC', 'USDC', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(765, 'DAI-USDT', 'DAI', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(766, 'DAI-USDT', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(767, 'USDC', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(768, 'ETH', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(769, 'STAKE', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(770, 'STAKE', 'USDC', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(771, 'ETH', 'USDC', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(772, 'YFII', 'ETH', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(774, 'STAKE', 'UNI', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(775, 'CRV', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(776, 'STAKE', 'USDT', 'uniswap', 0, 0, 1, 0, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(777, 'STAKE', 'STAKE', 'bitmax', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(778, 'USDT', 'USDT', 'bitmax', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(779, 'USDT', 'USDT', 'hotbit', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(780, 'UBT', 'UBT', 'hotbit', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(781, 'YFII', 'YFII', 'hotbit', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(782, 'STAKE', 'STAKE', 'hotbit', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(783, 'ETH', 'ETH', 'idex', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(784, 'EWT', 'EWT', 'liquid', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(785, 'USDC', 'USDC', 'liquid', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(786, 'ETH', 'ETH', 'liquid', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(787, 'LINK', 'LINK', 'okex', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(788, 'CVP', 'CVP', 'okex', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(790, 'ETH', 'ETH', 'okex', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(791, 'USDT', 'USDT', 'okex', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(792, 'AUD', 'USD', 'swyftx', 0, 0, 0, 1, 0.000, 'fiat', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(793, 'ETH', 'ETH', 'swyftx', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(794, 'USDC', 'USDC', 'swyftx', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);
INSERT INTO `symbol_pairs` (`pair_id`, `symbol_buy`, `symbol_sell`, `exchange`, `collect`, `analyse`, `trade`, `transfer`, `leverage`, `class`, `interval`, `refresh`, `currency_start`, `currency_end`, `history_start`, `history_end`) VALUES(798, 'USDT', 'USDT', 'swyftx', 0, 0, 0, 1, 0.000, 'crypto', 0, 0, 0, 0, 0, 1999999999999);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transaction_id` int(11) NOT NULL,
  `time_opened` bigint(20) DEFAULT NULL,
  `time_closed` bigint(20) DEFAULT NULL,
  `event` varchar(16) DEFAULT NULL,
  `exchange` varchar(16) DEFAULT NULL,
  `exchange_transaction_id` varchar(64) DEFAULT NULL,
  `exchange_transaction_status` varchar(16) DEFAULT 'unconfirmed',
  `percent_complete` decimal(23,20) DEFAULT NULL,
  `recorded` tinyint(1) NOT NULL DEFAULT 0,
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

--
-- Dumping data for table `transaction`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user`) VALUES('AK');
INSERT INTO `user` (`user`) VALUES('JB');
INSERT INTO `user` (`user`) VALUES('JK');
INSERT INTO `user` (`user`) VALUES('joint');

-- --------------------------------------------------------

--
-- Table structure for table `wallet`
--

CREATE TABLE `wallet` (
  `wallet_id` int(11) NOT NULL,
  `address` varchar(128) DEFAULT NULL,
  `label` varchar(16) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `exchange` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wallet`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets_investments`
--
ALTER TABLE `assets_investments`
  ADD PRIMARY KEY (`ass_inv_id`),
  ADD KEY `FK_ass_inv_par_id_assets_investments` (`ass_inv_par_id`),
  ADD KEY `FK_transaction_id_transaction` (`transaction_id`),
  ADD KEY `FK_investment_id_investment` (`investment_id`),
  ADD KEY `FK_event_assets_investments_event` (`event`),
  ADD KEY `FK_assets_investments_symbol` (`symbol`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`event`),
  ADD UNIQUE KEY `asset_investment` (`assets_investments`),
  ADD UNIQUE KEY `transaction` (`transaction`);

--
-- Indexes for table `exchange`
--
ALTER TABLE `exchange`
  ADD PRIMARY KEY (`exchange`),
  ADD KEY `FK_network_exchange_network` (`network`);

--
-- Indexes for table `interval`
--
ALTER TABLE `interval`
  ADD PRIMARY KEY (`interval`);

--
-- Indexes for table `investment`
--
ALTER TABLE `investment`
  ADD PRIMARY KEY (`investment_id`),
  ADD KEY `FK_investment_user` (`user`),
  ADD KEY `FK_open_transaction_id_investment_transaction` (`open_transaction_id`) USING BTREE;

--
-- Indexes for table `network`
--
ALTER TABLE `network`
  ADD PRIMARY KEY (`network`);

--
-- Indexes for table `period`
--
ALTER TABLE `period`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `periods_assets`
--
ALTER TABLE `periods_assets`
  ADD PRIMARY KEY (`per_ass_id`),
  ADD KEY `FK_period_id_periods_assets` (`period_id`) USING BTREE,
  ADD KEY `FK_ass_inv_id_periods_assets` (`ass_inv_id`) USING BTREE,
  ADD KEY `FK_user_periods_assets` (`user`) USING BTREE;

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status`),
  ADD UNIQUE KEY `transaction` (`transaction`) USING BTREE,
  ADD UNIQUE KEY `tactic` (`tactic`),
  ADD UNIQUE KEY `tactic_external` (`tactic_external`);

--
-- Indexes for table `symbol`
--
ALTER TABLE `symbol`
  ADD PRIMARY KEY (`symbol`);

--
-- Indexes for table `symbol_class`
--
ALTER TABLE `symbol_class`
  ADD PRIMARY KEY (`class`);

--
-- Indexes for table `symbol_pairs`
--
ALTER TABLE `symbol_pairs`
  ADD PRIMARY KEY (`pair_id`),
  ADD KEY `FK_exchange_symbol_pairs` (`exchange`),
  ADD KEY `FK_symbol_buy_pairs_symbol` (`symbol_buy`),
  ADD KEY `FK_symbol_sell_pairs_symbol` (`symbol_sell`),
  ADD KEY `FK_class_symbol_pairs_class` (`class`),
  ADD KEY `FK_interval_symbol_pairs_interval` (`interval`),
  ADD KEY `FK_refresh_symbol_pairs_interval` (`refresh`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `FK_exchange_transaction` (`exchange`),
  ADD KEY `FK_from_symbol_symbol` (`from_symbol`),
  ADD KEY `FK_to_symbol_symbol` (`to_symbol`),
  ADD KEY `FK_from_wallet_id_wallet` (`from_wallet_id`),
  ADD KEY `FK_to_wallet_id_wallet` (`to_wallet_id`),
  ADD KEY `FK_transaction_status` (`exchange_transaction_status`),
  ADD KEY `FK_transaction_event` (`event`),
  ADD KEY `FK_pair_id_transaction_symbol_pairs_exchange_transaction` (`pair_id`),
  ADD KEY `FK_price_ref_exch_transaction_exchange` (`price_ref_exch`),
  ADD KEY `FK_price_base_denom_ref_exch_transaction_exchange` (`price_base_denom_ref_exch`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user`);

--
-- Indexes for table `wallet`
--
ALTER TABLE `wallet`
  ADD PRIMARY KEY (`wallet_id`),
  ADD KEY `FK_wallet_user` (`user`) USING BTREE,
  ADD KEY `FK_wallet_exchange` (`exchange`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets_investments`
--
ALTER TABLE `assets_investments`
  MODIFY `ass_inv_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investment`
--
ALTER TABLE `investment`
  MODIFY `investment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `period`
--
ALTER TABLE `period`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `periods_assets`
--
ALTER TABLE `periods_assets`
  MODIFY `per_ass_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `symbol_pairs`
--
ALTER TABLE `symbol_pairs`
  MODIFY `pair_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=799;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1151;

--
-- AUTO_INCREMENT for table `wallet`
--
ALTER TABLE `wallet`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets_investments`
--
ALTER TABLE `assets_investments`
  ADD CONSTRAINT `FK_ass_inv_par_id_assets_investments` FOREIGN KEY (`ass_inv_par_id`) REFERENCES `assets_investments` (`ass_inv_id`),
  ADD CONSTRAINT `FK_assets_investments_symbol` FOREIGN KEY (`symbol`) REFERENCES `symbol` (`symbol`),
  ADD CONSTRAINT `FK_event_assets_investments_event` FOREIGN KEY (`event`) REFERENCES `event` (`assets_investments`),
  ADD CONSTRAINT `FK_investment_id_investment` FOREIGN KEY (`investment_id`) REFERENCES `investment` (`investment_id`),
  ADD CONSTRAINT `FK_transaction_id_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction` (`transaction_id`);

--
-- Constraints for table `exchange`
--
ALTER TABLE `exchange`
  ADD CONSTRAINT `FK_network_exchange_network` FOREIGN KEY (`network`) REFERENCES `network` (`network`);

--
-- Constraints for table `investment`
--
ALTER TABLE `investment`
  ADD CONSTRAINT `FK_investment_user` FOREIGN KEY (`user`) REFERENCES `user` (`user`),
  ADD CONSTRAINT `FK_transaction_id_investment_transaction` FOREIGN KEY (`open_transaction_id`) REFERENCES `transaction` (`transaction_id`);

--
-- Constraints for table `periods_assets`
--
ALTER TABLE `periods_assets`
  ADD CONSTRAINT `FK_ass_inv_id` FOREIGN KEY (`ass_inv_id`) REFERENCES `assets_investments` (`ass_inv_id`),
  ADD CONSTRAINT `FK_period_id` FOREIGN KEY (`period_id`) REFERENCES `period` (`period_id`),
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user`) REFERENCES `user` (`user`);

--
-- Constraints for table `symbol_pairs`
--
ALTER TABLE `symbol_pairs`
  ADD CONSTRAINT `FK_class_symbol_pairs_class` FOREIGN KEY (`class`) REFERENCES `symbol_class` (`class`),
  ADD CONSTRAINT `FK_exchange_symbol_pairs` FOREIGN KEY (`exchange`) REFERENCES `exchange` (`exchange`),
  ADD CONSTRAINT `FK_interval_symbol_pairs_interval` FOREIGN KEY (`interval`) REFERENCES `interval` (`interval`),
  ADD CONSTRAINT `FK_refresh_symbol_pairs_interval` FOREIGN KEY (`refresh`) REFERENCES `interval` (`interval`),
  ADD CONSTRAINT `FK_symbol_buy_pairs_symbol` FOREIGN KEY (`symbol_buy`) REFERENCES `symbol` (`symbol`),
  ADD CONSTRAINT `FK_symbol_sell_pairs_symbol` FOREIGN KEY (`symbol_sell`) REFERENCES `symbol` (`symbol`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `FK_exchange_transaction` FOREIGN KEY (`exchange`) REFERENCES `exchange` (`exchange`),
  ADD CONSTRAINT `FK_from_symbol_symbol` FOREIGN KEY (`from_symbol`) REFERENCES `symbol` (`symbol`),
  ADD CONSTRAINT `FK_from_wallet_id_wallet` FOREIGN KEY (`from_wallet_id`) REFERENCES `wallet` (`wallet_id`),
  ADD CONSTRAINT `FK_pair_id_transaction_symbol_pairs_exchange_transaction` FOREIGN KEY (`pair_id`) REFERENCES `symbol_pairs` (`pair_id`),
  ADD CONSTRAINT `FK_price_base_denom_ref_exch_transaction_exchange` FOREIGN KEY (`price_base_denom_ref_exch`) REFERENCES `exchange` (`exchange`),
  ADD CONSTRAINT `FK_price_ref_exch_transaction_exchange` FOREIGN KEY (`price_ref_exch`) REFERENCES `exchange` (`exchange`),
  ADD CONSTRAINT `FK_to_symbol_symbol` FOREIGN KEY (`to_symbol`) REFERENCES `symbol` (`symbol`),
  ADD CONSTRAINT `FK_to_wallet_id_wallet` FOREIGN KEY (`to_wallet_id`) REFERENCES `wallet` (`wallet_id`),
  ADD CONSTRAINT `FK_transaction_event` FOREIGN KEY (`event`) REFERENCES `event` (`transaction`),
  ADD CONSTRAINT `FK_transaction_status` FOREIGN KEY (`exchange_transaction_status`) REFERENCES `status` (`transaction`);

--
-- Constraints for table `wallet`
--
ALTER TABLE `wallet`
  ADD CONSTRAINT `FK_user_wallet` FOREIGN KEY (`user`) REFERENCES `user` (`user`),
  ADD CONSTRAINT `FK_wallet_exchange` FOREIGN KEY (`exchange`) REFERENCES `exchange` (`exchange`);
COMMIT;
