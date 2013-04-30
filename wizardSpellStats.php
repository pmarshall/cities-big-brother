<html><head><title>Cities Wizard Spell-Gaining Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Spell-Gaining Statistics</h2>
<?php
include ('dbConnect.php');

$ranks=array('Apprentice','Shaman','Wizard');
for ($i=0;$i<3;$i++) {
	echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<caption><b>'.$ranks[$i].'s</b></caption>';
	echo '<tr><th>Item</th><th>Find Chance</th></tr>';
	$query = "SELECT * FROM wizard_spell WHERE rank='".$ranks[$i]."' ORDER BY item";
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
	$total=0;
}

mysql_close($conn);

?>
</body>