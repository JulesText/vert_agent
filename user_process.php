<?php

include('system_includes.php');

if (isset($_GET['exchange'])) $config['exchange'] = $_GET['exchange'];
else die;

$config['debug'] = TRUE;

$response = create_account($config);
process($response, $config);

include('system_speed.php');
