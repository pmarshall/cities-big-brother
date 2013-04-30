<html><head><title>Tarot Tool</title></head>
<body onload="checkParam()">
<?php
include ('siteHeader.php');
include ('zzStatCounter.php');
?>
<h2>Tarot Tool</h2><br>
A little widget brought to you by PotatoEngineer, a.k.a. Cor.<p>
Enter your tarot deck readings here:<br>
<textarea id="readings"  rows="10" cols="50"></textarea>
<input type="button" value="Compute!" onclick="compute()" /><p>

There are now <i>two</i> ways to enter data: either in the form "X Y direction distance" (where direction is the abbreviation, such as ENE for East North East), or by copy-pasting your coordinates and the tarot message on the same line (such as "2E 3S The cards indicate that the nearest one is about 5 squares away to the North.").<p>
So if you stood at 15E 32S, and found a Sand Worm 20 North North East of you, you would enter<br>
15 -32 NNE 20 (or "15E 32S The cards indicate that the nearest one is about 20 squares away to the North North East").<p>
If you got closer, and taroted again, you would add the next reading to a new line (say, 15 -22 NE 11).  Two readings are usually enough to narrow down the location to 1 or 2 tiles if the direction is different.  So if your first reading says NNE, and the second one says ENE, you can probably pinpoint the tile, even if you're over 50 tiles away.  Such is the power of the Tarot Deck!<p>

<p>Comment lines, should you need them, start with a #
<script type="text/javascript">
function checkParam() {
	var params=window.location.href.split('?');
	if (params.length>1) {
		params=params[1].split('&');
		var paramObj={};
		for (var i=0;i<params.length;i++) {
			var varPair=params[i].split('=');
			paramObj[varPair[0]]=varPair[1];
		}
		var text='';
		for (var i in paramObj) {
			if (i.substr(0,7)=='reading') {
				text+=decodeURI(paramObj[i])+"\n";
			}
		}
		if (text!='') {
			document.getElementById('readings').value=text;
			compute();
		}
	}
}

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
		
	var locations=new Array();
	var firstParse=true;
	var latitudeRE=/(\d+)(N|S)/;
	var longitudeRE=/(\d+)(E|W)/;
	var distanceRE=/The cards indicate that the nearest one is about (\d+) squares away to the (here|North|South|East|West) ?(North|East|South|West)? ?(North|East|South|West)?/
	for (var i=0;i<lines.length;i++) {
		if ('#'==lines[i].substr(0,1)) continue;
		try {
			var tokens=lines[i].split(' ');
			var x = parseInt(tokens[0]);
			var y=parseInt(tokens[1]);
			var distance=parseInt(tokens[3]);
			var direction=tokens[2].toUpperCase();
			
			if (tokens.length!=4) {
				//textbox.value+='token length wrong:'+tokens.length;
				throw new Exception();
			}
			//else document.body.appendChild(document.createTextNode('reading:'+x+' '+y+' '+direction+' '+distance));
		}
		catch(ex) {
			try {
				// something went wrong with the above parsing, so maybe it's a copy-paste from the game.
				var y=latitudeRE.exec(lines[i]);
				var x=longitudeRE.exec(lines[i]);
				var distance=distanceRE.exec(lines[i]);
				if (x && y && distance && x[1] && x[2] && y[1] && y[2] && distance[1] && distance[2]) {
					y=parseInt(y[1])*(y[2]=='S'?-1:1);
					x=parseInt(x[1])*(x[2]=='W'?-1:1);
					direction=distance[2].substr(0,1)+(distance[3]?distance[3].substr(0,1):'')+(distance[4]?distance[4].substr(0,1):'');
					distance=parseInt(distance[1]);
				}
			}
			// generic parsing failure: ignore that line.
			catch (ex) {continue;}
			// it parsed, but somehow glitched.  Continue.
			if (typeof x != 'number' || typeof y != 'number' || typeof distance != 'number' || typeof direction !='string') {
				//textbox.value+="Generic glitch. x:"+x+" y:"+y+' distance:'+distance+' direction:'+direction;
				continue;
			}
		}
		var startArc;
		var badData=false;
		switch (direction) {
			case 'E':
				startArc=Math.PI*-1.0/16;
				break;
			case 'ENE':
				startArc=Math.PI*1.0/16;
				break;
			case 'NE':
				startArc=Math.PI*3.0/16;
				break;
			case 'NNE':
				startArc=Math.PI*5.0/16;
				break;
			case 'N':
				startArc=Math.PI*7.0/16;
				break;
			case 'NNW':
				startArc=Math.PI*9.0/16;
				break;
			case 'NW':
				startArc=Math.PI*11.0/16;
				break;
			case 'WNW':
				startArc=Math.PI*13.0/16;
				break;
			case 'W':
				startArc=Math.PI*15.0/16;
				break;
			case 'WSW':
				startArc=Math.PI*17.0/16;
				break;
			case 'SW':
				startArc=Math.PI*19.0/16;
				break;
			case 'SSW':
				startArc=Math.PI*21.0/16;
				break;
			case 'S':
				startArc=Math.PI*23.0/16;
				break;
			case 'SSE':
				startArc=Math.PI*25.0/16;
				break;
			case 'SE':
				startArc=Math.PI*27.0/16;
				break;
			case 'ESE':
				startArc=Math.PI*29.0/16;
				break;
			default:
				badData=true;
		}
		if (badData) continue;
		
		var tempLocations=new Array();
		// we'll test the arc at distance*2+1 points, so the interval between points is arc/(distance*2), because we want both the first point and the last point.
		var interval=Math.PI*1/8/(distance*2);
		// generate points along the arc, truncate the decimal, and if it's a point not already in our temp array, push it.  We're rounding because the REAL points may be further out or closer in, so we'll just get to the closest points along our (relatively dense) arc.
		for (var j=0;j<distance*2+1;j++) {
			var point=makeHighPoint(x,y,startArc+j*interval, distance);
			
			var duplicate=false;
			for (var k=0;k<tempLocations.length;k++) {
				if (point[0]==tempLocations[k][0] && point[1]==tempLocations[k][1]) {
					duplicate=true;
					break;
				}
			}
			if (!duplicate) {
				tempLocations.push(point);
		//document.body.appendChild(document.createTextNode('new point:'+point[0]+' '+point[1]));
			}

			// now do it again, but one unit further out, and always round down.
			var point=makeLowPoint(x,y,startArc+j*interval, distance+1);
			
			var duplicate=false;
			for (var k=0;k<tempLocations.length;k++) {
				if (point[0]==tempLocations[k][0] && point[1]==tempLocations[k][1]) {
					duplicate=true;
					break;
				}
			}
			if (!duplicate) {
				tempLocations.push(point);
		//document.body.appendChild(document.createTextNode('new point:'+point[0]+' '+point[1]));
			}
			// And one last time, rounding to the closest number on a line between the other two.
			var point=makeMidPoint(x,y,startArc+j*interval, distance+0.5);
			
			var duplicate=false;
			for (var k=0;k<tempLocations.length;k++) {
				if (point[0]==tempLocations[k][0] && point[1]==tempLocations[k][1]) {
					duplicate=true;
					break;
				}
			}
			if (!duplicate) {
				tempLocations.push(point);
		//document.body.appendChild(document.createTextNode('new point:'+point[0]+' '+point[1]));
			}
		}
		// now for dummy-checking those points we generated.  Everything was rounded, so it's possible that the new points are not, in fact, at the proper distance.  I assume that all distances are truncated.
		var offMap=false;
		for (var j=0;j<tempLocations.length;j++) {
			var distX=tempLocations[j][0]-x;
			var distY=tempLocations[j][1]-y;
			var testDistance=Math.floor(Math.sqrt(distX*distX+distY*distY));
			if (testDistance!=distance) {
		//document.body.appendChild(document.createTextNode('failed via distance:'+tempLocations[j][0]+' '+tempLocations[j][1]+'('+testDistance+')'));
				tempLocations.splice(j,1);
				j--;
				continue;
			}
			// also test if it's the proper direction.
			var arctan=Math.atan2(distY, distX);
			if (arctan<Math.PI*-1.0/16) arctan+=2.0*Math.PI;
			//if (distX<0) arctan+=Math.PI;
		//document.body.appendChild(document.createTextNode('arctan:'+arctan+' startArc:'+startArc+' endArc:'+(startArc+1.0/8.0*Math.PI)));
			
			if (!(arctan>startArc && arctan<startArc+1.0/8.0*Math.PI)) {
		//document.body.appendChild(document.createTextNode('failed via angle:'+tempLocations[j][0]+' '+tempLocations[j][1]));
				tempLocations.splice(j,1);
				j--;
				continue;
			}
			// finally, double-check that it's actually on the map.  The left border is 0 (except for the thieves' forest, where it's -25), the right border is 100, the bottom border is -370, and the top border is 200.
			if (tempLocations[j][0] < -25 || (tempLocations[j][0] <0 && !(tempLocations[j][1]>=0 && tempLocations[j][1]<=100)) || tempLocations[j][1]>195 || tempLocations[j][1]<-370 || tempLocations[j][0]>100) {
				tempLocations.splice(j,1);
				j--;
				offMap=true;
				continue;
			}
		}
		
		// the first time through, we're ADDING locations to our array.
		if (firstParse) {
			for (var j=0;j<tempLocations.length;j++) {
				locations.push(tempLocations[j]);
			}
		}
		// in each subsequent run through, we're comparing against existing locations, and removing them from the array.  Fear my not-at-all-optimized O(n^2) algorithm!
		else {
			for (var j=0;j<locations.length;j++) {
				var found=false;
				for (var k=0;k<tempLocations.length;k++) {
					if (tempLocations[k][0]==locations[j][0] && tempLocations[k][1]==locations[j][1]) {
						found=true;
		//document.body.appendChild(document.createTextNode('FOUND ONE!: '+locations[j][0]+'  '+locations[j][1]));
					}
				}
				if (!found) {
					locations.splice(j,1);
					j--;
				}
			}
		}
		// if we got here and added some locations, we're done with the first parse, so we'll be winnowing from here.
		if (locations.length) firstParse=false;
	}
	textbox.value+="\nPossible locations for the monster:\n";
	for (i=0;i<locations.length;i++) {
		var xAxis, yAxis;
		if (locations[i][0]<0) {
			xAxis='W ';
			locations[i][0]*=-1;
		}
		else {
			xAxis='E ';
		}
		if (locations[i][1]<0) {
			yAxis='S ';
			locations[i][1]*=-1;
		}
		else {
			yAxis='N ';
		}
		textbox.value+=locations[i][0]+xAxis+locations[i][1]+yAxis+"\n";
	}
	if (offMap) {
		textbox.value+="Some possible locations were off the map, and are not displayed.\n";
	}
}

function makeHighPoint(xx, yy, dir, dist) {
	var xPos=xx+dist*Math.cos(dir);
	if (dir>Math.PI/2.0 && dir <Math.PI*3.0/2.0) xPos=Math.floor(xPos);
	else xPos=Math.floor(xPos);
	
	var yPos=yy+dist*Math.sin(dir);
	if (dir > 0 && dir <Math.PI) yPos=Math.ceil(yPos);
	else yPos=Math.floor(yPos);
	
	return new Array(xPos, yPos);
}
function makeLowPoint(xx, yy, dir, dist) {
	var xPos=xx+dist*Math.cos(dir);
	if (dir>Math.PI/2.0 && dir <Math.PI*3.0/2.0) xPos=Math.ceil(xPos);
	else xPos=Math.ceil(xPos);
	
	var yPos=yy+dist*Math.sin(dir);
	if (dir > 0 && dir <Math.PI) yPos=Math.floor(yPos);
	else yPos=Math.ceil(yPos);
	
	return new Array(xPos, yPos);
}
function makeMidPoint(xx, yy, dir, dist) {
	var xPos=Math.round(xx+dist*Math.cos(dir));
	
	var yPos=Math.round(yy+dist*Math.sin(dir));
	
	return new Array(xPos, yPos);
}
</script>
</body></html>