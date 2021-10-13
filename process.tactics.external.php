<?php

include('includes.php');

$response = check_tactics_ext($config);
$response['msg'] = 'check_tactics_ext: ' . $response['msg'];
process($response, $config);

$response = process_tactics_ext($config);
$response['msg'] = 'process_tactics_ext: processed ' . $response['count'] . ' tactics_external' . PHP_EOL . $response['msg'];
process($response, $config);

include('system_metrics.php');
