<html><head><title>Singing for Falling Rocks</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
include ('dbConnect.php');
?>

<h2>Singing for Falling Rocks</h2>
Chance of the harvest song causing a star to fall on your crop: 
<?php
$query = "SELECT SUM(starfall) as starfall, COUNT(*) as total FROM harvest_song";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo precisePercentile($results[0]['starfall'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];
mysql_close($conn);
?>


</body>