<?php

include('includes.php');
echo '<pre>';

// delete alert
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
  $query = "DELETE FROM tactics WHERE tactic_id = {$_GET['delete']}";
  query($query, $config);
  header("Location: " . strtok($_SERVER["REQUEST_URI"], "?"));
  exit;
}

// Load data
if (isset($_GET['pair_id_load'])) {
  $pair_id_load = $_GET['pair_id_load'];
} else {
  $pair_id_load = 1;
}
// Fetch data from database for input
$query = "SELECT * FROM asset_pairs WHERE pair_id = {$pair_id_load}";
$pair_load = query($query, $config);
$pair_load = $pair_load[0];
$query = "
  SELECT * FROM price_history
  WHERE pair = '{$pair_load['pair']}'
  AND source = '{$pair_load['source']}'
  AND period = '{$config['period'][$pair_load['period']]}'
  ORDER BY timestamp DESC
  LIMIT 20
  ";
$data = query($query, $config);

// Form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $pair_id = $_POST['pair_id'];
    $pair_indicator = $_POST['pair_indicator'];
    $pair_value_operand = $_POST['pair_value_operand'];
    $pair_value = $_POST['pair_value'];
    $condition_tactic = $_POST['condition_tactic'];
    if (is_numeric($condition_tactic) && $condition_tactic > 0) $condition_tactic_test = 0;
    else $condition_tactic_test = 1;
    $note = $_POST['note'];

    // Fetch data from database for input
    $query = "SELECT * FROM asset_pairs WHERE pair_id = {$pair_id}";
    $pair = query($query, $config);
    $pair = $pair[0];

    // Insert data into database
    $query = "INSERT INTO tactics (
      status
      , condition_tactic_test
      , condition_tactic
      , condition_pair_test
      , condition_pair_id
      , condition_pair_currency_min
      , condition_pair_indicator
      , condition_pair_value_operand
      , condition_pair_value
      , action
      , note
    ) VALUES (
      'conditional'
      , '{$condition_tactic_test}'
      , '{$condition_tactic}'
      , '0'
      , '{$pair_id}'
      , '60'
      , '{$pair_indicator}'
      , '{$pair_value_operand}'
      , '{$pair_value}'
      , 'alert'
      , '{$note}'
    )";
    query($query, $config);

}

// Fetch data from database for form
$query = "SELECT * FROM asset_pairs WHERE collect = '1' AND analyse = '1'";
$pairs = query($query, $config);
// var_dump($pairs);die;

$indicators = technical_analysis($config, FALSE, TRUE);
// print_r($indicators);die;

// Fetch data from database for summary table
$query = "
  SELECT t.*, ap.pair, ap.source, ap.reference, ap.period
  FROM tactics t
  LEFT JOIN asset_pairs ap
  ON t.condition_pair_id = ap.pair_id
  WHERE t.action = 'alert'
  ORDER BY status DESC
  ";
$alerts = query($query, $config);
foreach ($alerts as $key => $r) {
  $query = "
  SELECT timestamp, {$r['condition_pair_indicator']}
  FROM price_history
  WHERE pair = '{$r['pair']}'
  AND period = '{$config['period'][$r['period']]}'
  AND source = '{$r['source']}'
  ORDER BY timestamp DESC
  LIMIT 1
  ";
  $res = query($query, $config);
  $res = $res[0];
  $alerts[$key]['condition_pair_value'] = rtrim(rtrim($r['condition_pair_value'], "0"), ".");

  $alerts[$key]['as_at'] = date('Y-m-d', $res['timestamp']/1000);
  $alerts[$key]['value'] = rtrim(rtrim($res[$r['condition_pair_indicator']], "0"), ".");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alerts</title>
</head>
<script>
    function reloadPage() {
        var selectedValue = document.getElementById("pairSelect").value;
        window.location.href = window.location.pathname + '?pair_id_load=' + selectedValue;
    }
</script>
<body>
    <h2>Submit</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        Pair: <select name = "pair_id" id = "pairSelect" onchange="reloadPage()">
              <?php
                if ($pairs != 0) {
                  echo "<option value=''>--</option>";
                  foreach ($pairs as $row) {
                      echo "<option value='".$row['pair_id']."' ".($pair_id_load == $row['pair_id'] ? 'selected' : '').">".$row['pair']." ".$config['period'][$row['period']]." (" . $row['reference'] . ")"."</option>";
                  }
                } else {
                    echo "<option value=''>No asset pairs available</option>";
                }
              ?>
              </select><br>
        Indicator: <select name = "pair_indicator">
              <?php
                if ($indicators != 0) {
                  foreach ($indicators as $key => $val) {
                      echo "<option value='".$key."'>".$val."</option>";
                  }
                } else {
                    echo "<option value=''>No indicators available</option>";
                }
              ?>
              </select><br>
        Operand: <select name = "pair_value_operand">
              <option value=">=">>=</option>
              <option value="<="><=</option>
              <option value="=">==</option>
              </select><br>
        Value:<input type="text" name="pair_value"><br>
        Note:<input type="text" name="note"> (optional)<br>
        Conditional id:<input type="text" name="condition_tactic"> (optional)<br>
        <input type="submit" value="Submit">
    </form>

    <h2>Alerts</h2>
    <table border="1">
        <tr>
          <th>id</th>
          <th>status</th>
          <th>refresh</th>
          <th>pair</th>
          <th>period</th>
          <th>con_id</th>
          <th>condition</th>
          <th>satisfied</th>
          <th>value</th>
          <th>as at</th>
          <th>note</th>
          <th>delete</th>
        </tr>
        <?php
        if ($alerts != 0) {
            foreach ($alerts as $row) {
              echo "<tr>
                <td>".$row['tactic_id']."</td>
                <td>".$row['status']."</td>
                <td>".$config['period'][$row['refresh']]."</td>
                <td>".$row['pair']."</td>
                <td>".$config['period'][$row['period']]."</td>
                <td>".$row['condition_tactic']."</td>
                <td>".$row['condition_pair_indicator']." "
                  .$row['condition_pair_value_operand']." "
                  .$row['condition_pair_value']."</td>
                <td>".($row['condition_pair_test'] ? 'true' : 'false')."</td>
                <td>".$row['value']."</td>
                <td>".$row['as_at']."</td>
                <td>".$row['note']."</td>
                <td><a href='?delete=".$row['tactic_id']."'>X</a></td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No data available</td></tr>";
        }
        ?>
    </table>

    <?php
      echo "<h2>Data {$pair_load['pair']} {$config['period'][$pair_load['period']]}";
    ?>

    <table border="1">
        <?php
        if ($pair_id_load) {

          echo '<tr><th>time</th>';
          foreach ($indicators as $key => $val) {
            echo '<th>' . $key . '</th>';
          }
          echo '</tr>';

          foreach ($data as $row) {
            $time = date('Y-m-d', $row['timestamp']/1000);
            echo "<tr><td>{$time}</td>";
            foreach ($indicators as $key => $val) {
              $d = rtrim($row[$key], "0");
              $d = rtrim($d, ".");
              echo "<td>".$d."</td>";
            }
            echo "</tr>";
          }
        } else {
            echo "Select pair to load data";
        }
        ?>
    </table>

</body>
</html>

<?php
// Close database connection
// $conn->close();
?>
