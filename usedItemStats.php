<html><head><title>Used Item Breakage</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Summon Stones</h2>
<?php
include ('dbConnect.php');


echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Result</th><th>Chance</th></tr>';
$query = "SELECT COUNT(*) as total FROM summon_stone WHERE result='Stone broke completely'";
$stoneBroke = mysql_evaluate_array($query,MYSQL_ASSOC);
$query = "SELECT COUNT(*) as total FROM summon_stone WHERE result='Summon Stone'";
$whole = mysql_evaluate_array($query,MYSQL_ASSOC);
$query = "SELECT COUNT(*) as total FROM summon_stone WHERE result='Summon Stone (cracked)'";
$cracked = mysql_evaluate_array($query,MYSQL_ASSOC);
$total=$stoneBroke[0]['total']+$cracked[0]['total']+$whole[0]['total'];

echo '<tr><td>Stone Broke Completely</td><td>'.precisePercentile($stoneBroke[0]['total'],$total).'%</td></tr>';
echo '<tr><td>Stone Cracked</td><td>'.precisePercentile($cracked[0]['total'],$total).'%</td></tr>';
echo '<tr><td>Stone Intact</td><td>'.precisePercentile($whole[0]['total'],$total).'%</td></tr>';
echo '</table>';
echo 'Sample Size: '.$total.'<p><p>';
?>
<h2>Wormholes</h2>
Chance of breaking: 
<?php
$query = "SELECT SUM(break) as breaks, COUNT(*) as total FROM wormhole";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo precisePercentile($results[0]['breaks'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];
?>

<h2>Flashguns</h2>
Chance of breaking: 
<?php
$query = "SELECT SUM(break) as breaks, COUNT(*) as total FROM flashgun";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo precisePercentile($results[0]['breaks'],$results[0]['total']).'%<br>';
echo 'Sample Size: '.$results[0]['total'];
mysql_close($conn);
?>


</body>