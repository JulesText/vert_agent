<?php

/* send request to API  */

function info($config) {

	$httpheader = http_headers($config);

	$class = new APIREST($config['url']);
	$result = $class -> call($httpheader, $config['method']);

	return json_decode($result, TRUE);

}

/* send notification message to telegram bot */
function telegram($config) {

	/*

	show chat id (needs webhook disabled):
	https://api.telegram.org/bot0000000000:xxx_API_KEY_xxx/getUpdates

	Use $config['chatPath'] the HTTP API:

	For a description of the Bot API, see this page: https://core.telegram.org/bots/api

	for interactive bot
	example: https://nordicapis.com/how-to-build-your-first-telegram-bot-using-php-in-under-30-minutes/

	activate webhook:
	https://api.telegram.org/bot0000000000:xxx_API_KEY_xxx/setwebhook?url=https://your.server.com/telegram.php

	deactivate webhook:
	https://api.telegram.org/bot0000000000:xxx_API_KEY_xxx/setwebhook?url=

	*/

	$httpheader = http_headers($config, TRUE);
	$query =
		$config['chatPath'] .
		'/sendmessage?chat_id=' . $config['chatId'] .
		'&parse_mode=html&text=' . $config['chatText']
	;

	$class = new APIREST($query);
	$result = $class -> call($httpheader, $config['method']);

	return json_decode($result, TRUE);

}

/* define http headers and parameters for exchange API */
function http_headers($config, $default = FALSE) {

	if ($default) {

		$httpheader = array(
			'Accept: ' . $config['accept'],
			'Content-Type: ' . $config['content_type']
		);

	} else {

		switch ($config['exchange']) {

			case 'bitmax':
				$config['msg'] =
					$config['timestamp'] .
					'+' .
					$config['api_request']
				;
				$config['signature'] = hmac($config['msg'], $config['secret']);
				$httpheader = array(
					'Accept: ' . $config['accept'],
					'Content-Type: ' . $config['content_type'],
					'x-auth-key: ' . $config['api_key'],
					'x-auth-signature: ' . $config['signature'],
					'x-auth-timestamp: ' . $config['timestamp']
				);
			break;

			case 'okex':
				$date =  new DateTime();
				$timestamp = $date->format('Y-m-d\TH:i:s\.000\Z'); # ISO 8601 standard format with Z
				$config['msg'] =
					$timestamp .
					$config['method'] .
					$config['api_request']
				;
				$config['signature'] = hmac($config['msg'], $config['secret']);
				$httpheader = array(
					'Accept: ' . $config['accept'],
					'Content-Type: ' . $config['content_type'],
					'ok-access-key: ' . $config['api_key'],
					'ok-access-sign: ' . $config['signature'],
					'ok-access-timestamp: ' . $timestamp,
					'ok-access-passphrase: ' . $config['pass']
					#'OK-TEXT-TO-SIGN: ' . $config['msg']
				);
			break;

			default:
				$httpheader = array(
					'Accept: ' . $config['accept'],
					'Content-Type: ' . $config['content_type']
				);

		}

	}

	return $httpheader;

}
