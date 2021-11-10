<?php

/* settings server */

$config['timezone_php'] = 'UTC'; # recommend 'UTC'
date_default_timezone_set($config['timezone_php']);

$config['memory_limit'] = '64M'; # memory limit, set '-1' for unlimited, recommend 64M
ini_set('memory_limit', $config['memory_limit']);

/* mysql connection */

$config['sql_link'] = mysqli_connect(
	'localhost', /* hostname of database server */
	'username', /* username */
	'password', /* user password */
	'database' /* name of database */
);

$config['timezone_sql'] = '+00:00'; # recommend '+00:00', equal to UTC
query("SET time_zone = '" . $config['timezone_sql'] . "'", $config);

/* print outputs on screen */

$config['debug'] = FALSE;
$config['debug_sql'] = FALSE;

/* record error messages to log, typically for maintenance bot to find and email alert */

$config['error_log'] = TRUE;

/* cURL parameters */

$config['accept'] = 'application/json';
$config['content_type'] = 'application/json';

/* current time */

$config['timestamp'] = (string) milliseconds();

/* function response handling */

$config['response'] = array(
	'msg' => '',
	'config' => array(),
	'error' => FALSE,
	'alert' => FALSE,
	'count' => 0,
	'result' => array()
);

/* alert bot settings */

/*
	To create a telegram bot, connect to the @BotFather by the link: https://telegram.me/BotFather
	click 'start', then enter '/newbot', enter a name, enter a username
	save the token in $config['chat_token'], it should look like: 000000000:AAAAAAAAA_AAAAAAAA_AAAAAAAA
	to activate messaging, search for the bot username in telegram by @username, start a conversation, and say something like 'test'
	retrieve your user id by starting a conversation with @userinfobot, and save the id in $config['chat_id'], it should look like: 111111111
	For a full description of the telegram bot API, see this page: https://core.telegram.org/bots/api
*/
// alert bot API path
$config['chat_api'] = 'https://api.telegram.org/bot';
// alert bot auth token
$config['chat_token'] = '000000000:AAAAAAAAA_AAAAAAAA_AAAAAAAA';
// alert bot API url
$config['chat_url'] = $config['chat_api'] . $config['chat_token'];
// alert user id
$config['chat_id'] = '111111111';

/* sort order */

$config['sort'] = array();

/* interval key, in minutes */

$config['period'] = array(
	'1' => '1m',
	'5' => '5m',
	'15' => '15m',
	'30' => '30m',
	'45' => NULL,
	'60' => '1h',
	'120' => '2h',
	'240' => '4h',
	'360' => '6h',
	'720' => '12h',
	'1440' => '1d',
	'10080' => '1w'
);

$config['obs_anal_curr'] = 500;

/* price history */

$config['price_query'] = array(
	'pair_id' => 0, /* asset pair id */
	'pair' => '', /* asset pair */
	'exchange' => '', /* exchange name */
	'period' => 0, /* time period between observations as minutes */
	'period_ms' => 0, /* period as milliseconds */
	'hist_long' => FALSE, /* default = FALSE, flag to query long history where possible, if API only provides limited history by default */
	'obs_iter' => 0, /* number of observations per iterative query to API */
	'start' => 0, /* start of time period for API query */
	'stop' => 0, /* end of time period */
	'weekends' => TRUE, /* flag that records will be reported on weekends */
	'dedupe_hist' => TRUE /* default = TRUE, command to deduplicate individual records as they are added */
);

$config['obs_iter_max'] = 100; # default maximum number of observations for each API call
$config['obs_hist_max'] = 0; # if API server supports default maximum number of observations for each API call
$config['obs_curr_max'] = 100; # buffer the demand for price_recent.php, will be repeating every 5 minutes anyway

$config['obs_imp_max'] = 10; # maximum number of consecutive missing price records to impute

/* settings by exchange */

$config['exchanges_trade'] = array('okex', 'ascendex');
$config['exchanges_price'] = array('twelve');
$config['exchange'] = '';
$config['method'] = 'GET'; # default
$config['statuses_orders'] = array( /* 1: include in history_orders(), or 0: exclude */
	'failed' => 1,
	'submitting' => 0,
	'cancelling' => 0,
	'cancelled' => 1,
	'open' => 1,
	'open_partial' => 0, /* open and/or partially filled, may duplicate 'open' */
	'filled_partial' => 0, /* closed and partially filled */
	'filled' => 0, /* closed and filled */
	'complete' => 1 /* completed and filled */
);

/* order parameters */

$config['order_types'] = array('limit', 'market');

/* select $config settings according to exchange */

function config_exchange($config) {

	switch ($config['exchange']) {

		case 'twelve':
			# https://twelvedata.com/docs
			$config['api_key'] = 'api_key';
			$config['url'] = 'https://api.twelvedata.com';
			$config['price_history'] = '/time_series';
			$config['pairs_info'] = '';
			$config['period_exchange'] = array(
				'1' => '1min',
				'5' => '5min',
				'15' => '15min',
				'30' => '30min',
				'45' => '45min',
				'60' => '1h',
				'120' => '2h',
				'240' => '4h',
				'1440' => '1day',
				'10080' => '1week'
			);
			$config['obs_iter_max'] = 5000;
		break;

		case 'ascendex':
			# https://ascendex.github.io/ascendex-futures-pro-api-v2/
			$config['user_id'] = 'U3598552315';
			$config['api_key'] = '1gXxkkwZ0XUAyBJPlaNXvznS0TgqvBf9';
			$config['secret'] = 'uUbbVMNrCNg8EZ8IGSp5WDPVImAfiRF9oVu4ew8I2Y8rEf6fmqGRZ9TKbIRew8Y9';
			$config['url'] = 'https://ascendex.com/';
			$config['group'] = '4/';
			$config['account_info'] = 'api/pro/v1/info';
			$config['account_info_hash'] = 'info';
			$config['account_asset'] = '';
			$config['price'] = 'api/pro/v1/ticker';
			$config['price_history'] = 'api/pro/v1/barhist';
			$config['price_history_hash'] = 'barhist';
			$config['pairs_info'] = '';
			$config['order_book'] = 'api/pro/v1/depth';
			$config['recent_trades'] = 'api/pro/v1/trades';
			$config['balance'] = 'api/pro/v1/cash/balance';
			$config['order'] = 'api/pro/v1/cash/order';
			$config['orders_status'] = 'api/pro/v1/cash/order/status';
			$config['open_orders'] = 'api/pro/v1/cash/order/open';
			$config['order_history'] = 'api/pro/v1/cash/order/hist/current';
			$config['order_history_hash'] = 'order/hist/current';
			$config['delete_all'] = 'api/pro/v1/cash/order/all';
			$config['period_exchange'] = array(
				'1' => '1',
				'5' => '5',
				'15' => '15',
				'30' => '30',
				'60' => '60',
				'120' => '120',
				'240' => '240',
				'360' => '360',
				'720' => '720',
				'1440' => '1d',
				'10080' => '1w'
			);
			$config['status_exchange'] = array();
			$config['obs_iter_max'] = 500;
		break;

		case 'okex':
			# https://www.okex.com/docs/en/
			# depending on the type of http request
			# the api_request may need to match the url, or may only for parts
			$config['user_id'] = '';
			$config['api_key'] = 'api_key';
			$config['secret'] = 'secret';
			$config['pass'] = 'pass';
			$config['url'] = 'https://www.okex.com';
			$config['group'] = '';
			$config['account_info'] = '/api/account/v3/wallet/';
			$config['account_asset'] = '/api/spot/v3/accounts/';
			$config['price'] = '';
			$config['price_history'] = '/api/spot/v3/instruments/';
			$config['pairs_info'] = '/api/spot/v3/instruments/';
			$config['order_book'] = '';
			$config['recent_trades'] = '';
			$config['balance'] = '';
			$config['order'] = '/api/spot/v3/orders';
			$config['orders_status'] = '/api/spot/v3/orders/';
			$config['open_orders'] = '';
			$config['order_history'] = '';
			$config['order_fills'] = '/api/spot/v3/fills';
			$config['delete'] = '/api/spot/v3/cancel_orders/';
			$config['delete_all'] = '';
			$config['period_exchange'] = array(
				'1' => '1',
				'5' => '5',
				'15' => '15',
				'30' => '30',
				'60' => '60',
				'120' => '120',
				'240' => '240',
				'360' => '360',
				'720' => '720',
				'1440' => '1440',
				'10080' => '10080'
			);
			foreach ($config['period_exchange'] as &$val) $val *= 60;
			$config['status_exchange'] = array(
				'failed' => '-2',
				'submitting' => '3',
				'cancelling' => '4',
				'cancelled' => '-1',
				'open' => '0',
				'open_partial' => '6',
				'filled_partial' => '1',
				'filled' => '2',
				'complete' => '7'
			);
			# API allows query of only 200 for some functions
			$config['obs_iter_max'] = 200;
			# API limits history query to last 1440 for most pairs
			$config['obs_hist_max'] = 1440;
			# but full history for some, which are:
			$config['hist_pairs'] = array('BTC/USDT', 'ETH/USDT', 'LTC/USDT', 'ETC/USDT', 'XRP/USDT', 'EOS/USDT', 'BCH/USDT', 'BSV/USDT', 'TRX/USDT');
		break;

		case 'etherscan':
			$config['method'] = 'GET';
			$config['api_key'] = 'ABCDEF0000000000000000000000000000';
			$config['url'] = 'https://api.etherscan.io/api?';
			$config['address_eth'] = '0x000ABC1230000000000000000000000000000000';
		break;

		case 'uniswap':
		# https://uniswap.org/docs/v2/API/queries/#pair-data
			$config['url'] = 'https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v2';
			$config['address_uniswap'] = '0x0000000000000000000000000000000000000000';
		break;

	}

	return $config;

}
