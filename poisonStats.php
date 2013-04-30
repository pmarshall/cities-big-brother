<html><head><title>Cities Poisoning Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Poisoning Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Monster</th><th>Chance of Poisoning</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM poison ORDER BY monster";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($results as $row) {
	$query="SELECT hits FROM monster_accuracy WHERE monster='".$row['monster']."'";
	$monsterResults=mysql_query($query);
	$monsterRow=mysql_fetch_assoc($monsterResults);
	echo '<tr>';
	echo '<td>'.$row['monster'].'</td><td>'.precisePercentile($row['count'],($monsterRow['hits']-$row['offset'])).'%</td><td>'.($monsterRow['hits']-$row['offset']).'</td>';
	echo '</tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>