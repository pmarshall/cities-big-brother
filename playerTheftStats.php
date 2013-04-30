<html><head><title>Cities Player Theft Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Player Theft Statistics</h2>
<?php
include ('dbConnect.php');
$query = "SELECT SUM(theft) as thefts, COUNT(*) as total FROM player_theft";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo 'Chance of successfully stealing: '.precisePercentile($results[0]['thefts'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];

mysql_close($conn);

?>
</body>