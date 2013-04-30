<html><head><title>Cities Vent Road Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Vent Road Statistics</h2>
<?php
include ('dbConnect.php');
echo 'As a temporary measure, I am releasing the full dump or data reported on the vents, sorted by location, direction, and time.  There are only north and east paths recorded for each tile.';
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>X</th><th>Y</th><th>Direction</th><th>Time</th><th>Open</th></tr>';
$query = "SELECT x,y,dir,time,open FROM vent_roads ORDER BY x,y,dir,time";
$total=0;
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['x'].'</td><td>'.$row['y'].'</td><td>'.$row['dir'].'</td><td>'.gmdate('Y F j, H:i',$row['time']).'</td><td>'.($row['open']==1?'yes':'no').'</td>';
	echo '</tr>';
	$total++;
}
echo '</table>';
echo 'Sample size: '.$total;
echo '<p>';



mysql_close($conn);

?>
</body>