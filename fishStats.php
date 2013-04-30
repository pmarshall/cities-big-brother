<html><head><title>Cities Fishing Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Fishing Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Alignment</th><th>Terrain</th><th>Item</th><th>Find Chance</th><th>Sample Size</th></tr>';
$query = "SELECT alignment, count, item, terrain FROM fishing ORDER BY alignment, terrain, item";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);

$terrainCount=0;
$currTerrain='';
$currItem='';
foreach ($results as $row) {
	if ($currTerrain!=$row['terrain']) {
		$itemCount=0;
		$currTerrain=$row['terrain'];
	}
	if ($currItem!=$row['item']) {
		$query="SELECT SUM(count) as total FROM fishing WHERE terrain='".$row['terrain']."' and alignment='".$row['alignment']."'";
		$totals = mysql_evaluate_array($query,MYSQL_ASSOC);
		$terrainCount=$totals[0]['total'];
	}
	echo '<tr>';
	echo '<td>'.$row['alignment'].'</td><td>'.$row['terrain'].'</td><td>'.$row['item'].'</td><td>'.precisePercentile($row['count'],$terrainCount).'%</td><td>'.$terrainCount.'</td>';
	echo '</tr>';
	$total+=$row['count'];
}
echo '</table>';
echo 'Sample size: '.$total;

mysql_close($conn);

?>
</body>