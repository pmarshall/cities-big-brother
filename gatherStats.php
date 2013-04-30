<html><head><title>Cities Wood Gathering Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Wood Gathering Statistics</h2>
<?php
include ('dbConnect.php');

$query = "SELECT DISTINCT terrain FROM wood_gathering ORDER BY terrain";
$terrains=mysql_evaluate_array($query);

foreach ($terrains as $terrainRow) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$terrainRow[0].' Wood</b></caption>';
	echo '<tr><th>Item</th><th>Portion of Finds</th></tr>';
	$query = "SELECT * FROM wood_gathering WHERE terrain='".$terrainRow[0]."'";
	$results = mysql_evaluate_array($query,MYSQL_ASSOC);
	foreach ($results as $row) {
		$total+=$row['count'];
	}
	foreach ($results as $row) {
		echo '<tr>';
		echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['count'],$total).'%</td>';
		echo '</tr>';
	}
	echo '</table>';
	$query="SELECT * FROM gathering_count WHERE terrain='".$terrainRow[0]."'";
	$results = mysql_query($query);
	$row = mysql_fetch_assoc($results);
	echo 'Average number of items found: '.sigFigs((float)$total/$row['count'], calcSigFigs((float)$total,$row['count'])).'<br>';
	echo 'Sample size: '.$row['count'];
	echo '<p>';
	
	$total=0;
}



mysql_close($conn);

?>
</body>