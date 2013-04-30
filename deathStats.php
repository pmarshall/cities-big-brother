<html><head><title>Fallen Contributors</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Fallen Compatriots</h2>
<?php
include ('dbConnect.php');

$query="select * from death";
$deaths=mysql_evaluate_array($query,MYSQL_ASSOC);
foreach($deaths as $deathRow) {
	$total+=$deathRow['count'];
}
if ($total) {
	echo '<p><strong>Contributors fallen in the line of duty: '.$total.'</strong><p>';
}

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><strong>Well-fed Monsters</strong></caption>';
echo '<tr><th>Monster</th><th>Meals</th></tr>';
$query = "SELECT SUM(count) as deaths, monster FROM death GROUP BY monster ORDER BY deaths DESC, monster";
$contributors = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($contributors as $row) {
	echo '<tr>';
	echo '<td>'.$row['monster'].'</td><td>'.$row['deaths'].'</td>';
	echo '</tr>';
}
echo '</table>';

mysql_close($conn);

?>
</body>