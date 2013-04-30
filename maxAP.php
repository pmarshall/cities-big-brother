<html><head><title>Cities Time To Max AP Calculator</title></head>
<body onload="compute()">
<?php
include ('siteHeader.php');
include ('zzStatCounter.php');
if ($_GET["username"] && $_GET["username"]!='') {
	$ch = curl_init('http://cities.totl.net/cgi-bin/info?username='.urlencode($_GET["username"]));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result=curl_exec($ch);
	curl_close($ch);
	$matches=array();
	preg_match('/Maximum Action Points: (\d+)/',$result,$matches);
	$maxAP=$matches[1];
	preg_match('/Current Action Points: (\d+)/',$result,$matches);
	$curAP=$matches[1];
	preg_match('/Base AP Delay: (\d+\.?\d*)/',$result,$matches);
	$regen=$matches[1];
}
?>
<h2>Time to Max AP Calculator</h2><br>
A little widget brought to you by PotatoEngineer, a.k.a. Cor.<p>
<script type="text/javascript">
// PART I: CURRENT SERVER TIME
time=new Date();
// British daylight savings time.  Changes EVERY SINGLE FREAKING YEAR.  MUST. KILL. DST.
// eh, it should be roughly correct for a few years, unless the British do the same thing the Americans did.
if ((time.getMonth()>2 || (time.getMonth()==2 && time.getDate()>=25)) && (time.getMonth()<9 || (time.getMonth==9 &&time.getDate()<=28))) {
	time.setHours(time.getHours()+1)
}
offset=time.getTimezoneOffset();
time.setMinutes(time.getMinutes()+offset);
var timeString='The current server time is: ';
if (time.getHours()<10) timeString+='0';
timeString+=time.getHours()+':';
if (time.getMinutes()<10) timeString+='0';
timeString+=time.getMinutes()+' (24-hour clock)';
document.body.appendChild(document.createTextNode(timeString));

document.body.appendChild(document.createElement('p'));

// PART 4: COMPUTING TIME UNTIL MAX AP
function compute() {
	var AP=parseInt(document.getElementById('currAP').value);
	var maxAP=parseInt(document.getElementById('maxAP').value);
	var hat=document.getElementById('hat').options[document.getElementById('hat').selectedIndex].value;
	var fashion=document.getElementById('fashion').options[document.getElementById('fashion').selectedIndex].value;
	var party=document.getElementById('party').value;
	var APregen=parseFloat(document.getElementById('APregen').value);
	var monsterLeagueAP=document.getElementById('monsterLeagues').options[document.getElementById('monsterLeagues').selectedIndex].value;
	
	//var print=AP+''+maxAP+''+''+hat+''+party+''+APregen+''+fashion;
	//document.firstChild.lastChild.appendChild(document.createTextNode("Compute() completed successfully "+print));
	
	var fashionTick=0;
	switch(fashion) {
	case 'CuttingEdge':
		fashionTick=1;
		break;
	case 'InFashion':
		fashionTick=3;
		break;
	case 'JustAboutIn':
		fashionTick=8;
		break;
	}

	var dailyAP=parseInt(monsterLeagueAP);
	
	var APbonusHour=-1;
	var APperHour=0;
	switch(hat) {
	case 'WizardHat':
		APperHour=3;
		APbonusHour=0;
		break;
	case 'Crown':
	case 'LegendaryHat':
		APperHour=3;
		APbonusHour=12;
		break;
	case 'BaseballCap':
	case 'TopHat':
	case 'JestersHat':
		APperHour=2;
		break;
	case 'BowlerHat':
	case 'Bonnet':
		APperHour=1;
		break;
	case 'PirateHat':
		APperHour=-2;
		break;
	}
	
	APregen*=party;
	
	time=new Date();
	// British daylight savings time.  Changes EVERY SINGLE FREAKING YEAR.  MUST. KILL. DST.
	// eh, it should be roughly correct for a few years, unless the British do the same thing the Americans did.
	if ((time.getMonth()>2 || (time.getMonth()==2 && time.getDate()>=25)) && (time.getMonth()<9 || (time.getMonth==9 &&time.getDate()<=28))) {
		time.setHours(time.getHours()+1)
	}
	offset=time.getTimezoneOffset();
	time.setMinutes(time.getMinutes()+offset);

	var minutes=60-time.getMinutes();
	
	var endTime=new Date(time);
	
	var leftOverMinutes=false;
	var firstLoop=true;
	// the main calculation loop: run one hour.  If that hour puts me over maxAP, then exit, and step through the last hour the slow way.  Otherwise, if the once-per-hour AP is what drives me over the line, then end at exactly the hour.
	while(AP<maxAP) {
		if (!firstLoop) minutes+=60;
		
		var APthisHour=Math.floor(minutes/APregen);
		if (AP+APthisHour>=maxAP) {
			leftOverMinutes=true;
//document.body.appendChild(document.createTextNode('leftover minutes'+' '));
			break;
		}
		minutes-=APthisHour*APregen;
//document.body.appendChild(document.createTextNode('AP from regen:'+APthisHour+' '));
		
		endTime.setHours(endTime.getHours()+1);
		endTime.setMinutes(0);
		APthisHour+=APperHour;
		if (fashionTick && endTime.getHours()%fashionTick==0) APthisHour++;
		if (dailyAP>0 && endTime.getHours()%24==12) APthisHour+=dailyAP;
		if (endTime.getHours()%24==APbonusHour) APthisHour+=50;	// crown & wizard hat
		
		AP+=APthisHour;
//document.body.appendChild(document.createTextNode('total AP this hour'+APthisHour+' '));
		
		if (firstLoop) firstLoop=false;
	}
	
	if (leftOverMinutes) {
		if (!firstLoop) minutes-=60;
		endTime.setMinutes(endTime.getMinutes()+(maxAP-AP)*APregen-minutes);
//document.body.appendChild(document.createTextNode('AP from leftovers:'+(maxAP-AP)+' '+'leftover minutes:'+minutes));
	}
	
	var elapsedHours=Math.floor((endTime-time)/3600000);
	var elapsedMinutes=Math.floor((endTime-time)/60000);
	elapsedMinutes=elapsedMinutes%60;
	var output='You will reach maximum AP in '+elapsedHours+' hours';
	if (elapsedMinutes>0) output+=' and '+elapsedMinutes+' minutes.';
	else output+='.';
	document.getElementById('timeToMax').value=output;

//document.body.appendChild(document.createTextNode(time+' '));
//document.body.appendChild(document.createTextNode(endTime+' '));
//document.body.appendChild(document.createTextNode(Math.floor((endTime-time)/3600000)+' '));
//document.body.appendChild(document.createTextNode(Math.floor((endTime-time)/60000)%60+' '));
}

function loadUsername() {
	var user=document.getElementById('usernameInput').value;
	location.href='http://potatoengineer.110mb.com/maxAP.php?username='+escape(user);
}
</script>
You can save a little time by entering your username here and clicking the button: 
<input type="text" id="usernameInput" size="12" value="<? echo $_GET['username']; ?>"/><input type="button" value="Load!" onclick="loadUsername()" />

<p>
Current AP: <input type="text" id="currAP" size="5" value="<? echo $curAP; ?>"/><br>
Maximum AP: <input type="text" id="maxAP" size="5" value="<? echo $maxAP; ?>"/><br>
AP Regen Time (in minutes): <input type="text" id="APregen" size="5" value="<? echo $regen; ?>"/><br>
Location: <select id="party"><option value="1">Nowhere interesting</option><option value="5">Bleak Island</option><option value=".5">Party</option><option value=".75">Flying Party</option></select><br>
Hat Type: <select id="hat"><option value="None">None (or hat doesn't affect AP)</option><option value="WizardHat">Wizard's Hat</option><option value="Crown">Crown</option><option value="BaseballCap">Baseball Cap</option><option value="TopHat">Top Hat</option><option value="JestersHat">Jester's Hat</option><option value="Bonnet">Bonnet</option><option value="BowlerHat">Bowler Hat</option><option value="PirateHat">Pirate Hat</option><option value="LegendaryHat">Legendary Hat</option></select><br>
Fashion: <select id="fashion"><option value="None">None</option><option value="CuttingEdge">Cutting Edge Fashion</option><option value="InFashion">In Fashion</option><option value="JustAboutIn">Just About In Fashion</option></select><br>
Number of Monster Leagues you are top of: <select id="monsterLeagues"><option value="0">None</option><option value="10">1-3 Leagues</option><option value="20">4-8 Leagues</option><option value="30">9-15 Leagues</option><option value="40">16-24 Leagues</option><option value="50">25-35 Leagues</option><option value="60">36-48 Leagues</option><option value="70">49-63 Leagues</option><option value="80">64-80 Leagues</option></select><br>

<input type="button" value="Compute!" onclick="compute()" /><p>

<input type="text" id="timeToMax" size="100"/><br>
</body>