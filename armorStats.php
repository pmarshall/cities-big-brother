<html><head><title>Cities Armor Breakage Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Armor Breakage Statistics</h2>
<?php
include ('dbConnect.php');
$labels=array('Normal Armor Breakage','Armor Breakage when blue pill is "hard"','Armor Breakage when blue pill is "well hard"');
$pillState=array('noPill','hard','wellHard');
for ($i=0;$i<3;$i++) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$labels[$i].'</b></caption>';
	echo '<tr><th>Item</th><th>Chance of Breaking</th><th>Sample Size</th></tr>';
	$query = "SELECT * FROM armor_break WHERE pill_state='".$pillState[$i]."'ORDER BY item";
	$results = mysql_evaluate_array($query, MYSQL_ASSOC);
	foreach ($results as $row) {
		echo '<tr>';
		echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['breaks'],$row['count']).'%</td><td>'.$row['count'].'</td>';
		echo '</tr>';
	}
	echo '</table><p>';
}

mysql_close($conn);

?>
</body>