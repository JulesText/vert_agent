<?php

/* process bot actions for trading tactics */

function actionable_tactics($config, $tactic_id = FALSE) {

	/* query actionable tactics */

	$values['filterquery'] = " WHERE status = 'actionable'";
	if ($tactic_id) $values['filterquery'] .= " AND tactic_id IN (" . $tactic_id . ")";
	$tactics = query('get_tactics', $config, $values);
	if ($config['debug']) { print_r($tactics); }

	# process each action
	if (!empty($tactics))
	foreach ($tactics as $t) {

		# check if time limit for executing action has been exceeded, for instance when order attempts have failed
		if ($t['currency'] + $t['action_time_limit'] * 60000 < $config['timestamp']) {

			$query = "
				UPDATE tactics SET
				status = 'failed',
				currency = " . $config['timestamp'] . ",
				WHERE tactic_id = " . $t['tactic_id']
			;
			query($query, $config);

			$config['chatText'] = 'actionable tactic unable to execute for tactic_id ' . $t['tactic_id'];
			telegram($config);

			continue 1;

		}

		$config['exchange'] = $t['exchange'];
		$config = config_exchange($config);

		# if action is to place an order
		if (in_array($t['action'], array('limit', 'market'), TRUE)) {

			# construct order
			$order_query = array(
				'exchange' => $t['exchange'],
				'symbol' => $t['pair_asset'],
				'orderType' => $t['action']
			);

			if (strpos($t['pair_asset'], $t['from_asset'] . '/') !== FALSE) {
				$order_query['side'] = 'sell';
			} else {
				$order_query['side'] = 'buy';
			}

			if ($order_query['orderType'] == 'limit') $order_query['orderPrice'] = $t['trade_price'];

			switch($t['exchange']) {

				case 'bitmax':
					# define response requested from exchange
					$order_query['respInst'] = 'ACCEPT';
				break;

				case 'okex':
					# 0: limit order (max price and quantity)
					# 1: market order (quantity only)
					# 2: fill or kill (ignore)
					# 3: immediate or cancel (ignore)
					if ($t['action'] == 'limit') $order_query['orderTypeCode'] = 0;
					if ($t['action'] == 'market') $order_query['orderTypeCode'] = 1;
				break;

			}

			//  check for available funds on okex, since we only expect orders on okex
			$config['exchange'] = 'okex';
			$config['method'] = 'GET';

// $config = config_exchange($config);
// $config['api_request'] = '/api/spot/v3/orders?instrument_id=ETH-USDT&state=7';
// $config['url'] .= $config['api_request'];
// $transactions = info($config);

			$config = config_exchange($config);
			$config['api_request'] = '/api/spot/v3/instruments';
			$config['url'] .= $config['api_request'];

			//find minimum amount, minimum size increment amount
			$result = info($config);
			$pair = array_values(array_filter($result, function ($currency_pair) use ($order_query) {
					return $currency_pair['instrument_id'] == str_replace('/', '-', $order_query['symbol']);
			}))[0];

			$config = config_exchange($config);
			$config['api_request'] = '/api/spot/v3/accounts';
			$config['url'] .= $config['api_request'];

			$result = info($config);
			$wallets = array_column($result, 'currency');
			$available = array_column($result, 'available');
			$balances = array_combine($wallets, $available);
			$walletBalance = $balances[$t['from_asset']];

			if (!empty($t['from_percent'])) {
					$order_query['orderQty'] = closest_multiple(($t['from_percent'] / 100) * $walletBalance, $pair['tick_size']);
			} elseif (!empty($t['from_amount'])) {
					$order_query['orderQty'] = $t['from_amount'];
			}

			if ($order_query['orderQty'] > $walletBalance) {
					//report wrong order amount
					$config['chatText'] = 'order attempt failed for tactic_id because transaction amount (' . floatval($order_query['orderQty']) . ') is more than available balance (' . floatval($walletBalance) . ')';
					telegram($config);
			} else if ($order_query['orderQty'] < $pair['min_size']) {
					//report wrong order amount
					$config['chatText'] = 'order attempt failed for tactic_id because transaction amount (' . rtrim($order_query['orderQty'], "0") . ') is less than min size for asset pair(' . rtrim($pair['min_size'], "0") . ')';
					telegram($config);
			} else if (($order_query['orderQty'] / $pair['tick_size']) % 1 != 0) {
					//report wrong order amount
					$config['chatText'] = 'order attempt failed for tactic_id because transaction amount (' . rtrim($order_query['orderQty'], "0") . ') failed min_increment for asset pair(' . rtrim($pair['size_increment'], "0") . ')';
					telegram($config);
			} else {

				$config = config_exchange($config);

				# place order
				$result = place_order($config, $order_query, $orderId = '');

				# process response of order placed success
				if ($result['res']['placed']) {

					# slight risk order is placed but response not received
					# could check number of open orders matches number in tactics and/or transactions
					# ignore risk for now

					# record order in transactions table
					$query = "
						INSERT INTO transactions
						(
						investment_id,
						strategy_id,
						time_opened,
						time_closed,
						percent_complete,
						exchange,
						exchange_transaction_id,
						pair_asset,
						pair_price
						)
						VALUES
						(
						'0',
						'" . $t['strategy_id'] . "',
						'0',
						'0',
						'0',
						'" . $t['exchange'] . "',
						'" . $result['res']['exchange_transaction_id'] . "',
						'" . $order_query['symbol'] . "',
						'0'
						)
					";
					query($query, $config);

					# record order in tactics table
					$query = "
						SELECT transaction_id FROM transactions
						WHERE exchange = '" . $t['exchange'] . "'
						AND exchange_transaction_id = '" . $result['res']['exchange_transaction_id'] . "'
					";
					$id = query($query, $config);

					$query = "
						UPDATE tactics SET
						status = 'ordered',
						currency = " . $config['timestamp'] . ",
						transaction_id = " . $id[0]['transaction_id'] . "
						WHERE tactic_id = " . $t['tactic_id']
					;
					query($query, $config);

					# note that we can leave order history to update itself

				} else {

					# process response of order placed failure
					# if an order fails send a message to telegram
					# next time actionable_tactics function is called, bot will try to place order again
					$config['chatText'] = 'order attempt failed for tactic_id ' . $t['tactic_id'] . ", reason: " . $result['res']['message'];
					telegram($config);

				}

			}

		}


		/* if action is to delete order */
		if ($t['action'] == 'delete') {

			/* get tactic's order */
			$values['filterquery'] = " WHERE transaction_id = '" . $t['transaction_id'] . "'";
			$transaction = query('get_transactions', $config, $values);

			/* delete order */
			$order_query = array();
			#$order['id'] = $t['transaction_id']; # optional, for echo back
			$order_query['orderId'] = $transaction[0]['exchange_transaction_id'];
			$order_query['symbol'] = $transaction[0]['pair_asset'];

			$result = cancel_order($config, $order_query);

			# process response of order deleted success
			if ($result['res']['placed']) {

				$transaction = check_transaction($config, $t['transaction_id']);

				if ($transaction !== FALSE && $transaction['exchange_transaction_status'] == 'cancelled') {

					$query = "
						UPDATE tactics SET
						status = 'inactive',
						currency = " . $config['timestamp'] . ",
						action = 'none'
						WHERE tactic_id = " . $t['tactic_id']
					;
					query($query, $config);

				}

			} else {

				# process response of order deleted failure
				$config['chatText'] = 'delete order attempt failed for tactic_id ' . $t['tactic_id'];
				telegram($config);

			}

		}

	}

}

/* test conditions for tactics and update status if conditions met */

function conditional_tactics($config, $tactic_id = FALSE) {

		$subquery = "";
		if ($tactic_id) $subquery = " AND tactic_id IN (" . $tactic_id . ")";

		/* query current tactics */
		$values['filterquery'] = "
			WHERE status = 'conditional'
			AND currency < (" . $config['timestamp'] . " - refresh * 60000)
			" . $subquery . "
			ORDER BY condition_tactic DESC
		";
		$tactics = query('get_tactics', $config, $values);
		if ($config['debug']) print_r($tactics);

		# process each tactic
		if (!empty($tactics))
		foreach ($tactics as $t) {

			$update = FALSE;

			/*
			notes:
			there are 3 possible conditions, recorded in tactics table in database:
				time delay condition (condition_time)
				dependent tactic condition (condition_tactic)
				pair price indicator condition (condition_pair_*)
			condition_time requires that a fixed amount of time has passed
			condition_tactic requires that another tactic status has changed to 'executed'
			condition_pair_* requires that a pair price indicator value passes a threshold value
			all 3 conditions need to be met for tactic status to change from 'conditional' to 'actionable'
			when a condition is met, its test variable is set to 1
			if a condition is not yet met, its test variable is set to 0
			the test variables are:
				condition_time_test
				condition_tactic_test
				condition_pair_test
			if any conditions are not used for a tactic, the test variable is set to 1 by default
			*/

			# test time delay condition (condition_time), if not yet satisfied (condition_time_test=0)
			if (!$t['condition_time_test']) {
				if ($t['condition_time'] < $config['timestamp']) {
					$t['condition_time_test'] = 1;
					$update = TRUE;
					if ($config['debug']) echo 'condition_time_test positive' . PHP_EOL;
				}
			}

			# test dependent tactic condition (condition_tactic), if not yet satisfied (condition_tactic_test=0)
			# and time delay condition already satisfied
			if (!$t['condition_tactic_test'] && $t['condition_time_test']) {
				$values['filterquery'] = " WHERE tactic_id = " . $t['condition_tactic'];
				$res = query('get_tactics', $config, $values);
				if ($res[0]['status'] == 'executed') {
					$t['condition_tactic_test'] = 1;
					$update = TRUE;
					if ($config['debug']) echo 'condition_tactic_test positive' . PHP_EOL;
				} elseif ($res[0]['status'] == 'inactive' || $res[0]['status'] == 'failed') {
					# if dependent tactic is inactive or failed, change status
					/* change tactic status */
					$values['tactic_id'] = $res[0]['tactic_id'];
					$values['field'] = 'status';
					$values['value'] = 'inactive';
					query('update_tactic', $config, $values);
					continue 1;
				}
			}

			# test pair price indicator condition (condition_pair_*), if not yet satisfied (condition_pair_test=0)
			# and time delay and dependent tactic conditions already satisfied
			if (!$t['condition_pair_test'] && $t['condition_time_test'] && $t['condition_tactic_test']) {

				# query the most recent price and calculate indicators first
				price_recent($config, $t['condition_pair_id']);
				technical_analysis($config, $t['condition_pair_id']);

				# assess threshold value
				$query = "SELECT * FROM asset_pairs WHERE pair_id = " . $t['condition_pair_id'];
				$res = query($query, $config);
				$a = $res[0];
				$currency_min = $config['timestamp'] - $t['condition_pair_currency_min'] * 60000;
				if ($config['debug']) echo 'condition_pair_test if ' . $a['currency'] . ' >= ' . $currency_min . PHP_EOL;
				# check if the asset_pair is current enough, also depends on asset_pair refresh rate
				if ($a['currency'] >= $currency_min) {
					$query = "
						SELECT history_id FROM price_history
						WHERE pair = '" . $a['pair'] . "'
						AND source = '" . $a['source'] . "'
						AND period = '" . $config['period'][$a['period']] . "'
						AND timestamp = " . $a['currency'] . "
						AND " . $t['condition_pair_indicator'] . $t['condition_pair_value_operand'] . $t['condition_pair_value']
					;
					$res = query($query, $config);
					if ($config['debug']) echo 'condition_pair_test: ' . $query . PHP_EOL;
					# if any result was returned from the sql query this proves the condition has been met
					if (!empty($res)) {
						$t['condition_pair_test'] = 1;
						$update = TRUE;
						if ($config['debug']) echo 'condition_pair_test positive' . PHP_EOL;
					}
				}
			}

			# test if all 3 condition tests have been met, and if the status has changed ($update=TRUE)
			if (
				$t['condition_pair_test'] &&
				$t['condition_time_test'] &&
				$t['condition_tactic_test'] &&
				$update
			) {
				if ($config['debug']) echo 'status change' . PHP_EOL;
				if ($t['action'] == 'none') {
					$t['status'] = 'executed';
				} else {
					$t['status'] = 'actionable';
					$config['chatText'] =
						'trade ready, attempting to ' . $t['action'] .
						' order for ' . $t['from_asset'] . ' ' . $t['to_asset'] .
						' on ' . $t['exchange'];
					telegram($config);
				}
			}

			# update the tactics status if necessary
			if ($update) {
				$query = "
					UPDATE tactics SET
					status = '" . $t['status'] . "'
					,condition_tactic_test = " . $t['condition_tactic_test'] . "
					,condition_time_test = " . $t['condition_time_test'] . "
					,condition_pair_test = " . $t['condition_pair_test'] . "
					,currency = " . $config['timestamp'] . "
					WHERE tactic_id = " . $t['tactic_id']
				;
				query($query, $config);
				if ($config['debug']) echo 'update record' . PHP_EOL;
			}
		}
}

/* check order status for tactics with placed orders and update tactic status if order is complete */

function ordered_tactics($config, $tactic_id = FALSE) {

	/* query tactics */
	$subquery = "";
	if ($tactic_id) $subquery = " AND tactic_id IN (" . $_GET['tactic_id'] . ")";

	$values['filterquery'] = "
		WHERE status = 'ordered'
		AND currency < (" . $config['timestamp'] . " - refresh * 60000)" .
		$subquery
	;
	if ($tactic_id) $values['filterquery'] .= " AND tactic_id IN (" . $tactic_id . ")";
	$tactics = query('get_tactics', $config, $values);
	if ($config['debug']) { print_r($tactics); }

	if (!empty($tactics))
	foreach ($tactics as $t) {

		/* get tactic's order */
		if (!empty($t['transaction_id'])) {

			$order = check_transaction($config, $t['transaction_id']);

			if ($order === FALSE)
				continue 1;

		} else {

			# process response of transaction_id not recorded
			$config['chatText'] = 'query tactic transaction_id attempt failed for tactic_id ' . $t['tactic_id'];
			telegram($config);

			continue 1;

		}

		if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($order); }

		$update = FALSE;

		/* check if order completed */
		if ($order['exchange_transaction_status'] == 'complete') {

			$values['value'] = 'executed';
			$update = TRUE;

		}

		/* check if order cancelled */
		if ($order['exchange_transaction_status'] == 'cancelled') {

			$values['value'] = 'inactive';
			$update = TRUE;

		}

		/* process update */
		if ($update) {

			/* change tactic status */
			$values['tactic_id'] = $t['tactic_id'];
			$values['field'] = 'status';
			query('update_tactic', $config, $values);

			/* query any conditional tactics */
			$values['filterquery'] = "
				WHERE status = 'conditional'
				AND condition_tactic = '" . $t['tactic_id'] . "'"
			;
			$triggers = query('get_tactics', $config, $values);
			if ($config['debug']) print_r($triggers);
			if (!empty($triggers))
				foreach ($triggers as $trigger)
					conditional_tactics($config, $trigger['tactic_id']);

		}

	}

}
