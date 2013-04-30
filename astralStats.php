<html><head><title>Astral Travel</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
include ('dbConnect.php');
?>

<h2>Astral Travel</h2>
Chance of the Astral Plane spell taking you to the Universe of Shrimp: 
<?php
$query = "SELECT SUM(shrimp) as shrimp, COUNT(*) as total FROM astral_plain";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo precisePercentile($results[0]['shrimp'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];
mysql_close($conn);
?>


</body>