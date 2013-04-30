<html><head><title>Cities Monster Drop Statistics</title></head>
<body>


<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Monster Drop Statistics</h2><br>
These statistics were last cleared on March 30, 2007.<br>
Quick jump: 
<a href="http://potatoengineer.110mb.com/dropStats.php?end=c">A-B</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=c&end=e">C-D</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=e&end=g">E-F</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=g&end=i">G-H</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=i&end=k">I-J</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=k&end=m">K-L</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=m&end=o">M-N</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=o&end=q">O-P</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=q&end=s">Q-R</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=s&end=u">S-T</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=u&end=w">U-V</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=w&end=y">W-X</a> 
<a href="http://potatoengineer.110mb.com/dropStats.php?start=y">Y-Z</a><br>
<script type="text/javascript">
function replaceDoc() {
	window.location.replace("http://potatoengineer.110mb.com/dropStats.php?start="+document.getElementById('start').value+"&end="+document.getElementById('end').value);
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
function keyHandler(e) {
	var characterCode;
	if(e && e.which){ //if which property of event object is supported (NN4)
		e = e;
		characterCode = e.which; //character code is contained in NN4's which property
	}
	else{
		e = event;
		characterCode = e.keyCode; //character code is contained in IE's keyCode property
	}

	if(characterCode == 13){ //if generated character code is equal to ascii 13 (if enter key)
		replaceDoc();
	}
}
</script>
Start at: <input type="text" id="start" size="10" onkeyup="keyHandler(event)" />
End at: <input type="text" id="end" size="10" onkeyup="keyHandler(event)" />
<input type="submit" value="Load Stats"  onclick="replaceDoc()" />
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
$query = "SELECT * FROM monster_drop";
if ($start && $end) {
	if ($start > $end) $end=chr(ord($start[0])+1);
	$query=$query." where monster >= '$start' and monster <= '$end'";
}
else if ($start) {
	$query=$query." where monster >= '$start'";
	// if start was specified, and end wasn't, apply a default of 1 letters' worth of stuff.
	if (ord($start)<ord('y')) {
		$newEnd=$start;
		$newEnd[0]=chr(ord($newEnd[0])+1);
		$query=$query." AND monster <= '$newEnd'";
	}
}
else if ($end) $query=$query." where monster <= '$end'";
else die();

$query=$query." ORDER BY monster ASC, probability DESC";
$results = mysql_evaluate_array($query,MYSQL_ASSOC);
$monster='not a monster';
foreach ($results as $row) {
	if ($monster!=$row['monster']) {
		// if we've found a new monster, finish the previous table and start a new one.
		if ($monster!='not a monster') {
			echo '</table>Sample size: '.$monsterKills.'<br>Minimum item types dropped: '.$minItemTypes.'<br>Maximum item types dropped: '.$maxItemTypes.'<br>Average item types dropped: '.sigFigs((float)$totalItemTypes/$monsterKills).'<p>';
		}

		$monster=$row['monster'];
		$query="SELECT count FROM monster_kills WHERE monster='$monster'";
		$killsResult=mysql_query($query);
		$killRow=mysql_fetch_row($killsResult);
		$monsterKills=$killRow[0];
		//$monsterKills=$killsResult[0][0];
		
		echo '<table border="1" cellpadding="3" cellspacing="0">';
		echo '<caption><b>'.$monster.'</b></caption>';
		echo '<tr><th>Item</th><th>Chance</th><th>Number (average)</th><th>Standard Deviation of Drop Chance</th></tr>';
	}
	if ($row['item']=='different items') {
		$minItemTypes=$row['minDrop'];
		$maxItemTypes=$row['maxDrop'];
		$totalItemTypes=$row['count'];
	}
	else {
		echo '<tr><td>'.$row['item'].'</td><td>'.precisePercentile($row['count'],$monsterKills).'%</td><td>';
		if ($row['minDrop']==$row['maxDrop']) echo $row['minDrop'];
		else echo $row['minDrop'].' to '.$row['maxDrop'].' ('.sigFigs($row['totalDrop']/(float)$row['count'], calcSigFigs($row['count'],$monsterKills)).')';
		echo '</td><td>'.sigFigs(100.0*calcStdDev($row['count'],$monsterKills),3).'%</td></tr>';
	}
}
// one last table wrap-up.
echo '</table>Sample size: '.$monsterKills.'<br>Minimum item types dropped: '.$minItemTypes.'<br>Maximum item types dropped: '.$maxItemTypes.'<br>Average item types dropped: '.sigFigs((float)$totalItemTypes/$monsterKills).'<p>';

mysql_close($conn);
?>
</body>