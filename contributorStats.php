<html><head><title>Contributors</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Contributor Competition</h2>
Data counting started April 1, 3:07AM PST<br>
As of April 3, 1AM PST, scores are weighted roughly by the rarity of data.  For common data, you gain 2 bonus points if there are less than 100 entries, and 1 bonus point if there are less than 1000 entries.  Uncommon data types, such as farming, or fruit-gathering in the jungle, have a points multiplier between 2 and 5.  Let the silly competition for meaningless status begin!
<?php
include ('dbConnect.php');

$query="select * from death";
$deaths=mysql_evaluate_array($query,MYSQL_ASSOC);
foreach($deaths as $deathRow) {
	$total+=$deathRow['count'];
}
if ($total) {
	echo '<p><strong>Contributors fallen in the line of duty: '.$total.'</strong>';
}

echo'<table border="0" cellpadding="5"><tr VALIGN="bottom" ALIGN="center"><td width=200>Aggregate data score, listed by the first character to use a key</td><td>Data score by Username</td><td>Aggregate data score <br>in the past 30 days</td><td>Data score by username <br>in the past 30 days</td></tr>';

echo '<tr ALIGN="center"><td VALIGN="top">';

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Username</th><th>Data Score</th></tr>';
$total=0;
$query="SELECT SUM(count) as score, username FROM contributors GROUP BY id_key ORDER BY score DESC";
$users=mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($users as $row) {
	if ($row['score']>0) {
		echo '<tr>';
		echo '<td>'.$row['username'].'</td><td>'.$row['score'].'</td>';
		echo '</tr>';
		// quick fixit for count mismatch.  Hopefully fixed now.
		//$query="UPDATE validation SET count='$total' WHERE id_key='".$row['id_key']."'";
		//mysql_query($query);
	}
}
echo '</table>';

echo '</td><td>';

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Username</th><th>Data Score</th></tr>';
$query = "SELECT * FROM contributors ORDER BY count DESC";
$contributors = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($contributors as $row) {
	echo '<tr>';
	echo '<td>'.$row['username'].'</td><td>'.$row['count'].'</td>';
	echo '</tr>';
}
echo '</table>';

echo '</td><td VALIGN="top">';

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Username</th><th>Data Score</th></tr>';
$query = "SELECT SUM(count) as score, id_key, username FROM data_score WHERE DATE_SUB(NOW(),INTERVAL 30 DAY) <= time GROUP BY id_key ORDER BY score DESC";
$contributors = mysql_evaluate_array($query,MYSQL_ASSOC);
$idKey='blah!';
$displayData=array();
foreach ($contributors as $row) {
	echo '<tr>';
	echo '<td>'.$row['username'].'</td><td>'.$row['score'].'</td>';
	echo '</tr>';
}
echo '</table>';


echo '</td><td VALIGN="top">';

echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<tr><th>Username</th><th>Data Score</th></tr>';
$query = "SELECT SUM(count) as score, username FROM data_score WHERE DATE_SUB(NOW(),INTERVAL 30 DAY) <= time GROUP BY username ORDER BY score DESC";
$contributors = mysql_evaluate_array($query,MYSQL_ASSOC);
foreach ($contributors as $row) {
	echo '<tr>';
	echo '<td>'.$row['username'].'</td><td>'.$row['score'].'</td>';
	echo '</tr>';
}
echo '</table>';

echo '</td></tr></table>';

mysql_close($conn);

?>
</body>