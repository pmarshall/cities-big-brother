<html><head><title>Cities Flying Healer Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Flying Healer Statistics</h2>
<?php
include ('dbConnect.php');

$query = "SELECT * FROM flying_healer";
$results = mysql_query($query);
$row=mysql_fetch_assoc($results);
echo 'Chance of healing: '.precisePercentile($row['heal'],(float)$row['count']).'%<br>';
if ($row['heal']>0) echo 'Average AP until healed: '.sprintf("%01.2f", 10*$row['count']/(float)$row['heal']).'AP<br>';
echo 'Sample size: '.$row['count'];

mysql_close($conn);

?>
</body>