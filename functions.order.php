<?php

/* place/alter order */

function order($config, $order) {

	$httpheader = http_headers($config);
	$order = json_encode($order, JSON_UNESCAPED_SLASHES);

	$class = new APIREST($config['url']);
	$result = $class -> call($httpheader, $config['method'], $order);

	return json_decode($result, TRUE);

}

/* place new order on exchange, and receive response */

function place_order($config, $order_query, $orderId = '') {

	if (isset($order_query['orderId'])) {
		$orderId = $order_query['orderId'];
	} else {
		$orderId = '';
	}

	$order = array();

	switch($config['exchange']) {

		case 'bitmax':
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
				$config['chatText'] = 'incorrect order id for bitmax';
				telegram($config);
				die;
			}
			$order['time'] = (int)$config['timestamp'];
			$order['symbol'] = $order_query['symbol'];
			$order['orderPrice'] = $order_query['orderPrice'];
			$order['orderQty'] = $order_query['orderQty'];
			$order['orderType'] = $order_query['orderType']; # market, limit, stop_market, stop_limit
			$order['side'] = $order_query['side']; # buy, sell
			$order['respInst'] = $order_query['respInst']; # ACK, ACCEPT, DONE
		break;

		case 'okex':
			$order['type'] = $order_query['orderType']; # limit or market. When placing market orders, order_type must be 0
			$order['side'] = $order_query['side']; # buy or sell
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			if ($orderId !== '') $order['client_oid'] = $orderId; # between 1-32 characters
			if ($order['type'] == 'limit') {
				$order['size'] = $order_query['orderQty'];
				$order['price'] = $order_query['orderPrice'];
				if (!empty($order_query['orderTypeCode']))
					# 0: Normal order (Unfilled and 0 imply normal limit order)
					# 1: Post only
					# 2: Fill or Kill
					# 3: Immediate Or Cancel
					$order['order_type'] = $order_query['orderTypeCode'];
			}
			if ($order['type'] == 'market') {
				if ($order['side'] == 'buy') {
					$order['notional'] = $order_query['orderQty'];
				} else {
					$order['size'] = $order_query['orderQty'];
				}
				#$order['order_type'] = '0';
			}
			$config['method'] = 'POST';
			$config['api_request'] = $config['order'] . json_encode($order, JSON_UNESCAPED_SLASHES);
			$config['url'] .= $config['api_request'];
		break;

	}

	if ($config['debug']) echo $config['url'] . PHP_EOL . $config['api_request'] . PHP_EOL;

	$res = order($config, $order);
	if ($config['debug']) print_r($res);
	$result = array(
		'res' => array(
			'placed' => FALSE,
			'code' => '',
			'message' => '',
			'exchange_transaction_id' => ''
		),
		'query' => $order
	);

	switch($config['exchange']) {
		case 'bitmax':
			$result['res']['code'] = $res['code'];
			if ($res['code'] == 0) {
				$result['res']['placed'] = TRUE;
				$result['res']['exchange_transaction_id'] = $res['info']['id'];
			} else {
				$result['res']['message'] = $res['reason'] . ' ' . $res['message'];
			}
		break;
		case 'okex':
			$result['res']['code'] = $res['error_code'];
			if ($res['error_code'] == 0) {
				$result['res']['placed'] = TRUE;
				$result['res']['exchange_transaction_id'] = $res['order_id'];
			} else {
				$result['res']['message'] = $res['error_message'];
			}
		break;
	}

	return $result;
}

/* cancel order on exchange, and receive response */

function cancel_order($config, $order_query) {

	$order = array();

	switch($config['exchange']) {
		case 'bitmax':
			$order['orderId'] = $order_query['orderId'];
			$order['symbol'] = $order_query['symbol'];
			$order['time'] = (int)$config['timestamp'];
			$config['api_request'] = 'order';
			$config['url'] .= $config['group'] . $config['order'];
			$config['method'] = 'DELETE';
		break;
		case 'okex':
			$config['method'] = 'POST';
			$order['instrument_id'] = str_replace('/', '-', $order_query['symbol']);
			$config['api_request'] =
				$config['delete'] .
				$order_query['orderId'] .
				json_encode($order, JSON_UNESCAPED_SLASHES);
			$config['url'] .= $config['api_request'];
		break;
	}

	$res = order($config, $order);
	if ($config['debug']) print_r($res);
	$result = array(
		'res' => array(
			'placed' => FALSE,
			'code' => '',
			'message' => '',
			'exchange_transaction_id' => ''
		),
		'query' => $order
	);

	switch($config['exchange']) {
		case 'bitmax':
			$result['res']['code'] = $res['code'];
			if ($res['code'] == 0) {
				$result['res']['placed'] = TRUE;
			} else {
				$result['res']['message'] = $res['reason'] . ' ' . $res['message'];
			}
		break;
		case 'okex':
			$result['res']['code'] = $res['error_code'];
			if ($res['error_code'] == 0) {
				$result['res']['placed'] = TRUE;
			} else {
				$result['res']['message'] = $res['error_message'];
			}
		break;
	}

	return $result;

}
