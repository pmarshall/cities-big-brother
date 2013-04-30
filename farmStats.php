<html><head><title>Cities Farming Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Farming Statistics</h2>
These statistics were last cleared on April 23, 2007.
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0" >';
echo '<caption><b>Normal Terrains</b></caption>';
echo '<tr><th>Alignment</th><th>Terrain</th><th>Item Grown</th><th>Number</th><th>Find rate</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM farming WHERE NOT terrain='Satanic Gardens' ORDER BY alignment, terrain";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
$currTerrain='not a terrain';
$currAlignment='not an alignment';
foreach ($results as $key => $row) {
	if ($row['terrain'] != $currTerrain || $row['alignment'] != $currAlignment) {
		$total=0;
		$currTerrain=$row['terrain'];
		$currAlignment=$row['alignment'];
		for ($lookAhead=$key;$lookAhead<count($results) && $results[$lookAhead]['terrain']==$currTerrain && $results[$lookAhead]['alignment']==$currAlignment;$lookAhead++) {
			$total+=$results[$lookAhead]['count'];
		}
	}
	echo '<tr>';
	echo '<td>'.$row['alignment'].'</td><td>'.$row['terrain'].'</td><td>'.$row['item'].'</td><td>';
	if ($row['minDrop']==$row['maxDrop']) echo $row['minDrop'];
	else echo $row['minDrop'].' to '.$row['maxDrop'].' ('.sigFigs($row['totalDrop']/$row['count'], calcSigFigs($row['count'], $total)).')';
	echo '</td>';
	echo '<td>'.precisePercentile($row['count'],$total)."%</td><td>$total</td>";
	echo '</tr>';
}
echo '</table><p>';

echo '<table border="1" cellpadding="3" cellspacing="0" >';
echo '<caption><b>Satanic Gardens</b></caption>';
echo '<tr><th>Item Grown</th><th>Number (Average)</th><th>Find rate</th></tr>';
$query = "SELECT SUM(count) as itemCount, SUM(totalDrop) as totalDropped, MIN(minDrop) as minDropped, MAX(maxDrop) as maxDropped, item FROM farming WHERE terrain='Satanic Gardens' GROUP BY item ORDER BY item";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
$total=0;
foreach ($results as $row) {
	$total+=$row['itemCount'];
}
foreach ($results as $key => $row) {
		echo '<tr>';
		echo '<td>'.$row['item'].'</td><td>';
		if ($row['minDropped']==$row['maxDropped']) echo $row['minDropped'];
		else echo $row['minDropped'].' to '.$row['maxDropped'].' ('.sprintf("%01.2f", $row['totalDropped']/$row['itemCount']).')';
		echo '</td>';
		echo '<td>'.precisePercentile($row['itemCount'],$total).'%</td>';
		echo '</tr>';
}
echo "</table>Sample Size: $total<p>";


echo '<p><table border="1" cellpadding="3" cellspacing="0" >';
echo '<th>Terrain</th></th><th>Beanstalk Chance</th><th>Sample Size</th></tr>';
$query="SELECT * FROM beanstalk ORDER BY terrain";
$results=mysql_evaluate_array($query, MYSQL_ASSOC);
$total=0;
$totalStalks=0;
foreach ($results as $row) {
	$total+=$row['total'];
	$totalStalks+=$row['beanstalks'];
	echo '<tr><td>'.$row['terrain'].'</td><td>'.precisePercentile($row['beanstalks'],(float)$row['total']).'%</td><td>'.$row['total'].'</td></tr>';
}
if ($total) echo '<td>Total</td><td>'.precisePercentile($totalStalks,(float)$total).'%</td><td>'.$total.'</td>';
echo '</table>';

mysql_close($conn);

?>
</body>