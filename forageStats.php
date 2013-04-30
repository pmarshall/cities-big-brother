<html><head><title>Cities Foraging Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Foraging Statistics</h2>
These statistics were last cleared on March 31, 2007.
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0" >';
echo '<tr><th>Terrain</th><th>Item Grown</th><th>Portion of Finds</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM foraging ORDER BY terrain";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
$currTerrain='not a terrain';
foreach ($results as $key => $row) {
	if ($row['terrain'] != $currTerrain) {
		$total=0;
		$currTerrain=$row['terrain'];
		for ($lookAhead=$key;$lookAhead<count($results) && $results[$lookAhead]['terrain']==$currTerrain ;$lookAhead++) {
			$total+=$results[$lookAhead]['count'];
		}
	}
	echo '<tr>';
	echo '<td>'.$row['terrain'].'</td><td>'.$row['item'].'</td>';
	echo '<td>'.precisePercentile($row['count'],$total)."%</td><td>$total</td>";
	echo '</tr>';
}
echo '</table><p>';

mysql_close($conn);

?>
</body>