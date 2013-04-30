<html><head><title>Stall Profiteer</title></head>
<body>
<?php
include ('siteHeader.php');
include ('zzStatCounter.php');
?>
<h2>Stall Profiteer</h2><br>
A little widget brought to you by PotatoEngineer, a.k.a. Cor.<p>
Copy-paste all the purchase/sell records from your stall reports here:<br>
<textarea id="readings"  rows="10" cols="50"></textarea>
<input type="button" value="Compute!" onclick="compute()" /><p>

Blank lines are fine.
<p>Comment lines, should you need them, start with a #<p>
Total Profits from your Stall: <input type="text" id="profits" size="20"/><p>
Profits will add to the above value, so feel free to do your reports one at a time, in series.
<script type="text/javascript">
function compute() {
	var textbox=document.getElementById('readings');
	var text=textbox.value;
	var lines;
	if (text.indexOf("\n")==-1) {
		lines=new Array(text);
	}
	else {
		lines=text.split("\n");
	}

	var total=0;
	var cashRE=/^(Purchased|Sold) (a|\d+) .+? for (\d+) gold\./;
	for (var i=0;i<lines.length;i++) {
		if (lines[i].substr(0,1)=='#') continue;
		var results=cashRE.exec(lines[i]);
		if (!results) continue; //generic parsing failure; ignore that line.
		
		if (results[1]=='Purchased') total-=parseInt(results[3]);
		else total+=parseInt(results[3]);
	}
	profitText=document.getElementById('profits');
	if (parseInt(profitText.value)) profitText.value=parseInt(profitText.value)+total;
	else profitText.value=total;
}
</script>
</body></html>