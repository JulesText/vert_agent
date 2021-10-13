<?php

include('includes.php');

if(isset($_GET['pair_id'])) $pair_id = $_GET['pair_id'];
else $pair_id = FALSE;

$response = price_recent($config, $pair_id);
process($response, $config);

include('system_metrics.php');
