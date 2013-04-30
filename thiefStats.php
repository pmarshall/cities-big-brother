<html><head><title>Cities Thief Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Thief Action Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Thief Type</th><th>Attack</th><th>Steal</th><th>Steal, then change to Stab</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM thief_action ORDER BY monster DESC";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($results as $row) {
	$rowTotal=$row['attack']+$row['stab']+$row['steal'];
	echo "<tr><td>".$row['monster']."</td><td>".precisePercentile($row['attack'],$rowTotal).'%</td><td>'.precisePercentile($row['steal'],$rowTotal).'%</td><td>'.precisePercentile($row['stab'],$rowTotal).'%</td><td>'.$rowTotal.'</td></tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>