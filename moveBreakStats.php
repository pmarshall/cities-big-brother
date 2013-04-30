<html><head><title>Cities Equipment Breakage Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Equipment Breakage Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Item</th><th>Chance of Breaking</th><th>Number Equipped</th><th>Sample Size</th></tr>';
$query = "SELECT * FROM move_break ORDER BY item, numberWorn";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['breaks'],$row['count']).'%</td><td>'.$row['numberWorn'].'</td><td>'.$row['count'].'</td>';
	echo '</tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>