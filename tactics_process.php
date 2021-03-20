<?php

include('includes.php');
echo '<pre>';

$tactic_id = FALSE;
if(isset($_GET['tactic_id'])) $tactic_id = $_GET['tactic_id'];

actionable_tactics($config, $tactic_id);

conditional_tactics($config, $tactic_id);

ordered_tactics($config, $tactic_id);

include('system_metrics.php');
