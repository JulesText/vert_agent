<?

/* calculate and save price indicators */

function technical_analysis($config, $pair_id = FALSE, $indicator_list = FALSE) {

	$query = "SELECT DISTINCT pair, period, source, currency, history_start, class FROM asset_pairs WHERE analyse";
	if ($pair_id) $query .= " AND pair_id = " . $pair_id;
	$pairs = query($query, $config);

	$stop = $config['timestamp'];
	$points = 100; # chart
	$points_buffer = 200;

	$analysis = array();

	/* technical analysis indicators */

	$rsi = array(8, 12, 24, 36); // 'rsi'.$n
	$rsiob = 75; // overbought, 'rsi'.$n.'ob'
	$rsios = 25; // oversold, 'rsi'.$n.'os'
	$ema = array(6, 12, 26, 50, 100, 200); // 'ema'.$n, crossover upwards 'ema'.$n.'cu', crossover downwards 'ema'.$n.'cd'
	$roc = array(1, 2, 4, 6, 12, 24); // 'roc'.$n
	$corr = array(
		'BTC' => array(30,50),
		'ETH' => array(30,50)
	); // 'corr'.$n.'btc'

	/* build list */

	if ($indicator_list) {

		$indicators = array('close' => 'close', 'open' => 'open', 'high' => 'high', 'low' => 'low', 'volume' => 'volume');
		foreach ($rsi as $n) {
			$indicators['rsi'.$n] = 'RSI ' . $n;
			$indicators['rsi'.$n.'ob'] =  'RSI ' . $n . ' overbought if RSI > ' . $rsiob . ' (" = 1" to trigger)';
			$indicators['rsi'.$n.'os'] =  'RSI ' . $n . ' oversold if RSI < ' . $rsios . ' (" = 1" to trigger)';
		}
		foreach ($ema as $n) {
			$indicators['ema'.$n] = 'EMA ' . $n;
			$indicators['ema'.$n.'cu'] =  'EMA ' . $n . ' cross upwards if prev close < EMA & new close > EMA (" = 1" to trigger)';
			$indicators['ema'.$n.'os'] =  'EMA ' . $n . ' cross downwards if prev close > EMA & new close < EMA (" = 1" to trigger)';
		}
		foreach ($roc as $n) {
			$indicators['roc'.$n] = 'Rate of Change ' . $n . ' (i.e. "50" for 50%)';
		}
		foreach ($corr as $key => $arr) {
			foreach ($arr as $val)
				$indicators['corr'.$val.strtolower($key)] = 'Correlation to ' . $key . ' ' . $val;
		}

		return $indicators;

	}

	foreach ($pairs as $pair) {

		$period_txt = $config['period'][$pair['period']];
		$period = $pair['period'] * 60000;
		$start = $stop - $period * ($points + $points_buffer);

		$query = "
			SELECT history_id, timestamp, close FROM price_history
			WHERE pair = '" . $pair['pair'] . "'
			AND source = '" . $pair['source'] . "'
			AND period = '" . $period_txt . "'
			AND timestamp >= " . $start . "
			AND timestamp <= " . $stop . "
			ORDER BY timestamp ASC
		";
		$hist = query($query, $config);
		if ($config['debug']) echo $query . PHP_EOL;
		if (empty($hist)) continue 1;

		$last = $hist[0]['timestamp'] - $period;
		$missing_data = FALSE;
		$weekends = weekends($pair['class']);
		$i = 0;
		foreach ($hist as $h) {
			$diff_obs = $h['timestamp'] - $last;
			if ($period !== $diff_obs) {
				$day = date('l', $h['timestamp'] / 1000);
				if (
					$weekends # asset classes like crypto expect data on weekends, no exception for missing data
					|| (!$weekends && !in_array($day, ['Sunday','Monday'])) # classes like fiat or stocks do not expect so allow Sat/Sun missing using test today is Sun/Mon
				) {
					$missing_data = TRUE;
					$missing_obs = $h['timestamp'] - $period;
					$missing_array = $i;
				}
			}
			$last = $h['timestamp'];
			$i++;
		}
		if ($missing_data) {
			echo '
				missing data at array position ' . $missing_array . ', <br>
				time ' . date('Y-m-d H:i T', $missing_obs / 1000) . ', <br>
				timestamp ' . $missing_obs . ', <br>
				period ' . $period . '<br>
			';
			var_dump($hist[$missing_array - 1]);
			var_dump($hist[$missing_array]);
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
				SELECT DISTINCT pair_id, pair, source, currency, history_start FROM asset_pairs
				WHERE analyse
				AND period = " . $pair['period'] . "
				AND pair LIKE '%" . $key . "/USD%'
			";
			$res = query($query, $config);
			if (!empty($res)) {
				$query = "
					SELECT timestamp, close FROM price_history
					WHERE pair = '" . $res[0]['pair'] . "'
					AND source = '" . $res[0]['source'] . "'
					AND period = '" . $period_txt . "'
					AND timestamp >= " . $hist[0]['timestamp'] . "
					AND timestamp <= " . $hist[$count-1]['timestamp'] . "
					ORDER BY timestamp ASC
				";
				$res = query($query, $config);
				if (!empty($res) && count($res) == $count) {
					$cc = array_column($res, 'close');
					foreach ($array as $n) {
						$r = trader_correl($dc, $cc, $n);
						for ($i = 0; $i < $count; $i++) {
							if (isset($r[$i])) {
								$hist[$i]['corr'.$n.strtolower($key)] = $r[$i];
							}
						}
					}
				}
			}
		}

		$omit = array('history_id', 'timestamp', 'open', 'high', 'low', 'close', 'volume');
		foreach ($hist as $h) {
			$sep = '';
			$query = "UPDATE price_history SET ";
			foreach ($h as $key => $val) {
				if (!in_array($key, $omit, TRUE)) {
					$query .= $sep . $key . " = '" . $val . "'";
					$sep = ',';
				}
			}
			$query .= " WHERE history_id = " . $h['history_id'];
			if ($config['debug']) echo $query . PHP_EOL;
			query($query, $config);
		}

	}

}
