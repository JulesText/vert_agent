<?php

/* create api request string for order history for selected exchange */

function history_orders($config) {

	// make query string
	switch ($config['exchange']) {
		case 'bitmax':
			$config['api_request'] = 'order/hist/current';
			$config['url'] .=
				$config['group'] .
				$config['order_history'] .
				'?n=1000&symbol=' .
				$config['pair']
			;
		break;
		case 'okex':
			$config['api_request'] =
				'/api/spot/v3/orders' .
				'?instrument_id=' . str_replace('/', '-', $config['pair'])
				. '&state=2'
			;
			$config['url'] .= $config['api_request'];
		break;
	}

	return info($config);

}

/* call exchange API for single transaction details and update database */

function check_transaction($config, $transaction_id) {

	$values = array();
	$values['filterquery'] = " WHERE transaction_id = " . $transaction_id;
	$t = query('get_transactions', $config, $values);

	$config['exchange'] = $t[0]['exchange'];
	$config = config_exchange($config);
	$config['method'] = 'GET';

	switch($t[0]['exchange']) {
		case 'bitmax':
			$config['api_request'] = 'order/status';
			$config['url'] .=
				$config['group'] .
				$config['orders_status'] .
				'?orderId=' . $t[0]['exchange_transaction_id']
			;
		break;
		case 'okex':
			$config['api_request'] =
				$config['orders_status'] .
				$t[0]['exchange_transaction_id'] .
				'?instrument_id=' . str_replace('/', '-', $t[0]['pair_asset'])
			;
			$config['url'] .= $config['api_request'];
		break;
	}

	if($t[0]['pair_asset']) {

		$transaction = info($config);

		if ($t[0]['exchange'] == 'bitmax') {
			if ($transaction['code'] !== 0 && isset($transaction['code'])) {
				$config['chatText'] = 'Error ' . $transaction['code'] . ' on bitmax for transaction_id ' . $transaction_id;
				telegram($config);
				return FALSE;
			} else {
				$transaction = $transaction[0]['data'];
			}
		}

		if ($t[0]['exchange'] == 'okex') {
			if ($transaction['error_code'] !== 0 && isset($transaction['error_code'])) {
				$config['chatText'] = 'Error ' . $transaction['error_code'] . ' on okex for transaction_id ' . $transaction_id;
				telegram($config);
				return FALSE;
			}
		}

		$transaction['exchange'] = $t[0]['exchange'];

		/* map array to exchange-specific format */
		$transaction = map_transaction(array(), $transaction, $config);
		query('update_transaction', $config, $transaction);

		return $transaction;

	} else {

		$config['chatText'] = 'Invalid pair_asset for transaction_id ' . $transaction_id;
		telegram($config);
		return FALSE;

	}

}

/* update all transactions in database by querying exchanges */

function update_transactions($config) {

	// select all possible combinations of exchange and pair_asset
	$query = "
		SELECT DISTINCT
		exchange,
		pair_asset
		FROM transactions
	";
	$sub_query = "
		WHERE exchange_transaction_status != 'cancelled'
		AND from_asset != to_asset
	";
	if (!empty($config['exchange'])) {
		$sub_query .= " AND exchange = '" . $config['exchange'] . "'";
	} else {
		$sub_query .= " AND exchange IN (" . $config['exchanges_trade'] . ")";
	}
	if (!empty($config['pair'])) $sub_query .= " AND pair_asset = '" . $config['pair'] . "'";
	$pairs = query($query . $sub_query, $config);
	if ($config['debug']) var_dump($pairs);
	if (empty($pairs)) return FALSE;

	// for each combination, query the exchange and put result in $transactions
	$transactions = array();
	foreach ($pairs as $pair) {
		$config['exchange'] = $pair['exchange'];
		$config = config_exchange($config);
		$config['pair'] = $pair['pair_asset'];
		$result = history_orders($config);
		if (!empty($result)) {
			// countdim checks the array depth
			if (countdim($result) == 1) {
				$result['exchange'] = $config['exchange'];
				array_push($transactions, $result);
			} elseif (countdim($result) > 1) {
				foreach ($result as $res) {
					$res['exchange'] = $config['exchange'];
					array_push($transactions, $res);
				}
			}
		}
		// pause to limit number of requests to exchange per second
		sleep(0.2);
	}

	if (empty($transactions)) return FALSE;

	$query = "SELECT * FROM transactions"; # whole records
	$existing = query($query . $sub_query, $config);

	foreach ($transactions as $t) {
		if ($t['exchange'] == 'bitmax') {
			if (!empty($t['data'])) {
				process_transaction($config, $existing, $t['data']);
			}
		}
		if ($t['exchange'] == 'okex') {
			if (!empty($t)) {
				process_transaction($config, $existing, $t);
			}
		}
	}

}

/* transform transaction data from exchange and save in database */

function process_transaction($config, $existing, $order) {

	// map order id only, not other order details
	// note that $order_id_only = TRUE
	$order = map_transaction(array(), $order, $config, TRUE);

	// if transaction exists in database, use existing record
	$exists = FALSE;
	if (!empty($existing))
		foreach ($existing as $exist) {
			if (
				$exist['exchange_transaction_id'] == $order['exchange_transaction_id']
				&& $exist['exchange'] == $order['exchange']
			) {
				$exists = TRUE;
				$values = $exist;
				echo 'existing transaction' . PHP_EOL;
			}
		}

	// if transaction not exists in database, create new entry with default values
	if (!$exists) {
		$values = $config['transaction'];
		$values['purpose'] = 'trade';
		$values['exchange'] = $config['exchange'];
		echo 'new transaction' . PHP_EOL;
	}

	/* map exchange data array */
	$values = map_transaction($values, $order, $config, FALSE);

	query('update_transaction', $config, $values);

}

/* map exchange order data array format to database format */

function map_transaction($values, $order, $config, $order_id_only = FALSE) {

	switch ($order['exchange']) {
		case 'bitmax':
			$order['exchange_transaction_id'] = $order['orderId'];
		break;
		case 'okex':
			$order['exchange_transaction_id'] = $order['order_id'];
		break;
	}

  if ($order_id_only) return $order;

	$time = NULL;
	$status = NULL;
	$pair_price = NULL;
	$symbol = NULL;
	$filled = NULL;
	$quantity = NULL;
	$avg_price = NULL;
	$fee = NULL;
	$fee_asset = NULL;
	$direction = NULL;

	switch ($order['exchange']) {

		case 'bitmax':
			$time = $order['lastExecTime'];
			switch($order['status']) {
				case 'Filled':
					$status = 'filled';
					$values['time_closed'] = $time;
				break;
				case 'New':
				case 'PartiallyFilled':
					$status = 'open';
					$values['time_opened'] = $time;
				break;
				case 'PendingNew':
					$status = 'pending_trigger';
					$values['time_opened'] = $time;
				break;
				case 'Canceled': # (server typo spelled Canceled)
				case 'Reject':
					$status = 'cancelled';
					$values['time_closed'] = $time;
				break;
			}
			$pair_price = $order['price'];
			$symbol = $order['symbol'];
			$filled = $order['cumFilledQty'];
			$quantity = $order['orderQty'];
			$avg_price = $order['avgPx'];
			$fee = $order['cumFee'];
			$fee_asset = $order['feeAsset'];
			$direction = $order['side'];
		break;

		case 'okex':
			switch($order['state']) {
				case '2': # Fully Filled
					$status = 'filled';
				break;
				case '0': # Open
				case '1': # Partially Filled
					$status = 'open';
				break;
				case '3': # Submitting
				case '4': # Canceling
					$status = 'unconfirmed';
				break;
				case '-1': # Canceled
				case '-2': # Failed
					$status = 'cancelled';
				break;
			}
			$values['time_opened'] = strtotime($order['created_at']);
			#$values['time_closed'] = strtotime($order['timestamp']);
			$pair_price = $order['price_avg'];
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
			$avg_price = $order['price_avg'];
			$fee = abs($order['fee']);
			$fee_asset = $order['fee_currency'];
			$direction = $order['side'];
		break;

	}

	/* order filled */
	if ($status == 'filled') {
		$values['exchange_transaction_status'] = 'complete';
	}

	/* order open */
	if ($status == 'open') {
		$values['exchange_transaction_status'] = 'open';
		$values['pair_price'] = $pair_price;
	}

	/* order open, but pending trigger */
	if ($status == 'pending_trigger') {
		$values['exchange_transaction_status'] = 'trigger open';
		$values['pair_price'] = $pair_price;
	}

	/* order cancelled */
	if ($status == 'cancelled') {
		$values['exchange_transaction_status'] = 'cancelled';
	}

	/* simple order information */
	$values['exchange_transaction_id'] = $order['exchange_transaction_id'];
	$values['pair_asset'] = $symbol;

	/* read asset names */
	$pair = explode('/', $symbol);

	/* read amounts */
	$values['percent_complete'] = round(100 * $filled / $quantity, 0);
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
			$values['from_price_usd'] = $values['to_amount'] / $values['from_amount'];
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
		$values['pair_price'] = $values['from_amount'] / $values['to_amount'];
		if (in_array($pair[1], array('USDT','USD','USDC','DAI'))) {
			$values['from_price_usd'] = 1;
			$values['to_price_usd'] = $values['from_amount'] / $values['to_amount'];
			$values['price_reference'] = $order['exchange'];
		}
	}

	return $values;

}

/* calculate $AUD price from $USD price */

function calculate_orders($config) {

	$query = "
		SELECT
		a.transaction_id,
		b.close AS price_aud_usd,
		'https://twelvedata.com/' AS aud_usd_reference
		FROM transactions a
		CROSS JOIN price_history b
		INNER JOIN (
			SELECT
			a.transaction_id,
			MIN(ABS(a.time_closed - b.timestamp)) AS min_abs_value
			FROM transactions a
			CROSS JOIN price_history b
			WHERE a.price_aud_usd = 0
			AND a.exchange_transaction_status = 'complete'
			AND b.pair = 'AUD/USD'
			GROUP BY a.transaction_id
		) t
		ON a.transaction_id = t.transaction_id
		AND ABS(a.time_closed - b.timestamp) = t.min_abs_value
		WHERE a.price_aud_usd = 0
		AND a.exchange_transaction_status = 'complete'
		AND b.pair = 'AUD/USD'
	";
	$orders = query($query, $config);
	foreach ($orders as $order) {
		$query = "
			UPDATE transactions
			SET price_aud_usd = " . $order['price_aud_usd'] . ",
			aud_usd_reference = '" . $order['aud_usd_reference'] . "'
			WHERE transaction_id = " . $order['transaction_id']
		;
		query($query, $config);
	}

	# capital estimate
	$query = "
		UPDATE transactions SET
		fee_amount_usd = to_fee * to_price_usd,
		capital_amount = to_amount * to_price_usd / price_aud_usd,
		capital_fee = fee_amount_usd / price_aud_usd
		WHERE exchange_transaction_status = 'complete'
	";
	query($query, $config);

}
