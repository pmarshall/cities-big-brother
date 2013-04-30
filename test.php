<?php
include('HTTPheader.php');
include('logger.php');

include('dbConnect.php');

//mysql_query('START TRANSACTION');
echo "Starting page.";
$killQuery="SELECT count, monster from monster_kills";
$killResults=mysql_evaluate_array($killQuery, MYSQL_ASSOC);
$killArray=array();
foreach($killResults as $killRow) {
	$killArray[$killRow['monster']]=$killRow['count'];
}

$results=mysql_evaluate_array('SELECT * from monster_drop',MYSQL_ASSOC) or die ('Cannot select!?');
foreach ($results as $row) {
	echo $row['monster'];
	if ($killArray[$row['monster']]==0) $killArray[$row['monster']]=1;
	$pEstimate=$row['count']/$killArray[$row['monster']];
	if ($pEstimate>1.0)$pEstimate=1.0;
	$deviation=sqrt($pEstimate*(1.0-$pEstimate)/$killArray[$row['monster']]);
	// quick hack to make 100% with one hit a small number.  0% and 100% kinda fall apart with the standard deviation calculations.
	if ($deviation==0) $deviation=1.0/$killArray[$row['monster']];
	$probability=$pEstimate-$deviation;
	if ($probability <0) $probability=0;
	$update="UPDATE monster_drop SET probability='".$probability."' WHERE monster='".$row['monster']."' and item='".$row['item']."'";
	mysql_query($update);
}
echo "done!";
//mysql_query('COMMIT') or die ('Cannot unlock!?');

mysql_close($conn);
?>
