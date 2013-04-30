<html><head><title>Cities Golden Condor Location</title></head>
<body>
<?php
include ('siteHeader.php');
include ('logger.php');
?>
<h2>The last five known locations of the Golden Condor</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Location</th><th>Last Report</th></tr>';
$query = "SELECT x,y,time FROM condor_location ORDER BY time DESC LIMIT 5";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.abs($row['x']).($row['x']<0?'W ':'E ').abs($row['y']).($row['y']<0?'S':'N').'</td><td>'.$row['time'].'</td>';
	echo '</tr>';
}
echo '</table><p>';
$results = mysql_evaluate_array("SELECT NOW()");
echo 'Current Golden Condor Navigator database server time: '.$results[0][0];

mysql_close($conn);

?>
<br>
Want to help keep this page accurate?  Install the <a href="http://potatoengineer.110mb.com/citiesgoldencondornaviga.user.js">Golden Condor Navigator</a>, a Greasemonkey script, and get a Golden Condor Navigator/Big Brother key from PotatoEngineer (IRC) or Cor (palantir).  The same key works on both scripts. Installing Big Brother isn't necessary, but wouldn't it be nice?
<p>
As a bonus, the Golden Condor Navigator script will keep track of your position while you fly around in the Condor, even if you hit an obstacle, and it will prevent you from moving onto the High Plateau or Strange Crags.
</body>