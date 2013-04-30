<html><head><title>Cities Item Drop Statistics</title></head>
<body>
<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Item Drop Statistics</h2><br>
These statistics were last cleared on March 29, 2007.<br>
Quick jump: 
<a href="http://potatoengineer.110mb.com/itemStats.php?end=c">A-B</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=c&end=e">C-D</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=e&end=g">E-F</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=g&end=i">G-H</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=i&end=k">I-J</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=k&end=m">K-L</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=m&end=o">M-N</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=o&end=q">O-P</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=q&end=s">Q-R</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=s&end=u">S-T</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=u&end=w">U-V</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=w&end=y">W-X</a> 
<a href="http://potatoengineer.110mb.com/itemStats.php?start=y">Y-Z</a><br>
<script type="text/javascript">
function replaceDoc() {
	window.location.replace("http://potatoengineer.110mb.com/itemStats.php?start="+document.getElementById('start').value+"&end="+document.getElementById('end').value);
}
function getURLParam(strParamName){
  var strReturn = "";
  var strHref = window.location.href;
  if ( strHref.indexOf("?") > -1 ){
    var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
    var aQueryString = strQueryString.split("&");
    for ( var iParam = 0; iParam < aQueryString.length; iParam++ ){
      if (
aQueryString[iParam].indexOf(strParamName.toLowerCase() + "=") > -1 ){
        var aParam = aQueryString[iParam].split("=");
        strReturn = aParam[1];
        strReturn=strReturn.replace("'","");
        break;
      }
    }
  }
  return unescape(strReturn);
}
</script>
Start at: <input type="text" id="start" size="10"/>
End at: <input type="text" id="end" size="10"/>
<input type="button" value="Load Stats" onclick="replaceDoc()" />
<script type="text/javascript">
document.getElementById('start').value=getURLParam("start");
// if start was specified, and end wasn't, apply a default of 1 letters' worth of stuff.
if (getURLParam('start') != "" && getURLParam('end')=="") {
	var newEnd=getURLParam("start");
	if (newEnd.charCodeAt(0)<'y'.charCodeAt(0)) newEnd=String.fromCharCode(newEnd.charCodeAt(0)+1);
	document.getElementById('end').value=newEnd;
}
else {
	document.getElementById('end').value=getURLParam("end");
}
</script>


<?php
if ($_GET['start']) {
	$start=$_GET['start'];
}
if ($_GET['end']) {
	$end=$_GET['end'];
}
$start=preg_replace('/[;<>,\'"]/','',$start); // make it secret.  make it safe!
$end=preg_replace('/[;<>,\'"]/','',$end); 

include ('dbConnect.php');

// this is probably overkill, but the alternative is to look up each monster kill as we find it.  The OTHER option would be to look up only the monsters we've actually found, but that sounds needlessly complicated considering that we might be looking at every row in the database to start with.
$killQuery="SELECT count, monster from monster_kills";
$killResults=mysql_evaluate_array($killQuery, MYSQL_ASSOC);
$killArray=array();
foreach($killResults as $killRow) {
	$killArray[$killRow['monster']]=$killRow['count'];
}

$query = "SELECT * FROM monster_drop";
if ($start && $end) $query=$query." where item >= '$start' and item <= '$end'";
else if ($start) {
	$query=$query." where item >= '$start'";
	// if start was specified, and end wasn't, apply a default of 1 letters' worth of stuff.
	if (ord($start)<ord('y')) {
		$newEnd=$start;
		$newEnd[0]=chr(ord($newEnd[0])+1);
		$query=$query." AND item <= '$newEnd'";
	}
}
else if ($end) $query=$query." where item <= '$end'";
else die();
$query=$query." ORDER BY item ASC, probability DESC";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
$item='not an item';
foreach ($results as $row) {
	if ($item!=$row['item']) {
		// if we've found a new item, finish the previous table and start a new one.
		if ($item!='not an item') {
			echo '</table>Times dropped:'.$timesDropped.'<br>Total Found: '.$total.'<p>';
		}
		$total=0;
		$timesDropped=0;
		
		if ($row['item']=='different items') continue;
		$item=$row['item'];
		
		echo '<table border="1" cellpadding="3" cellspacing="0">';
		echo '<caption><b>'.$item.'</b></caption>';
		echo '<tr><th>Monster</th><th>Chance</th><th>Number (average)</th><th>Sample Size</th><th>Standard Deviation of Drop Chance</th></tr>';
	}
	/*$monster=$row['monster'];
	$query="SELECT count FROM monster_kills WHERE monster='$monster'";
	$killsResult=mysql_query($query);
	$killRow=mysql_fetch_assoc($killsResult);*/
	$monsterKills=$killArray[$row['monster']];  // separated out here just for clarity.
	
	echo '<tr><td>'.$row['monster'].'</td><td>'.precisePercentile($row['count'],$monsterKills).'%</td><td>';
	if ($row['minDrop']==$row['maxDrop']) echo $row['minDrop'];
	else echo $row['minDrop'].' to '.$row['maxDrop'].' ('.sigFigs($row['totalDrop']/(float)$row['count'], calcSigFigs($row['count'],$monsterKills)).')';
	echo '</td><td>'.$monsterKills.'</td><td>'.sigFigs(100.0*calcStdDev($row['count'],$monsterKills),3).'%</td></tr>';
	$total+=$row['totalDrop'];
	$timesDropped+=$row['count'];
}
// one last table wrap-up.
echo '</table>Times dropped:'.$timesDropped.'<br>Total Found: '.$total.'<p>';

mysql_close($conn);
?>
</body>