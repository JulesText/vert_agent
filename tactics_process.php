<?php

include('includes.php');

$tactic_ids = FALSE;
if(isset($_GET['tactic_ids'])) $tactic_ids = $_GET['tactic_ids'];
$changed_ids = array();

$response = actionable_tactics($config, $tactic_ids);
$response['msg'] = 'actionable_tactics' . PHP_EOL . $response['msg'];
process($response, $config);
foreach ($response['result'] as $key => $val)
	array_push($changed_ids, $key);

$response = ordered_tactics($config, $tactic_ids);
$response['msg'] = 'ordered_tactics' . PHP_EOL . $response['msg'];
process($response, $config);
foreach ($response['result'] as $key => $val)
	array_push($changed_ids, $key);

$response = conditional_tactics($config, $tactic_ids);
$response['msg'] = 'conditional_tactics' . PHP_EOL . $response['msg'];
process($response, $config);
foreach ($response['result'] as $key => $val)
	array_push($changed_ids, $key);

if (!empty($changed_ids)) {
	$changed_ids = dedupe_array($changed_ids);
	$changed_ids = implode(',', $changed_ids);
	$response = trigger_tactics($config, $changed_ids);
	$response['msg'] = 'trigger_tactics ' . PHP_EOL . $response['msg'];
	process($response, $config);
	if ($response['count']) {
		$tactic_ids = implode(',', $response['result']);
		$response = conditional_tactics($config, $tactic_ids);
		$response['msg'] = 'conditional_tactics' . PHP_EOL . $response['msg'];
		process($response, $config);
	}
}

include('system_metrics.php');
