<html><head><title>Cities Mining Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Mining Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Terrain</th><th>Bronze</th><th>Copper</th><th>Gold</th><th>Iron</th><th>Lead</th><th>Silver</th><th>Stone</th><th>Gold Bits</th><th>Flint</th><th>Scone</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM mining";
$results = mysql_query($query) or logErrorAndDie('Error, getting mining table failed'.mysql_error()."\n");
while ($row=mysql_fetch_row($results)) {
	echo '<tr>';
	foreach ($row as $oreCount) {
		$totalOre+=$oreCount;
	}
	$firstColumn=1;
	foreach ($row as $oreCount) {
		if ($firstColumn==1) {
			$firstColumn=0;
			echo '<td>'.$oreCount.'</td>';
		}
		else {
			if ($oreCount)echo '<td>'.precisePercentile($oreCount,(float)$totalOre).'%</td>';
			else echo '<td> </td>';
		}
	}
	echo'<td>'.$totalOre.'</td></tr>';
	$totalOre=0;
}
echo '</table><p><p>';

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Terrain</th><th>Collapse Chance</th><th>Sample Size</th></tr>';
$query='SELECT SUM(collapse) as collapses, COUNT(*) as total, terrain FROM mine_duration GROUP BY terrain';
$results=mysql_query($query) or die(mysql_error());
while ($mine=mysql_fetch_assoc($results)) {
	echo '<tr><td>'.$mine['terrain'].'</td><td>'.precisePercentile($mine['collapses'],$mine['total']).'%</td><td>'.$mine['total'].'</td>';
}
echo '</table>';

mysql_close($conn);
?>
</body>