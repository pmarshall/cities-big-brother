<html><head><title>Cities Northern Rock Stats</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
include ('dbConnect.php');
?>

<h2>Northern Rock Statistics</h2>
Chance of a darling from the treasury giving you 1000 gold: 
<?php
$query = "SELECT SUM(darling) as darling, COUNT(*) as total FROM northern_rock";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo precisePercentile($results[0]['darling'],$results[0]['total']).'%<br>';
?>
Chance of teleportation:
<?php
echo precisePercentile($results[0]['total']-$results[0]['darling'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];
mysql_close($conn);
?>


</body>