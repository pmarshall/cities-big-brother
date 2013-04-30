<html><head><title>Cities Silver Vs. Dragon Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Silver Weapons Vs. Dragons</h2>
<?php
include ('dbConnect.php');

$query = "SELECT * FROM silver_vs_dragon";
$results=mysql_evaluate_array($query,MYSQL_ASSOC);

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Weapon</th><th>Dragon\'s Dodge Chance</th><th>Actual Hit Chance</th><th>Expected Hit Chance</th><th>Sample Size</th></tr>';
foreach ($results as $row) {
	$totalAccuracy+=$row['accuracy'];
	$totalHits+=$row['hits'];
	$totalTries+=$row['count'];
}
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['item'].'</td><td>'.sigFigs(100.0*(1.0-100.0*$row['hits']/$row['accuracy']), calcSigFigs($row['hits'],$row['accuracy'])).'%</td><td>'.precisePercentile($row['hits'],$row['count']).'%</td>';
	echo '<td>'.sprintf("%01.1f",1.0*$row['accuracy']/$row['count']).'%</td><td>'.$row['count'].'</td>';
	echo '</tr>';
}
echo '<tr>';
echo '<td>Total</td><td>'.sigFigs(100.0*(1.0-100.0*$totalHits/$totalAccuracy), calcSigFigs($totalHits,$totalAccuracy)).'%</td><td>'.precisePercentile($totalHits,$totalTries).'%</td>';
echo '<td>'.sprintf("%01.1f",1.0*$totalAccuracy/$totalTries).'%</td><td>'.$totalTries.'</td>';
echo '</tr>';

echo '</table>';



mysql_close($conn);

?>
</body>