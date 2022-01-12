<?php

/* process bot actions for trading tactics with actionable order instructions  */

function actionable_tactics($config, $tactic_ids = FALSE) {

	$response = $config['response'];

	/* query actionable tactics */

	$values['filterquery'] = " WHERE t.status = 'actionable'";
	if ($tactic_ids) $values['filterquery'] .= " AND t.tactic_id IN (" . $tactic_ids . ")";
	$tactics = query('select_tactics', $config, $values);
	if ($config['debug']) $response['msg'] .= var_export($tactics, TRUE);

	# process each tactic
	if (empty($tactics)) $response['msg'] = 'no tactics found' . PHP_EOL;
	else
	foreach ($tactics as $t) {

		# check if time limit for executing action has been exceeded
		# for instance when order attempts have repeatedly failed
		if (
			$t['currency'] + $t['action_time_limit'] * 60000 < $config['timestamp']
			|| array_search($t['exchange'], $config['exchanges_trade']) === FALSE
		) {

			$query = "
				UPDATE tactics SET
				status = 'failed'
				WHERE tactic_id = " . $t['tactic_id']
			;
			query($query, $config);

			$response['result'][$t['tactic_id']] = 'failed';
			$response['msg'] .= 'actionable tactic unable to execute for tactic_id ' . $t['tactic_id'] . ' on ' . $t['exchange'] . ', tactic set to failed' . PHP_EOL;
			$response['alert'] = TRUE;

			continue 1;

		}

		$config['exchange'] = $t['exchange'];

		# if action is to place an order
		if (in_array($t['action'], $config['order_types'], TRUE)) {

			# construct order
			$order = array(
				'tactic_id' => $t['tactic_id'],
				'exchange' => $t['exchange'],
				'symbol' => $t['pair'],
				'type' => $t['action'],
				'from' => $t['from_asset'],
				'price' => $t['trade_price'],
				'qty_pct' => $t['from_percent'],
				'qty' => $t['from_amount'],
				'side' => NULL
			);

			$response_x = prepare_order($config, $order);

			if ($response_x['error']) {

				$response['msg'] .= $response_x['msg'];
				$response['alert'] = TRUE;
				continue 1;

			} else {

				$order = $response_x['result'];

				# order passed above validation tests, now place order
				$config = config_exchange($config);
				$response_x = place_order($config, $order, $orderId = '');

				# process response of order placed success
				if ($response_x['result']['placed']) {

					# record order in transactions table
					$values = array();
					if (!is_null($t['strategy_id'])) $values['strategy_id'] = $t['strategy_id'];
					$values['exchange'] = $t['exchange'];
					$values['exchange_transaction_id'] = $response_x['result']['exchange_transaction_id'];
					$values['pair'] = $t['pair'];
					$values['tactic_id'] = $t['tactic_id'];
					$values['purpose'] = 'trade';
					switch ($t['exchange']) {
						case 'okex_spot':
						case 'okex_margin':
							$values['from_wallet'] = $t['exchange'];
							$values['to_wallet'] = $t['exchange'];
						break;
					}
					query('insert_transaction', $config, $values);
					$id = mysqli_insert_id($config['sql_link']);

					# record order in tactics table
					$query = "
						UPDATE tactics SET
						status = 'ordered',
						currency = " . milliseconds() . ",
						transaction_id = {$id}
						WHERE tactic_id = {$t['tactic_id']}
					";
					$res = query($query, $config);
					# if unable to update tactics execute controlled stop on all order tactics
					if ($res !== 1) {
						$actions = implode("','", $config['order_types']);
						$query = "
							UPDATE tactics SET
							status = 'failed'
							WHERE action IN ('{$actions}')
							AND status IN ('actionable', 'conditional')
						";
						$res = query($query, $config);
						$response['msg'] .= "failed to update tactic order, set {$res} pending tactic orders to status = failed";
						$response['alert'] = TRUE;
						break;
					}

					$response['msg'] .= "order placed for tactic_id {$t['tactic_id']} on {$t['exchange']} for {$values['pair']} with id {$values['exchange_transaction_id']}" . PHP_EOL;
					$response['result'][$t['tactic_id']] = 'ordered';
					$response['alert'] = TRUE;

				} else {

					# process response of order placed failure
					# next time actionable_tactics function is called, bot will try to place order again
					$response['msg'] .= 'order attempt failed for tactic_id ' . $t['tactic_id'] . ", reason: " . $response_x['result']['message'];
					$response['alert'] = TRUE;

				}

			}

		}


		/* if action is to delete order */
		else if ($t['action'] == 'delete') {

			/* get tactic's order */
			$values['filterquery'] = " WHERE transaction_id = '" . $t['transaction_id'] . "'";
			$transaction = query('select_transactions', $config, $values);

			/* delete order */
			$order = array();
			$order['exchange'] = $transaction[0]['exchange'];
			$order['orderId'] = $transaction[0]['exchange_transaction_id'];
			$order['symbol'] = $transaction[0]['pair'];

			$response_x = cancel_order($config, $order);

			# process response of order deleted success
			if ($response_x['result']['placed']) {

				$response_y = check_transaction($config, $t['transaction_id']);
				process($response_y, $config);

				if (!$response_y['error'] && $response_y['result']['exchange_transaction_status'] == 'cancelled') {

					$query = "
						UPDATE tactics SET
						status = 'inactive',
						currency = " . $config['timestamp'] . ",
						WHERE tactic_id = " . $t['tactic_id']
					;
					query($query, $config);

					$response['msg'] .= 'order deletion confirmed for tactic_id ' . $t['tactic_id'] . PHP_EOL;
					$response['result'][$t['tactic_id']] = 'inactive';

				} else {

					$query = "
						UPDATE tactics SET
						status = 'ordered',
						currency = " . $config['timestamp'] . ",
						WHERE tactic_id = " . $t['tactic_id']
					;
					query($query, $config);
					$response['msg'] .= 'warning: order deletion request placed for tactic_id ' . $t['tactic_id'] .
					' and accepted by exchange but order not showing as cancelled yet' . PHP_EOL;
					$response['alert'] = TRUE;
					$response['result'][$t['tactic_id']] = 'ordered';

				}

			} else {

				# process response of order deleted failure
				$response['msg'] .= "delete order attempt failed for tactic_id {$t['tactic_id']}, reason: " . $response_x['result']['message'];;
				$response['alert'] = TRUE;

			}

		}

		/* if action is to loan */
		else if ($t['action'] == 'loan') {

			# construct loan
			$loan = array(
				'tactic_id' => $t['tactic_id'],
				'pair_id' => $t['pair_id'],
				'exchange' => $t['exchange'],
				'pair' => $t['pair'],
				'from' => $t['from_asset'],
				'percent' => $t['from_percent'],
				'amount' => $t['from_amount'],
				'amount_available' => NULL,
				'leverage_asset_max' => $t['leverage'],
				'leverage_available' => NULL
			);

			$response_x = prepare_loan($config, $loan);

			if ($response_x['error']) {

				$response['msg'] .= $response_x['msg'];
				$response['alert'] = TRUE;
				continue 1;

			} else {

				$loan = $response_x['result'];

				# loan passed above validation tests, now place loan
				$config = config_exchange($config);
				$response_x = place_loan($config, $loan, $loanId = '');

				# process response of order placed success
				if ($response_x['result']['placed']) {

					# record loan in transactions table
					$values = array();
					if (!is_null($t['strategy_id'])) $values['strategy_id'] = $t['strategy_id'];
					$values['exchange'] = $t['exchange'];
					$values['exchange_transaction_id'] = $response_x['result']['exchange_transaction_id'];
					$values['pair'] = $t['pair'];
					$values['tactic_id'] = $t['tactic_id'];
					$values['purpose'] = 'loan';
					switch ($t['exchange']) {
						case 'okex_margin':
							$values['from_wallet'] = $t['exchange'];
							$values['to_wallet'] = $t['exchange'];
						break;
					}
					query('insert_transaction', $config, $values);
					$id = mysqli_insert_id($config['sql_link']);

					# record loan in tactics table
					$query = "
						UPDATE tactics SET
						status = 'executed',
						currency = " . milliseconds() . ",
						transaction_id = {$id}
						WHERE tactic_id = {$t['tactic_id']}
					";
					$res = query($query, $config);
					# if unable to update tactics execute controlled stop on all loan tactics
					if ($res !== 1) {
						$query = "
							UPDATE tactics SET
							status = 'failed'
							WHERE action = 'loan'
							AND status IN ('actionable', 'conditional')
						";
						$res = query($query, $config);
						$response['msg'] .= "failed to update tactic loan, set {$res} pending tactic loans to status = failed";
						$response['alert'] = TRUE;
						break;
					}

					$response['msg'] .= "loan placed for tactic_id {$t['tactic_id']} on {$t['exchange']} for {$values['pair']} with id {$values['exchange_transaction_id']}" . PHP_EOL;
					$response['result'][$t['tactic_id']] = 'executed';
					$response['alert'] = TRUE;

				} else {

					# process response of loan placed failure
					# next time actionable_tactics function is called, bot will try to place loan again
					$response['msg'] .= 'loan attempt failed for tactic_id ' . $t['tactic_id'] . ", reason: " . $response_x['result']['message'];
					$response['alert'] = TRUE;

				}

			}

		}

	}

	return $response;

}

/* test conditions for tactics and update status if conditions met */

function conditional_tactics($config, $tactic_ids = FALSE) {

	$response = $config['response'];

	$subquery = "";
	if ($tactic_ids) $subquery = " AND t.tactic_id IN (" . $tactic_ids . ")";

	/* query current tactics */
	$values['filterquery'] = "
		WHERE t.status = 'conditional'
		AND t.currency < (" . $config['timestamp'] . " - t.refresh * 60000)
		" . $subquery . "
		ORDER BY t.condition_tactic DESC
	";
	$tactics = query('select_tactics', $config, $values);
	if ($config['debug']) $response['msg'] .= var_export($tactics, TRUE);

	# process each tactic
	foreach ($tactics as $t) {

		$response['msg'] .= PHP_EOL . 'processed tactic_id ' . $t['tactic_id'] . PHP_EOL;

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
			$past = $config['timestamp'] - $t['condition_time'];
			if ($past > 0) {
				$t['condition_time_test'] = 1;
				$update = TRUE;
				$response['msg'] .= "positive ";
			} else {
				$response['msg'] .= "negative ";
			}
			$response['msg'] .= "condition_time_test for tactic_id {$t['tactic_id']}, " . (ceil($past / 60000)) . " minutes past " . unixtime_datetime($t['condition_time']) . PHP_EOL;
		}

		# test dependent tactic condition (condition_tactic), if not yet satisfied (condition_tactic_test=0)
		# and time delay condition already satisfied
		if (!$t['condition_tactic_test'] && $t['condition_time_test']) {

			$values['filterquery'] = " WHERE t.tactic_id = " . $t['condition_tactic'];
			$res = query('select_tactics', $config, $values);
			if ($res[0]['status'] == 'executed') {
				$t['condition_tactic_test'] = 1;
				$update = TRUE;
				$response['msg'] .= "positive ";
			} else if ($res[0]['status'] == 'inactive' || $res[0]['status'] == 'failed') {
				# if dependent tactic is inactive or failed, change status
				/* change tactic status */
				$values = array();
				$values['tactic_id'] = $res[0]['tactic_id'];
				$values['status'] = 'inactive';
				$res = query('update_tactic', $config, $values);
				if ($res === 1) {
					$response['msg'] .= 'set tactic inactive due to impossible ';
					$response['result'][$values['tactic_id']] = 'inactive';
					$response['alert'] = TRUE;
				}
			} else {
				$response['msg'] .= "negative ";
			}
			$response['msg'] .= "condition_tactic_test for tactic_id {$t['tactic_id']}, dependent upon tactic_id {$t['condition_tactic']}" . PHP_EOL;
			if (!$update) continue 1;
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
			# check if the asset_pair is current enough, also depends on asset_pair refresh rate
			if ($a['currency_end'] >= $currency_min) {
				$test = $t['condition_pair_indicator'] . ' ' . $t['condition_pair_value_operand'] . ' ' . ($t['condition_pair_value'] + 0);
				$query = "
					SELECT history_id FROM price_history
					WHERE pair_id = '" . $a['pair_id'] . "'
					AND timestamp = " . $a['currency_end'] . "
					AND NOT ISNULL({$t['condition_pair_value']})
					AND {$test}"
				;
				$res = query($query, $config);
				if ($config['debug']) $response['msg'] .= 'condition_pair_test: ' . $query . PHP_EOL;
				# if any result was returned from the sql query this proves the condition has been met
				if (!empty($res)) {
					$t['condition_pair_test'] = 1;
					$update = TRUE;
					$response['alert'] = TRUE;
					$response['msg'] .= "positive ";
				} else {
					$response['msg'] .= "negative ";
				}
				$response['msg'] .=
					'condition_pair_test for ' .
					$test .
					PHP_EOL .
					'positive '
				;
			} else {
				$response['msg'] .=
					'negative ';
				;
			}
			$response['msg'] .= 'currency test for asset_pair to pass threshold of ' .
				unixtime_datetime($a['currency_end']) .
				' < ' .
				$t['condition_pair_currency_min'] .
				' minutes old' .
				PHP_EOL
			;
		}

		# test if all 3 condition tests have been met, and the status has changed ($update=TRUE)
		if (
			$t['condition_pair_test'] &&
			$t['condition_time_test'] &&
			$t['condition_tactic_test'] &&
			$update
		) {
			if ($t['action'] == 'none') {
				$t['status'] = 'executed';
			} else if ($t['action'] == 'alert') {
				$response['alert'] = TRUE;
				$t['status'] = 'executed';
			} else {
				$t['status'] = 'actionable';
				if (!is_null($t['from_amount'])) $amount = ($t['from_amount'] + 0) . ' ';
				else if (!is_null($t['from_percent'])) $amount = ($t['from_percent'] + 0) . '% of available ';
				$response['msg'] .=
					'action ready for ' . $t['action'] . ' order ';
				if ($t['action'] == 'market')
					$response['msg'] .= 'for ' . $amount . $t['from_asset'] . ' to ' . $t['to_asset'] .
						' on ' . $t['exchange'] . PHP_EOL
					;
				if ($t['action'] == 'limit')
					$response['msg'] .= 'for ' . $amount . $t['from_asset'] . ' to ' . $t['to_asset'] .
						' at ' . ($t['trade_price'] + 0) .
						' on ' . $t['exchange'] . PHP_EOL
					;
				$response['alert'] = TRUE;
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
			$res = query($query, $config);
			if ($res === 1) {
				$response['msg'] .= 'status updated to ' . $t['status'] . ' for tactic_id ' . $t['tactic_id'] . PHP_EOL;
				$response['result'][$t['tactic_id']] = $t['status'];
			} else {
				$response['msg'] .= 'unable to update status to ' . $t['status'] . ' for tactic_id ' . $t['tactic_id'] . PHP_EOL;
				$response['alert'] = TRUE;
			}
		}

	}

	if (empty($tactics)) {
		$response['msg'] .= PHP_EOL . 'no tactics found' . PHP_EOL;
	} else {
		$response['msg'] .= PHP_EOL . count($tactics) . ' tactics found, ';
		if (empty($response['result']))
			$response['msg'] .= '0 updated' . PHP_EOL;
		else
			$response['msg'] .= count($response['result']) . ' updated' . PHP_EOL;
	}

	return $response;

}

/* check order status for tactics with placed orders and update tactic status if order is complete */

function ordered_tactics($config, $tactic_ids = FALSE) {

	$response = $config['response'];

	/* query tactics */
	$values['filterquery'] = " WHERE t.status = 'ordered'";
	if ($tactic_ids) $values['filterquery'] .= " AND t.tactic_id IN (" . $tactic_ids . ")";
	$tactics = query('select_tactics', $config, $values);
	if ($config['debug']) $response['msg'] .= var_export($tactics, TRUE);

	if (empty($tactics)) $response['msg'] = 'no tactics found' . PHP_EOL;
	else
	foreach ($tactics as $t) {

		/* get tactic's order */
		if (!empty($t['transaction_id'])) {

			$response_x = check_transaction($config, $t['transaction_id']);
			process($response_x, $config);

			# transaction does not exist
			if ($response_x['error']) {
				$response['msg'] .= "query tactic transaction_id {$t['transaction_id']} attempt failed for tactic_id {$t['tactic_id']}" . PHP_EOL;
				$response['alert'] = TRUE;
				continue 1;
			}

			$order = $response_x['result'];

		} else {

			# process response of transaction_id not recorded
			$response['msg'] .= 'query transaction_id (NULL) attempt failed for tactic_id ' . $t['tactic_id'] . PHP_EOL;
			$response['alert'] = TRUE;
			continue 1;

		}

		if ($config['debug']) $response['msg'] .= PHP_EOL . var_export($order, TRUE);

		$update = FALSE;
		$values = array();

		/* check if order completed */
		if ($order['exchange_transaction_status'] == 'complete') {

			$values['status'] = 'executed';
			$update = TRUE;

		}

		/* check if order cancelled */
		if ($order['exchange_transaction_status'] == 'cancelled') {

			$values['status'] = 'inactive';
			$update = TRUE;

		}

		/* process update */
		if ($update) {

			/* change tactic status */
			$values['tactic_id'] = $t['tactic_id'];
			$res = query('update_tactic', $config, $values);
			if ($res === 1) {
				$response['msg'] .= 'status updated to ' . $values['status'] . ' for tactic_id ' . $t['tactic_id'] . PHP_EOL;
				$response['result'][$t['tactic_id']] = $values['status'];
			} else {
				$response['msg'] .= 'could not update status to ' . $values['status'] . ' for tactic_id ' . $t['tactic_id'] . PHP_EOL;
				$response['alert'] = TRUE;
				continue 1;
			}

		}

	}

	return $response;

}

/* query any conditional tactics */

function trigger_tactics($config, $tactic_ids) {

	$response = $config['response'];

	$values['filterquery'] = "
		WHERE t.status = 'conditional'
		AND t.condition_tactic IN (" . $tactic_ids . ")"
	;
	$triggers = query('select_tactics', $config, $values);
	if ($config['debug']) $response['msg'] .= var_export($triggers, TRUE);
	foreach ($triggers as $trigger) {
		$response['count']++;
		array_push($response['result'], $trigger['tactic_id']);
	}
	$response['msg'] .= $response['count'] . ' trigger tactics found' . PHP_EOL;

	return $response;

}

/* check for new external trading bot tactics actions */

function check_tactics_ext($config) {

	$response = $config['response'];

	// query channel for all messages
	// no way with bot api to filter getUpdates call
	// server only returns messages in the past 24 hours
	$config['url'] = $config['chat_url'] . '/getUpdates';
	$response_x = query_api($config, $default_headers = TRUE);

	$i = 0;
	$j = 0;
	$tactics = array();

	if ($response_x['ok'])
	foreach ($response_x['result'] as $message) {

		// limit to channel messages
		if (!isset($message['channel_post'])) continue;
		// limit to nominated channels
		$channel = array_search($message['channel_post']['chat']['id'], $config['channels']);
		if ($channel === FALSE) continue;
		$i++;

		$result = map_tactics_ext($channel, $message, $config);

		if (!$result['error']) array_push($tactics, $result['tactic']);

	}

	// process result
	if (count($tactics) > 0) {

		// dedupe any repeated tactics returned from channel, retain oldest post
		foreach ($tactics as $key => $t) {
			$tactics[$key]['order_string'] = $t['pair'] . '_' . $t['side'] . '_' . $t['entry'] . '_target_' . $t['target'] . '_stop_' . $t['stop'];
		}
		foreach ($tactics as $key => $t) {
			foreach ($tactics as $d) {
				if ($t['order_string'] === $d['order_string'] && $t['channel_post_id'] > $d['channel_post_id'] && isset($tactics[$key])) {
					unset($tactics[$key]);
					$j++;
				}
			}
		}

		// pull in any existing external tactics
		$order_strings = array_column($tactics, 'order_string');
		$order_strings = implode($order_strings, '","');
		$query = 'SELECT * FROM tactics_external WHERE order_string IN ("' . $order_strings . '") ';
		$tactics_exist = query($query, $config);

		// drop any tactics existing in database
		// this should block any repeat orders
		foreach ($tactics_exist as $d) {
			foreach ($tactics as $key => $t) {
				if ($t['order_string'] === $d['order_string'] && isset($tactics[$key])) {
					unset($tactics[$key]);
					$j++;
				}
			}
		}

		// insert any new tactics to database
		foreach ($tactics as $values) query('insert_tactic_external', $config, $values);

		# to do:  add count for num of tactics inserted, throw error message

	}

	$response['count'] = count($tactics);

	$response['msg'] = "processed {$i} messages on channel, found {$response['count']} new tactics, {$j} duplicate tactics";

	return $response;

}

/* map text for channel */

function map_tactics_ext($channel, $message, $config) {

	$t = array();
	$text = explode(PHP_EOL, $message['channel_post']['text']);

	$result = array('error' => 1);

	if ($channel == 'vert_agent_channel') {

		foreach ($text as $line) {
			if (strpos($line, '#') === 0) $t['pair'] = substr($line, 1);
			else if (!strpos($line, ' = ') === FALSE) {
				$keyval = explode(' = ', $line);
				$t[$keyval[0]] = $keyval[1];
			}
		}

		$t = array_change_key_case($t, CASE_LOWER);

		if (
				isset($t['pair']) &&
				isset($t['leverage']) &&
				(isset($t['short']) || isset($t['long'])) &&
				isset($t['target']) &&
				isset($t['stop']) &&
				isset($t['time frame'])
			) {

			if (isset($t['short'])) {
				$t['entry'] = $t['short'];
				$t['side'] = 'short';
			}
			if (isset($t['long'])) {
				$t['entry'] = $t['long'];
				$t['side'] = 'long';
			}

			$tactic = array(
				'status' => 'processing',
				'timestamp' => $message['channel_post']['date'] * 1000,
				'action_time_limit' => $config['channel_time_limit'],
				'channel_post_id' => $message['update_id'],
				'channel' => $message['channel_post']['sender_chat']['username'],
				'pair' => $t['pair'],
				'side' => $t['side'],
				'entry' => $t['entry'],
				'target' => $t['target'],
				'stop' => $t['stop'],
				'leverage' => str_replace('x', '', $t['leverage'])
			);

			$result['error'] = 0;
			$result['tactic'] = $tactic;

		}

	}

	return $result;

}

/* process any new external trading bot tactics actions for possible trading */

function process_tactics_ext($config) {

	$response = $config['response'];

	$query = 'SELECT * FROM tactics_external WHERE status = "processing"';
	$tactics = query($query, $config);

	foreach ($tactics as $t) {

		$response['count']++;

		// run tests to confirm if valid order
		$actionable = TRUE;

		// check tactic is current within action_time_limit
		if ($t['action_time_limit'] * 60000 + $t['timestamp'] < milliseconds()) {
			$response['msg'] .= 'external tactic ' . $t['tactic_ext_id'] . ' not processed within action_time_limit';
			$actionable = FALSE;
		}

		// check symbol is tradeable
		if ($actionable) {

			$query = '
				SELECT * FROM asset_pairs
				WHERE pair = "' . $t['pair'] . '"
				AND trade
				AND exchange IN ("' . implode($config['exchanges_trade'], '","') . '")
				ORDER BY leverage DESC
			';
			$asset_pairs = query($query, $config);
			if (empty($asset_pairs)) {
				$response['msg'] .= 'external tactic ' . $t['tactic_ext_id'] . ' has no tradeable asset_pair';
				$actionable = FALSE;
			}

			// process each tradeable asset pair
			$pair_id = FALSE;
			foreach ($asset_pairs as $asset_pair) {

				// check available funds
				// isn't this a test in tactics?
				if (0) {
					continue;
				}

				// check entry price is within 10% of actual price, i.e. not farcical
				if (0) {
					continue;
				}

				// check entry price allows at least 75% of predicted margin, i.e. not missed the price
				if (0) {
					continue;
				}

				// if passes all tests then select and stop processing
				$pair_id = $asset_pair['pair_id'];
				break;

			}
			if (!$pair_id) $actionable = FALSE;

		}

		// set inactive if any checks failed
		if (!$actionable) {

			$response['alert'] = TRUE;
			$response['msg'] .= ', for ' . $t['order_string'] . PHP_EOL;

			$query = 'UPDATE tactics_external SET status = "inactive" WHERE tactic_ext_id = ' . $t['tactic_ext_id'];
			query($query, $config);

		}

	}

	return $response;

}
