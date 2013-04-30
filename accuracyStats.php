<html><head><title>Cities Monster Accuracy Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Monster Accuracy Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Monster</th><th>Accuracy</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM monster_accuracy ORDER BY monster";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['monster'].'</td><td>'.precisePercentile($row['hits'],$row['attacks']).'%</td><td>'.$row['attacks'].'</td>';
	echo '</tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>