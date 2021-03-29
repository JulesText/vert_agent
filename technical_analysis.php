<?php

include('includes.php');

# https://www.php.net/manual/en/ref.trader.php

echo '<pre>';

if (isset($_GET['pair_id'])) $pair_id = $_GET['pair_id'];
else $pair_id = FALSE;

technical_analysis($config, $pair_id);

include('system_metrics.php');

/*

# ignore all following code


$values['filterquery'] = "
	WHERE pair = '" . $pair . "'
	AND source = '" . $source . "'
	AND period = '" . $period_txt . "'
	AND timestamp >= " . $start . "
	AND timestamp <= " . $stop
;
$history = query('select_history', $config, $values);

echo 'pair: ' . $pair . '<br>';
echo 'source: ' . $source . '<br>';
echo 'period: ' . $period . '<br><br>';
echo 'observations: ' . count($history). '<br>';

usort($history, function($a, $b) {
	return $a['timestamp'] - $b['timestamp'];
});

$last = $history[0]['timestamp'] - $diff;
$missing_data = FALSE;
$i = 0;
foreach ($history as $hist) {
	$diff_obs = $hist['timestamp'] - $last;
	if ($diff !== $diff_obs) {
		$missing_data = TRUE;
		$missing_obs = $hist['timestamp'] - $diff;
		$missing_array = $i;
	}
	$last = $hist['timestamp'];
	$i++;
}
if ($missing_data) {
	echo 'missing data at array position ' . $missing_array . ', <br>time ' . date('Y-m-d H:i T', $missing_obs / 1000) . ', <br>timestamp ' . $missing_obs . ', <br>period ' . $diff . '<br>';
	var_dump($history[$missing_array - 1]);
	var_dump($history[$missing_array]);
}
$data_open = array_column($history, 'open');
$data_high = array_column($history, 'high');
$data_low = array_column($history, 'low');
$data_close = array_column($history, 'close');
$data_volume = array_column($history, 'volume');

$values['filterquery'] = " WHERE class = 'candlestick'";
$indicators = query('select_indicators', $config, $values);

foreach ($indicators as $indicator) {
	$result = $indicator['function']($data_open, $data_high, $data_low, $data_close);
	#if (end($result)) echo $indicator['description'] . ': ' . end($result) . '<br>';
	if ($indicator['reliability'])
		for ($i = count($result) - 5; $i <= count($result); $i++) {
			if ($result[$i]) {
				echo $i . ' ' . $indicator['description'] . ': ' . $result[$i] . ', ';
				echo $indicator['indication'] . ', ' . $indicator['reliability'] . '<br>';
			}
		}
}

$result = trader_ema($data_close, 12);
echo 'ema 12: ' . end($result);
if (end($data_close) > end($result)) {
	echo ' above <br>';
} else {
	echo ' below <br>';
}

$result = trader_ema($data_close, 50);
echo 'ema 50: ' . end($result) . '<br>';

$result = trader_ema($data_close, 200);
echo 'ema 200: ' . end($result) . '<br>';

$result = trader_rsi($data_close, 14);
echo 'rsi 14: ' . end($result) . '<br>';

$result = trader_rsi($data_close, 24);
echo 'rsi 24: ' . end($result) . '<br>';

$last = count($history);
$history_recent = array();
$volume_recent = array();
for ($i = $last - $points; $i < $last; $i++) {
	array_push($history_recent, array(
		$history[$i]['timestamp'],
		$history[$i]['open'],
		$history[$i]['high'],
		$history[$i]['low'],
		$history[$i]['close']
	));
	array_push($volume_recent, array(
		$history[$i]['timestamp'],
		(int)$history[$i]['volume']
	));
}
#var_dump($history_recent);
echo '<br>from ' . date('Y-m-d H:i T', $history_recent[$points - 1][0] / 1000);
echo ' to ' . date('Y-m-d H:i T', $history_recent[0][0] / 1000) . '<br>';
echo $points . ' observations';

?>

<script src="chart/code/highstock.js"></script>
<script src="chart/code/modules/drag-panes.js"></script>
<script src="chart/code/modules/exporting.js"></script>

<!-- tools not working
<script src="chart/code/indicators/indicators-all.js"></script>
<script src="chart/code/modules/annotations-advanced.js"></script>
<script src="chart/code/modules/price-indicator.js"></script>
<script src="chart/code/modules/full-screen.js"></script>
<script src="chart/code/modules/stock-tools.js"></script>
<link rel="stylesheet" type="text/css" href="chart/css/stocktools/gui.css">
<link rel="stylesheet" type="text/css" href="chart/css/annotations/popup.css">
-->

<div id="container" style="width:100%; height:400px;">
</div>

<script type="text/javascript">

let chart; // globally available

document.addEventListener('DOMContentLoaded', function () {

	const timezone = new Date().getTimezoneOffset()

	Highcharts.setOptions({
		global: {
			timezoneOffset: timezone
		}
	});

	// create the chart
	chart = Highcharts.stockChart('container', {

		rangeSelector: {
			selected: 1
		},

		yAxis: [{
			labels: {
				align: 'right',
				x: -3
			},
			title: {
				text: 'OHLC'
			},
			height: '100%',
			lineWidth: 2,
			resize: {
				enabled: true
			}
		}, {
			labels: {
				align: 'right',
				x: -3
			},
			title: {
				text: 'Volume'
			},
			top: '65%',
			height: '35%',
			offset: 0,
			lineWidth: 2
		}],

		tooltip: {
			split: true,
			headerFormat: '{point.x:%e %b %H:%M}'
		},

		series: [{
			type: 'candlestick',
			name: 'Pair',
			color: 'red',
			upColor: 'green',
			data: <?php echo json_encode($history_recent); ?>,
			pointStart: <?php echo $history_recent[0][0]; ?>,
			pointInterval: <?php echo $diff; ?>
		}, {
			type: 'column',
			name: 'Volume',
			color: '#6666aa',
			data: <?php echo json_encode($volume_recent); ?>,
			pointStart: <?php echo $volume_recent[0][0]; ?>,
			pointInterval: <?php echo $diff; ?>,
			yAxis: 1,
		}]
	});
});
</script>

<?php
}
