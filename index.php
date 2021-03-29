<?php

include('includes.php');

if (isset($_GET['exchange'])) { $config['exchange'] = $_GET['exchange']; } else { $config['exchange'] = ''; }
if (isset($_GET['query'])) { $query = $_GET['query']; } else { $query = ''; }
if (isset($_GET['action'])) { $action = $_GET['action']; } else { $action = ''; }
if (isset($_GET['id'])) { $id = $_GET['id']; } else { $id = ''; }
if (isset($_GET['var1'])) { $var1 = $_GET['var1']; } else { $var1 = ''; }
if (isset($_GET['var2'])) { $var2 = $_GET['var2']; } else { $var2 = ''; }
if (isset($_GET['var3'])) { $var3 = $_GET['var3']; } else { $var3 = ''; }
if (isset($_GET['var4'])) { $var4 = $_GET['var4']; } else { $var4 = ''; }
if (isset($_GET['var5'])) { $var5 = $_GET['var5']; } else { $var5 = ''; }
if (isset($_GET['var6'])) { $var6 = $_GET['var6']; } else { $var6 = ''; }
if (isset($_GET['var7'])) { $var7 = $_GET['var7']; } else { $var7 = ''; }

$config = config_exchange($config);

echo '<pre>';

# test uniswap
# index.php?action=uniswap&id=1
if ($action == 'uniswap') {

	$result = info_uniswap($config);
	var_dump($result);

}

# process actionable tactics
# index.php?action=check_transaction&transaction_id=171
if ($action == 'check_transaction') {

	check_transaction($config, $_GET['transaction_id']);

}

# process actionable tactics
# index.php?action=actionable_tactics&tactic_id=108,107
if ($action == 'actionable_tactics') {

	$tactic_id = FALSE;
	if (isset($_GET['tactic_id'])) $tactic_id = $_GET['tactic_id'];

	actionable_tactics($config, $tactic_id);

}


# refresh tactic
# index.php?action=conditional_tactics&tactic_id=108,107
if ($action == 'conditional_tactics') {

	$tactic_id = FALSE;
	if (isset($_GET['tactic_id'])) $tactic_id = $_GET['tactic_id'];

	conditional_tactics($config, $tactic_id);

}

# refresh tactic
# index.php?action=ordered_tactics&tactic_id=108,107
if ($action == 'ordered_tactics') {

	$tactic_id = FALSE;
	if (isset($_GET['tactic_id'])) $tactic_id = $_GET['tactic_id'];

	ordered_tactics($config, $tactic_id);

}

# current price
# index.php?exchange=bitmax&query=price_current&var1=STAKE/USDT
if ($query == 'price_current') {

	$config['api_request'] = 'balance';
	$coins = '?symbol=' . $var1;
	$config['url'] .= $config['price'] . $coins;
	$result = info($config);
	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

}

# historical price
# index.php?exchange=okex&query=price_history&archive=TRUE&pair=BTC-USDT&period=1440&obs_iter=200&start=1592573700000&stop=1593677000000
if ($query == 'price_history') {

	if (isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = ''; }
	if (isset($_GET['period'])) { $period = $_GET['period']; } else { $period = ''; }
	if (isset($_GET['archive'])) { $archive = $_GET['archive']; } else { $archive = FALSE; }
	if (isset($_GET['obs_iter'])) { $obs_iter = $_GET['obs_iter']; } else {
		if ($archive) {
			$obs_iter = $config['obs_iter_hist'];
		} else $obs_iter = $config['obs_iter_curr'];
	}

	# calculate time periods
	$period_ms = $period * 60000;
	$current_period = floor(milliseconds()/$period_ms) * $period_ms;
	if (isset($_GET['start'])) { $start = $_GET['start']; } else { $start = $current_period - $period_ms * $obs_iter; }
	if (isset($_GET['stop'])) { $stop = $_GET['stop']; } else { $stop = $current_period; }
	echo '$start: ' . $start . ' $stop: ' . $stop . '<br>';
	echo '$start: ' . date('Y-m-d H:i:s', $start / 1000) . ' $stop: ' . date('Y-m-d H:i:s', $stop / 1000) . '<br>';

	# array format defined in config.php
	$ph = $config['price_query'];
	$ph['pair'] = $pair;
	$ph['period'] = $period;
	$ph['period_ms'] = $period_ms;
	$ph['hist_long'] = $archive;
	$ph['obs_iter'] = $obs_iter;
	$ph['start'] = $start;
	$ph['stop'] = $stop;

	price_query($config, $ph);

}

# deduplicate history
# index.php?query=price_history_dedupe
if ($query == 'price_history_dedupe') {

	echo '<pre>';
	price_history_dedupe();

}

# order book
if ($query == 'order_book') {

	$config['api_request'] = 'depth';
	$coins = '?symbol=' . $var1;
	$config['url'] .= $config['order_book'] . $coins;
	$result = info($config);
	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

}

# recent trades
if ($query == 'recent_trades') {

	$config['api_request'] = 'trades';
	$coins = '?symbol=' . $var1;
	$number = '&n=100';
	$config['url'] .= $config['recent_trades'] . $coins . $number;
	$result = info($config);
	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

}

# account balance
if ($query == 'balance') {

	$config['api_request'] = 'balance';
	$config['url'] .= $config['group'] . $config['balance'];
	$result = info($config);
	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

}

# account info
if ($query == 'account_info') {

	switch ($config['exchange']) {

		case 'bitmax':
			$config['api_request'] = 'user/info';
		break;

		case 'okex':
			$config['api_request'] = $config['account_info'];
		break;

	}

	$config['url'] .= $config['account_info'];
	$result = info($config);
	if ($config['debug']) { echo 'url: ' . $config['url'] . PHP_EOL . 'result: '; print_r($result); }

}

# update open orders
if ($query == 'open_orders') {

	# index.php?exchange=bitmax&query=open_orders
	$config['api_request'] = 'order/open';
	$config['url'] .= $config['group'] . $config['open_orders'];
	$result = info($config);

	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

	update_transactions($config, $result);

}

# update history orders
if ($query == 'history_orders' && $action == 'save') {

	# index.php?exchange=bitmax&query=history_orders&action=save
	# index.php?exchange=okex&query=history_orders&action=save&pair=CVP/USDT

	if (isset($_GET['pair'])) $config['pair'] = $_GET['pair'];
	if (isset($_GET['exchange'])) $config['exchange'] = $_GET['exchange'];
	update_transactions($config);

}

# calculate orders
if ($query == 'calculate_orders') {

	# index.php?query=calculate_orders

	calculate_orders($config);

}

# list history orders
if ($query == 'history_orders' && $action == 'print') {

	# index.php?exchange=bitmax&query=history_orders&action=print
	$config['api_request'] = 'order/hist/current';
	$config['url'] .= $config['group'] . $config['order_history'];
	$config['url'] .= '?executedOnly=1&n=1000';
	$result = info($config);
	if ($config['debug']) { echo $config['url'] . PHP_EOL; print_r($result); }

}

# place order
if ($query == 'place_order') {

	/*
	index.php?exchange=bitmax&query=place_order&symbol=STAKE/USDT&orderPrice=40.00&orderQty=1&orderType=limit&side=sell&respInst=ACCEPT
	index.php?
	exchange=bitmax
	&query=place_order
	&orderId=12345678Z
	&symbol=STAKE/USDT
	&orderPrice=40.00
	&orderQty=1
	&orderType=limit
	&side=sell
	&respInst=ACCEPT

	index.php?exchange=okex&query=place_order&symbol=ETH/USDT&orderPrice=40.00&orderQty=1&orderType=limit&orderTypeCode=0&side=sell
	index.php?
	exchange=bitmax
	&query=place_order
	&orderId=12345678Z
	&symbol=ETH/USDT
	&orderPrice=40.00
	&orderQty=1
	&orderType=limit
	&side=sell
	&orderTypeCode=0
	*/

	$order_query = $_GET;

	place_order($config, $order_query, $orderId);

}

# cancel order
if ($query == 'cancel_order') {

	# index.php?exchange=bitmax&query=cancel_order&var1=12345678Z&var2=12345678Z&var3=STAKE/USDT

	$order = array();
	$order['id'] = $var1;  # optional, for echo back
	$order['orderId'] = $var2;
	$order['symbol'] = $var3;
	$order['time'] = (int)$config['timestamp'];

	$result = cancel_order($config, $order);

}

# delete all orders (by symbol)
if ($query == 'delete_all_order') {

	# index.php?exchange=bitmax&query=delete_all_order&var1=ETH/USDT
	$config['api_request'] = 'order/all';
	$config['url'] .= $config['group'] . $config['delete_all'];
	$config['method'] = 'DELETE';

	$order = array();
	$order['symbol'] = $var1; # optional limit to coin pair

	$result = order($config, $order);

	if ($config['debug']) {
		echo $config['url'] . PHP_EOL;
		print_r($order) . PHP_EOL;
		print_r($result) . PHP_EOL;
	}

}

include('system_metrics.php');
