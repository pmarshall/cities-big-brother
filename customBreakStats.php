<html><head><title>Cities Custom Weapon Breakage Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Custom Weapon Breakage Statistics</h2><br>
These statistics were last cleared on June 11, 2007.
<?php
include ('dbConnect.php');

$pills=array('noPill','wellHard','hard');
$labels=array('Custom Weapon Breakage','Custom Weapon Breakage when Blue Pill is "Well Hard"','Custom Weapon Breakage when Blue Pill is "Hard"');
for ($i=0;$i<3;$i++) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption>'.$labels[$i].'</caption>';
	echo '<tr><th>Material</th><th>Estimated Lifespan</th><th>Chance of Breaking</th><th>Actual Lifespan</th><th>Sample 	Size</th></tr>';
	$query = "SELECT * FROM custom_weapon_break WHERE pill_state='$pills[$i]' AND is_bane='0' ORDER BY material, lifespan";
	$results = mysql_evaluate_array($query, MYSQL_ASSOC);
	foreach ($results as $row) {
		echo '<tr>';
		echo '<td>'.$row['material'].'</td><td>'.$row['lifespan'].'</td>';
		if ($row['breaks']>0) echo '<td>'.precisePercentile($row['breaks'],$row['count']).'%</td><td>'.sigFigs($row['count']/$row['breaks'], calcSigFigs($row['count'],$row['breaks'])).'</td>';
		else echo '<td>None have broken</td><td>None have broken</td>';
		echo'<td>'.$row['count'].'</td>';
		echo '</tr>';
	}
	echo '</table><p>';
}

mysql_close($conn);

?>
</body>