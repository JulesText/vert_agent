<?php

include('includes.php');
echo '<pre>';

if(isset($_GET['pair_id'])) $pair_id = $_GET['pair_id'];
else $pair_id = FALSE;

price_history($config, $pair_id);

include('system_metrics.php');
