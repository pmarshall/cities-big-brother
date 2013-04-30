<html><head><title>Cities Vorpal Blade Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Vorpal Blade Statistics</h2>
<?php
include ('dbConnect.php');

echo 'Total snicker-snack rate for vorpal blades (not counting immune monsters): ';

$query="SELECT COUNT(*) AS total, SUM(vorp) AS snacks, monster FROM vorpal_blade GROUP BY monster ORDER BY monster";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
$totalSnacks=0;
$total=0;
foreach ($results as $row) {
	if ($row['snacks']) {
		$totalSnacks+=$row['snacks'];
		$total+=$row['total'];
	}
}
echo precisePercentile($totalSnacks,$total).'%';

echo '<p>Snicker-snack rate by monster';
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Monster</th><th>Snicker-Snack rate</th><th>Sample Size</th></tr>';
foreach ($results as $row) {
	echo "<tr><td>".$row['monster']."</td><td>".precisePercentile($row['snacks'],$row['total']).'%</td><td>'.$row['total'].'</td></tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>