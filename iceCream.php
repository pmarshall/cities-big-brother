<html><head><title>Ice Cream Locator</title></head>
<body>
<?php
include ('siteHeader.php');
include ('zzStatCounter.php');
?>
<h2>Ice Cream Locator</h2><br>
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

// PART 2: determining the position of the ice cream van: start at a known position & location, count travel since then.  Modulus by the size of the circle, then start counting off squares until we're out of minutes.
var iceCreamTimeStart=new Date("June 12, 2007 4:54:00");
var travel=Math.floor((time-iceCreamTimeStart)/60000);
var totalTravel=travel;
travel %=91*4;

// moving about.  Start at northeast corner, travel clockwise, checking for journey's end at each turn.
var east=95;
var north=95;
if (travel && travel>91) {
	north=4;
	travel-=91;
}
else if (travel) {
	north-=travel;
	travel=0;
}
if (travel && travel>91) {
	east=4;
	travel-=91;
}
else if (travel) {
	east-=travel;
	travel=0;
}
if (travel && travel>91) {
	north=95;
	travel-=91;
}
else if (travel) {
	north+=travel;
	travel=0;
}
if (travel) {
	east+=travel;
	travel=0;
}

document.body.appendChild(document.createTextNode('The Ice Cream Van is at '+east+'E '+north+'N but only if the above clock matches Cities time. (it\'s based on your computer\'s clock)'));
document.body.appendChild(document.createElement('br'));
document.body.appendChild(document.createTextNode('If the clock is inaccurate, remember that the Ice Cream Van moves clockwise around the Cities at one square per minute, and its range is (4,4) to (95,95).'));

/*var ambulanceStart=new Date('July 6, 2007, 9:48:30');
var travel=Math.floor((time-ambulanceStart)/15000);
travel%=91*4;
// moving about.  Start at northeast corner, travel clockwise, checking for journey's end at each turn.
var east=95;
var north=95;
if (travel && travel>91) {
	north=4;
	travel-=91;
}
else if (travel) {
	north-=travel;
	travel=0;
}
if (travel && travel>91) {
	east=4;
	travel-=91;
}
else if (travel) {
	east-=travel;
	travel=0;
}
if (travel && travel>91) {
	north=95;
	travel-=91;
}
else if (travel) {
	north+=travel;
	travel=0;
}
if (travel) {
	east+=travel;
	travel=0;
}

document.body.appendChild(document.createElement('br'));
document.body.appendChild(document.createTextNode('The Ambulance is at '+north+'N '+east+'E, but only if the above clock matches Cities time, and +/- 3 spaces. (it\'s based on your computer\'s clock)'));
*/
</script>
</body>