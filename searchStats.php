<html><head><title>Cities Searching Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Searching Statistics</h2>
<?php
include ('dbConnect.php');

$query = "SELECT DISTINCT terrain FROM searching ORDER BY terrain";
$terrains=mysql_evaluate_array($query);

foreach ($terrains as $terrainRow) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$terrainRow[0].'</b></caption>';
	echo '<tr><th>Item</th><th>Find Chance</th></tr>';
	$query = "SELECT * FROM searching WHERE terrain='".$terrainRow[0]."' ORDER BY item";
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
	echo 'Sample size: '.$total;
	echo '<p>';
	
	$total=0;
}



mysql_close($conn);

?>
</body>