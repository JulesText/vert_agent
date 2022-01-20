<?php

/* calculate and save price indicators */

function technical_analysis($config, $pair_ids = FALSE, $backdate = FALSE) {

	$response = $config['response'];

	# values in the returned arrays from trader will be rounded to this precision
	ini_set('trader.real_precision', '18');

	/*
	new code snippet
	SELECT FORMAT(a.value, b.decimals) as value FROM ana_price as a LEFT JOIN ana_measurement as b
	ON a.indicator = b.indicator
	*/


	$query = "
		SELECT
		pair_id,
		pair,
		period,
		exchange,
		currency_end,
		history_start
		FROM asset_pairs
		WHERE analyse
	";
	if ($pair_ids) $query .= " AND pair_id IN (" . $pair_ids . ")";
	$pairs = query($query, $config);

	$stop = $config['timestamp'];
	$points = 100; # chart
	$points_buffer = 200;

	$analysis = array();
	$rsi = array(8, 12, 24, 36); // 'rsi'.$n
	$rsiob = 75; // overbought, 'rsi'.$n.'ob'
	$rsios = 25; // oversold, 'rsi'.$n.'os'
	$ema = array(6, 12, 26, 50, 100, 200); // 'ema'.$n, crossover upwards 'ema'.$n.'cu', crossover downwards 'ema'.$n.'cd'
	$roc = array(1, 2, 4, 6, 12, 24); // 'roc'.$n
	$corr = array(
		'BTC' => array(50),
		'ETH' => array(50)
	); // 'corr'.$n.'btc'

	foreach ($pairs as $pair) {

		$period = $pair['period'] * 60000;
		if ($backdate)
			$start = $backdate - $period * ($points + $points_buffer);
		else
			$start = $stop - $period * ($points + $points_buffer);

		$query = "
			SELECT
			history_id, timestamp, close
			FROM price_history
			WHERE pair_id = '" . $pair['pair_id'] . "'
			AND timestamp >= " . $start . "
			AND timestamp <= " . $stop . "
			ORDER BY timestamp
		";
		$hist = query($query, $config);
		if ($config['debug']) $response['msg'] .= $query . PHP_EOL;
		if (empty($hist)) continue 1;

		$last = $hist[0]['timestamp'] - $period;
		$missing_data = FALSE;
		$i = 0;
		foreach ($hist as $h) {
			$diff_obs = $h['timestamp'] - $last;
			if ($period !== $diff_obs) {
					$missing_data = TRUE;
					$missing_obs = $h['timestamp'] - $period;
					$missing_array = $i;
			}
			$last = $h['timestamp'];
			$i++;
		}
		if ($missing_data) {
			$response['msg'] .=
				'missing data at array position ' . $missing_array . PHP_EOL .
				'time ' . date('Y-m-d H:i T', $missing_obs / 1000) . PHP_EOL .
				'timestamp ' . $missing_obs . PHP_EOL .
				'period ' . $period . PHP_EOL .
				var_export($hist[$missing_array - 1], TRUE) . PHP_EOL .
				var_export($hist[$missing_array], TRUE) . PHP_EOL
			;
			continue 1;
		}

		$count = count($hist);
		$dc = array_column($hist, 'close');

		foreach ($rsi as $n) {
			$r = trader_rsi($dc, $n);
			$j = 0;
			for ($i = 0; $i < $count; $i++) {
				if (isset($r[$i])) {
					$hist[$i]['rsi'.$n] = $r[$i];
					if (isset($r[$j])) {
						if ($r[$j] <= $rsiob && $r[$i] > $rsiob)
							$hist[$i]['rsi'.$n.'ob'] = 1;
						else
							$hist[$i]['rsi'.$n.'ob'] = 0;
						if ($r[$j] >= $rsios && $r[$i] < $rsios)
							$hist[$i]['rsi'.$n.'os'] = 1;
						else
							$hist[$i]['rsi'.$n.'os'] = 0;
					}
				}
				$j = $i;
			}
		}

		foreach ($ema as $n) {
			$r = trader_ema($dc, $n);
			$j = 0;
			for ($i = 0; $i < $count; $i++) {
				if (isset($r[$i])) {
					$hist[$i]['ema'.$n] = $r[$i];
					if (isset($r[$j])) {
						if ($dc[$j] <= $r[$j] && $dc[$i] > $r[$i]) {
							$hist[$i]['ema'.$n.'cu'] = 1; // crossover upwards
						} else {
							$hist[$i]['ema'.$n.'cu'] = 0;
						}
						if ($dc[$j] >= $r[$j] && $dc[$i] < $r[$i]) {
							$hist[$i]['ema'.$n.'cd'] = 1; // crossover downwards
						} else {
							$hist[$i]['ema'.$n.'cd'] = 0;
						}
					}
				}
				$j = $i;
			}
		}

		foreach ($roc as $n) {
			$r = trader_roc($dc, $n);
			for ($i = 0; $i < $count; $i++) {
				if (isset($r[$i]))
					$hist[$i]['roc'.$n] = $r[$i];
			}
		}

		foreach ($corr as $key => $array) {
			$query = "
				SELECT DISTINCT
				pair_id, pair, exchange, currency_end, history_start
				FROM asset_pairs
				WHERE analyse
				AND period = " . $pair['period'] . "
				AND pair LIKE '%" . $key . "/USD%'
			";
			$res = query($query, $config);
			if (!empty($res)) {
				$query = "
					SELECT
					timestamp, close
					FROM price_history
					WHERE pair_id = '" . $res[0]['pair_id'] . "'
					AND timestamp >= " . $hist[0]['timestamp'] . "
					AND timestamp <= " . $hist[$count-1]['timestamp'] . "
					ORDER BY timestamp
				";
				$res = query($query, $config);
				if (!empty($res) && count($res) == $count) {
					$cc = array_column($res, 'close');
					foreach ($array as $n) {
						$r = trader_correl($dc, $cc, $n);
						for ($i = 0; $i < $count; $i++) {
							if (isset($r[$i]))
								$hist[$i]['corr'.$n.$key] = $r[$i];
						}
					}
				}
			}
		}



		/*
		new code snippet
		INSERT INTO ana_price VALUES value = LEFT(value, 64) # max characters 64 as db
		*/


		$omit = array('history_id', 'timestamp', 'open', 'high', 'low', 'close', 'volume');
		$updated = 0;
		$indicators = 0;
		foreach ($hist as $h) {
			$i = 0;
			$sep = '';
			$query = "UPDATE price_history SET ";
			foreach ($h as $key => $val) {
				if (!in_array($key, $omit, TRUE)) {
					$query .= $sep . $key . " = '" . $val . "'";
					$sep = ',';
					$i++;
				}
			}
			$query .= " WHERE history_id = " . $h['history_id'];
			if ($i) {
				$j = query($query, $config);
				$updated += $j;
				if ($j && $i > $indicators) $indicators = $i;
			}
		}

		$response['msg'] .= "analysis: updated up to {$indicators} indicators across {$updated} records for pair_id {$pair['pair_id']}, not counting existing values" . PHP_EOL;
		$response['count'] += $indicators * $updated;

	}

	return $response;

}