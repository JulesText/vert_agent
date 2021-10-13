<?php

function getsql($config, $values, $querylabel) {

	if (is_array($values))
		foreach ($values as $key => $value)
			$values[$key] = safeIntoDB($value, $key, $config);

	switch ($querylabel) {

		case 'select_indicators':
			$sql = "
				SELECT
				`function`,
				`description`,
				`indication`,
				`class`
				FROM `indicators` {$values['filterquery']}
			";
		break;

		case 'select_history':
			$sql = "SELECT
				`history_id`,
				`pair_id`,
				`timestamp`,
				`open`,
				`close`,
				`high`,
				`low`,
				`volume`
				FROM `price_history` {$values['filterquery']}
			";
		break;

		case 'update_content':
		case 'insert_transaction':
		case 'insert_asset_pair':
		case 'insert_price_history':
		case 'insert_tactic':
		case 'insert_tactic_external':
			if ($querylabel == "insert_price_history")
				$sql = "INSERT INTO price_history" . PHP_EOL;
			else if ($querylabel == "update_content")
				$sql = "REPLACE INTO web_content" . PHP_EOL;
			else if ($querylabel == "insert_transaction")
				$sql = "INSERT INTO transactions" . PHP_EOL;
			else if ($querylabel == "insert_asset_pair")
				$sql = "INSERT INTO asset_pairs" . PHP_EOL;
			else if ($querylabel == "insert_tactic")
				$sql = "INSERT INTO tactics" . PHP_EOL;
			else if ($querylabel == "insert_tactic_external")
				$sql = "INSERT INTO tactics_external" . PHP_EOL;
			$sql .= "SET" . PHP_EOL;
			$sep = "";
			foreach ($values as $key => $val) {
				if ($key == 'filterquery') continue 1;
				if ($val == '') $val = 'NULL';
				else $val = "'{$val}'";
				$sql .= $sep . $key . " = {$val}" . PHP_EOL;
				$sep = ", ";
			}
		break;

		case 'update_transaction':
			$sql = "UPDATE transactions SET" . PHP_EOL;
			$sep = "";
			foreach ($values as $key => $val) {
				if ($key == 'transaction_id' || $key == 'filterquery') continue 1;
				if ($val == '') $val = 'NULL';
				else $val = "'{$val}'";
				$sql .= $sep . $key . " = {$val}" . PHP_EOL;
				$sep = ", ";
			}
			$sql .= "WHERE transaction_id = {$values['transaction_id']}";
		break;

		case 'update_tactic':
			$tactic_id = $values['tactic_id'];
			unset($values['tactic_id']);
			$sql = "UPDATE tactics SET ";
			$sep = "";
			foreach ($values as $key => $val) {
				if ($key == 'filterquery') continue 1;
				$sql .= $sep . "{$key} = '{$val}' " . PHP_EOL;
				$sep = ", ";
			}
			$sql .= "WHERE tactic_id = {$tactic_id}";
		break;

		# keep the most recent record
		case 'deduplicate_history':
			# warning this is inefficient with a large db, better to iterate with $values filter to small number of records
			$sql = "
				DELETE a FROM `price_history` a
				INNER JOIN `price_history` b
				ON a.pair_id = b.pair_id
				AND a.timestamp = b.timestamp
				WHERE
				a.history_id < b.history_id
				{$values['filterquery']}
				;
			";
		break;

		case 'select_content':
			$sql = "SELECT * FROM `web_content` {$values['filterquery']}";
		break;

		case 'select_tactics':
			$sql = "
				SELECT
				t.*,
				a.exchange,
				a.pair,
				a.leverage
				FROM tactics t
				LEFT JOIN asset_pairs a
				ON t.pair_id = a.pair_id
				{$values['filterquery']}
			";
		break;

		case 'select_transactions':
			$sql = "SELECT * FROM `transactions` {$values['filterquery']}";
		break;

		default: // default to assuming that the label IS the query
			$sql = $querylabel;
		break;

	}

	return $sql;

}
