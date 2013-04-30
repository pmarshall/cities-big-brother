<html><head><title>Cities Cute Seal Kudo Loss Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Cute Seal Kudos Loss Statistics</h2>
<?php
include ('dbConnect.php');
$query = "SELECT * FROM seal_kudo_loss";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($results as $row) {
	$query="SELECT count FROM monster_kills WHERE monster='Cute Seal'";
	$monsterResults=mysql_query($query);
	$monsterRow=mysql_fetch_assoc($monsterResults);
	
	echo 'Chance of losing kudos: '.precisePercentile($row['count'],($monsterRow['count']-$row['offset'])).'%<br>';
	echo 'Sample Size: '.($monsterRow['count']-$row['offset']);
}

mysql_close($conn);

?>
</body>