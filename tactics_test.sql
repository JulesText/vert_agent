
--
-- Dumping data for table `tactics`
--

INSERT INTO `tactics` (`tactic_id`, `strategy_id`, `status`, `refresh`, `currency`, `action_time_limit`, `condition_time_test`, `condition_time`, `condition_tactic_test`, `condition_tactic`, `condition_pair_test`, `condition_pair_id`, `condition_pair_currency_min`, `condition_pair_indicator`, `condition_pair_value_operand`, `condition_pair_value`, `action`, `exchange`, `pair_asset`, `from_asset`, `from_amount`, `from_percent`, `to_asset`, `trade_price`, `to_fee_max`, `transaction_id`) VALUES
(200, 0, 'conditional', 5, 1614242938000, 1440, 0, 1614253738000, 1, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'market', 'okex', 'WBTC/ETH', 'ETH', 0.04350000000000000000, NULL, 'WBTC', NULL, NULL, NULL),
(201, 0, 'conditional', 5, 1614242938000, 1440, 1, NULL, 0, 200, 1, NULL, NULL, NULL, NULL, NULL, 'market', 'okex', 'WBTC/ETH', 'WBTC', 0.00150000000000000000, NULL, 'ETH', NULL, NULL, NULL),
(202, 0, 'conditional', 5, 1614242938000, 1440, 1, NULL, 1, NULL, 0, 26, 1, 'rsi24', '>=', 60.00000000000000000000, 'market', 'okex', 'WBTC/ETH', 'ETH', 0.02000000000000000000, NULL, 'WBTC', NULL, NULL, NULL),
(203, 0, 'conditional', 5, 1614242938000, 1440, 0, 1614253738000, 0, 202, 0, 26, 1, 'rsi24', '<=', 90.00000000000000000000, 'market', 'okex', 'WBTC/ETH', 'WBTC', 0.00070000000000000000, NULL, 'ETH', NULL, NULL, NULL);

-- 200 waits for the time 1614253738000 to pass, then places order
-- 201 waits for tactic 200 to execute, then places order
-- 202 waits until the indicator rsi24 to pass above 60, for the asset_pair 26 (ETH/USDT), then places order
-- 203 waits for all of:
--      time 1614253738000 to pass
--      tactic 202 to execute
--      indicator rsi24 to pass below 90 for the asset_pair 26 (ETH/USDT)
--    then places order

-- `strategy_id` is not important
-- `status` will change from:
--      conditional to actionable when all conditions are met (ie conditions = 1)
--      actionable to ordered when order is placed
--      ordered to executed when order is completed
-- `refresh` is set to 5 minutes, to check tactic status and conditions no more than every 5 minutes
-- `currency` is the last time the tactic status and conditions were checked
-- `action_time_limit` is set to default 1440, so error message is generated and tactic cancelled if it has remained actionable for more than 1440 minutes (1 day)
-- `condition_time_test` is set to 0 if waiting for time to arrive, otherwise 1
-- `condition_time` is set to a point in the future (you can change this if necessary for testing)
-- `condition_tactic_test` is set to 0 if waiting for another tactic to execute, otherwise 1
-- `condition_tactic` indicates other tactic to wait for
-- `condition_pair_test` is set to 0 if waiting for a price indicator to pass a threshold, otherwise 1
-- `condition_pair_id`, `condition_pair_currency_min`, `condition_pair_indicator`, `condition_pair_value_operand`, `condition_pair_value`, these define the price indicator
-- `action`, `exchange`, `pair_asset`, `from_asset`, `from_amount`, `from_percent`, `to_asset`, `trade_price`, `to_fee_max`, these define the order, though note order is optional, some tactics will not have any order and only serve as dependency for another tactic
-- `transaction_id` captures the transaction_id (order id) once it is placed
