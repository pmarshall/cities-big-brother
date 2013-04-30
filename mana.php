<html><head><title>Cities Time To X Mana Calculator</title></head>
<body>
<?php
include ('siteHeader.php');
include ('zzStatCounter.php');
?>
<h2>Time to X Mana Calculator</h2><br>
A little widget brought to you by PotatoEngineer, a.k.a. Cor.<p>

Current Mana: <input type="text" id="currMana" size="5"/><br>
Target Mana: <input type="text" id="targetMana" size="5"/><br>
At a Pentacle? <input type="checkbox" id="pentacle"/><br>
Wizard Hat? <input type="checkbox" id="hat"/><br>
Wizard Rank:<select id="rank"><option value="8">Apprentice</option><option value="3">Shaman</option><option value="1">Wizard</option></select><br>

<input type="button" value="Compute!" onclick="compute()" /><p>

<input type="text" id="timeToMana" size="100"/><p>

<script type="text/javascript">
// PART I: CURRENT SERVER TIME
	time=new Date();
	// British daylight savings time.  Changes EVERY SINGLE FREAKING YEAR.  MUST. KILL. DST.  It's what, the third Monday in February, or somthing?
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

// COMPUTING TIME UNTIL TARGET MANA
function compute() {
	var mana=parseInt(document.getElementById('currMana').value);
	var targetMana=parseInt(document.getElementById('targetMana').value);
	var rankTick=document.getElementById('rank').options[document.getElementById('rank').selectedIndex].value;
	var pentacle=document.getElementById('pentacle').checked;
	var hat=document.getElementById('hat').checked;
	
	//var print=AP+''+maxAP+''+''+hat+''+party+''+APregen+''+fashion;
	//document.firstChild.lastChild.appendChild(document.createTextNode("Compute() completed successfully "+print));
	
	time=new Date();
	// British daylight savings time.  Changes EVERY SINGLE FREAKING YEAR.  MUST. KILL. DST.
	// eh, it should be roughly correct for a few years, unless the British do the same thing the Americans did.
	if ((time.getMonth()>2 || (time.getMonth()==2 && time.getDate()>=25)) && (time.getMonth()<9 || (time.getMonth==9 &&time.getDate()<=28))) {
		time.setHours(time.getHours()+1)
	}
	offset=time.getTimezoneOffset();
	time.setMinutes(time.getMinutes()+offset);

	var endTime=new Date(time);
	
	// the main calculation loop: run one hour.  If that hour puts me over maxAP, then exit, and step through the last hour the slow way.  Otherwise, if the once-per-hour AP is what drives me over the line, then end at exactly the hour.
	while(mana<targetMana) {
		endTime.setHours(endTime.getHours()+1);
		endTime.setMinutes(0);

		if (endTime.getHours()%rankTick==0) {
			if (hat && pentacle) {
				mana+=9;
			}
			else if (pentacle || hat) {
				mana+=3;
			}
			else  mana++;
		}
	}
	
	var elapsedHours=Math.floor((endTime-time)/3600000);
	var elapsedMinutes=Math.floor((endTime-time)/60000);
	elapsedMinutes=elapsedMinutes%60;
	var output='You will reach your target Mana in '+elapsedHours+' hours';
	if (elapsedMinutes>0) output+=' and '+elapsedMinutes+' minutes.';
	else output+='.';
	document.getElementById('timeToMana').value=output;
}

function calcBook() {
	var sMana=parseInt(document.getElementById('spellMana').value);
	if (sMana) {
		document.getElementById('bookMana').value=sMana*5;
		document.getElementById('bookAP').value=sMana*30;
	}
}
</script>


<h4>While we're at it: a Spellbook Calculator</h4>

Spell's Casting Cost in Mana: <input type="text" id="spellMana" onkeyup="javascript:calcBook()" size="5"/><p>
Mana to inscribe spell: <input type="text" id="bookMana" onkeyup="javascript:calcBook()" size="5"/><br>
AP to inscribe spell: <input type="text" id="bookAP" onkeyup="javascript:calcBook()" size="5"/><br>



</body>