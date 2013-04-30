<?php
require_once 'phplot/phplot.php';
include ('logger.php');
if ($_GET['monster']) {
	$monster=$_GET['monster'];
}
if ($_GET['item']) {
	$item=$_GET['item'];
}
if ($_GET['startInterval']) {
	$startInterval=$_GET['startInterval'];
}
if ($_GET['endInterval']) {
	$endInterval=$_GET['endInterval'];
}
$item=preg_replace('/[-;<>,\'"]/','',$item); // make it secret.  make it safe!
$monster=preg_replace('/[-;<>,\'"]/','',$monster); 
$startInterval=preg_replace('/[^\d]/','',$startInterval); // only numbers in the intervals.
$endInterval=preg_replace('/[^\d]/','',$endInterval); 

if ($endInterval>$startInterval) $endInterval=$startInterval-30;
if ($endInterval<0) $endInterval=0;
if ($startInterval<0) $startInterval=30;

if (!$startInterval) $startInterval=30;
if (!$endInterval) $endInterval=0;

include ('dbConnect.php'); // connect me to the DB
if ($monster && $item) {
	$query="SELECT count, UNIX_TIMESTAMP(time) FROM monster_drop_timed WHERE monster='$monster' AND item='$item' AND DATE_SUB(NOW(), INTERVAL $startInterval DAY) <= time AND DATE_SUB(NOW(),INTERVAL $endInterval DAY) >= time ORDER BY time";
}
else if ($monster) {
	$query="SELECT numRepeats, UNIX_TIMESTAMP(time) FROM monster_drop_timed WHERE monster='$monster' AND item='different items' AND DATE_SUB(NOW(), INTERVAL $startInterval DAY) <= time AND DATE_SUB(NOW(),INTERVAL $endInterval DAY) >= time ORDER BY time";
}
else if ($item) {
	$query="SELECT count, UNIX_TIMESTAMP(time) FROM monster_drop_timed WHERE item='$item' AND DATE_SUB(NOW(), INTERVAL $startInterval DAY) <= time AND DATE_SUB(NOW(),INTERVAL $endInterval DAY) >= time ORDER BY time";
}
else die(); // if both start and end weren't entered, don't display anything.
//echo $query;
$results = mysql_evaluate_array($query);
$chartData=array();
$total=0;
$day=-1;
foreach ($results as $row) {
	$timePassed=time()-$row[1];
	$daysAgo=floor($timePassed/60/60/24); // Number of days elapsed.
	if ($day==-1) $day=$daysAgo;	//set the first day manually.
	//echo 'days elapsed: '.$daysAgo.' time:'.$row[1];
	if ($day!=$daysAgo && $daysAgo!=$endInterval) {
		$chartData[]=array('',$daysAgo*-1,$total);	// if today is not the current day, add a new day.
		$day=$daysAgo;
	}
	$total+=$row[0];
}
if ($daysAgo==$endInterval)$chartData[]=array('',$daysAgo*-1,$total);	// add the last day.

//plotting!
$plot = new PHPlot(600, 400);
$plot->SetPlotType("lines");
$plot->SetDataType('data-data');
$plot->SetDataValues($chartData);
if ($monster && $item) {
	$plot->SetTitle("Number of $item found on $monster");
	$plot->SetYTitle('Item count');
}
else if ($monster) {
	$plot->SetTitle("$monster kills");
	$plot->SetYTitle('Kills');
}
else if ($item) {
	$plot->SetTitle("Number of $item found globally");
	$plot->SetYTitle('Item Count');
}
$plot->SetXTitle('Days Ago');
$plot->SetPlotAreaWorld($startInterval*-1, 0, $endInterval*-1, NULL); //y-axis starts at 0.  Don't care about the rest.
$plot->DrawGraph();
?>