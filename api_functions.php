<?php

/* send request to API  */

function query_api($config, $default_headers = FALSE) {

	$httpheader = http_headers($config, $default_headers);

	$class = new APIREST($config['url']);
	$result = $class -> call($httpheader, $config['method']);

	if ($result['error']) {
		$response = $config['response'];
		$response['msg'] =
			'failed: query_api() ' . PHP_EOL .
			'exchange: ' . $config['exchange'] . PHP_EOL .
			'response: ' . PHP_EOL . $result['result'] . PHP_EOL .
			'api_request: ' . $config['api_request'] . PHP_EOL
		;
		if ($config['debug']) $response['msg'] .= 'url: ' . $config['url'] . PHP_EOL;
		# avoid interminable loop if alert query_api() has failed
		if (strpos($config['url'], $config['chat_url']) === 0)
			$response['alert'] = FALSE;
		else
			$response['alert'] = TRUE;
		process($response, $config);
	}

	return json_decode($result['result'], TRUE);

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

			case 'dydx':
				$config['msg'] =
					$config['timestamp_iso8601'] .
					$config['method'] .
					$config['api_request'] .
					$config['data']
				;
				$config['signature'] = hmac($config['api_secret'], $config['msg']);
				$config['signature'] = hmac($config['msg'], $config['api_secret']);
				$httpheader = array(
					'Accept: ' . $config['accept']
					,'Content-Type: ' . $config['content_type']
					,'DYDX-TIMESTAMP: ' . $config['timestamp_iso8601']
					,'DYDX-SIGNATURE: ' . $config['signature']
					,'DYDX-API-KEY: ' . $config['api_key']
					,'DYDX-PASSPHRASE: ' . $config['api_passphrase']
					,'DYDX-ETHEREUM-ADDRESS: ' . $config['ethereumAddress']
					,'DYDX-ACCOUNT-NUMBER: ' . $config['account_num']
				);

			break;

			case 'ascendex':
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

			case 'okex_spot':
			case 'okex_margin':
				$config['msg'] =
					$config['timestamp_iso8601'] .
					$config['method'] .
					$config['api_request']
				;
				$config['signature'] = hmac($config['msg'], $config['secret']);
				$httpheader = array(
					'Accept: ' . $config['accept'],
					'Content-Type: ' . $config['content_type'],
					'ok-access-key: ' . $config['api_key'],
					'ok-access-sign: ' . $config['signature'],
					'ok-access-timestamp: ' . $config['timestamp_iso8601'],
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

/* REST API class */
class APIREST {

	private $url;
	public $log;
	public function __construct($url) {
		$this->url = $url;
	}

	/**
	* @param $httpheader array of headers
	* @return response_api
	*/
	public function call($httpheader, $method, $query = NULL) {

		try {

			$curl = curl_init();
			if ($curl === FALSE)
				throw new Exception('Failed to initialize');

			$verbose = fopen('error_log_api', 'w+');
			$curl_opt = array(
				CURLOPT_URL => $this->url,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 60, /* number of seconds to allow curl function to execute */
				CURLOPT_CONNECTTIMEOUT => 60, /* number of seconds to wait for response */
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_POSTFIELDS => $query,
				CURLOPT_HTTPHEADER => $httpheader,
				CURLOPT_VERBOSE => TRUE,
				CURLOPT_STDERR => $verbose
			);
			curl_setopt_array($curl, $curl_opt);

			$response_api = curl_exec($curl);
			rewind($verbose);
			$this->log = stream_get_contents($verbose);
			if ($response_api === FALSE)
				throw new Exception(curl_error($curl), curl_errno($curl));

			$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($http_status != 200)
				throw new Exception($response_api, $http_status);
			curl_close($curl);

			$error = FALSE;

		} catch(Exception $e) {

			$response_api = $e->getCode() . $e->getMessage();

			$error = TRUE;

		}

		return array('error' => $error, 'result' => $response_api);

	}
}

/* encrypt key */

function hmac($str1, $str2) {

	$hmac = hash_hmac('sha256', $str1, $str2, true);
	$hmac = base64_encode($hmac);
	return $hmac;

}
