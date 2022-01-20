<?php

include('system_includes.php');

if (isset($_GET['pair_id'])) $pair_id = $_GET['pair_id'];
else $pair_id = FALSE;
if (isset($_GET['history'])) $history = $_GET['history'];
else $history = FALSE;

if ($history) $response = price_history($config, $pair_id);
else $response = price_recent($config, $pair_id);
process($response, $config);

include('system_speed.php');
