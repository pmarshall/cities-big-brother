<?php
$dbhost = 'localhost';
$dbuser = '846179_potato';
$dbpass = 'dsi444';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or logError ('Error connecting to mysql');

$dbname = 'potatoengineer_db';
mysql_select_db($dbname) or logError('Database? What database?');
?>