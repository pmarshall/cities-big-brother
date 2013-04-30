<html><head><title>Cities Tunnel Mining Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Tunnel Mining Statistics</h2>
<h6>Note: Big Brother can no longer tell the difference between the Tunnels and Guildspace, so the results are lumped together.</h6>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Southeast Tunnels</b></caption>';
echo '<tr><th>Vein</th><th>Find Chance</th></tr>';
$query = "SELECT * FROM tunnel_mining WHERE guild='1' ORDER BY count DESC";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	$total+=$row['count'];
}
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['terrain'].'</td><td>'.precisePercentile($row['count'],$total).'%</td>';
	echo '</tr>';
}
echo '</table>';
echo 'Sample size: '.$total;
echo '<p>';

/*$total=0;
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Guild Tunnels</b></caption>';
echo '<tr><th>Vein</th><th>Find Chance</th></tr>';
$query = "SELECT * FROM tunnel_mining WHERE guild='1' ORDER BY count DESC";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	$total+=$row['count'];
}
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['terrain'].'</td><td>'.precisePercentile($row['count'],$total).'%</td>';
	echo '</tr>';
}
echo '</table>';
echo 'Sample size: '.$total;

mysql_close($conn);
*/
?>
</body>