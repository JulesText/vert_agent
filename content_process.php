<?php

include('includes.php');

$query = "SELECT content_id, source, url, timestamp, notified FROM web_content";
$sentiments = query($query, $config);

if (!empty($sentiments))
foreach ($sentiments as $sentiment) {

	$response = $config['response'];

	$config['exchange'] = $sentiment['source'];
	$config = config_exchange($config);

	$config['url'] = $sentiment['url'];
	$content_new = query_api($config);

	$values = array();
	$values['content'] = $content_new['data']['children'][0]['data']['selftext'];

	# is new sentiment content?
	if (!isset($sentiment['content_id'])) {

		$query = query('update_content', $config, $values);

	# is comparing existing content?
	} else {

		$values['filterquery'] = " WHERE `content_id` = '" . $sentiment['content_id'] . "'";
		$content_old = query('select_content', $config, $values);

		if ($content_old[0]['content'] !== $values['content']) {

			$response['msg'] .= 'content changed at [this link](' . $sentiment['url'] . ')';
			$response['alert'] = TRUE;
			process($response, $config);

		}

	}

}

include('system_metrics.php');
