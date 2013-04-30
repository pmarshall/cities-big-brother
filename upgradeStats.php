<html><head><title>Cities Weapon Upgrade Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Angry Bow Statistics</h2>
<?php
// ORDER OF OPERATIONS: collect all bows.  For each bow, query the weapon_break and angry_upgrade tables to get statistics.
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Item</th><th>Upgrade Chance</th><th>Break Chance</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM angry_upgrade ORDER BY rank";
$angryBows = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($angryBows as $row) {
	$query="SELECT * FROM weapon_break WHERE item='".$row['item']."'";
	//logError('Angry weapon found:'.$row['item'].":".$query.":\n");
	$weaponResults=mysql_query($query);
	$weaponRow=mysql_fetch_assoc($weaponResults);
	echo '<tr>';
	echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['count'],$weaponRow['count']).'%</td><td>'.precisePercentile($weaponRow['breaks'],$weaponRow['count']).'%</td><td>'.$weaponRow['count'].'</td>';
	echo '</tr>';
}
echo '</table>';


?>
<h2>Daemonic Weapon Melting Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Item</th><th>Chance of Melting</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM daemonic_melt ORDER BY rank";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['melts'],$row['count']).'%</td><td>'.$row['count'].'</td>';
	echo '</tr>';
}
echo '</table>';
?>

<h2>Whip Grabbing Statistics</h2>
<?php
include ('dbConnect.php');

$query = "SELECT count FROM whip_grab";
$results = mysql_query($query);
$whipRow=mysql_fetch_assoc($results);

$query="SELECT count FROM weapon_break WHERE item='Bull Whip'";
//logError('Angry weapon found:'.$row['item'].":".$query.":\n");
$weaponResults=mysql_query($query);
$weaponRow=mysql_fetch_assoc($weaponResults);
if ($weaponRow['count']!=0) {
	echo 'Chance of whip being grabbed: '.precisePercentile($whipRow['count'],$weaponRow['count']).'%<br>';
	echo 'Sample size: '.$weaponRow['count'];
}


mysql_close($conn);

?>
</body>