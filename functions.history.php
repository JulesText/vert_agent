<?php

/* create api request for order history for selected exchange */

function history_orders($config, $condition) {

	// make query string
	switch ($config['exchange']) {
		case 'ascendex':
			$config['api_request'] = $config['order_history_hash'];
			$config['url'] .=
				$config['group'] .
				$config['order_history'] .
				'?n=1000&symbol=' .
				$config['pair'] .
				$condition
			;
		break;
		case 'okex_spot':
		case 'okex_margin':
			$config['api_request'] =
				$config['order_history'] .
				'?instrument_id=' . str_replace('/', '-', $config['pair']) .
				$condition
			;
			$config['url'] .= $config['api_request'];
		break;
	}

	return query_api($config);

}

/* call exchange API for single transaction details and update database */

function check_transaction($config, $transaction_id) {

	$response = $config['response'];

	$values = array();
	$values['filterquery'] = " WHERE transaction_id = " . $transaction_id;
	$t = query('select_transactions', $config, $values);

	$config['exchange'] = $t[0]['exchange'];
	$config = config_exchange($config);
	$config['method'] = 'GET';

	switch($t[0]['exchange']) {
		case 'ascendex':
			$config['api_request'] = 'order/status';
			$config['url'] .=
				$config['group'] .
				$config['orders_status'] .
				'?orderId=' . $t[0]['exchange_transaction_id']
			;
		break;
		case 'okex_spot':
		case 'okex_margin':
			$config['api_request'] =
				$config['orders_status'] .
				$t[0]['exchange_transaction_id'] .
				'?instrument_id=' . str_replace('/', '-', $t[0]['pair'])
			;
			$config['url'] .= $config['api_request'];
		break;
	}

	if($t[0]['pair']) {

		$transaction = query_api($config);

		if ($t[0]['exchange'] == 'ascendex') {
			if ($transaction['code'] !== 0 && isset($transaction['code'])) {
				$response['msg'] .= 'Error ' . $transaction['code'] . ' on ascendex for transaction_id ' . $transaction_id . PHP_EOL;
				$response['alert'] = TRUE;
				$response['error'] = TRUE;
				return $response;
			} else {
				$transaction = $transaction[0]['data'];
			}
		}

		if ($t[0]['exchange'] == 'okex_spot' || $t[0]['exchange'] == 'okex_margin') {
			if (isset($transaction['error_code']) && $transaction['error_code'] !== 0) {
				$response['msg'] .= 'Error ' . $transaction['error_code'] . ' on okex for transaction_id ' . $transaction_id . PHP_EOL;
				$response['alert'] = TRUE;
				$response['error'] = TRUE;
				return $response;
			}
		}

		$transaction['exchange'] = $t[0]['exchange'];

		/* map array to exchange-specific format */
		$values = array();
		$values['transaction_id'] = $transaction_id;
		$transaction = map_order($values, $transaction, $config);
		query('update_transaction', $config, $transaction);

		$response['result'] = $transaction;
		return $response;

	} else {

		$response['msg'] .= 'Invalid pair for transaction_id ' . $transaction_id;
		$response['alert'] = TRUE;
		$response['error'] = TRUE;
		return $response;

	}

	return $response;

}

/* update all transactions in database by querying exchanges */

function update_transactions($config, $exchanges = '', $pair = '') {

	$response = $config['response'];

	if (empty($exchanges)) $exchanges = implode("','", $config['exchanges_trade']);

	// select all possible combinations of exchange and pair
	$pairs = array();
	$query = "
		SELECT DISTINCT
		exchange,
		pair
		FROM asset_pairs
	";
	$sub_query = "
		WHERE exchange IN ('{$exchanges}')
		AND trade = 1
	";
	if (!empty($pair)) $sub_query .= " AND pair = '{$pair}'";
	$sub_query .= " ORDER BY pair, exchange";
	$result = query($query . $sub_query, $config);
	if (!empty($result))
		foreach ($result as $pair) array_push($pairs, $pair);
	$query = "
		SELECT DISTINCT
		exchange,
		pair
		FROM transactions
	";
	$sub_query .= "
		AND from_asset != to_asset
	";
	$result = query($query . $sub_query, $config);
	if (!empty($result))
		foreach ($result as $pair) array_push($pairs, $pair);
	// deduplicate exchange pairs
	$pairs = dedupe_array($pairs);
	if ($config['debug']) $response['msg'] .= var_export($pairs, TRUE);
	if (empty($pairs)) {
		$response['msg'] .= 'database query returned no pairs to update' . PHP_EOL;
		return $response;
	}

	// for each combination, query the exchange and put result in $transactions
	$transactions = array();
	foreach ($pairs as $pair) {
		$config['exchange'] = $pair['exchange'];
		$config = config_exchange($config);
		$config['pair'] = $pair['pair'];
		// for each statuses_orders permutate query
		$conditions = array();
		switch ($pair['exchange']) {
			case 'ascendex':
				array_push($conditions, '');
			break;
			case 'okex_spot':
			case 'okex_margin':
				foreach ($config['statuses_orders'] as $key => $val)
					if ($val)
						array_push($conditions, '&state=' . $config['status_exchange'][$key]);
			break;
		}
		foreach ($conditions as $condition) {
			$result = history_orders($config, $condition);
			if (!empty($result)) {
				// countdim checks the array depth
				if (countdim($result) == 1) {
					$result['exchange'] = $pair['exchange'];
					$result['purpose'] = 'trade';
					array_push($transactions, $result);
				} elseif (countdim($result) > 1) {
					foreach ($result as $res) {
						$res['exchange'] = $pair['exchange'];
						$res['purpose'] = 'trade';
						array_push($transactions, $res);
					}
				}
			}
			// pause to limit number of requests to exchange per second
			sleep(0.2);
		}
	}

	// after orders have been queried, check loans
	foreach ($config['exchanges_margin'] as $exchange) {

		// check for open (0) and closed (1) loans
		foreach (array(0,1) as $status) {

			$config['exchange'] = $exchange;
			$config['account'] = 'margin';
			$config = config_exchange($config);

			switch ($config['exchange']) {
				case 'okex_spot':
				case 'okex_margin':
					$config['api_request'] =
						$config['loan_history'] .
						'?status=' . $status
					;
					$config['url'] .= $config['api_request'];
				break;
			}

			// query exchange
			$result = query_api($config);

			// convert result to transaction format
			foreach ($result as $loan) {

				$loan['exchange'] = $config['exchange'];
				$loan['purpose'] = 'loan';
				if ($status === 1) {
					$loan['exchange_transaction_status'] = 'complete';;
				} else if ($status === 0) {
					$loan['exchange_transaction_status'] = 'open';
				}

				array_push($transactions, $loan);

			}
		}
	}

	if (empty($transactions)) {
		$response['msg'] .= 'exchange(s) returned no transactions to update' . PHP_EOL;
		return $response;
	}

	$transactions = dedupe_array($transactions);

	$query = "SELECT transaction_id, exchange, exchange_transaction_id, recorded, purpose FROM transactions";
	$sub_query = "
		WHERE exchange IN ('{$exchanges}')
		AND purpose IN ('trade','loan')
	";
	$existing = query($query . $sub_query, $config);

	foreach ($transactions as $t) {
		if ($t['exchange'] == 'ascendex') $t = $t['data'];
		if (!empty($t)) {
			$response_x = process_transaction($config, $existing, $t);
			$response['msg'] .= $response_x['msg'];
			$id = $response_x['result']['id'];
			$response['result'][$t['exchange']][$id] = $response_x['result']['action'];
		}
	}

	return $response;

}

/* transform transaction data from exchange and save in database */

function process_transaction($config, $existing, $transaction) {

	$response = $config['response'];

	// map transaction id only, not other details
	// in update_transactions we query the exchange
	// without specifying exchange_transaction_id to return any unrecorded transactions
	$transaction = map_transaction_id($transaction);

	// if transaction exists in database, use existing record
	$exists = FALSE;
	if (!empty($existing))
		foreach ($existing as $exist) {
			if (
				$exist['exchange_transaction_id'] == $transaction['exchange_transaction_id']
				&& $exist['exchange'] == $transaction['exchange']
				&& $exist['purpose'] == $transaction['purpose']
			) {
				$exists = TRUE;
				$response['msg'] .= "found existing {$transaction['purpose']} exchange_transaction_id {$transaction['exchange_transaction_id']}, ";
				if ($exist['recorded']) {
					$response['msg'] .= 'closed and already recorded' . PHP_EOL;
					$response['result']['id'] = $transaction['exchange_transaction_id'];
					$response['result']['action'] = 'ignored';
					return $response;
				}
				$query = "SELECT * FROM transactions WHERE transaction_id = {$exist['transaction_id']}";
				$values = query($query, $config);
				$values = $values[0];
				break 1;
			}
		}

	// if transaction not exists in database, create new entry with default values
	if (!$exists) {
		$values = array();
		$values['purpose'] = $transaction['purpose'];
		$values['exchange'] = $transaction['exchange'];
		$response['msg'] .= "new {$transaction['purpose']} transaction for exchange_transaction_id {$transaction['exchange_transaction_id']}, ";
	}

	/* map exchange data array */
	if ($values['purpose'] == 'trade')
		$values = map_order($values, $transaction, $config);
	if ($values['purpose'] == 'loan')
		$values = map_loan($values, $transaction, $config);

	if ($exists) {
		$result = query('update_transaction', $config, $values);
		if (!is_numeric($result))
			$action = 'error';
		else if ($result)
			$action = 'updated';
		else
			$action = 'no update';
	} else {
		$result = query('insert_transaction', $config, $values);
		if ($result !== 1)
			$action = 'error';
		else
			$action = 'inserted';
	}

	if ($action === 'error') {
		$response['msg'] .= "error: unable to update database correctly " . PHP_EOL . var_export($values, TRUE);
		$response['alert'] = TRUE;
		process($response, $config);
	} else {
		$response['msg'] .= $action . PHP_EOL;
	}

	$response['result']['id'] = $transaction['exchange_transaction_id'];
	$response['result']['action'] = $action;

	return $response;

}

/* map exchange transaction id to database format */

function map_transaction_id($transaction) {

	switch ($transaction['exchange']) {
		case 'ascendex':
			$transaction['exchange_transaction_id'] = $transaction['orderId'];
		break;
		case 'okex_spot':
		case 'okex_margin':
			if ($transaction['purpose'] == 'trade')
				$transaction['exchange_transaction_id'] = $transaction['order_id'];
			if ($transaction['purpose'] == 'loan')
				$transaction['exchange_transaction_id'] = $transaction['borrow_id'];
		break;
	}

	return $transaction;

}

/* map exchange order data array format to database format */

function map_order($values, $order, $config) {

	$order = map_transaction_id($order);

	$time = NULL;
	$pair_price = NULL;
	$symbol = NULL;
	$filled = NULL;
	$quantity = NULL;
	$avg_price = NULL;
	$fee = NULL;
	$fee_asset = NULL;
	$direction = NULL;

	switch ($order['exchange']) {

		case 'ascendex':
			$time = $order['lastExecTime'];
			switch($order['status']) {
				/* order filled */
				case 'Filled':
					$values['exchange_transaction_status'] = 'complete';
					$values['time_closed'] = $time;
				break;
				/* order open */
				case 'New':
				case 'PartiallyFilled':
					$values['exchange_transaction_status'] = 'open';
					$values['time_opened'] = $time;
				break;
				/* order open, but pending trigger */
				case 'PendingNew':
					$values['exchange_transaction_status'] = 'trigger open';
					$values['time_opened'] = $time;
				break;
				/* order cancelled */
				case 'Canceled': # (server typo spelled Canceled)
				case 'Reject':
					$values['exchange_transaction_status'] = 'cancelled';
					$values['time_closed'] = $time;
				break;
				default:
					$values['exchange_transaction_status'] = 'unconfirmed';
			}
			$pair_price = $order['price'];
			$avg_price = $order['avgPx'];
			$symbol = $order['symbol'];
			$filled = $order['cumFilledQty'];
			$quantity = $order['qty'];
			$fee = $order['cumFee'];
			$fee_asset = $order['feeAsset'];
			$direction = $order['side'];
		break;

		case 'okex_spot':
		case 'okex_margin':
			switch($order['state']) {
				/* order filled */
				case '2': # Fully Filled
				case '7': # Complete
					$values['exchange_transaction_status'] = 'complete';
				break;
				/* order open */
				case '0': # Open
				case '1': # Partially Filled
				case '6': # open and/or partially filled
					$values['exchange_transaction_status'] = 'open';
				break;
				/* order unconfirmed */
				case '3': # Submitting
				case '4': # Canceling
					$values['exchange_transaction_status'] = 'unconfirmed';
				break;
				/* order cancelled */
				case '-1': # Canceled
				case '-2': # Failed
					$values['exchange_transaction_status'] = 'cancelled';
				break;
				default:
					$values['exchange_transaction_status'] = 'unconfirmed';
			}
			$values['time_opened'] = strtotime($order['created_at']) * 1000;
			/* time_closed is not provided in order details */
			# if (isset($order['time_closed'])) $values['time_closed'] = $order['time_closed'];
			$symbol = str_replace('-', '/', $order['instrument_id']);
			if ($order['type'] == 'limit') {
				$quantity = $order['size'];
				$filled = $order['filled_size'];
			} elseif ($order['type'] == 'market') {
				if ($order['status'] == 'filled') {
					$quantity = $order['filled_notional'];
				} else {
					$quantity = $order['notional'];
				}
				$filled = $order['filled_notional'];
			}
			$pair_price = $order['price'];
			$avg_price = $order['price_avg'];
			$fee = abs($order['fee']);
			$fee_asset = $order['fee_currency'];
			$direction = $order['side'];
		break;

	}

	/* simple order information */
	$values['exchange_transaction_id'] = $order['exchange_transaction_id'];
	$values['pair'] = $symbol;

	/* read asset names */
	$pair = explode('/', $symbol);

	/* read amounts */
	$values['percent_complete'] = 100 * $filled / $quantity;
	$values['percent_complete'] = number_format($values['percent_complete'], 20, '.', '');
	if ($filled > 0) {
		$price = $avg_price;
	} else {
		$price = $pair_price;
	}

	/* sell order */
	if (strtolower($direction) == 'sell') {
		$values['from_asset'] = $pair[0];
		$values['from_amount'] = $quantity;
		$values['to_asset'] = $pair[1];
		$values['to_amount'] = $price * $quantity;
		if ($fee_asset == $pair[1]) {
			$values['to_fee'] = $fee;
		} else {
			$values['to_fee'] = $fee * $price;
		}
		$values['pair_price'] = $values['to_amount'] / $values['from_amount'];
		if (in_array($pair[1], array('USDT','USD','USDC','DAI'))) {
			$values['from_price_usd'] = $values['to_amount'] / $values['from_amount']; // return different value to $price
			$values['to_price_usd'] = 1;
			$values['price_reference'] = $order['exchange'];
		}
	}

	/* buy order */
	if (strtolower($direction) == 'buy') {
		$values['from_asset'] = $pair[1];
		$values['from_amount'] = $price * $quantity;
		$values['to_asset'] = $pair[0];
		$values['to_amount'] = $quantity;
		if ($fee_asset == $pair[0]) {
			$values['to_fee'] = $fee;
		} else {
			$values['to_fee'] = $fee / $price;
		}
		$values['pair_price'] = $values['from_amount'] / $values['to_amount']; // return different value to $price
		if (in_array($pair[1], array('USDT','USD','USDC','DAI'))) {
			$values['from_price_usd'] = 1;
			$values['to_price_usd'] = $values['from_amount'] / $values['to_amount'];
			$values['price_reference'] = $order['exchange'];
		}
	}

	/* order is complete */
	if (in_array($values['exchange_transaction_status'], array('complete','cancelled')))
		$values['recorded'] = 1;

	return $values;

}

/* map exchange loan data array format to database format */

function map_loan($values, $loan, $config) {

	$loan = map_transaction_id($loan);

	$values['exchange_transaction_id'] = $loan['exchange_transaction_id'];
	$values['exchange_transaction_status'] = $loan['exchange_transaction_status'];
	if ($values['exchange_transaction_status'] == 'complete')
		$values['recorded'] = 1;
	if ($values['exchange_transaction_status'] == 'open')
		$values['recorded'] = 0;

	switch ($config['exchange']) {
		case 'okex_spot':
		case 'okex_margin':
			$values['to_amount'] = $loan['amount'];
			$values['to_fee'] = $loan['interest'];
			$values['exchange_transaction_id'] = $loan['borrow_id'];
			$values['time_opened'] = strtotime($loan['created_at']) * 1000;
			$values['from_asset'] = $loan['currency'];
			$values['to_asset'] = $loan['currency'];
			if ($values['recorded']) {
				$values['time_closed'] = strtotime($loan['force_repay_time']) * 1000;
				$values['percent_complete'] = 100;
			} else {
				$values['percent_complete'] = 100 * ($loan['repay_amount'] + $loan['paid_interest']) / ($loan['amount'] + $loan['interest']);
				$values['percent_complete'] = number_format($values['percent_complete'], 20, '.', '');
			}
			$values['pair'] = str_replace('-', '/', $loan['instrument_id']);
			$values['pair_price'] = $loan['rate']; # interest rate daily
		break;
	}

	return $values;

}

/* calculate $AUD price from $USD price */

function calculate_aud($config, $all = FALSE) {

	$response = $config['response'];

	$query = "
		SELECT transaction_id
		FROM transactions
		WHERE exchange_transaction_status = 'complete'
	";
	if (!$all) $query .= "AND price_aud_usd = 0";
	$transactions = query($query, $config);
	foreach ($transactions as $t) {
		$query = "
			SELECT
			b.close AS price_aud_usd,
			b.timestamp AS aud_usd_timestamp,
			c.reference AS aud_usd_reference,
			ABS(a.time_closed - b.timestamp) AS min_abs_value
			FROM transactions a
			CROSS JOIN price_history b
			INNER JOIN asset_pairs c
			ON b.pair_id = c.pair_id
			WHERE a.transaction_id = '{$t['transaction_id']}'
			AND c.pair = 'AUD/USD'
			AND c.currency_end > a.time_closed
			AND c.history_start < a.time_closed
			AND b.close > 0
			AND ABS(a.time_closed - b.timestamp) < ABS(a.time_closed - a.aud_usd_timestamp)
			ORDER BY min_abs_value
			LIMIT 1
		";
		$history = query($query, $config);
		if (!empty($history)) {
			$history = $history[0];
			$query = "
				UPDATE transactions
				SET
				price_aud_usd = {$history['price_aud_usd']},
				aud_usd_reference = '{$history['aud_usd_reference']}',
				aud_usd_timestamp = {$history['aud_usd_timestamp']},
				fee_amount_usd = to_fee * to_price_usd,
				capital_amount = to_amount * to_price_usd / {$history['price_aud_usd']},
				capital_fee = fee_amount_usd / {$history['price_aud_usd']}
				WHERE transaction_id = {$t['transaction_id']}
			";
			$result = query($query, $config);
			$response['count'] += $result;
		}
	}
	$response['msg'] .= "Updated AUD price for {$response['count']} completed transactions" . PHP_EOL;

	return $response;

}
