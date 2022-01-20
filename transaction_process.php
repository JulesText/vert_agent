<?php

include('includes.php');

# process.transactions.php?exchange=okex&account=margin&pair=CVP/USDT
if (isset($_GET['exchange'])) { $exchange = $_GET['exchange']; } else { $exchange = ''; }
if (isset($_GET['pair'])) { $pair = $_GET['pair']; } else { $pair = ''; }

$response = update_transactions($config, $exchange, $pair);
$response['msg'] = 'update_transactions' . PHP_EOL . $response['msg'];
process($response, $config);

$response = calculate_aud($config, TRUE);
$response['msg'] = 'calculate_aud' . PHP_EOL . $response['msg'];
process($response, $config);

include('system_metrics.php');
