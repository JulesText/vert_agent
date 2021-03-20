<?php

/* settings server */

date_default_timezone_set('UTC');

if(version_compare(phpversion(),"8.0.0",'>=')
    || version_compare(phpversion(),"7.0.0",'<'))
    die ('requires php version 7.x.x, running ' . phpversion());

ini_set('memory_limit', '64M'); # memory limit, set '-1' for unlimited, default 32M

/* mysql connection */

$config['sql_link'] = mysqli_connect(
        'localhost', /* hostname of database server */
        'username', /* username */
        'password', /* user password */
        'database' /* name of database */
    )
    or die ('Unable to connect to MySQL server');

/* settings bot print outputs on screen */

$config['debug'] = TRUE;
$config['debug_sql'] = FALSE;

/* settings bot print cURL parameters */

$config['accept'] = 'application/json';
$config['content_type'] = 'application/json';
$config['timestamp'] = (string) milliseconds();

/* telegram settings */

$config['chatId'] = 'chatId'; # default telegram chat id
$config['chatPath'] = 'https://api.telegram.org/bot132'; # bot API path and key
$config['chatText'] = 'message failed';

/* database arrays */

$values = array();

$config['transaction'] = array (
  'transaction_id' => NULL,
  'investment_id' => NULL,
  'investment_proportion' => NULL,
  'time_opened' => NULL,
  'time_closed' => NULL,
  'capital_amount' => NULL,
  'capital_fee' => NULL,
  'purpose' => NULL,
	'exchange' => NULL,
	'exchange_transaction_id' => NULL,
	'exchange_transaction_status' => NULL,
	'percent_complete' => NULL,
	'pair_asset' => NULL,
	'from_asset' => NULL,
	'from_amount' => NULL,
	'to_asset' => NULL,
	'to_amount' => NULL,
	'to_fee' => NULL,
	'pair_price' => NULL,
	'from_price_usd' => NULL,
	'to_price_usd' => NULL,
	'price_reference' => NULL,
  'fee_amount_usd' => NULL,
  'price_aud_usd' => NULL,
  'aud_usd_reference' => NULL,
  'from_wallet' => NULL,
  'to_wallet' => NULL,
  'tactic_id' => NULL,
  'strategy_result_usd' => NULL
	);

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
  'source' => '', /* exchange name */
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

$config['obs_imp_max'] = 5; # maximum number of consecutive missing price records to impute

/* settings by exchange */

$config['exchanges_trade'] = "'okex', 'bitmax'";
$config['exchange'] = '';
$config['method'] = 'GET'; # default

/* select $config settings according to exchange */

function config_exchange($config) {

  switch ($config['exchange']) {

  case 'twelve':
    # https://twelvedata.com/docs
    $config['api_key'] = 'api_key';
    $config['url'] = 'https://api.twelvedata.com';
    $config['price_history'] = '/time_series';
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

  case 'bitmax':
    # https://bitmax-exchange.github.io/bitmax-pro-api/
    $config['user_id'] = 'user_id';
    $config['api_key'] = 'api_key';
    $config['secret'] = 'secret';
    $config['url'] = 'https://bitmax.io/';
    $config['group'] = '4/';
    $config['account_info'] = 'api/pro/v1/info';
    $config['price'] = 'api/pro/v1/ticker';
    $config['price_history'] = 'api/pro/v1/barhist';
    $config['order_book'] = 'api/pro/v1/depth';
    $config['recent_trades'] = 'api/pro/v1/trades';
    $config['balance'] = 'api/pro/v1/cash/balance';
    $config['order'] = 'api/pro/v1/cash/order';
    $config['orders_status'] = 'api/pro/v1/cash/order/status';
    $config['open_orders'] = 'api/pro/v1/cash/order/open';
    $config['order_history'] = 'api/pro/v1/cash/order/hist/current';
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
    $config['obs_iter_max'] = 500;
    break;

  case 'okex':
    # https://www.okex.com/docs/en/
    $config['user_id'] = '';
    $config['api_key'] = 'api_key';
    $config['secret'] = 'secret';
    $config['pass'] = 'pass';
    $config['url'] = 'https://www.okex.com';
    $config['group'] = '';
    $config['account_info'] = '/api/account/v3/wallet/';
    $config['price'] = '';
    $config['price_history'] = '/api/spot/v3/instruments/';
    $config['order_book'] = '';
    $config['recent_trades'] = '';
    $config['balance'] = '';
    $config['order'] = '/api/spot/v3/orders/';
    $config['orders_status'] = '/api/spot/v3/orders/';
    $config['open_orders'] = '';
    $config['order_history'] = '';
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
    # API allows query of only 200 for some functions
    $config['obs_iter_max'] = 200;
    # API limits history query to last 1440 for most pairs
    $config['obs_hist_max'] = 1440;
    # but full history for some, which are:
    $config['hist_pairs'] = array('BTC/USDT', 'ETH/USDT', 'LTC/USDT', 'ETC/USDT', 'XRP/USDT', 'EOS/USDT', 'BCH/USDT', 'BSV/USDT', 'TRX/USDT');
    break;

  case 'uniswap':
    $config['url'] = 'https://api.thegraph.com/subgraphs/name/uniswap/uniswap-v2';
    $config['address_uniswap'] = '0x0000000000000000000000000000000000000000';
    break;

    }

    return $config;

}
