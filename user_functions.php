<?php

function create_account($config) {

	$config = config_exchange($config);

	switch($config['exchange']) {
		case 'dydx':
			$config['api_request'] = $config['onboarding'];
			$config['url'] .= $config['api_request'];
		break;
	}

	$result = query_api($config);

	return $result;

}
