<?php

include('includes.php');
echo '<pre>';

price_history_trim($config);

price_history_dedupe($config);

price_history_imputed($config);

include('system_metrics.php');
