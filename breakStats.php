<html><head><title>Cities Weapon Breakage Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Weapon Breakage Statistics</h2><br>
These statistics were last cleared on April 3, 2007.
<?php
include ('dbConnect.php');

$isWeapons=array(0,0,0,0,0,0,1,1,1);
$banes=array(0,1,0,0,1,1,0,0,0);
$pills=array('noPill','noPill','wellHard','hard','wellHard','hard','noPill','wellHard','hard');
$labels=array('Normal Weapon Breakage',"Baned Weapon Breakage","Breakage while Blue Pill is 'Well Hard'","Breakage while Blue Pill is 'Hard'","Baned Weapon Breakage while Blue Pill is 'Well Hard'","Baned Weapon Breakage while Blue Pill is 'Hard'","You attacked with WHAT!?","You attacked with WHAT!? while Blue Pill is 'Well Hard'","You attacked with WHAT!? while Blue Pill is 'Hard'");
for ($i=0;$i<9;$i++) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$labels[$i].'</b></caption>';
	echo '<tr><th>Item</th><th>Chance of Breaking</th><th>Sample Size</th></tr>';
	$query = 'SELECT * FROM weapon_break WHERE NOT is_weapon="'.$isWeapons[$i].'" AND is_bane="'.$banes[$i].'" AND pill_state="'.$pills[$i].'" ORDER BY item';
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