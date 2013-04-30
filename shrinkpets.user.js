// ==UserScript==
// @name           Shrink Pets
// @namespace      http://kiwu.org/greasemonkey
// @description    Move some parts of the pet display about so it needs less space
// @include        http://cities.totl.net/cgi-bin/game*
// @include        http://carmilla.ecs.soton.ac.uk:8080/cgi-bin/game*
// ==/UserScript==

//KD is getting a "age_and_class_div is null" error.

var elements = xpath('//input[contains(@value, "Feed (")]/ancestor::div[contains(@class,"controls item_")]');

var debug=false;

for (var i=0; i < elements.snapshotLength; i++)
{
    var petNode = elements.snapshotItem(i);

    if (debug)GM_log("petNode: " + petNode.innerHTML);

    var pickupNode = xpathOneNode('.//input[@value="Pick Up"]', petNode);
    if (debug)GM_log("PICK: " + pickupNode);

    if (pickupNode == null)
    {
        if (debug) GM_log("No Pick Up node, try Stop Riding");
        pickupNode = xpathOneNode('.//input[@value="Stop Riding"]', petNode);
        if (debug) GM_log("PICK: " + pickupNode);

    }

    if (pickupNode != null)
    {
	// delete the horizontal row
        pickupNode.previousSibling.style.display = "none";
		while (pickupNode.nextSibling){
			pickupNode.parentNode.removeChild(pickupNode.nextSibling);
		}

		var feedNode=xpathOneNode('.//div[select]',petNode);
		if (debug) GM_log('feednode:'+feedNode.innerHTML);
        feedNode.appendChild(pickupNode);

        // that is one WEIRD form for the DOM tree on tables here.  I have no idea why there's an extra, illusory tbody node.
        var tableRow = xpathOneNode('.//table/tbody/tr', petNode);	

        // Find the "N old and poor" div, parse the line, and append age and classification to table
        var age_and_class_div = xpathOneNode('.//div[contains(., " old")]', petNode);
        if (debug) GM_log("AGE AND CLASS" + age_and_class_div);

        var age_and_class = age_and_class_div.innerHTML;
        if (debug) GM_log("AGE AND CLASS" + age_and_class);
		
		var ageClassRE=/^.* is (starving|very hungry|hungry|nearly full|full|stuffed)!? ?\(?(\d+%)?\)?, (\d+ [a-z]+) old,? ?(\d+% evil)?/;
        match = ageClassRE.exec(age_and_class);
        if (debug) GM_log("MATCH: " + match);
        match[3]=match[3].replace('weeks','w');
        match[3]=match[3].replace('days','d');
        match[3]=match[3].replace('hours','h');
        match[3]=match[3].replace('minutes','m');
        match[3]=match[3].replace('seconds','s');
    
        var td = document.createElement("td");
        var hunger=match[2]?match[2]:match[1];
        var hungerText = document.createTextNode("hunger: "+hunger);
        var ageText = document.createTextNode("age: " + match[3]);
        var evilText=document.createTextNode(match[4]);
        td.appendChild(hungerText);
        tableRow.appendChild(td);
        td = document.createElement("td");
        td.appendChild(ageText);
        tableRow.appendChild(td);
        if (match[4]) {
			td = document.createElement("td");
			td.appendChild(evilText);
			tableRow.appendChild(td);
		}
        age_and_class_div.style.display = "none";
    }
    else if (debug) GM_log("Wha?  No pickup node!");
}

// just a quick way of getting our favorite arguments.
function xpath(path,contextNode) {
	if (typeof contextNode=='undefined') contextNode=document;
	return document.evaluate(path,contextNode,null, XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE, null);
}

function xpathOneNode(path, contextNode){
	if (typeof contextNode=='undefined') contextNode=document;
	return document.evaluate(path, contextNode, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
}
