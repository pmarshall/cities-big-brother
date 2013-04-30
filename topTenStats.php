<html><head><title>Cities Top Ten Lists</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Top Ten Lists</h2><br>
<?php
include ('dbConnect.php');
$query = "SELECT * FROM monster_kills ORDER BY count DESC limit 10";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);


echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Top Ten (Un?)Popular Monsters</b></caption>';
echo '<tr><th>Monster</th><th>Number Brutally Butchered</th></tr>';
foreach ($results as $row) {
	echo '<tr><td>'.$row['monster'].'</td><td>'.$row['count'].'</td></tr>';
}
echo '</table><p><p>';

$query = "SELECT SUM(count) as count, item FROM weapon_break WHERE item NOT LIKE '%Wand%' GROUP BY item ORDER BY count DESC limit 10";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Top Ten Popular Weapons</b></caption>';
echo '<tr><th>Weapon</th><th>Lucky Graceless Flailings</th></tr>';
foreach ($results as $row) {
	echo '<tr><td>'.$row['item'].'</td><td>'.$row['count'].'</td></tr>';
}
echo '</table><p><p>';

$query = "SELECT SUM(count) as count, item FROM weapon_break WHERE item LIKE '%Wand%' GROUP BY item ORDER BY count DESC limit 10";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Top Ten Popular Wands</b></caption>';
echo '<tr><th>Wand</th><th>Lucky Graceless Flailings</th></tr>';
foreach ($results as $row) {
	echo '<tr><td>'.$row['item'].'</td><td>'.$row['count'].'</td></tr>';
}
echo '</table><p><p>';


$query = "SELECT item, SUM(totalDrop) AS total FROM monster_drop GROUP BY item ORDER BY total DESC LIMIT 30";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
echo '<table border="1" cellpadding="3" cellspacing="0">';
echo '<caption><b>Top Thirty Items by Total Dropped</b></caption>';
echo '<tr><th>Item</th><th>Loot Summarily Stripped from Recently-Created Corpses</th></tr>';
foreach ($results as $row) {
	echo '<tr><td>'.$row['item'].'</td><td>'.$row['total'].'</td></tr>';
}
echo '</table><p><p>';


mysql_close($conn);
?>
</body>