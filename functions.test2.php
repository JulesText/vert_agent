<?php

/*  */

function investment_transaction($config, $a) {

	$sep = $config['sep'];
	$response = $config['response'];
	$response['result']['sql_undo'] = array();

	$response['msg'] = 'select asset_investment ...' . PHP_EOL . PHP_EOL;
	$sql = "
		SELECT ass_inv_id, asset_proportion, investment_id
		FROM asset_investment
		WHERE asset_id = {$a['asset_id']}
		AND asset_proportion > 0
		";
	$result = query($sql, $config);
	if (empty($result)) {
		$response['msg'] .= "failed: query asset_investment, asset_id does not exist" . PHP_EOL . $sql;
		$response['error'] = TRUE;
		return $response;
	} else {
		# WARNING assumes 1 record
		$a['ass_inv_id'] = $result[0]['ass_inv_id'];
		$response['msg'] .= "ok: queried asset_investment, returned ass_inv_id {$a['ass_inv_id']}" . PHP_EOL;
	}

	$response['msg'] = 'match deposit to exchange transaction ...' . PHP_EOL . PHP_EOL;
	if ($a['purpose'] == 'capital') {
		$purpose = "AND purpose = 'transfer in'";
	} else {
		$purpose = '';
	}
	$sql = "
	SELECT * FROM transactions
	WHERE exchange = {$a['exchange']}
	AND ISNULL(from_ass_inv_id)
	AND ISNULL(to_ass_inv_id)
	{$purpose}
	AND time_closed >= {$a['timestamp_held_first']}
	AND time_closed - {$a['timestamp_held_first']} <= {$config['match_threshold']}
	AND from_asset = '{$a['asset']}'
	AND from_amount <= {$a['amount_held']}
	ORDER BY time_closed ASC
	LIMIT 1
	";
	$t = query($sql, $config);
	if ($t === 0) {
		$response['msg'] .= "failed: query transaction, match does not exist" . PHP_EOL . $sql;
		$response['error'] = TRUE;
		return $response;
	} else {
		$t = $t[0];
		$response['msg'] .= "ok: queried transaction, returned transaction_id {$t['transaction_id']}" . PHP_EOL;
	}

	$response['msg'] .= $sep . 'update assets record to deduct transaction from_amount  ...' . PHP_EOL . PHP_EOL;
	$proportion_held = $a['proportion_held'] - ($t['from_amount'] * $t['percent_complete'] / 100) / $a['amount_held'];
	if ($proportion_held == 0) {
		$tmp_timestamp_held_last = $t['time_closed'];
	} else {
		$tmp_timestamp_held_last = 'NULL';
	}
	$sql = "
	UPDATE assets SET
	proportion_held = {$proportion_held}
	, tmp_timestamp_held_last = {$tmp_timestamp_held_last}
	WHERE asset_id = {$a['asset_id']}
	";
	$result = query($sql, $config);
	if ($result === 1) {
		$response['msg'] .= "ok: updated asset_id " . $a['asset_id'] . PHP_EOL;
		$sql_undo = "
			UPDATE assets SET
			proportion_held = {$a['proportion_held']}
			, tmp_timestamp_held_last = 'NULL'
			WHERE asset_id = {$a['asset_id']}
			";
		array_push($response['result']['sql_undo'], $sql_undo);
	} else {
		$response['msg'] .= "failed: update asset" . PHP_EOL . $sql . PHP_EOL;
		$response['error'] = TRUE;
		return $response;
	}

	$response['msg'] .= $sep . 'update or insert asset record to add transaction to_amount  ...' . PHP_EOL . PHP_EOL;
	$sql = "
		SELECT * FROM assets
		WHERE asset = '{$a['asset']}'
		AND exchange_held = '{$a['exchange']}'
		AND proportion_held > 0
		";
	$result = query($sql, $config);
	if (empty($result)) {

		$response['msg'] .= $sep . 'insert new record to assets ...' . PHP_EOL . PHP_EOL;
		$proportion_held_next = 1;
		$response['msg'] .= 'note: set proportion_held = 100%' . PHP_EOL;
		$sql = "
			INSERT INTO assets SET
			asset_id = NULL
			, purpose = 'investment'
			, asset = '{$t['to_asset']}'
			, amount_held = '{$t['to_amount']}'
			, proportion_held = {$proportion_held_next}
			, exchange_held = '{$t['exchange']}'
			, tmp_class = NULL
			, timestamp_held_first = '{$t['time_closed']}'
			, tmp_timestamp_held_last = NULL
			, tmp_date = NULL
			, financial_year_held = NULL
			";
		$result = query($sql, $config);
		if ($result === 1) {
			$asset_id = mysqli_insert_id($config['sql_link']);
			$response['msg'] .= "ok: inserted asset_id " . $asset_id . PHP_EOL;
			$sql_undo = "DELETE FROM assets WHERE asset_id = {$asset_id}";
			array_push($response['result']['sql_undo'], $sql_undo);
		} else {
			$response['msg'] .= "failed: insert asset" . PHP_EOL . $sql . PHP_EOL;
			$response['error'] = TRUE;
			return $response;
		}

	} else {


	}


	$response['msg'] .= $sep . 'insert next record to asset_investment ...' . PHP_EOL . PHP_EOL;
	$asset_proportion_next = 1;
	$response['msg'] .= 'assume: asset_proportion = 100%' . PHP_EOL;
	$asset_parent_proportion = 1;
	$response['msg'] .= 'assume: asset_parent_proportion = 100%' . PHP_EOL;
	$investment_proportion_next = 1;
	$response['msg'] .= 'assume: investment_proportion = 100%' . PHP_EOL;
	$price_investment_asset_next = 1;
	$response['msg'] .= 'assume: price_investment_asset is in capital currency' . PHP_EOL;
	$sql = "
	INSERT INTO asset_investment SET
	ass_inv_id = NULL
	, asset_id = {$asset_id}
	, asset_proportion = {$asset_proportion_next}
	, asset_parent_id = {$a['asset_id']}
	, asset_parent_proportion = {$asset_parent_proportion}
	, investment_id = {$t['transaction_id']}
	, investment_proportion = {$investment_proportion_next}
	, price_investment_asset = {$price_investment_asset_next}
	";
	$result = query($sql, $config);
	if ($result === 1) {
		$ass_inv_id_next = mysqli_insert_id($config['sql_link']);
		$response['msg'] .= "ok: inserted ass_inv_id " . $ass_inv_id_next . PHP_EOL;
		$sql_undo = "DELETE FROM asset_investment WHERE ass_inv_id = {$ass_inv_id_next}";
		array_push($response['result']['sql_undo'], $sql_undo);
	} else {
		$response['msg'] .= "failed: insert asset_investment" . PHP_EOL . $sql . PHP_EOL;
		$response['error'] = TRUE;
		return $response;
	}

	$response['msg'] .= $sep . "update transaction_id {$t['transaction_id']} ..." . PHP_EOL . PHP_EOL;
	$sql = "
	UPDATE transactions SET
	from_ass_inv_id = {$a['ass_inv_id']}
	, to_ass_inv_id = {$ass_inv_id_next}
	WHERE transaction_id = {$t['transaction_id']}
	";
	$result = query($sql, $config);
	if ($result === 1) {
		$response['msg'] .= "ok: from_ass_inv_id = {$a['ass_inv_id']}" . PHP_EOL;
		$response['msg'] .= "ok: to_ass_inv_id = {$ass_inv_id_next}" . PHP_EOL;
		$sql_undo = "UPDATE transactions SET from_ass_inv_id = NULL, to_ass_inv_id = NULL WHERE transaction_id = {$t['transaction_id']}";
		array_push($response['result']['sql_undo'], $sql_undo);
 	} else {
		$response['msg'] .= "failed: update transaction" . PHP_EOL . $sql . PHP_EOL;
		$response['error'] = TRUE;
		return $response;
	}

	$response['result']['asset_id'] = $asset_id;
	return $response;

}

/*  */

function investment_transaction_undo($config, $response) {

	$sep = $config['sep'];

	if (empty($response['result']['sql_undo'])) {
		$response['msg'] .= $sep . "no sql inserts or updates to undo" . $sep;
	} else {
		$response['msg'] .= $sep . "undoing sql inserts or updates ..." . PHP_EOL . PHP_EOL;
		foreach ($response['result']['sql_undo'] as $sql) {
			$result = query($sql, $config);
			if ($result === 1) {
				$response['msg'] .= "ok: {$sql} " . PHP_EOL;
			} else {
				$response['msg'] .= "failed: {$sql}" . PHP_EOL;
			}
		}
		$response['msg'] .= $sep . PHP_EOL;
	}

	return $response;

}
