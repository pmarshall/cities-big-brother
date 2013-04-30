<html><head><title>Cities Monster Drop Statistics</title></head>
<body>


<?php
//SHORT ANSWER: The GD thingy installed has most of the interesting features, including PNG, JPEG, and other good stuff.  Also, GD IS INSTALLED.  This is good.
/*$array=gd_info ();
foreach ($array as $key=>$val) {
 
  if ($val===true) {
    $val="Enabled";
  }

  if ($val===false) {
    $val="Disabled";
  }

  echo "$key: $val <br />\n";

}*/
?>

<?php
include ('statHeader.php');
include ('logger.php');
?>
<h2>Item Drop Graphs</h2><br>
This graph's data begins on about March 15th, 2008.  Previous data was all aggregated, so searches in the far past will show nothing, and then a LOT of items beginning on March 15th.<br>
<script type="text/javascript">
function replaceDoc() {
	window.location.replace("http://potatoengineer.110mb.com/timedDropStats.php?monster="+document.getElementById('monster').value+"&item="+document.getElementById('item').value+"&startInterval="+document.getElementById('startInterval').value+"&endInterval="+document.getElementById('endInterval').value);
}
function getURLParam(strParamName){
  var strReturn = "";
  var strHref = window.location.href;
  if ( strHref.indexOf("?") > -1 ){
    var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
    var aQueryString = strQueryString.split("&");
    for ( var iParam = 0; iParam < aQueryString.length; iParam++ ){
      if (aQueryString[iParam].indexOf(strParamName.toLowerCase() + "=") > -1 ){
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
	var keycode;
	if (window.event) keycode = window.event.keyCode;
	else if (e) keycode = e.which;
	else return true;

	if (keycode == 13) {
		replaceDoc();
		return false;
	}
	else {
		return true;
	}
}
</script>
Monster: <input type="text" id="monster" size="20" onkeyup="keyHandler(event)" />
Item: <input type="text" id="item" size="20" onkeyup="keyHandler(event)" />
Starting from <input type="text" id="startInterval" size="2" onkeyup="keyHandler(event)" /> days ago and running to
 <input type="text" id="endInterval" size="2" onkeyup="keyHandler(event)" /> days ago.
<input type="submit" value="Load Stats"  onclick="replaceDoc()" />
<p>
Enter the monster, item, and interval to get a graph of the total count of that item dropped by that monster. Entering a monster without an item will give you the graph of the kill count for that monster.  Entering an item without a monster will give you the graph of the item across all monsters.  The default interval is from 30 days ago to the present.
<p>
<script type="text/javascript">
// This bit reads the URL, grabs parameters, sets the values of the elements with those IDs with parameter names to the value given.  It also evaluates the URL to create variables on the page with those values.
var strHref = window.location.href;
if ( strHref.indexOf("?") > -1 ){
	strHref=decodeURIComponent(strHref);
	var strQueryString = strHref.substr(strHref.indexOf("?")+1);
    var aQueryString = strQueryString.split("&");
    for ( var iParam = 0; iParam < aQueryString.length; iParam++ ){
        var aParam = aQueryString[iParam].split("=");
        if (aParam.length==2 && aQueryString[iParam].indexOf(',')==-1 && aQueryString[iParam].indexOf(';')==-1 && document.getElementById(aParam[0])) {
			document.getElementById(aParam[0]).value=aParam[1];
			// in theory, since we've stripped the commas and semicolons, there's a limit to how much damage you can do by eval-ing user input here.  I'm sure some hacker will probably prove me wrong.  If, by some lucky happenstance, a white hat comes by before a black hat, please drop me a line at theycalledmemad [ AT ] gmail dot com and let me know what I'm doing wrong here.
			eval('var '+aParam[0]+'="'+aParam[1]+'";');
		}
    }
}

// send off for the image.
if ((monster && typeof (monster)=='string') || (item && typeof(item)=='string')) {
	var args='';
	if (monster && typeof (monster)=='string') { 
		args+='monster='+encodeURIComponent(monster);
		if (item || startInterval || endInterval) args+='&';
	}
	if (item && typeof(item)=='string') {
		args+='item='+encodeURIComponent(item);
		if (startInterval || endInterval) args+='&';
	}
	if (startInterval&&typeof(startInterval)=='string' || typeof(startInterval)=='number') {
		args+='startInterval='+startInterval;
		if (endInterval) args+='&';
	}
	if (endInterval&&typeof(endInterval)=='string' || typeof(endInterval)=='number') args+='endInterval='+endInterval;
	document.write('<img src="http://potatoengineer.110mb.com/timedDropImage.php?'+args+'">');
}
</script>
</body></html>