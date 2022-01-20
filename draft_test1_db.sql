-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 21, 2022 at 12:01 AM
-- Server version: 10.2.41-MariaDB-cll-lve
-- PHP Version: 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `julesnet_vert_agent_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` int(11) NOT NULL,
  `purpose` enum('capital','investment','return') DEFAULT NULL,
  `asset` varchar(32) NOT NULL,
  `amount_held` decimal(40,20) NOT NULL,
  `proportion_held` decimal(21,20) NOT NULL DEFAULT 1.00000000000000000000,
  `exchange_held` varchar(32) NOT NULL,
  `tmp_class` enum('crypto','fiat','stock') DEFAULT NULL,
  `timestamp_held_first` bigint(20) DEFAULT NULL,
  `tmp_timestamp_held_last` bigint(20) DEFAULT NULL,
  `tmp_date` datetime DEFAULT NULL,
  `financial_year_held` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`asset_id`, `purpose`, `asset`, `amount_held`, `proportion_held`, `exchange_held`, `tmp_class`, `timestamp_held_first`, `tmp_timestamp_held_last`, `tmp_date`, `financial_year_held`) VALUES
(20, 'capital', 'AUD', 100.00000000000000000000, 0.00000000000000000000, 'cba', NULL, 1581018120000, 1581018120000, NULL, NULL),
(21, 'investment', 'AUD', 98.00000000000000000000, 0.00000000000000000000, 'swyftx', NULL, 1581018120000, 1581018120000, NULL, NULL),
(22, 'investment', 'USD', 62.09238474000000000000, 0.00000000000000000000, 'swyftx', NULL, 1581018120000, 1581054120000, NULL, NULL),
(23, 'investment', 'USDT', 61.00000000000000000000, 1.00000000000000000000, 'swyftx', NULL, 1581054120000, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `asset_investment`
--

CREATE TABLE `asset_investment` (
  `ass_inv_id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `asset_proportion` decimal(21,20) DEFAULT NULL,
  `asset_parent_id` int(11) DEFAULT NULL,
  `asset_parent_proportion` decimal(21,20) DEFAULT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `investment_proportion` decimal(21,20) DEFAULT NULL,
  `price_investment_asset` decimal(40,20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `asset_investment`
--

INSERT INTO `asset_investment` (`ass_inv_id`, `asset_id`, `asset_proportion`, `asset_parent_id`, `asset_parent_proportion`, `investment_id`, `investment_proportion`, `price_investment_asset`) VALUES
(30, 20, 1.00000000000000000000, NULL, NULL, 10, 1.00000000000000000000, 1.00000000000000000000),
(31, 21, 1.00000000000000000000, 20, 1.00000000000000000000, 131, 1.00000000000000000000, 1.00000000000000000000),
(32, 22, 1.00000000000000000000, 21, 1.00000000000000000000, 132, 1.00000000000000000000, 1.00000000000000000000),
(33, 23, 1.00000000000000000000, 22, 1.00000000000000000000, 133, 1.00000000000000000000, 1.00000000000000000000);

-- --------------------------------------------------------

--
-- Table structure for table `asset_pairs`
--

CREATE TABLE `asset_pairs` (
  `pair_id` int(11) NOT NULL,
  `pair` varchar(64) NOT NULL,
  `exchange` varchar(64) NOT NULL,
  `trade` tinyint(1) NOT NULL DEFAULT 0,
  `transfer` tinyint(1) NOT NULL DEFAULT 0,
  `class` enum('crypto','fiat','stock') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `investment_id` int(11) NOT NULL,
  `asset` varchar(32) NOT NULL,
  `amount` decimal(40,20) NOT NULL,
  `roi` decimal(40,20) NOT NULL DEFAULT 0.00000000000000000000
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `investments`
--

INSERT INTO `investments` (`investment_id`, `asset`, `amount`, `roi`) VALUES
(10, 'AUD', 100.00000000000000000000, 0.00000000000000000000);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `from_ass_inv_id` int(20) DEFAULT NULL,
  `to_ass_inv_id` int(20) DEFAULT NULL,
  `time_opened` bigint(20) DEFAULT NULL,
  `time_closed` bigint(20) DEFAULT NULL,
  `capital_fee` decimal(40,20) DEFAULT NULL,
  `purpose` enum('','trade','transfer in','transfer out','loan','loan repay','pool','fee') DEFAULT NULL,
  `exchange` char(64) DEFAULT NULL,
  `exchange_transaction_status` enum('trigger open','open','complete','cancelled','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
  `pair_id` int(10) DEFAULT NULL,
  `from_asset` char(64) DEFAULT NULL,
  `from_amount` decimal(40,20) DEFAULT NULL,
  `to_asset` char(64) DEFAULT NULL,
  `to_amount` decimal(40,20) DEFAULT NULL,
  `to_fee` decimal(40,20) DEFAULT NULL,
  `pair_price` decimal(40,20) DEFAULT NULL,
  `from_price_usd` decimal(40,20) DEFAULT NULL,
  `to_price_usd` decimal(40,20) DEFAULT NULL,
  `fee_amount_usd` decimal(40,20) DEFAULT NULL,
  `price_aud_usd` decimal(40,20) DEFAULT NULL,
  `from_wallet` char(128) DEFAULT NULL,
  `to_wallet` char(128) DEFAULT NULL,
  `percent_complete` decimal(23,20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `from_ass_inv_id`, `to_ass_inv_id`, `time_opened`, `time_closed`, `capital_fee`, `purpose`, `exchange`, `exchange_transaction_status`, `pair_id`, `from_asset`, `from_amount`, `to_asset`, `to_amount`, `to_fee`, `pair_price`, `from_price_usd`, `to_price_usd`, `fee_amount_usd`, `price_aud_usd`, `from_wallet`, `to_wallet`, `percent_complete`) VALUES
(131, 30, 31, NULL, 1581018120000, NULL, 'transfer in', 'swyftx', 'complete', NULL, 'AUD', 100.00000000000000000000, 'AUD', 98.00000000000000000000, 2.00000000000000000000, 1.00000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(132, 31, 32, NULL, 1581018120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'AUD', 98.00000000000000000000, 'USD', 62.09238474000000000000, 0.00000000000000000000, 0.63359576000000000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(133, 32, 33, NULL, 1581054120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'USD', 62.09238474000000000000, 'USDT', 61.00000000000000000000, 0.30500000000000000000, 0.98731914157755000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(134, NULL, NULL, NULL, 1581882120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'USDT', 61.00000000000000000000, 'ETH', 0.10000000000000000000, 0.00050000000000000000, 0.00164754098360660000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(135, NULL, NULL, NULL, 1582746120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'ETH', 0.10000000000000000000, 'USDT', 80.00000000000000000000, 0.40000000000000000000, 804.00000000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(136, NULL, NULL, NULL, 1583610120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'USDT', 80.00000000000000000000, 'USD', 75.00000000000000000000, 0.37500000000000000000, 0.94218750000000000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000),
(137, NULL, NULL, NULL, 1581054120000, NULL, 'trade', 'swyftx', 'complete', NULL, 'USD', 75.00000000000000000000, 'AUD', 110.00000000000000000000, 0.55000000000000000000, 1.47400000000000000000, NULL, NULL, NULL, NULL, NULL, NULL, 100.00000000000000000000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`);

--
-- Indexes for table `asset_investment`
--
ALTER TABLE `asset_investment`
  ADD PRIMARY KEY (`ass_inv_id`);

--
-- Indexes for table `asset_pairs`
--
ALTER TABLE `asset_pairs`
  ADD PRIMARY KEY (`pair_id`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`investment_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `asset_investment`
--
ALTER TABLE `asset_investment`
  MODIFY `ass_inv_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `asset_pairs`
--
ALTER TABLE `asset_pairs`
  MODIFY `pair_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `investment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
