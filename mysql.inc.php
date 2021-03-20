<?php

function getsql($config, $values, $querylabel) {

    if (is_array($values))
        foreach ($values as $key => $value)
            $values[$key] = safeIntoDB($value, $key, $config);

	switch ($querylabel) {

        case 'select_indicators':
            $sql = "SELECT
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
                    `timestamp`,
                    `open`,
                    `close`,
                    `high`,
                    `low`,
                    `volume`
                    FROM `price_history` {$values['filterquery']}
            ";
            break;

        case 'update_history':
            $sql = "INSERT INTO `price_history`
                    (
                    `pair`,
                    `source`,
                    `timestamp`,
                    `period`,
                    `open`,
                    `close`,
                    `high`,
                    `low`,
                    `volume`,
                    `imputed`
                    )
                    VALUES
                    (
                    '{$values['pair']}',
                    '{$values['source']}',
                    '{$values['timestamp']}',
                    '{$values['period']}',
                    '{$values['open']}',
                    '{$values['close']}',
                    '{$values['high']}',
                    '{$values['low']}',
                    '{$values['volume']}',
                    '{$values['imputed']}'
                    )
            ";
            break;

				# keep the most recent record
				case 'deduplicate_history':

						# warning this is inefficient with a large db, better to iterate with $values filter to small number of records
						$sql = "
                DELETE a FROM `price_history` a
                INNER JOIN `price_history` b
								ON a.pair = b.pair
                AND a.timestamp = b.timestamp
                AND a.period = b.period
                AND a.source = b.source
                WHERE
                    a.history_id < b.history_id
                    {$values['filterquery']}
										;
            		";

            break;

        case 'update_content':
            $sql = "REPLACE INTO `web_content`
                    (
                    `content_id`,
                    `content`,
                    `timestamp`,
                    `notified`
                    )
                    VALUES
                    (
                    '{$values['content_id']}',
                    '{$values['content']}',
                    '{$values['timestamp']}',
                    '{$values['notified']}'
                    )
            ";
            break;

        case 'get_content':
            $sql = "SELECT * FROM `web_content` {$values['filterquery']}";
            break;

        case 'get_tactics':
            $sql = "SELECT * FROM `tactics` {$values['filterquery']}";
            break;

        case 'update_tactic':
            $sql = "UPDATE `tactics` SET `{$values['field']}` = '{$values['value']}' WHERE `tactic_id` = '{$values['tactic_id']}'";
            break;

        case 'get_transactions':
            $sql = "SELECT * FROM `transactions` {$values['filterquery']}";
            break;

		case 'update_transaction':
			$sql = "REPLACE INTO `transactions`
						(
						`transaction_id`,
						`investment_id`,
						`investment_proportion`,
						`time_opened`,
						`time_closed`,
						`capital_amount`,
						`capital_fee`,
						`purpose`,
						`exchange`,
						`exchange_transaction_id`,
						`exchange_transaction_status`,
						`percent_complete`,
						`pair_asset`,
						`from_asset`,
						`from_amount`,
						`to_asset`,
						`to_amount`,
						`to_fee`,
						`pair_price`,
						`from_price_usd`,
						`to_price_usd`,
						`price_reference`,
						`fee_amount_usd`,
						`price_aud_usd`,
						`aud_usd_reference`,
						`from_wallet`,
						`to_wallet`,
						`tactic_id`,
						`strategy_result_usd`
						)
				VALUES (
		        '{$values['transaction_id']}',
		        '{$values['investment_id']}',
		        '{$values['investment_proportion']}',
		        '{$values['time_opened']}',
		        '{$values['time_closed']}',
		        '{$values['capital_amount']}',
		        '{$values['capital_fee']}',
		        '{$values['purpose']}',
						'{$values['exchange']}',
						'{$values['exchange_transaction_id']}',
		        '{$values['exchange_transaction_status']}',
		        '{$values['percent_complete']}',
		        '{$values['pair_asset']}',
						'{$values['from_asset']}',
						'{$values['from_amount']}',
						'{$values['to_asset']}',
						'{$values['to_amount']}',
						'{$values['to_fee']}',
		        '{$values['pair_price']}',
						'{$values['from_price_usd']}',
						'{$values['to_price_usd']}',
						'{$values['price_reference']}',
		        '{$values['fee_amount_usd']}',
		        '{$values['price_aud_usd']}',
		        '{$values['aud_usd_reference']}',
		        '{$values['from_wallet']}',
		        '{$values['to_wallet']}',
		        '{$values['tactic_id']}',
		        '{$values['strategy_result_usd']}'
						)";
			break;

    default: // default to assuming that the label IS the query
        $sql=$querylabel;
        break;

    }
	return $sql;
}

function sqlparts($part,$config,$values) {

  if (is_array($values))
    foreach ($values as $key=>$value)
        $values[$key] = safeIntoDB($value, $key, $config);

  switch ($part) {

  	case "test":
  		$sqlpart = " ";
		  break;
    default:
        if ($config['debug_sql']) echo "<p class='error'>Failed to find sql component '$part'</p>'";
        $sqlpart=$part;
      break;
  }

  if ($config['debug_sql'])
      echo "<pre>Sqlparts '$part': Result $sqlpart<br />Sanitised values in sqlparts: ",print_r($values,true),'</pre>';

  return $sqlpart;
}
