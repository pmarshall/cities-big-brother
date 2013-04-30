<html><head><title>Cities Fairy Nuff Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Fairy Nuff Statistics</h2>
<?php
include ('dbConnect.php');

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Item</th><th>Percentage of Finds</th></tr>';
$query = "SELECT * FROM fairy";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
$total=0;
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
echo '<p>';



mysql_close($conn);

?>
</body>