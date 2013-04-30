<html><head><title>Cities Digging Harvey Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Statistics for Digging at Harvey's Farm</h2>
<?php
include ('dbConnect.php');

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>With a Spade</b></caption>';
echo '<tr><th>Result</th><th>Percentage of Finds</th></tr>';
$query = "SELECT item,count FROM harvey_dig WHERE spade=1 ORDER BY item";
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


echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Without a Spade</b></caption>';
echo '<tr><th>Result</th><th>Percentage of Finds</th></tr>';
$query = "SELECT item, count FROM harvey_dig where spade=0 ORDER BY item ";
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