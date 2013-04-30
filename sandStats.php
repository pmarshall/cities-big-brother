<html><head><title>Cities Sand Gathering Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Sand Gathering Statistics</h2>
<?php
include ('dbConnect.php');

$query = "SELECT DISTINCT terrain FROM sand_gathering ORDER BY terrain";
$terrains=mysql_evaluate_array($query);

foreach ($terrains as $terrainRow) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$terrainRow[0].' Sand</b></caption>';
	echo '<tr><th>Item</th><th>Number Found</th><th>Find Chance</th></tr>';
	$query = "SELECT * FROM sand_gathering WHERE terrain='".$terrainRow[0]."'";
	$results = mysql_evaluate_array($query,MYSQL_ASSOC);
	foreach ($results as $row) {
		$total+=$row['count'];
	}
	foreach ($results as $row) {
		echo '<tr>';
		echo '<td>'.$row['item'].'</td><td>'.$row['count'].'</td><td>'.precisePercentile($row['count'],$total).'%</td>';
		echo '</tr>';
	}
	echo '</table>';
	$query="SELECT * FROM sand_count WHERE terrain='".$terrainRow[0]."'";
	$results = mysql_query($query);
	$row = mysql_fetch_assoc($results);
	echo 'Average number of items found: '.sprintf("%01.2f", (float)$total/$row['count']).'<br>';
	echo 'Sample size: '.$row['count'];
	echo '<p>';
	
	$total=0;
}



mysql_close($conn);

?>
</body>