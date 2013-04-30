<html><head><title>Cities Ballroom Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Ballroom Statistics</h2>
<?php
include ('dbConnect.php');
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Item</th><th>Find Chance</th></tr>';
$query = "SELECT * FROM ballroom";
$results = mysql_evaluate_array($query, MYSQL_ASSOC);
foreach ($results as $row) {
	$total+=$row['count'];
}
foreach ($results as $row) {
	echo '<tr>';
	echo '<td>'.$row['item'].'</td><td>'.precisePercentile($row['count'],$total).'%</td>';
	echo '</tr>';
}
echo '</table>';
echo 'Sample size: '.$total;

mysql_close($conn);

?>
</body>