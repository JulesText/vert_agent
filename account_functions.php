<?php

/* query assets held on exchange for account */

function check_account($config, $asset = '') {

	$response = $config['response'];

	// query assets
	$config['api_request'] = $config['account_asset'] . $asset;
	$config['url'] .= $config['api_request'];
	$result = query_api($config);

	// check number of responses
	if (empty($result)) {
		$response['msg'] = 'no matching asset balance returned';
		return $response;
	} else if (countdim($result) == 1) {
		$result = array($result);
	}

	// map responses
	foreach ($result as $res) {

		switch ($config['exchange']) {

			case 'okex_spot':
			case 'okex_margin':
				$response['result'][$res['currency']]['available'] = $res['available'];
				$response['result'][$res['currency']]['total'] = $res['balance'];
			break;

		}

		$response['count']++;

	}

	return $response;

}

/* query pair parameters for exchange */

function check_pairs($config, $pair = '') {

	$response = $config['response'];

	// query pair list from exchange
	// exchange might not allow querying specific pairs so query them all
	$config['api_request'] = $config['pairs_info'];
	$config['url'] .= $config['api_request'];
	$result = query_api($config);

	// find minimum amount, and minimum size increment amount
	// map responses
	switch ($config['exchange']) {

		case 'okex_spot':
		case 'okex_margin':
			// if necessary, limit result to request pair
			if ($pair !== '') {
				$key = array_search(
					str_replace('/', '-', $pair),
					array_column($result, 'instrument_id')
				);
				if ($key === FALSE) {
					$response['msg'] = 'no matching pair returned';
					break;
				}
				$result = array($result[$key]);
			}
			foreach ($result as $res) {
				$pair = str_replace('-', '/', $res['instrument_id']);
				$response['result'][$pair]['price_increment'] = $res['tick_size'];
				$response['result'][$pair]['amount_increment'] = $res['size_increment'];
				$response['result'][$pair]['amount_minimum'] = $res['min_size'];
				$response['count']++;
			}
		break;

	}

	return $response;

}

/* query leverage parameter for asset */

function get_leverage($config, $loan) {

	$response = $config['response'];

	// find account maximum leverage ratio
	switch ($config['exchange']) {

		case 'okex_margin':

			$config['api_request'] = $config['leverage_get'] . str_replace('/', '-', $loan['pair']) . '/leverage';
			$config['url'] .= $config['api_request'];
			$result = query_api($config);

			// check result
			if (empty($result)) {
				$response['msg'] = 'server returned no result' . PHP_EOL;
				$response['error'] = TRUE;
				return $response;
			}
			if ($result['error_code'] !== '') {
				$response['msg'] .= $result['error_message'] . PHP_EOL;
				$response['error'] = TRUE;
				return $response;
			}

			// store result
			$loan['leverage_available'] = $result['leverage'];
			$response['result'] = $loan;

		break;

	}

	return $response;

}

/* set leverage parameter for asset */

function set_leverage($config, $loan) {

	$response = $config['response'];

	// set values to fixed format with 3 decimals to ensure accurate comparison, then set to float
	$loan['leverage_asset_max'] = (float) number_format($loan['leverage_asset_max'], 3, '.', '');
	$loan['leverage_available'] = (float) number_format($loan['leverage_available'], 3, '.', '');

	$response['result'] = $loan;

	// calculate new leverage ratio
	// if already equal, no need to update so exit
	if ($loan['leverage_asset_max'] == $loan['leverage_available']) {
		return $response;
	}
	// if asset pair leverage is greater than available leverage
	else if ($loan['leverage_asset_max'] > $loan['leverage_available']) {
		// then check max available leverage and increase to asset pair leverage if possible
		// to do: add max available leverage query
		$leverage_max_available = 3; # temp var $leverage_max_available until able to query
		$leverage_max_available = (float) number_format($leverage_max_available, 3, '.', '');
		// if available leverage already at max, exit
		if ($loan['leverage_available'] == $leverage_max_available) return $response;
		// if max available leverage > asset leverage max, set to asset leverage max
		else if ($leverage_max_available >= $loan['leverage_asset_max']) $leverage_new = $loan['leverage_asset_max'];
		// max available leverage < asset leverage max, set to max available leverage
		else $leverage_new = $leverage_max_available;
		$leverage = array('leverage' => (string) $leverage_new);
	}
	// if asset pair leverage is less than available leverage
	else if ($loan['leverage_asset_max'] < $loan['leverage_available']) {
		// then lower available leverage
		$leverage = array('leverage' => (string) $loan['leverage_asset_max']);
	}

	// set account maximum leverage ratio
	switch ($config['exchange']) {

		case 'okex_margin':

			$config['method'] = 'POST';
			$config['api_request'] =
				$config['leverage_set'] .
				str_replace('/', '-', $loan['pair']) .
				'/leverage'
			;
			$config['url'] .= $config['api_request'];
			$config['api_request'] .= json_encode($leverage, JSON_UNESCAPED_SLASHES);
			/*
			to do: fix json error, maybe compare with similar POST query for orders
			$result = query_api($config);

			// check result
			if (empty($result)) {
				$response['msg'] = 'server returned no result' . PHP_EOL;
				$response['error'] = TRUE;
				return $response;
			}
			if ($result['error_code'] !== '') {
				$response['msg'] .= $result['error_message'] . PHP_EOL;
				$response['error'] = TRUE;
				return $response;
			}

			// store result
			$loan['leverage_available'] = $result['leverage'];
			$response['result'] = $loan;
			*/

		break;

	}

	return $response;

}

/* query leverage parameters for exchange */

function check_credit($config, $loan) {

	$response = $config['response'];
var_dump($loan);
	// find maximum loan amount, and set maximum leverage ratio
	switch ($config['exchange']) {

		case 'okex_margin':

			$config['api_request'] = $config['loan_avail'] . str_replace('/', '-', $loan['pair']) . '/availability';
			$config['url'] .= $config['api_request'];
			$result = query_api($config);
			var_dump($result);die;

			// if necessary, limit result to request pair
			if ($pair !== '') {
				$key = array_search(
					str_replace('/', '-', $pair),
					array_column($result, 'instrument_id')
				);
				if ($key === FALSE) {
					$response['msg'] = 'no matching pair returned';
					break;
				}
				$result = array($result[$key]);
			}
			foreach ($result as $res) {
				$pair = str_replace('-', '/', $res['instrument_id']);
				$response['result'][$pair]['price_increment'] = $res['tick_size'];
				$response['result'][$pair]['amount_increment'] = $res['size_increment'];
				$response['result'][$pair]['amount_minimum'] = $res['min_size'];
				$response['count']++;
			}
		break;

	}

	return $response;

}
