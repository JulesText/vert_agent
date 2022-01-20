<?php

include('system_includes.php');

$response = price_history_trim($config);
process($response, $config);

$response = price_history_dedupe($config);
process($response, $config);

$response = price_history_imputed($config);
process($response, $config);

$response = convert_timestamps($config);
process($response, $config);

include('system_speed.php');
