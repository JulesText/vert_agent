<?php

/* place/alter order */

function order($config, $order) {

	$order = json_encode($order, JSON_UNESCAPED_SLASHES);

	$httpheader = http_headers($config);

	$class = new APIREST($config['url']);
	$result = $class -> call($httpheader, $config['method'], $order);

	return $result;

}

/* prepare and validate order request */

function prepare_order($config, $order) {

	$response = $config['response'];

	if (strpos($order['symbol'], $order['from'] . '/') !== FALSE) {
		$order['side'] = 'sell';
	} else {
		$order['side'] = 'buy';
	}

	switch($order['exchange']) {

		case 'ascendex':
			# define response requested from exchange
			$order['respInst'] = 'ACCEPT';
		break;

		case 'okex_spot':
		case 'okex_margin':
			# 0: limit order (max price and quantity)
			# 1: market order (quantity only)
			# 2: fill or kill (ignore)
			# 3: immediate or cancel (ignore)
			if ($order['type'] == 'limit') $order['orderTypeCode'] = 0;
			if ($order['type'] == 'market') $order['orderTypeCode'] = 1;
		break;

	}

	// query pair parameters from exchange
	$config = config_exchange($config);
	$response_x = check_pairs($config, $order['symbol']);

	// confirm exchange returned parameters
	if ($response_x['count'] !== 1) {
		$response['msg'] .=
			$response_x['msg'] . ' for ' . $order['symbol'] . PHP_EOL
		;
		$response['error'] = TRUE;
		return $response;
	} else {
		// return parameters for base asset
		// i.e. in pair JJK/VERT the base asset is JJK and quote asset VERT
		$price_increment = $response_x['result'][$order['symbol']]['price_increment'];
		$amount_increment = $response_x['result'][$order['symbol']]['amount_increment'];
		$amount_minimum = $response_x['result'][$order['symbol']]['amount_minimum'];
	}

	// query account balance
	$config = config_exchange($config);
	$response_x = check_account($config, $order['from']);

	// confirm exchange returned asset amount
	if ($response_x['count'] !== 1) {
		$response['msg'] .=
			$response_x['msg'] . ' for ' . $order['from'] . PHP_EOL
		;
		$response['error'] = TRUE;
		return $response;
	} else {
		$available = $response_x['result'][$order['from']]['available'];
	}

	// calculate order quantity
	if (!empty($order['qty_pct']))
		$order['qty'] = ($order['qty_pct'] / 100) * $available;

	// round order values to satisfy pair parameters
	if ($order['type'] == 'limit') {
		$order['price'] = lower_multiple($order['price'], $price_increment);
		// on buy side, we need to adjust by price and calculate to_amount
		if ($order['side'] == 'buy')
			$order['qty'] = $order['qty'] / $order['price'];
		$order['qty'] = lower_multiple($order['qty'], $amount_increment);
	} else if ($order['type'] == 'market') {
		// on buy side, we need to estimate market price to obtain minimum amount
		// and alter asset parameters
		if ($order['side'] == 'buy') {
			// estimate price
			$config = config_exchange($config);
			$response_x = price_last($config, $order['symbol']);
			$price_last = $response_x['result']['lowest_sell'];
			// calculate the minimum amount
			$amount_minimum *= $price_last;
			// use price increment for from_amount increment
			$amount_increment = $price_increment;
		}
		$order['qty'] = lower_multiple($order['qty'], $amount_increment);
	}

	// validate order
	// check for available funds on exchange
	if ($order['qty'] > $available) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' .
			$order['qty'] .
			' is more than available balance ' .
			$available . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check for order below exchange minimum
	if ($order['qty'] < $amount_minimum) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' . $order['qty'] .
			' is less than min size ' . $amount_minimum .
			' for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check order quantity is in correct increment
	if (($order['qty'] / $amount_increment) % 1 != 0) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' . $order['qty'] .
			' is not a multiple of increment ' . $amount_increment .
			' as permitted for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check order price is in correct increment
	if (($order['price'] / $price_increment) % 1 != 0) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction price ' . $order['price'] .
			' is not a multiple of increment ' . $price_increment .
			' as permitted for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}

	$response['result'] = $order;

	return $response;

}

/* place new order on exchange, and receive response */

function place_order($config, $order_query, $orderId = '') {

	$response = $config['response'];

	if (isset($order_query['orderId'])) {
		$orderId = $order_query['orderId'];
	} else {
		$orderId = '';
	}

	$order = array();

	switch($config['exchange']) {

		case 'ascendex':
			$config['api_request'] = 'order';
			$config['url'] .= $config['group'] . $config['order'];
			$config['method'] = 'POST';
			$order['id'] =
				'a' . # a for order via rest api
				dechex($config['timestamp']) . # convert timestamp to hex
				$config['user_id'] . # user id
				$orderId # order num 9 characters/numbers, ending with a character, system-generated if empty
			;
			if (strlen($order['id']) !== 32) {
				$response['msg'] = 'incorrect order id for ascendex';
				$response['alert'] = TRUE;
				$response['error'] = TRUE;
				return $response;
			}
			$order['time'] = (int)$config['timestamp'];
			$order['symbol'] = $order_query['symbol'];
			$order['price'] = $order_query['price'];
			$order['qty'] = $order_query['qty'];
			$order['type'] = $order_query['type']; # market, limit, stop_market, stop_limit
			$order['side'] = $order_query['side']; # buy, sell
			$order['respInst'] = $order_query['respInst']; # ACK, ACCEPT, DONE
		break;

		case 'okex_spot':
		case 'okex_margin':
			$order['type'] = $order_query['type']; # limit or market. When placing market orders, order_type must be 0
			$order['side'] = $order_query['side']; # buy or sell
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			if ($orderId !== '') $order['client_oid'] = $orderId; # between 1-32 characters
			if ($order['type'] == 'limit') {
				$order['size'] = $order_query['qty'];
				$order['price'] = $order_query['price'];
				if (isset($order_query['orderTypeCode']))
					# 0: Normal order (Unfilled and 0 imply normal limit order)
					# 1: Post only
					# 2: Fill or Kill
					# 3: Immediate Or Cancel
					$order['order_type'] = (string) $order_query['orderTypeCode'];
			}
			if ($order['type'] == 'market') {
				if ($order['side'] == 'buy') {
					$order['notional'] = $order_query['qty'];
				} else {
					$order['size'] = $order_query['qty'];
				}
				#$order['order_type'] = '0';
			}
			$config['method'] = 'POST';
			$config['api_request'] = $config['order'] . json_encode($order, JSON_UNESCAPED_SLASHES);
			$config['url'] .= $config['order'];
		break;

	}

	if ($config['debug']) $response['msg'] .= $config['url'] . PHP_EOL . $config['api_request'] . PHP_EOL;

	$response_x = order_response($config, $order);
	$response['msg'] .= $response_x['msg'];
	$response['result'] = $response_x['result'];

	return $response;

}

/* cancel order on exchange, and receive response */

function cancel_order($config, $order_query) {

	$response = $config['response'];

	$config['exchange'] = $order_query['exchange'];
	$config = config_exchange($config);

	$order = array();

	switch($config['exchange']) {
		case 'ascendex':
			$order['orderId'] = $order_query['orderId'];
			$order['symbol'] = $order_query['symbol'];
			$order['time'] = (int)$config['timestamp'];
			$config['api_request'] = 'order';
			$config['url'] .= $config['group'] . $config['order'];
			$config['method'] = 'DELETE';
		break;
		case 'okex_spot':
		case 'okex_margin':
			$config['method'] = 'POST';
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			$config['api_request'] = $config['delete'] . $order_query['orderId'];
			$config['url'] .= $config['api_request'];
			$config['api_request'] .= json_encode($order, JSON_UNESCAPED_SLASHES);
		break;
	}

	$response_x = order_response($config, $order);
	$response['msg'] .= $response_x['msg'];
	$response['result'] = $response_x['result'];

	return $response;

}

/* process API response to order request */

function order_response($config, $order) {

	$response = $config['response'];

	$query = order($config, $order);
	if ($config['debug']) $response['msg'] .= var_export($query, TRUE);

	$result = array(
		'placed' => FALSE,
		'code' => '',
		'message' => '',
		'exchange_transaction_id' => '',
		'query' => $order
	);

	switch($config['exchange']) {

		case 'ascendex':
			$result['code'] = $query['code'];
			if ($query['code'] == 0) {
				$result['placed'] = TRUE;
				$result['exchange_transaction_id'] = $query['info']['id'];
			} else {
				$result['message'] = $query['reason'] . ' ' . $query['message'];
			}
		break;

		case 'okex_spot':
		case 'okex_margin':
			if ($query['error']) {
				$result['message'] = $query['result'];
			} else {
				$query = json_decode($query['result'], TRUE);
				if ($query['error_code'] == 0) {
					$result['placed'] = TRUE;
					$result['code'] = 0;
					$result['message'] = 'order instruction received' . PHP_EOL;
					$result['exchange_transaction_id'] = $query['order_id'];
				} else {
					$result['code'] = $query['error_code'];
					$result['message'] = $query['error_message'] . PHP_EOL;
				}
			}
		break;

	}

	$response['result'] = $result;

	return $response;

}

/* prepare and validate loan request */

function prepare_loan($config, $loan) {

	$response = $config['response'];

	// set leverage parameters on exchange
	$config = config_exchange($config);
	$response_x = get_leverage($config, $loan);
	if ($response_x['error']) return $response_x;
	$loan = $response_x['result'];
	$response_x = set_leverage($config, $loan);
	var_dump($response_x);die;

	// confirm exchange returned parameters
	if ($response_x['count'] !== 1) {
		$response['msg'] .=
			$response_x['msg'] . ' for ' . $order['symbol'] . PHP_EOL
		;
		$response['error'] = TRUE;
		return $response;
	} else {
		// return parameters for base asset
		// i.e. in pair JJK/VERT the base asset is JJK and quote asset VERT
		$price_increment = $response_x['result'][$order['symbol']]['price_increment'];
		$amount_increment = $response_x['result'][$order['symbol']]['amount_increment'];
		$amount_minimum = $response_x['result'][$order['symbol']]['amount_minimum'];
	}

	// query account balance
	$config = config_exchange($config);
	$response_x = check_account($config, $order['from']);

	// confirm exchange returned asset amount
	if ($response_x['count'] !== 1) {
		$response['msg'] .=
			$response_x['msg'] . ' for ' . $order['from'] . PHP_EOL
		;
		$response['error'] = TRUE;
		return $response;
	} else {
		$available = $response_x['result'][$order['from']]['available'];
	}

	// calculate order quantity
	if (!empty($order['qty_pct']))
		$order['qty'] = ($order['qty_pct'] / 100) * $available;

	// round order values to satisfy pair parameters
	if ($order['type'] == 'limit') {
		$order['price'] = lower_multiple($order['price'], $price_increment);
		// on buy side, we need to adjust by price and calculate to_amount
		if ($order['side'] == 'buy')
			$order['qty'] = $order['qty'] / $order['price'];
		$order['qty'] = lower_multiple($order['qty'], $amount_increment);
	} else if ($order['type'] == 'market') {
		// on buy side, we need to estimate market price to obtain minimum amount
		// and alter asset parameters
		if ($order['side'] == 'buy') {
			// estimate price
			$config = config_exchange($config);
			$response_x = price_last($config, $order['symbol']);
			$price_last = $response_x['result']['lowest_sell'];
			// calculate the minimum amount
			$amount_minimum *= $price_last;
			// use price increment for from_amount increment
			$amount_increment = $price_increment;
		}
		$order['qty'] = lower_multiple($order['qty'], $amount_increment);
	}

	// validate order
	// check for available funds on exchange
	if ($order['qty'] > $available) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' .
			$order['qty'] .
			' is more than available balance ' .
			$available . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check for order below exchange minimum
	if ($order['qty'] < $amount_minimum) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' . $order['qty'] .
			' is less than min size ' . $amount_minimum .
			' for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check order quantity is in correct increment
	if (($order['qty'] / $amount_increment) % 1 != 0) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction amount ' . $order['qty'] .
			' is not a multiple of increment ' . $amount_increment .
			' as permitted for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}
	// check order price is in correct increment
	if (($order['price'] / $price_increment) % 1 != 0) {
		$response['msg'] .=
			'order attempt failed for tactic_id ' . $order['tactic_id'] .
			' because transaction price ' . $order['price'] .
			' is not a multiple of increment ' . $price_increment .
			' as permitted for asset pair' . PHP_EOL
		;
		$response['error'] = TRUE;
	}

	$response['result'] = $order;

	return $response;

}

/* make new loan on exchange, and receive response */

function place_loan($config, $loan, $loanId = '') {

	$response = $config['response'];

	if (isset($order_query['orderId'])) {
		$orderId = $order_query['orderId'];
	} else {
		$orderId = '';
	}

	$order = array();

	switch($config['exchange']) {

		case 'ascendex':
			$config['api_request'] = 'order';
			$config['url'] .= $config['group'] . $config['order'];
			$config['method'] = 'POST';
			$order['id'] =
				'a' . # a for order via rest api
				dechex($config['timestamp']) . # convert timestamp to hex
				$config['user_id'] . # user id
				$orderId # order num 9 characters/numbers, ending with a character, system-generated if empty
			;
			if (strlen($order['id']) !== 32) {
				$response['msg'] = 'incorrect order id for ascendex';
				$response['alert'] = TRUE;
				$response['error'] = TRUE;
				return $response;
			}
			$order['time'] = (int)$config['timestamp'];
			$order['symbol'] = $order_query['symbol'];
			$order['price'] = $order_query['price'];
			$order['qty'] = $order_query['qty'];
			$order['type'] = $order_query['type']; # market, limit, stop_market, stop_limit
			$order['side'] = $order_query['side']; # buy, sell
			$order['respInst'] = $order_query['respInst']; # ACK, ACCEPT, DONE
		break;

		case 'okex_spot':
		case 'okex_margin':
			$order['type'] = $order_query['type']; # limit or market. When placing market orders, order_type must be 0
			$order['side'] = $order_query['side']; # buy or sell
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			if ($orderId !== '') $order['client_oid'] = $orderId; # between 1-32 characters
			if ($order['type'] == 'limit') {
				$order['size'] = $order_query['qty'];
				$order['price'] = $order_query['price'];
				if (isset($order_query['orderTypeCode']))
					# 0: Normal order (Unfilled and 0 imply normal limit order)
					# 1: Post only
					# 2: Fill or Kill
					# 3: Immediate Or Cancel
					$order['order_type'] = (string) $order_query['orderTypeCode'];
			}
			if ($order['type'] == 'market') {
				if ($order['side'] == 'buy') {
					$order['notional'] = $order_query['qty'];
				} else {
					$order['size'] = $order_query['qty'];
				}
				#$order['order_type'] = '0';
			}
			$config['method'] = 'POST';
			$config['api_request'] = $config['order'] . json_encode($order, JSON_UNESCAPED_SLASHES);
			$config['url'] .= $config['order'];
		break;

	}

	if ($config['debug']) $response['msg'] .= $config['url'] . PHP_EOL . $config['api_request'] . PHP_EOL;

	$response_x = order_response($config, $order);
	$response['msg'] .= $response_x['msg'];
	$response['result'] = $response_x['result'];

	return $response;

}

/* process API response to loan request */

function loan_response($config, $loan) {

	$response = $config['response'];

	$query = order($config, $order);
	if ($config['debug']) $response['msg'] .= var_export($query, TRUE);

	$result = array(
		'placed' => FALSE,
		'code' => '',
		'message' => '',
		'exchange_transaction_id' => '',
		'query' => $order
	);

	switch($config['exchange']) {

		case 'ascendex':
			$result['code'] = $query['code'];
			if ($query['code'] == 0) {
				$result['placed'] = TRUE;
				$result['exchange_transaction_id'] = $query['info']['id'];
			} else {
				$result['message'] = $query['reason'] . ' ' . $query['message'];
			}
		break;

		case 'okex_spot':
		case 'okex_margin':
			if ($query['error']) {
				$result['message'] = $query['result'];
			} else {
				$query = json_decode($query['result'], TRUE);
				if ($query['error_code'] == 0) {
					$result['placed'] = TRUE;
					$result['code'] = 0;
					$result['message'] = 'order instruction received' . PHP_EOL;
					$result['exchange_transaction_id'] = $query['order_id'];
				} else {
					$result['code'] = $query['error_code'];
					$result['message'] = $query['error_message'] . PHP_EOL;
				}
			}
		break;

	}

	$response['result'] = $result;

	return $response;

}

/* repay loan on exchange, and receive response */

function repay_loan($config, $order_query) {

	$response = $config['response'];

	$config['exchange'] = $order_query['exchange'];
	$config = config_exchange($config);

	$order = array();

	switch($config['exchange']) {
		case 'ascendex':
			$order['orderId'] = $order_query['orderId'];
			$order['symbol'] = $order_query['symbol'];
			$order['time'] = (int)$config['timestamp'];
			$config['api_request'] = 'order';
			$config['url'] .= $config['group'] . $config['order'];
			$config['method'] = 'DELETE';
		break;
		case 'okex_spot':
		case 'okex_margin':
			$config['method'] = 'POST';
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			$config['api_request'] = $config['delete'] . $order_query['orderId'];
			$config['url'] .= $config['api_request'];
			$config['api_request'] .= json_encode($order, JSON_UNESCAPED_SLASHES);
		break;
	}

	$response_x = order_response($config, $order);
	$response['msg'] .= $response_x['msg'];
	$response['result'] = $response_x['result'];

	return $response;

}
