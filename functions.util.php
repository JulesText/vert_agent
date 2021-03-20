<?php

/* REST API class */
class APIREST
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @param $httpheader array of headers
     * @return response
     */
    public function call($httpheader, $method, $query = NULL)
    {
        try
        {
            $curl = curl_init();
            if (FALSE === $curl)
                throw new Exception('Failed to initialize');

            $curl_opt = array(
                CURLOPT_URL => $this->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 60, /* number of seconds to wait for response */
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $query,
                CURLOPT_HTTPHEADER => $httpheader
            );
            curl_setopt_array($curl, $curl_opt);

            $response = curl_exec($curl);
            if (FALSE === $response)
                throw new Exception(curl_error($curl), curl_errno($curl));

            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if (200 != $http_status)
                throw new Exception($response, $http_status);
            curl_close($curl);
        }
        catch(Exception $e)
        {
            $response= $e->getCode() . $e->getMessage();

            echo $response;
        }
        return $response;
    }
}

/* get timestamp */

function milliseconds() {
    list($msec, $sec) = explode(' ', microtime());
    return (int) ($sec . substr($msec, 2, 3));
}

/* encrypt key */

function hmac($msg, $secret) {
    $hmac = hash_hmac('sha256', $msg, $secret, true);
    $hmac = base64_encode($hmac);
    return $hmac;
}

/* array depth */

function countdim($array) {
  if (is_array(reset($array))) {
    $return = countdim(reset($array)) + 1;
  } else {
    $return = 1;
  }
  return $return;
}

/* asset pair only reported on weekdays
  expect some asset pairs are not recorded on weekends, for instance fiat currencies like $AUD to $USD
*/

function weekends($class) {
  if($class == 'fiat' || $class == 'stock') {
    $weekend = FALSE;
  } else {
    $weekend = TRUE;
  }
  return $weekend;
}

/* count number of observations falling on weekends */

function count_on_weekends($from, $to, $period_ms) {
  $wmiss = 0;
  for ($j = $from; $j <= $to; $j += $period_ms) {
    $day = date('l', $j / 1000);
    if(!($day == 'Sunday' || $day == 'Monday')) $wmiss++;
    #echo 'check UTC ' . date('Y-m-d H:i:s', $j / 1000) . ' ' . $day . PHP_EOL;
  }
  return $wmiss;
}
