<html><head><title>Cities Milking Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Milking Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Location</th><th>Chance of Cream</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM milking";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['location'].'</td><td>'.precisePercentile($row['cream'],$row['count']).'%</td><td>'.$row['count'].'</td>';
	echo '</tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>