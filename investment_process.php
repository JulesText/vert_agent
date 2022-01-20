<?php

include('system_includes.php');

# process.investments.php?investment_id=1
if (isset($_GET['investment_id'])) { $investment_id = $_GET['investment_id']; } else { $investment_id = ''; }

$response = update_investments($config, $investment_id);
$response['msg'] = 'update_investments' . PHP_EOL . $response['msg'];
process($response, $config);

$response = report_investments($config, $investment_id);
$response['msg'] = 'report_investments' . PHP_EOL . $response['msg'];
process($response, $config);

include('system_speed.php');
