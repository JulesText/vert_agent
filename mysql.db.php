<?php

/* mysql query and clean functions */

function query($querylabel, $config, $values=NULL) {

	# grab correct query string from query library array
	# values automatically inserted into array
	$query = getsql($config, $values, $querylabel);

	# perform query
	$result=doQuery($query, $config);

	# for testing only: display query
	if ($config['debug_sql']) {
		echo "Query: ". $querylabel . PHP_EOL;
		echo "Values: ";
		print_r($values) . PHP_EOL;
		echo "Result: ";
		print_r($result) . PHP_EOL;
		echo "Config: ";
		var_dump($config) . PHP_EOL;
	}

	return $result;

}

function doQuery($query, $config) {

	$reply = mysqli_query($config['sql_link'], $query);

	# failed query - return FALSE
	if ($reply === FALSE) {
		$result = FALSE;
	# return number of rows changed
	} elseif ($reply === TRUE) {
		$result = mysqli_affected_rows($config['sql_link']);
	# check if select returns zero
	} else if (mysqli_num_rows($reply) === 0) {
		$result = 0;
	# successful select - return array of results
	} else {
		# note that very large result (> 100000) can exceed memory limit
		$result = array();
		while ($mysql_result = mysqli_fetch_assoc($reply))
			$result[] = $mysql_result;
	}

	# get last autoincrement insert id
	# $GLOBALS['lastinsertid'] = mysqli_insert_id($config['sql_link']);

	$error = mysqli_errno($config['sql_link']);
	if ($error) $_SESSION['message'][] =
		"Error $error in query: '" . mysqli_error($config['sql_link']) . "'";

	return $result;

}

function safeIntoDB(&$value, $key, $config) {

	# don't clean arrays - clean individual strings/values
	if (is_array($value)) {

		foreach ($value as $key=>$string) $value[$key] = safeIntoDB($string,$key);
		return $value;

	} else {

		# don't clean filters - we've cleaned those separately in the sqlparts function
		if (
			strpos($key,'filterquery') === FALSE
			&& !preg_match("/^'\d\d\d\d-\d\d-\d\d'$/",$value)
		) { // and don't clean dates
			if (ini_get('magic_quotes_gpc') && !empty($value) && is_string($value))
				$value = stripslashes($value);
			$value = mysqli_real_escape_string($config['sql_link'], $value);
		} else {
			return $value;
		}

		return $value;

	}
}
