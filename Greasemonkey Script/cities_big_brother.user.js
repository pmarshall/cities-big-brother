//======================================================================
// ==UserScript==
// @name          Cities Big Brother
// @namespace     http://potatoengineer.110mb.com/
// @description	  Upload data to Cities statistics collector
// @include       http://cities.totl.net/cgi-bin/game*
// @include		  http://carmilla.ecs.soton.ac.uk:8080/cgi-bin/game*
// @require	http://potatoengineer.110mb.com/cities_potatolibrary.user.js
// ==/UserScript==
//
// Copyright 2007 Paul Marshall
// You may freely use, edit, copy, modify, or excerpt this script for any non-commercial purpose.
// Some bits stolen from Darksatanic's automapper script (location detection), and from Tard's KoL framework (auto-updates).  Both 
// of these sections have been further tweaked.
//
// Requires greasemonkey 0.6.4+ and firefox 1.5+
//
//TODO: trawler.  Track by location, time, and alignment?. act_equip_Trawler_seafish
//
// Version 3.84: PotatoLibrary update (items list, can't put console in console.)
//
// Version 3.83: Fixed Wand-vs-mirror-monster attack recognition.
//			A PotatoLibrary update at the same time should exclude dead pets from equipment-breakage.
//
// Version 3.82: Rainbow Wands now report breakage properly, cleaned out a few debug statements.
//
// Version 3.81: tweak to make custom weapon detection work again
//
// Version 3.8: Newfangled interface!
//
// Version 3.75: Tank break message added.
//
// Version 3.74: Added Toxicology herbs (and updated parseTerrain to single out graveyards)
//				Added reactive armor
//
// Version 3.73: Gorgan Heads hitting things is ACTUALLY fixed.  (Or rather, now it reports as a weapon.)
//				Fixed terrain reporting for murky lakes.
//
// Version 3.72: Gorgan Heads hitting things is properly recorded.  Also bugfixed fishing.
//
// Version 3.71: Fishing reporting updated to handle Murky Lakes.
//
// Version 3.7: Golf Clubs no longer report breakage on Vorder Men.
//				Also, PotatoConsole apparently broke the "control box appears on out of AP screen" bit.  
//					Nobody complained, but it's fixed anyway.
//				Added ambulance search results.
//				Possibly fixed Iron Bling breakage.  I was looking before the damage message, not afterward, and I got 0
//					breaks out of 80,000.  So now I'm looking AFTER the damage message.
//				Updated BB so that each action will only be used once.  This was occasionally a problem when you'd walk
//					around, and then update your inventory or something several times; the "didn't break on moving" 
//					message would be sent on each refresh.
//				Added vorpal blade snicker-snack rate.
//
// Version 3.6: PotatoConsole update: you can leave the console open between page loads if you unset the checkbox.
//
// Version 3.5: Added to PotatoConsole.
//
// Version 3.4: Harvest Song checking also checks for the "night" message.
//				Tweaked Treasure Chest reporting a bit; it'll work if you open several chests in succession now.
//				Thieves' Forest separated from Forest for foraging reports.
//				Added Northern Rock reporting.
//
// Version 3.31: Made checking for updates the FIRST thing to happen, so I can get out of script-breaking more easily.
//
// Version 3.3: Fixed alignment detection, plus a general script-self-destruct when you're a weird alignment.
//				Let other alignments into the farming data, and included an entry for "Nothing grows".
//				More data: Rainbow Wand alignment.
//
// Version 3.2: Alignment detection should now cover the new, weird alignments, whatever they end up being.
//				Your alignment is now stored, if the script can't find it, but I can't bring myself to actually use that
//					stored value, because the script can't tell if your alignment changes while you're hallucinating or
//					disguised.
//				Tweaked the way that the script ignores spoken lines; some of them could be NPC lines, which would
//					indicate that you've spent AP.
//				Fixed accuracy-reporting for silver dragons.
//				More data: Harvest Song odds of a falling star.
//
// Version 3.14: Updated to be compatible with Item Selector.  Why not just put item selector BELOW the inventory...?
//
// Version 3.13: Treasure Chests should report data again.  It got lost in the 3.0 upgrade.
//				"Enter your key" reminder toned down slightly.
//
// Version 3.12: Fixed the mislabeled "view statistics" button.  When will I stop with the copy-paste programming?
//
// Version 3.11: Fixed iron blings not reporting breaks, I think.
//				Some cleanup in collecting the weapon data.  The script now never checks the held items on the fly.
//				Rainbow Sword renamed to Rainbow Blade.  Apparently, I can't read.  +)
//
// Version 3.1: Added re-listing as a knight to Gold Bling possible-break messages.
//				Player theft action, harvey's farm digging action, bowling action, and flying healer action found.
//				More data: added astral plain odds.
//
// Version 3.06: Fixed flashgun breaking.
//				Also one more Einherjar.  Hopefully the last one.  In theory, with the new button-identifying method I'm
//					using, I could eliminate Einherjars entirely, but at this point, I have 99% of them (at least!) 
//					identified, so why get fussy?
//				Fixed another parsing error for custom weapons.  Makes me wonder how it ever reported data in the first 
//					place, really.... (gotta love WTF bugs!)
//
// Version 3.05: Fixed bascinet/sallet detection
//				A cheerful, friendly, whack-upside-the-head reminder for people whose data-reporting keys are null.
//				Survey vessels' special break message detected.
//				Red Hat Fedora removed from equipment-breakage.
//
// Version 3.04: Umlautted vowels converted to normal vowels.  Also the � in M��se.
//				Also fixed it so ICE DEMON hits are properly noted.
//				Leeches report their internal name on breaking (Leech1, Leech2, etc.), not "Leech"
//
// Version 3.03: Bugfix.  It turns out that not everyone gets the weapon statblock; non-armorers only get the text.
//					So if they leave the default statblock, it'll detect fine, but the lifespan-detecting regex is 
//					somewhat flexible, so as long as there's a number somewhere after "lifespan" or "durability," and
//					before some kind of semicolon, comma, or period, it'll detect it.
//
// Version 3.02: Bugfix.  Stupid Javascript and its funky scope rules.  Everything but monsters was broken, more or less,
//					if you had a special bling.
//				Also found the action name for gathering sand on beaches.
//
// Version 3.01: Buxfix; I accidentally broke the "display BB menu on out-of-AP screen" bit.
//
// Version 3.0: Fixed the case where a Trained Nunchuck User can kill multiple monsters, and the drops get double-listed.
//				Fixed Mountain identification in Oz.  (what's an offset of 50 between friends?)
//				Leech "breaking" now works.
//				Reasonably-major upgrade to the data-detection: the game now spams little event listeners all over the
//					interface (much like Button Masher) that record the name of the button you clicked, so there's less
//					guesswork (and less CPU time) taken to identify what action you took.  Since it runs faster now, I've
//					eliminated the brief delay before BB starts looking for stuff.
//				More data: As a result of the data-detection upgrade, there's now tracking for mining the SE Tunnels!
//				More Data: Flash guns breaking.
//
// Version 2.96: Being thorough about removing Trolls from equipment-breaking.
//				Huge Reptiles lumped (what?  It's not a Huge Lizard?)
//
// Version 2.95: Wands of Anger, Midas Wands, and alignment now report attacks (a buggy fix now done right, mostly)
//				Huge Lizards lumped
//				"Unknown Oz Mine or Outcrop" shortened to "Unknown Oz Mine", to fit in the DB column.  
//					(I'm too lazy to fix the DB)
//				Weapon breakage no longer reports clubs vs. seals.
//				Removed Trolls, Rock Trolls, and Adult Trolls from equipment breakage, in an attempt to pre-empt them.
//				Crossroads identified as Bad Lands.
//				Re-fixed an earlier bugfix for identifying multi-monsters.
//				Gorgan Head manually classified as a weapon. (again.)
//				Minor tweak to use old saved terrain slightly less when mining.
//				Delay before checking data reduced to 50 milliseconds, from 250 milliseconds.
//
// Version 2.94: Snowmen are now identified properly.  (tweak: and not broken, either!)
//				Tweaked getAlignment() to return "Hallucinating" if you're hallucinating.  Shouldn't be a big effect,
//					because alignment-sensitive data isn't sent if you're hallucinating.
//				Neutral alignment is detected properly.
//
// Version 2.93: Added the Master Armorer armors to the armor-breaking, and removed them from move-breaking.
//
// Version 2.92: Tweaked terrain identification to work from the wiki-link first, and the screen text second.
//				Gorgan Heads always sort as weapons
//				Error in finding n0rx and numbered player names fixed.  (Stupid 13375|*34|<....)  (Also Vorder Men.)
//
// Version 2.91: Custom weapons' break-reporting tweaked (it would ignore it the first time)
//				Added Winged Horsies & Baby Trolls to non-tracked list
//				Added Great Plain and Grounds to Civilized Green, added Fish Pond to Lake
//
// Version 2.9: Custom weapons get their own break-reporting.  No more cluttering the weapons tables, plus information
//					about break rates!
//
// Version 2.83: ACTUALLY fixed collapsing mine reporting.  (I hope!)
//
// Version 2.82: Adjusted summon stone reporting to report the summoner, so we can eliminate the auto-break (when you
// 					summon one of your alts) from data-reporting.
//
// Version 2.81: A mine that collapses will actually report as such, instead of crashing the script.
//
// Version 2.8: More data: summon stone breaking
//				More Data: mines collapsing with use
//				More! Data!: players stealing from each other
//				MORE! DATA!: wormhole break rates
//				Added Sharp Swords rusting into Rusty Swords
//				Daemonic weapons and Angry bows no longer report as non-weapons when upgrading
//				Roof Garden is now lumped with Jungle
//				Fixed Wilderness/Plain detection for Hills
// 
// Version 2.78: Added Obsessions, Dung Beetles, and the various Horsies to equipment-not-to-be-tracked.
//				Monster drops in the Gauntlet are no longer tracked.
//
// Version 2.77: Large and small thieves identified the RIGHT way around.
// 				Searching storerooms now avoids false positives from getting items from bags or crafting.
//				Equipment breakage reporting now detects the equipment screen AFTER sending data, so people who view 
//					their equipment in the side-pane no longer send bad data.
//				Bebe goggles no longer tracked for breakage.
//
// Version 2.76: Added debug mode, selectable under the "show key" button.  
//					Maybe I should change that to "config" sometime.
//				De-pluralized "sheaves of wheat/oats", not that it's likely to come up any time soon.
//				Tweaked equipment identification.  (Should have a few less errors now)
//
// Version 2.75: Feral Cats are now a special case (dammit): lumped for monster drops, unlumped for accuracy.
//				Fixed silver weapon vs. dragon reporting.  
//					(it would report twice if you hit, and had more than one silver weapon.)
//				Peeves excluded from equipment breakage.
//
// Version 2.74: Stripped execution time comments.
//				Feral Cats and Earth Worms lumped as their basic type, rather than by display name.
//				Fixed terrain identification for farming/foraging.
//				Tweaked glass-sword-shattering detection.
//
// Version 2.73: Tiny little fix to periods in monster names.
//
// Version 2.72: Fixed mimic naming on members of the military.
//				Hates no longer tracked for items wearing out.
//				Placement of periods in monster names corrected (again).
//				Added enter, exit, up, and down to possible movement directions.
//
// Version 2.71: Fixed nightly spell gains.
//
// Version 2.7: More data: wizard nightly spell gains
//				Stripped Glowing from worn items (AGAIN with the Glowing!)
//				BB config panel now displays on "Out of AP" page
//				Gold, Healing, and Major Healing blings now report on EVERY AP spent  (or at least 98% of 'em).
//				Iron blings now report wear & tear, and furthermore don't clutter the weapon-break page.
//
// Version 2.61: Renamed CBB to BB in the config box.
//				Poisoning now ignores "you are poisoned: -1HP" messages
//				Iron blings no longer track for movement-breaking, and glowing is stripped (DAMN YOU, "Glowing"!) from 
//					move-break items.
//
// Version 2.6: More data: how often does a cute seal cause kudos loss?
//				MORE DATA: how often do you miss a full-size dragon with a silver weapon?
//				MORE! DATA!: Items you wear, wear out.  How often?  (doesn't report armor & pets)
//				Bugfixes: 3 more Einherjars, and a wee bit of optimization for non-Einherjars.  
//					I must be nearly done with these....right?
//				Tweaked game include to be the full path, rather than all of cities.totl.net.
//				Thieves are (usually) identified properly, for both monster drops and thief action reporting. 
//					(About 1 in 7 thieves is simply called "Thief", for both types.)
//				Harvey's farm digging now works if you fall into either a cell or a dungeon tile, because  I'm not sure
//					what happens.
//				Weapon-breakage data now reports whether you used a weapon or a non-weapon.  
//					(the breakage page was getting kinda cluttered.)
//				Mirror Monsters and Mimics now compare against your period-stripped name.  
//					(the game strips periods for these.)
//
// Version 2.52: Added Loaves of Bread as a special-case de-pluralization, added terrain memory for beanstalk statistics.
//
// Version 2.51: Fixed thief stabby recognition.
//
// Version 2.5: Lumped Steaming Pools in with Lake on terrain, lumped Great Desert with Desert,
//					 lumped Imemorial Park with Plains, fixed spelling on Field (it ain't plural).
//				Tweaked de-pluralization: only knives actually convert -ves to -fe.  It was screwing with olives.
//				More de-pluralization: cards now get checked instead of the -s ending, not in addition to it.
//				Confused monsters are now generally recognized, rather than specifically remembered for puzzleboxes.
//					Flash guns should work fine now.
//				More data: Frequency of falling through when digging down in harvey farm, 
//					how often critters poison you when they hit, and how often thieves attack, steal, or feint-and-attack.
//
// Version 2.42: Added 5 new Einherjars.  Presumably, I'm going to be adding these for a good, long while. 
//					(The old solution would work---most of the time---but could be prone to mis-identifying some 
//					monsters as Einherjars in some cases.)
//				Bugfixed terrain identification. Each location has an extra, nested DIV.  
//					Presumably, this is part of the party-bug fix, or something.
//
// Version 2.41: Fixed a bug that reported weapons breaking every time a piece of armor broke.
//
// Version 2.4: Added Fairy Nuff: do you get more grams, or more pints?
//
// Version 2.3: Added sand-collecting on the beach and badlands.
//				Bugfix for identifying the Kansas storeroom.
//
// Version 2.21: Hills identification improved
//
// Version 2.2: New data reporting: whip-snatching odds, player deaths by monster type.
//				Bugfixes: Reports Head Hunter "hits".
//					Tweaked armor recognition (should report Mail On Sunday correctly now)
//					Lumped evil oozes
//					Tweaked statistics link to open a new window
//					Weapons baned to a multi-named monster should report breakage properly
//
// Version 2.1: Cleaned up Einherjar identification
//				Added ballroom reporting
//
// Version 2.02: Bugfix for screwing up monster naming in 2.01
//
// Version 2.01: Rainbow swords break properly, Pirates now identified.
//
// Version 2.0: Added bowling
//				Magic Mines no longer report mining data; we know what they do.
//				Baned weapons now get their own reporting tables, as do baned and blue-pilled weapons.
//
// Version 1.92: Fixed "attacks=0" bug on weapon breakage, added possible sword break message.
//
// Version 1.91: Tweaked stripping spoken lines, added Fields to Civilized Green and Heath to Wilderness Green.
//				Stripped "Glowing" one more time.
//				Weapon (and armor) breakage now tracks blue-pill status.
//				Foraging now always reports the IDed herb, whether you can ID them or not.
//
// Version 1.9: Added doom wands!  ...for what it's worth.
//				Also bugfixed terrains a bit.
//
// Version 1.84: Bugfix for armor breakage.
//
// Version 1.83: Half of 1.82 should be here, but I forgot to increment the version, so some people could theoretically
// 					have non-updating versions of 1.82.  
//				Removed spiky ninja throwing stars from drops.
//				Stripped a little bit of extra insistence from the "bad key" reminder.
//
// Version 1.82: Glass sword and phaser break messages fixed, Einherjars lumped (a bit roughly), weapon breakage corrected
// 					to only report hits, flying healers fixed.
//
// Version 1.81: More cleanup on items.  "Glowing" stripped more aggressively, "dose" and "measure" stripped properly,
//					plurals handled better.
//
// Version 1.8: Added ruin-searching.  Also works for kansas, though I couldn't test tunnel storerooms.
//				Also cleaned up a few regexes to prevent some possible errors.
//
// Version 1.74: Bugfix.  Now it checks whether a line is an item BEFORE it interrogates the item.
//
// Version 1.73: Redirected the update-checker to look at the CORRECT file this time.
//
// Version 1.72: Bugfix for item drops when counting items.  "True" and "false" are not numbers.  Argh.
//
// Version 1.71: bugfix for multiple item drops, which was an attempted fix for finding 20 D20s.
//				Bugfix for a HORRENDOUS misreporting error that would report items once per output line, removing one item
//					each time.
//
// Version 1.7: Added treasure chests.
//
// Version 1.61: living tars "absorb" things, too, so now they're excluded from armor breakage.
//				Corpses excluded from monster drops.
//
// Version 1.6: ACTUALLY fixed cornucopia drinks.  Stupid typo.  Also fixed dice to display the correct number.
//				More data reporting!  Daemonic weapon melt/upgrade odds, this time.
//
// Version 1.52: finally decided to strip Arrows, BBs, Cannon Balls, and Quarrels from dropped items.  
//					Still torn on harpoons.  Also stripped "Glowing" when it exists.
//
// Version 1.51: some minor security tweaks to prevent potential bad data from speaking players
//				Cornucopia drinks possibly fixed.
//				Updating made much more vocal (it only checks once per hour, but it'll bug you EVERY page once 
//					it does, if you're underversioned)
//
// Version 1.5: one last double-posting bug, tweaked update-checks to only run once per hour IF we actually found the
//						update site.  (So users that click quickly won't skip their updates.)
//				More data reporting: armor damage absorbtion and breakage.  (and fixed weapon breakage reporting when
//						armor broke)
//
// Version 1.4: Fish no longer send weapon-break stats when attacking parrots
//				Version-checking now goes to a PHP page that doesn't cache.  (you'll get updates immediately, not
//					after your web cache eventually expires)
//				Puzzleboxes no longer pollute the database with monster misses
//				More data collected: angry bow upgrades
//
// Version 1.35: monster name tweaks: Brute Squads lumped into "Brute", Lepr*c*ns fixed, Pheonixes lumped, Hydras named.
//
// Version 1.34: Bugfix: if, for some reason, you kill a monster with your last AP, and somebody gives you
//					fifteen Vorpal Swords, they'll no longer show up on the monster drop when next you log in.
//				Also, non-weapons should show up properly on weapon breakage charts, but sheets of paper vs. 
//					Rock Trolls will now not show up, and glass swords should "break" properly now.
//				Also also, removed the double-submission bug.
//				@version removed from header, because it might be interfering with holding onto old keys.  This
//					will probably delete old keys again if it's a problem, but at least it won't do it later.
//				Also also also, the script is rather insistent if you don't have a valid key, though it complains
//					only once per data submission.
//				Many alsos, weapon breakage now reports the number of attacks made.
//
// Version 1.33: Bugfix for infinite loop on monster drop, change in multiple-messages format, tribbles no longer 
//				"grow" from magic breans.
//
// Version 1.3: Updates are now checked once per hour
//				Pets are excluded from monster-accuracy reporting
//				More data collected: milking cows, cornucopia, weapon breakage, jungle/forest fruit/acorn hunting
//				Sends multiple messages for attack-related data
//
// Version 1.23: Bugfix for registering multiple-digit mob monsters, parrots are now all one category
//
// Version 1.22: Bugfix for complete failure.  It works now, and I've even figured out how to make 
//				setTimeout work for functions now.
//
// Version 1.21: All dropped items are now double-checked to see if they're items.
//				Hallucinations are detected, and the script is skipped if they're found.
//				Sends flying healer data.
//				Sends username and version number with every post, so contributors can be credited.
//				Replaced single-quotes with double-quotes so CDs should be registered correctly now.
//				Auto-notify for outdated versions.
//======================================================================

GM_setValue('scriptVersion',"3.84");


// Step ONE: check for updates.  Updates are reliable, and I never need to change that code.  But everything else can break.  So make sure updates come first, so if something else broke, I can get everyone to upgrade.
checkUpdates('Cities Big Brother','bigbro','cities_big_brother.user.js');



var debug=GM_getValue('debug',false);
if (debug) var configStart=new Date();
var key = GM_getValue("CDC_key");
// Needed globally

var configbtn;
var configDiv;
createInterface();


// the first time this script loads, button_name is undefined. But I count on it being a string later, so it needs to have SOME value.  And I'm too lazy to make that extra check later.
if (GM_getValue('button_name'+getUsername())==undefined) GM_setValue('button_name'+getUsername(),'NO ACTION');

// logging the time it takes to set up the config box.
if (debug) {
	var configEnd = new Date();
	GM_log('setup time:'+(configEnd.getTime()-configStart.getTime()));
}

// halluction detection: only do it once.
// NOTE: you still get accurate messages, for both monster drops and attacks and all else, too.  Funny, that.  But 	monster attacks have to be skipped, because it might be a confused monster, and we just can't tell if it's confused.  On a side note, this creates a false positive for parties, but since we can't tell if you're hallucinating at a party, it's better safe than sorry.
var hallucinating=isHallucinating();

// disguise kit detection: only do it once.
var disguised=isDisguised();

if (debug) GM_log('disguised:'+disguised+'  hallucinating:'+hallucinating);



var dataCount=0;
// This.... is everything.  Collect all data, pack it nicely, ship it to the DB.  Be nice to the user and delay it a quarter-second; I don't want people uninstalling this script because it's delaying page-loads.
checkForData();

spamListeners();	// add listeners to every button, that will record the button name.



function createInterface() {
	var box = makeBox("Big Brother:");
	configDiv = makeConfig();
	var maindiv = makeDisp();

	box.appendChild( maindiv );
	box.appendChild( configDiv );
	
	// put it in the usual position.  If it doesn't exist, we must be on the "out of AP" screen.
	try {
		insertAt(box,GM_getValue('display_location','PotatoConsole'));
	}
	catch (ex) {
		if ($('messages') && /You have run out of action points/.test(document.body.innerHTML)) {
			document.body.insertBefore(box, document.body.firstChild);
		}
	}
	
	configDiv.appendChild(locationSelect());
	
}
function buttonNamer(name) {
	if (debug) GM_log('spammed listener:'+name);
	return function () {
		GM_setValue('button_name'+getUsername(),name);
		GM_setValue('new_button',true); // a flag so that any given action only gets checked ONCE.
		if (GM_getValue('debug',false)) GM_log ('button clicked:'+name);
		// SE tunnels mining.  Look for "Rock" under oldCutTerrain, and then check the current terrain to discover its type.
		var cutDir=/act_cut_(n|e|w|s)/.exec(name);
		if (cutDir) {
			GM_setValue('oldCutTerrain',getTerrain(cutDir[1]));
		}
	}
}

function spamListeners() {
	var buttonxpath ='//input[@type="submit" or @type="image" and @class="button" or @class="moveicon"]';
	//var buttonxpath ='//input[@class="button" or @class="moveicon"]';
	var buttons = xpath(buttonxpath, document);
	
	// Watch it, the code gets kinda hairy here.  The intent is to create an eventListener function for each and every button, with a slightly different interior.  Javascript 1.7 (which comes with Firefox 2.0) would handle this by assigning variables with the "let" keyword, which would create a new instance of the variable for each iteration of the loop.  Unfortunately, Greasemonkey doesn't label its scripts as using 1.7 (which is silly, because greasemonkey only runs on FF, anyway), so we're stuck with the 1.6 method of doing it.  The solution is to create a function that returns a function, and then call that function-creator while assigning the callback function.
	for (var i=0;i<buttons.length;i++) {
		buttons[i].addEventListener('click',
			buttonNamer(buttons[i].name)	// this returns a function that does what we want!
			,true);
	}
}


function checkForData() {
	if (debug) var startTime = new Date();
	var action;
	if (GM_getValue('new_button',false)) {
		action=GM_getValue('button_name'+getUsername());
		GM_setValue('new_button',false);
	}
	else {
		action='NO ACTION';
	}
	if (debug) GM_log('action:'+action);
	
	
	if (debug) {
		GM_log('old held item:'+GM_getValue('oldHeldItem')+'%%');
		GM_log('old is weapon:'+GM_getValue('oldIsWeapon'));
		GM_log('is custom weapon:'+GM_getValue('holdingCustomWeapon'));
		GM_log('customWeaponLifespan:'+GM_getValue('customWeaponLifespan'));
		GM_log('customWeaponMaterial:'+GM_getValue('customWeaponMaterial'));
		GM_log('oldTerrain:'+GM_getValue('oldTerrain'));
		GM_log('oldRawTerrain:'+GM_getValue('oldRawTerrain'));
	}
	
	//GM_log(hallucinating);

	

	//GM_log('enter checkForData()');
	//GM_log(messages);
	var messages=$('messages').innerHTML;
	var lines;
	if (isNewfangled()) lines=messages.split("<br>");
	else lines=messages.split("\n");
	var args='';
	
	// strip out the "you say" "* says" lines.  The more complicated bit is trying to specifically rule out characters speaking, rather than NPCs; therefore, the title is required.  There'll still be some non-titled people speaking, but most of it should be okay now.
	var sayRegEx=/^You (say|sing|shout|roar) |^((Pvt\. |Cpl\. |Sgt\. )?(Duke |Earl |Baron |Knight |Shaman |Apprentice |Wizard |Smith |Armourer |Master Armourer |Great Lord ))[^ ]{1,15} ?[^ ]{0,15}( MD)? (says|sings|shouts|roars) '/;
	for (i=0;i<lines.length;i++) {
		if (sayRegEx.test(lines[i])) {
			lines.splice(i,1);
			i--;
		}
	}
	var giftRegEx=/ gives you /;
	if (action.substr(0,9)=='act_fight' || (action=='act_item_use' && GM_getValue('oldHeldItemCode')=='Treasure')) {
		// get the monster-processing in ONE place, so I have ONE place to fix.
		var monsterName='';
		var isAMonsterRegEx=/^You attack/;
		var slayRegEx=/^You slay /;
		var itsDeadJim=false;
		var parrotRegEx=/^The parrot takes your /;
		var monsterAttackRegEx=/( causes| misses)|^The Head Hunter locks the target\.|^The Head Hunter baits the line\.|^The Head Hunter slowly spreads the net\.|^The Head Hunter catches|^The demon breathes cold upon you\./;
		var missRegEx=/ misses you\.$/;
		var illusionRegEx=/^It was just an illusion!$/;
		var monsterAttacks=0;
		var monsterHits=0;
		var weapon='';
		var isWeapon=false;
		var weaponBreakRegEx=/^Your .+ has worn out\.|^Your glass sword shatters!|^Your sword seems more probable\.|^Your rainbow blade is gone|^Out of power\.|^Your sword gets rusty\.|^The leech is sated!/;
		var possibleBreakRegEx=/^Your sword seems more probable\./;
		var rainbowBreakRegEx=/^Your rainbow blade is gone/;
		var shatterRegEx=/^Your glass sword shatters!/;
		var phaserRegEx=/^Out of power\.$/;
		var rustRegEx=/^Your sword gets rusty\./;
		var leechRegEx=/^The leech is sated!/;
		var broken=false;
		var baneWeapon=false;
		var baneWeaponRegEx=/[^(]+ \(([^)]+) bane\) \(/;
		var playerAttacks=0;
		var angryUpgradeRegEx=/^Your weapon gets more ticked off\.\.\./;
		var absorbRegEx=/ absorbs \d+/;
		var daemonicRegEx=/^The .+? slurps up the life blood\.\.\./;
		var chestRegEx=/^You open the chest\.\.\./;
		var playerHitRegEx=/^You cause \d+ |^The .+ gets angry|^The .+ is magically aligned |^You turn the .+ to gold.|^You death ray the|^Your Vorpal Blade goes Snicker Snack.|^You show the head to the |^The ray bounces\./;
		var bluePillState='noPill';
		var veryBlueRegEx=/^You feel well hard!/;
		var semiBlueRegEx=/^You feel hard!/;
		var deathRegEx=/^You have died\./;
		var whipRegEx=/ grabs your whip and gets it off you\.$/;
		var fairyRegEx=/^'Thank you. We were wondering where that went.'/;
		var poisonRegEx=/^You are poisoned[^:]/;
		var thiefStealRegEx=/^The thief lunges for/;
		var thiefStabRegEx=/^\.\.\.but (slashes|stabs) with his /;
		var kudoLossRegEx=/^You feel like you've been naughty\./;
		var silverWeaponRegEx=/^Silver [KSC]/;
		var dragonRegEx=/^(Air|Earth|Fire|Water) Dragon/;
		var hitSilverDragon=false;
		var vorpalRegEx=/^Your Vorpal Blade goes Snicker Snack\./;

		for (i=0;i<lines.length;i++) {
			//GM_log('processing attack line '+i);
			if (isAMonsterRegEx.test(lines[i])) {
				if (monsterName=='') {
					monsterName=parseMonsterName(lines[i]);
					if (isSnowman(monsterName)) {
						monsterName='Snowman';
					}
				}
			}
			// I forgot.  Breaks occur on HITS, not ATTACKS.
			if (playerHitRegEx.test(lines[i])) {
				playerAttacks++;
				
				// special case: hitting a full-size dragon with a silver weapon (not a puzzle box!)
				if (dragonRegEx.test(monsterName) && silverWeaponRegEx.test(GM_getValue('oldHeldItem'))) {
					var silverWeapon=GM_getValue('oldHeldItem');
					silverWeapon=silverWeapon.replace(/ \(.+/,'');
					
					if (args!='') args+='%';
					args+='dataType=SilverVsDragon&hit=true&accuracy='+GM_getValue('oldAccuracy')+'&item='+silverWeapon;
					dataCount++;
					hitSilverDragon=true;
				}
				// another special case: recording the color that comes from a Rainbow Wand.
				if (GM_getValue('oldHeldItemCode')=='RainbowWand') {
					rainbowWandRegEx=/^The .+? is magically aligned with (.*)/;
					var rw;
					// Note: I don't need to loop through the lines, because BB is currently sitting on the 
					// "magically aligned with" line right now.
					if ((rw=rainbowWandRegEx.exec(lines[i]))) {
						if (debug) GM_log(rainbowWandRegEx.exec(lines[i]));
						if (args!='') args+='%';
						args+='dataType=RainbowWand&alignment='+rw[1];
						dataCount++;
						continue;
					}
				}
				//More special case: testing for vorpal snicker-snacking.
				if (GM_getValue('oldHeldItemCode','NOTHING')=='VorpalBlade') {
					var snickerSnack=vorpalRegEx.test(lines[i]);
					if (args!='') args+='%';
					args+='dataType=VorpalBlade&monster='+monsterName+'&vorp='+snickerSnack;
					dataCount++;
				}
			}
			// TREASURE CHESTS: done
			if (chestRegEx.test(lines[i])) {
				itsDeadJim=true;
				monsterName='Treasure Chest';
			}
			if (veryBlueRegEx.test(lines[i])) {
				bluePillState='wellHard';
			}
			else if (semiBlueRegEx.test(lines[i])) {
				bluePillState='hard';
			}
			// a tiny, tiny attempt to keep golems out of the DB.
			if (/golem/i.test(monsterName) && monsterName!='Small Paper Mache Golem') {
				monsterName='';
				itsDeadJim=false;
				continue;
			}
			// we attacked something.  Did we kill it, or are we counting monster accuracy instead?
			if (monsterName!='') {
				// PARROT: done.  That was easy.
				if (parrotRegEx.test(lines[i])) {
					monsterName='Parrot (when attacked with a fish)';
					itsDeadJim=true;
				}
				else if (slayRegEx.test(lines[i])) {
					itsDeadJim=true;
				}
				if (illusionRegEx.test(lines[i])) monsterName='Illusion';
				if (getTerrain()=='Arena') monsterName='Gladiator';
				
				// It's dead, Jim.  Quick, get its stuff.
				// MONSTER DROPS: DONE
				if (itsDeadJim) {
					// in the gauntlet, nothing drops items.  Therefore, it's bad data, and I don't want it!
					// also skip golems, if at all possible.
					if (getTerrain()=='Gauntlet' || getTerrain()=='First Aid Point' || /golem/i.test(monsterName)) {
						itsDeadJim=false;
						continue;
					}
					
					var itemTypes=0;
					var droppedItems=new Array();
					for (j=i+1;j<lines.length;j++) {
						if (illusionRegEx.test(lines[j])) monsterName='Illusion';
						if (isAMonsterRegEx.test(lines[j])) break;  // A problem caused ONLY by the Nun Chucks with Advanced Training.  It's possible to kill more than one monster at a time, so quit if we run into another "you attack" line.
						if (j+1<lines.length && giftRegEx.test(lines[j+1])) break; // if somebody gave us something, ignore this item and don't check for further items.
						var itemPair=parseGainedItem(lines[j]);
						if (!itemPair) continue; // this is one of many possible non-you-gain lines when something dies.
						if (/^Arrow|^BB|^Quarrel|^Cannon Ball|^Corpse|^Spikey Ninja Throwing Star/.test(itemPair[1])) continue; // skip any and all ammunition.  I'm a little torn on whether this should apply to Harpoons as well.
						if (itemPair[1]=='Pound of Poop') break; // if we're getting poop, we must have killed something with our last AP, and are getting poop after reading messages once we log in again.
						//GM_log('Loot: '+monsterName+'   '+itemPair[0]+'  '+itemPair[1]+'   '+lines[j]);
						itemTypes++;
						droppedItems.push(itemPair);
					}
			
					var dropName=monsterName;
					if (dropName=='Satisfied Cat' || dropName=='Duck-Filled Fatty-Puss') dropName='Feral Cat';
					if (args!='') args+='%';
					args+='dataType=MonsterDrop&monster='+dropName+'&itemTypes='+itemTypes;
					for (k=0;k<droppedItems.length;k++) {
						args+='&count'+k+'='+droppedItems[k][0]+'&item'+k+'='+droppedItems[k][1];
					}
					itsDeadJim=false;
					dataCount++;
					
					if (monsterName=='Cute Seal') {
						for (j=i+1;j<lines.length;j++) {
							if (kudoLossRegEx.test(lines[j])) {
								if (args!='') args+='%';
								args+='dataType=SealKudoLoss';
								dataCount++;
								continue;
							}
						}
					}
				}
				// it's NOT dead, Jim, and now it wants our gizzards.  With mint sauce.
				//MONSTER ATTACKS (the second part is to exclude pets, which have the same "misses" line.)
				if (monsterAttackRegEx.test(lines[i]) && (parseMonsterName(lines[i])==monsterName || i+1==lines.length)) {
					if (!missRegEx.test(lines[i])) {
						monsterHits++;
						
						//POISONOUS CRITTERS
						// if it hit us, it might poison us.  Check to see if the next message says that. I don't mind sending multiple messages for multi-attack creatures.
						if (i+1<lines.length && poisonRegEx.test(lines[i+1])) {
							dataCount++;
							if (args!='') args+='%';
							args+='dataType=Poison&monster='+monsterName;
						}
						
						// IRON BLING WEAR & TEAR
						if (getItemCount('Iron Bling')) {
							// look, mommy, code duplication!  Bad, bad, bad.  But honest, it's just this once.  
							//(...he says, as the Control, C, and V keys wear out from overuse)
							if (lines.length>i+1 && /^Your Iron Bling has worn out\./.test(lines[i+1])) {
								removeItem('Iron Bling');
								if (args!='') args+='%';
								args+='dataType=MoveBreak&item=Iron Bling&numberWorn='+(getItemCount('Iron Bling')+1);
								dataCount++;
							}
							else {
								// yes, if there's multiple attacks, there will be multiple "didn't break" messages.  But if we're being attacked by a 287-headed hydra, the number of iron blings worn is GOING to change, terribly fast.
								if (args!='') args+='%';
								args+='dataType=MoveIntact&itemTypes=1&item0=Iron Bling&numberWorn0='+getItemCount('Iron Bling');
								dataCount++;
							}
						}

					}
					monsterAttacks++;
				}
				// MILKING
				if (monsterName=='Cow') {
					itemPair=parseGainedItem(lines[i]);
					if (itemPair && haveGPS() && (itemPair[1]=='Pint of Milk' || itemPair[1]=='Pint of Cream')) {
						x=getLongitude();
						y=getLatitude();
						if (args!='') args+='%';
						args+='dataType=Milking&item='+itemPair[1];
						if (x<=-2 && x>=-4 && y>=35 && y<=37) {
							args+='&location=Harvey"s Farm';
						}
						else {
							args+='&location=Anywhere Else';
						}
						dataCount++;
					}
				}
				// WEAPON BREAKAGE:DONE
				if (weaponBreakRegEx.test(lines[i])) {
					broken=true;
					if (debug) GM_log('weapon broke! '+lines[i]);
					// cool.  Extracting banes to BEFORE  the special-handling means I can actually find banes.  For the weird funkiness with deleting a "normal" weapon before a bane weapon, though.... eh, this will be accurate.  I think.
					var tempWeapon=GM_getValue('oldHeldItem');	
					baneTest=baneWeaponRegEx.exec(tempWeapon);
					if (baneTest) {
						// I am NOT responsible if you don't read the Owner's Manual on your baned weapon.
						if (monsterName==specialMonsterParse(baneTest[1])) baneWeapon=true;
						else {
							broken=false;
							continue;
						}
					}
					
					if (shatterRegEx.test(lines[i])) {
						weapon='Glass Sword';
					}
					else if (phaserRegEx.test(lines[i])) {
						weapon='Phaser';
					}
					else if (possibleBreakRegEx.test(lines[i])) {
						weapon='Possible Sword';
					}
					else if (rainbowBreakRegEx.test(lines[i])) {
						weapon='Rainbow Blade';
					}
					else if (rustRegEx.test(lines[i])) {
						weapon='Sharp Sword';
					}
					else if (leechRegEx.test(lines[i])) {
						weapon='Leech';
					}
					else {
						var tempWeapon=lines[i];
						tempWeapon=tempWeapon.replace(/^Your /,''); //strip beginning
						tempWeapon=tempWeapon.replace(/ +\(.+/,''); // strip end (if weapon)
						tempWeapon=tempWeapon.replace(/ has worn out./,''); //strip end (if not weapon)
						tempWeapon=tempWeapon.replace(/^Glowing /,''); // strip "glowing"
						tempWeapon=tempWeapon.replace(/ x \d+/,''); //strip multiples (non-weapons)
						// if we found armor instead of a weapon, report that instead.
						if (isArmor(tempWeapon)) {
							if (args!='') args+='%';
							args+='dataType=ArmorBreak&break=true&item='+tempWeapon+'&bluePill='+bluePillState;
							dataCount++;
							broken=false;
						}
						else if (/^Iron Bling|^(Major )?Healing Bling|^Gold Bling/.test(tempWeapon)) broken=false;// these blings wearing out is covered elsewhere, since it can happen on ANY AP spent.  Iron Blings, similarly, have a custom break detection.
						else {
							weapon=tempWeapon;
							if (debug) GM_log('broken weapon: '+weapon);
						}
					}
				}
				// looking for angry bows....
				if (angryUpgradeRegEx.test(lines[i])) {
					weapon=lines[i+1];
					weapon=weapon.replace(/^You lose an? /,'');
					weapon=weapon.replace(/\.$/,'');
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=AngryUpgrade&item='+weapon;
				}
				// looking for armor absorbing damage.
				if (absorbRegEx.test(lines[i])) {
					var armor=lines[i];
					armor=armor.replace(/^Your /,''); //strip beginning
					armor=armor.replace(/ absorbs.+/,''); // strip end
					armor=armor.replace(/^Glowing /,''); // strip "glowing"
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=ArmorBreak&break=false&item='+armor+'&bluePill='+bluePillState;
				}
				// looking for daemonic weapons slurping up blood....
				if (daemonicRegEx.test(lines[i])) {
					weapon=lines[i+1];
					weapon=weapon.replace(/^You lose an? /,'');
					weapon=weapon.replace(/\.$/,'');
					var melt=/ melts\.$/.test(lines[i+2]);
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=DaemonicMelt&melt='+melt+'&item='+weapon;
				}
				// looking for death.  That poor, poor player...
				if (deathRegEx.test(lines[i])) {
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=Death&monster='+monsterName;
				}
				// did something snatch our whip?
				if (whipRegEx.test(lines[i])) {
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=WhipGrab';
				}
				// fairies: is it a pint, or a gram?
				if (monsterName=='Fairy' && fairyRegEx.test(lines[i]) && i+1<lines.length) {
					itemPair=parseGainedItem(lines[i+1]);
					if (args!='') args+='%';
					dataCount++;
					args+='dataType=Fairy&item='+itemPair[1];
				}
			}
		}
		// technically, this entire section could be moved one curly-brace down, but it's thematically lumped with 
		// attacking, so it might as well go here.
		
		// if any (non-puzzleboxed) attacks happened, report them.
		if (!hallucinating && monsterAttacks>0 && !isConfused(monsterName)) {
			//send message
			if (args!='') args+='%';
			args+='dataType=MonsterAccuracy&monster='+monsterName+'&hits='+monsterHits+'&attacks='+monsterAttacks;
			dataCount++;
		}
		// collect weapon name, if we don't have one yet.
		if (monsterName && weapon=='') {
			weapon=GM_getValue('oldHeldItem');
			baneWeapon=baneWeaponRegEx.test(weapon);
			
			weapon=weapon.replace(/ \(.+| x \d+/,''); // strip end of item (weapon|not weapon)
			weapon=weapon.replace(/^Glowing /,'');
			//GM_log(weapon);
		}
		// report silver weapon accuracy (if we got to this point, it's a miss.)
		if (silverWeaponRegEx.test(weapon) && dragonRegEx.test(monsterName) && !hitSilverDragon) {
			if (args!='') args+='%';
			args+='dataType=SilverVsDragon&hit=false&accuracy='+GM_getValue('oldAccuracy')+'&item='+weapon;
			dataCount++;
		}
		// reporting weapon breakage (or non-breakage)
		// Things not reported: paper vs. rock trolls, fish vs. parrots, clubs vs. cute seals, golf clubs vs. Vorder Men
		else if (weapon && !(/Rock Troll/.test(monsterName) && weapon=='Sheet of Paper')
					&& !(/Parrot/.test(monsterName) && /Eel|Lion Fish|Cod|Flying Fish/.test(weapon))
					&& !(monsterName=='Cute Seal' && (/^\d Iron$/.test(weapon) || weapon=='Night Club' || weapon=='Breakfast Club' || weapon=='Club 18-30'))
					&& !(/Vorder Man/.test(monsterName) && /^\d Iron/.test(weapon))) {

			// if we were JUST RECENTLY holding a custom weapon, report as such.  (only applies when weapon broke, and now we're holding fists.)
			if (GM_getValue('holdingCustomWeapon', true)) {
				if (args!='') args+='%';
				args+='dataType=CustomWeaponBreak&lifespan='+GM_getValue('customWeaponLifespan',0)+'&material='+GM_getValue('customWeaponMaterial','NO MATERIAL')+'&break='+broken+'&attacks='+playerAttacks+'&bluePill='+bluePillState+'&bane='+baneWeapon+'&isWeapon='+GM_getValue('oldIsWeapon');
				dataCount++;
			}
			// ah, leeches.  Report their internal name instead of their usual name.
			else if (weapon=='Leech') {
				weapon=GM_getValue('oldHeldItemCode','NO CODE');
				if (args!='') args+='%';
				args+='dataType=WeaponBreak&item='+weapon+'&break='+broken+'&attacks='+playerAttacks+'&bluePill='+bluePillState+'&bane='+baneWeapon+'&isWeapon='+GM_getValue('oldIsWeapon');
				dataCount++;
			}
			// non-custom weapons get standard reporting
			else {
				if (args!='') args+='%';
				args+='dataType=WeaponBreak&item='+weapon+'&break='+broken+'&attacks='+playerAttacks+'&bluePill='+bluePillState+'&bane='+baneWeapon+'&isWeapon='+GM_getValue('oldIsWeapon');
				dataCount++;
			}
		}
	
		// THIEF ACTIONS
		if (!itsDeadJim && /^Thief/.test(monsterName)) {
			var action='Attack';
			for (i=0;i<lines.length;i++) {
				if (thiefStealRegEx.test(lines[i])) {
					action='Steal';
					if (thiefStabRegEx.test(lines[i+1])) {
						action='Stab';
					}
				}
			}
			if (args!='') args+='%';
			args+='dataType=ThiefAction&monster='+monsterName+'&action='+action;
			dataCount++;
		}
	}
	
	
	

	// FARM:DONE
	// farming doesn't happen with anything but beanstalking, which we're checking.
	if (action=='act_bean') {
		//farm-checking.
		var beanRegEx=/^You lose a Magic Bean\./;
		for (i=0;i<lines.length;i++) {
			if (debug)GM_log('testing '+lines[i]+' for magic beanage.');
			if (beanRegEx.test(lines[i])) {
				var beanstalkRegEx=/bean ?stalk/;  //I can't remember if there's a space in the game description.
				var item;
				var itemCount;
				if (beanstalkRegEx.test(lines[i+1])) {
					// yay beanstalk!   <-- I may need more sleep.
					if (args!='') args+='%';
					args+='dataType=Farming&item=Beanstalk&terrain='+GM_getValue('oldTerrain',"First BB Use");
					dataCount++;
				}
				// for beanstalk data, I don't care what your alignment is.  For all other data, I DO care.
				else if (!hallucinating && !disguised) {
					var foundProduce=false;
					var itemPair;
					for (j=i;j<lines.length;j++) {
						itemPair=parseGainedItem(lines[j]);
						if (itemPair) {
							foundProduce=true;
							break; // once we find one item, quit.  We're only going to get one item from farming.
						}
					}
					// it's possible to grow nothing if you're one of the new, weird alignments.
					if (!foundProduce) itemPair=new Array(0,'Nothing grew');
					if (itemPair[1]=='Tribble') break; // stupid useless tribbles eating my magic beans....
					if (args!='') args+='%';
					args+='dataType=Farming&item='+itemPair[1]+'&count='+itemPair[0]+'&alignment='+getAlignment()+'&terrain='+parseTerrain();
					dataCount++;
					
				}
			}
		}
	}

	// MINING: DONE
	if (action=='act_mine') {
		// mining-checking.
		var miningRegEx=/^You mine\.\.\./;
		for (i=0;i<lines.length;i++) {
			if (miningRegEx.test(lines[i])) {
				if (getTerrain()!='Magic Mine' && !/^The mine collapses/.test(lines[i+1])) {
					itemPair=parseGainedItem(lines[i+1]);
					var terrain=parseTerrain();
					var item=itemPair[1].replace(/ /g,'');
					if (args!='') args+='%';
					args+='dataType=Mining&terrain='+terrain+'&item='+item;
					dataCount++;
				}
				var oldTerrain=GM_getValue('oldRawTerrain','NO TERRAIN');
				if (debug) GM_log('old raw terrain:'+oldTerrain);
				
				if (oldTerrain=='Mine' || oldTerrain=='Magic Mine') {
					var collapse=/^The mine collapses/.test(lines[i+1]);
					
					// we'll only rely on old terrain if it's REALLY necessary.
					if (!collapse) oldTerrain=getTerrain();
					
					if (args!='') args+='%';
					args+='dataType=MineDuration&terrain='+oldTerrain+'&collapse='+collapse;
					dataCount++;
					
				}
			}
		}
	}

	// FORAGING:DONE
	if (action=='act_forage') {
		var foragingRegEx=/^You forage\.\.\./;
		for (i=0;i<lines.length;i++) {
			if (foragingRegEx.test(lines[i])) {
				itemPair=parseGainedItem(lines[i+1]);
				var noIDHerbs = new Array("Brown Fleshy Root",'Bunch of Curly Green Leaves','Bunch of Dark Green Needles','Bunch of Long Silvery Leaves','Bunch of Pale Hairy Leaves','Bunch of Pointed Aromatic Leaves','Bunch of Small Green Leaves','Bunch of Small Yellow Leaves','Elongated Seed Pod','Fleshy Seed Pod','Handful of Papery Flowers','Knobbly Root','Large Hard Seed','Piece of Bark','Pinch of Small Brown Seeds','Pointed Red Fruit','Red Seed Pod','Small Black Berry','Small Blue-black Berry','Yellow Fleshy Root','Dark Blue Flower','Round Black Berry','Violet Bell-Shaped Flower','Handful of Small Red Berries','Hard Smooth Nut-Shell','Pink Spiny Seed Pod','Red Hairy Vine');
				var IDHerbs = new Array('Ginseng Root','Sprig of Parsley','Sprig of Rosemary','Sprig of Sage','Sprig of Wormwood','Sprig of Mint','Sprig of Oregano','Sprig of Thyme','Vanilla Pod','Cocoa Pod','Handful of Hops','Ginger Root','Nutmeg','Piece of Chinchona Bark','Pinch of Aniseed','Chilli Pepper','Rose Hip','Juniper Berry','Sloe','Mandrake Root','Aconite Flower','Belladonna Berry','Foxglove','Yew Berry','Wild Almond','Castor Bean Pod','Poison Ivy Stem');
				for (j=0;j<noIDHerbs.length;j++) {
					if (itemPair[1]==noIDHerbs[j]) {
						itemPair[1]=IDHerbs[j];
						break;
					}
				}
				var terrain=parseTerrain();
				if (args!='') args+='%';
				args+='dataType=Foraging&terrain='+terrain+'&item='+itemPair[1];
				dataCount++;
			}
		}
	}

	// FISHING: DONE
	if (action=='act_fish' && !disguised) {
		//fishing-checking.
		var fishingRegEx=/^You fish\.\.\./;
		for (i=0;i<lines.length;i++) {
			if (fishingRegEx.test(lines[i])) {
				itemPair=parseGainedItem(lines[i+1]);
				var foundFish=false;
				var itemPair;
				for (j=i;j<lines.length;j++) {
					itemPair=parseGainedItem(lines[j]);
					if (itemPair) {
						foundFish=true;
						break; // once we find one item, quit.  We're only going to get one item from farming.
					}
				}
				// it's possible to fish nothing if you're one of the new, weird alignments.
				if (!foundFish) itemPair=new Array(0,'Nothing fished');
				if (args!='') args+='%';
				args+='dataType=Fishing&item='+itemPair[1]+'&alignment='+getAlignment()+'&terrain='+parseTerrain();
				dataCount++;
			}
		}
	}

	// FLYING HEALERS
	if (action=='act_fullheal' && getTerrain()=='Flying Healer Station') {
		var flyerRegEx=/^You wait for the healer to arrive\./;
		var lose1000RegEx=/^You lose 1000 Gold Pieces\./;
		var healedRegEx=/^That feels so much better\./;
		for (i=0;i<lines.length;i++) {
			if (flyerRegEx.test(lines[i])) {
				if (args!='') args+='%';
				args+='dataType=FlyingHealer&status=wait';
				dataCount++;
			}
			else if (i+1<lines.length && lose1000RegEx.test(lines[i]) && healedRegEx.test(lines[i+1])) {
				if (args!='') args+='%';
				args+='dataType=FlyingHealer&status=heal';
				dataCount++;
			}
		}
	}
	
	// JUNGLE/FOREST FRUIT/ACORN ODDS:DONE
	if (action=='act_getwood' || action=='act_getmorewood') {
		var woodRegEx=/^You gain \d+ Pieces of Wood\./;
		var terrain=parseTerrain();
		for (i=0;i<lines.length;i++) {
			if (woodRegEx.test(lines[i]) && (terrain=='Jungle' || terrain=='Wizard Retreat Jungle'|| terrain=='Forest')) {
				var woodCount='ERROR';
				if (action=='act_getwood') woodCount='3';
				else if (action=='act_getmorewood') woodCount='30';
				var itemTypes=0;
				var foundItems=new Array();
				var cancel=false;
				for (j=i+1;j<lines.length;j++) {
					var itemPair=parseGainedItem(lines[j]);
					if (!itemPair) continue;
					//GM_log('Wood gathering: '+getTerrain()+'   '+itemPair[0]+'  '+itemPair[1]+'   '+lines[j]);
					// this switch is left over from when I wasn't checking button names.  It's probably no longer necessary, but it's worth testing before I disassemble it.
					switch(itemPair[1]) {
						case 'Lemon':
						case 'Lime':
						case 'Orange':
						case 'Pomegranate':
						case 'Pineapple':
						case 'Coconut':
						case 'Acorn':
							itemTypes++;
							foundItems.push(itemPair);
							break;
						default:
							cancel=true;
							if (debug) GM_log('Non-fruit-item in Wood Gathering.  Line:'+lines[j]);
					}
					if (cancel) break;
				}
				if (cancel && foundItems.length==0) break;
			
				if (args!='') args+='%';
				args+='dataType=Wood&terrain='+terrain+' Gathering '+woodCount+'&itemTypes='+itemTypes;
				for (i=0;i<foundItems.length;i++) {
					args+='&count'+i+'='+foundItems[i][0]+'&item'+i+'='+foundItems[i][1];
				}
				dataCount++;
				break;
			}
		}
	}
	
	// CORNUCOPIA: DONE
	if (action=='act_item_eat' || action=='act_item_drink') {
		var foodRegEx=/^You reach into the cornucopia/;
		var drinkRegEx=/^You up.end the cornucopia/;
		for (i=0;i<lines.length;i++) {
			if (foodRegEx.test(lines[i])) {
				var foodPair=parseGainedItem(lines[i+1]);
				var food=foodPair[1];
				if (args!='') args+='%';
				args+='dataType=CornucopiaFood&item='+food;
				dataCount++;
			}
			if (drinkRegEx.test(lines[i])) {
				var drinkPair=parseGainedItem(lines[i+1]);
				var drink=drinkPair[1];
				if (args!='') args+='%';
				args+='dataType=CornucopiaDrink&item='+drink;
				dataCount++;
			}
		}
	}
	
	// RUIN-SEARCHING (and storerooms, too?)
	var terrain=getTerrain();
	var petPoopRegEx=/Pound of Poop|Egg|Unit of Sand|BB|Stone/;
	var loseRegEx=/^You lose /;
	// ruin: act_locuse  storeroom: act_search
	if ((action=='act_search' || action=='act_locuse') &&(terrain=='Storeroom' || terrain=='Ruin' || terrain=='Store Room' || terrain=='Inside Ambulance')) {
		var searchRegEx=/^You search\.\.\.|^You find a box. You open it.../;
		if (terrain=='Store Room' && getTerrain("e")=='Lounge') {
			terrain='Kansas Store Room';
		}
		for (i=0;i<lines.length;i++) {
			// the Ruins and Ambulance actually give you some kind of message to show you're searching.  The Store Rooms just have "you gain X."
			if ((terrain=='Ruin' || terrain=='Inside Ambulance') && searchRegEx.test(lines[i])) {
				itemPair=parseGainedItem(lines[i+1]);
				if (args!='') args+='%';
				args+='dataType=Searching&terrain='+terrain+'&item='+itemPair[1];
				dataCount++;
				break;
			}
			else if ((terrain=='Storeroom' || terrain=='Store Room'|| terrain=='Kansas Store Room') &&i==lines.length-1) {
				itemPair=parseGainedItem(lines[i]);
				if (itemPair && !petPoopRegEx.test(itemPair[1])) {
					if (args!='') args+='%';
					args+='dataType=Searching&terrain='+terrain+'&item='+itemPair[1];
					dataCount++;
					break;
				}
			}
			else if (terrain=='Inside Ambulance' && lines[i]=='You don\'t find anything') {
				if (args!='') args+='%';
				args+='dataType=Searching&terrain='+terrain+'&item=Nothing';
				dataCount++;
				break;
			}
		}
	}
	
	//BOWLING
	if (action=='act_bowl') {
		var bowlingRegEx=/^You bowl\.\.\./;
		for (i=0;i<lines.length;i++) {
			if (bowlingRegEx.test(lines[i])) {
				itemPair=parseGainedItem(lines[i+2]);
				var item;
				if (!itemPair) item='Nothing';
				else item=itemPair[1];
				if (args!='') args+='%';
				args+='dataType=Bowling&item='+item;
				dataCount++;
			}
		}
	}
	
	//GOING TO THE BALL
	if (action=='act_join') {
		var ballRegEx=/^You lose a Ball Invitation\./;
		var ball2RegEx=/^You have a ball\./;
		for (i=0;i<lines.length;i++) {
			if (ballRegEx.test(lines[i]) && i+1<lines.length && ball2RegEx.test(lines[i+1])) {
				itemPair=parseGainedItem(lines[i+2]);
				var item;
				if (!itemPair) item='Nothing';
				else item=itemPair[1];
				if (args!='') args+='%';
				args+='dataType=Ball&item='+item;
				dataCount++;
			}
		}
	}
	
	// BEACH-LOOTING
	// act_getsand for desert/badlands.  For beaches, it's act_getsand, act_getsand10, or act_getsand100.  Once we're past the first IF test, though, the old version works just as well as the new one, so keep the old version.
	if (/^act_getsand/.test(action)) {
		var sandRegEx=/^You gain \d+ Units of Sand\./;
		var terrain=getTerrain();
		var beachRegEx=/Beach/;
		var badLandRegEx=/^Dusk Plain|^Bad Lands/;
		for (i=0;i<lines.length;i++) {
			var sandCount=/\d+/.exec(lines[i]);
			if ((beachRegEx.test(terrain) && (sandCount=='25' || sandCount=='250' || sandCount=='2500'))|| (badLandRegEx.test(terrain) && sandCount=='10')) {
				var itemTypes=0;
				var foundItems=new Array();
				var cancel=false;
				for (j=i+1;j<lines.length;j++) {
					var itemPair=parseGainedItem(lines[j]);
					if (!itemPair) continue;
					//GM_log('Wood gathering: '+getTerrain()+'   '+itemPair[0]+'  '+itemPair[1]+'   '+lines[j]);
					// this switch is because I don't trust the phrase "You gain \d+ units of sand" in the slightest, so I'm forcing it to only recognize salt and shells.  This must, of course, change if anything new can be found on beaches.
					switch(itemPair[1]) {
						case 'Pinch of Salt':
						case 'Shell':
							itemTypes++;
							foundItems.push(itemPair);
							break;
						default:
							cancel=true;
					}
					if (cancel) break;
				}
				if (cancel && foundItems.length==0) break;
			
				if (args!='') args+='%';
				args+='dataType=Sand&terrain='+terrain+' Gathering '+sandCount+'&itemTypes='+itemTypes;
				for (i=0;i<foundItems.length;i++) {
					args+='&count'+i+'='+foundItems[i][0]+'&item'+i+'='+foundItems[i][1];
				}
				dataCount++;
				break;
			}
		}
	}
	
	//DIGGING AT HARVEY'S FARM
	//...it's a good sign that new things are getting progressively more trivial.  I think I'm almost out of things to add.
	// Almost.
	if (action=='act_dig' && 
			((haveGPS() && getLongitude()<=-2 && getLongitude()>=-4 && getLatitude()>=35 && getLatitude()<=37)
			|| (!haveGPS() && (getTerrain()=='Cell'|| getTerrain()=='Dungeon')))) {
		var digRegEx=/^You dig\.\.\./;
		var fallRegEx=/^You fall through into a room below!/;
		for (i=0;i<lines.length;i++) {
			if (digRegEx.test(lines[i]) && i+1<lines.length) {
				itemPair=parseGainedItem(lines[i+1]);
				var item;
				if (!itemPair && fallRegEx.test(lines[i+1])) item='Fall into Dungeon';
				else if (!itemPair) item='Unknown';
				else item=itemPair[1];
				if (args!='') args+='%';
				args+='dataType=HarveyDig&item='+item+'&spade='+/Spade/.test(getHeldItem());
				dataCount++;
			}
		}
	}
	
	
	// THEFT.  YOU'RE AN EVIL, EVIL MAN/WOMAN/THING.  I LIKE YOU.
	if (action=='act_user_steal') {
		var playerAttackRegEx=/ attacks you using /;
		for (i=1;i<lines.length;i++) {
			if (playerAttackRegEx.test(lines[i])) {
				var itemPair=parseGainedItem(lines[i-1]);
				if (itemPair || /^You fail\./.test(lines[i-1])) {
					var victim=lines[i].replace(/ attacks you using .+/,'');
					victim=victim.replace(/^((Duke |Earl |Baron |Knight |Shaman |Apprentice |Wizard |Smith |Armourer |Master Armourer |Great Lord )|(Pvt\. |Cpl\. |Sgt\. )){0,2}/,'');
					victim=victim.replace(/ MD$/,''); 
					if (args!='') args+='%';
					args+='dataType=PlayerTheft&theft='+(itemPair!=null)+'&victim='+victim;
					dataCount++;
				}
			}
		}
	}
	

	// WORMHOLES, and the break rates that love them.
	// This is probably the same action name as other things. Side note: dumps are act_tip_add and act_tip_remove.
	// bags also have act_item_add.
	if (action=='act_item_add') {
		var wormholeRegEx=/^Wooosh! It is sucked through the rip in space./;
		for (i=1;i<lines.length;i++) {
			if (wormholeRegEx.test(lines[i])) {
				var holeBroke=(i+1<lines.length && /^Krunt! The wormhole has collapsed/.test(lines[i+1]));
				if (args!='') args+='%';
				args+='dataType=Wormhole&break='+holeBroke;
				dataCount++;
			}
		}
	}

	// FLASH GUNS, and the break rates that love them.
	if (action=='act_item_use') {
		var flashgunRegEx=/^You fire your flashgun/;
		for (i=0;i<lines.length;i++) {
			if (flashgunRegEx.test(lines[i])) {
				var gunBroke=(i+2<lines.length && /^Your flash gun has run out of power/.test(lines[i+2]));
				if (args!='') args+='%';
				args+='dataType=FlashGun&break='+gunBroke;
				dataCount++;
			}
		}
	}
	
	// SE TUNNEL MINING
	if (action.substr(0,8)=='act_cut_') {
		if (GM_getValue('oldCutTerrain')=='Rock') {
			var terrain=getTerrain(/act_cut_(n|e|w|s)/.exec(action)[1]);
			
			if (args!='') args+='%';
			args+='dataType=TunnelMining&terrain='+terrain+'&guild='+(!haveGPS());
			dataCount++;
		}
	}

	// ASTRAL PLAIN
	if (/^act_item_(cast|force)_SpellAstral/.test(action)) {
		var shrimpRegEx=/^Ooops. Something went wrong!/;
		var shrimp=false;
		for (i=0;i<lines.length;i++) {
			shrimp=(shrimp||shrimpRegEx.test(lines[i]));
		}
		if (args!='') args+='%';
		args+='dataType=AstralPlain&shrimp='+shrimp;
		dataCount++;
	}
	
	// HARVEST SONG: testing whether stars fall.
	if (action=='act_item_harvest') {
		var rainRegEx=/^A brief shower of rain falls on your field, then the (sun shines brightly|moon and stars shine clearly)\./;
		var starRegEx=/^You see a shooting star fall!/;
		var starfall=false;
		for (i=0;i<lines.length;i++) {
			if (rainRegEx.test(lines[i])) {
				if (i+1<lines.length && starRegEx.test(lines[i+1])) {
					starfall=true;
				}
				if (args!='') args+='%';
				args+='dataType=HarvestSong&starfall='+starfall;
				dataCount++;
				break;
			}
		}
	}
	// NORTHERN ROCKS: cash or teleport?
	if (action=='act_item_use' && GM_getValue('oldHeldItemCode')=='NorthernRock') {
		var darlingRegEx=/^A darling from the treasury runs up and gives you a fistful of cash/;
		var darling=false;
		//GM_log('begin darling detection');
		for (i=0;i<lines.length;i++) {
			if (darlingRegEx.test(lines[i])) {
				darling=true;
				//GM_log('found darling!');
				break;
			}
		}
		if (args!='') args+='%';
		args+='dataType=NorthernRock&darling='+darling;
		dataCount++;
	}
	
	// EQUIPPED ITEM WEAR & TEAR
	// Version 1.0 of this is only going to check for things wearing out when you MOVE (which shouldn't happen with any other data reporting).  Version 2 will be better, but there's a lot to check for.  Merely finding dataCount!=0 isn't going to be enough; we'll need to handle shop transactions, taking things from bags (but not putting things INTO bags), wearing & changing disguise kits, crafting, using Iron Hubbards and Unicornucopias (this & crafting might just be checking for a non-poop "you gain" message, while NOT holding a bag and not standing on a TP/party/pentagram), and ALL questy AP-spending.

	// inventory management part 1/data reporting part 1: did something wear out?
	var spendAPitemsRegEx=/^Iron Bling|^(Major )?Healing Bling|^Gold Bling/; 
	var brokeItem='';
	if (action.substr(0,8)=='act_move') {
		var brokeRegEx=/^Your (.+) has worn out\./;
		var clipRegEx=/^Your wings are clipped\./;
		var surveyRegEx=/^Your ship's GPS unit fizzles and sets light to the map\./;
		var tankRegEx=/^Busted!/;
		var results;
		// Look for things wearing out.
		for (var i=0;i<lines.length;i++) {
			if ((results=brokeRegEx.exec(lines[i])) || (clipRegEx.test(lines[i]) && (results=new Array('dummy','Pair of Daedalus Wings')))|| (surveyRegEx.test(lines[i]) && (results=new Array('dummy','Survey Vessel'))) || (tankRegEx.test(lines[i]) && (results=new Array('dummy','Tank')))) {
				results[1]=results[1].replace(/ \(.+/,'');
				if (removeItem(results[1])) {
					if (args!='') args+='%';
					args+='dataType=MoveBreak&item='+results[1]+'&numberWorn='+(getItemCount(results[1])+1);
					dataCount++;
					
					brokeItem=results[1];// I believe that only one item breaks per movement.  My logic is that since it cancels the move, it quits out of equipment-checking, too.  I haven't seen 2 things break in a row, at any rate.
				}
			}
		}
	
		// data reporting part 2: if we moved, what DIDN'T wear out?
		var totalItems=GM_getValue('totalItems'+getUsername(),0)
		if (totalItems>0 && !brokeItem) {
			// we moved.  Now report everything that DIDN'T break.
			var send='';
			var sendCount=0;
			for (var j=0;j<totalItems;j++) {
				// we track iron blings by hits, not by movement.
				if (!spendAPitemsRegEx.test(GM_getValue('wornItem'+getUsername()+j,''))) {
					send+='&item'+j+'='+GM_getValue('wornItem'+getUsername()+j)+'&numberWorn'+j+'='+GM_getValue('wornItemCount'+getUsername()+j,0);
					sendCount++;
				}
			}
					
			if (args!='') args+='%';
			dataCount++;
			args+='dataType=MoveIntact&itemTypes='+sendCount+send;
		}
	}
	
	// inventory management part 2: did we put something on, or take it off?
	var wearRegEx=/^You put on (an?|\d+) (.+)\./;
	var removeRegEx=/^You take off (an?|\d+) (.+)\./;
	var result;
	//GM_log('beginning wear/remove detection');
	for (var i=0;i<lines.length;i++) {
		//GM_log('wear/remove detection: line '+i);
		// if we put something on, update the worn items
		if ((result=wearRegEx.exec(lines[i]))) {
			if (result[1]=='a' || result[1]=='an') result[1]=1;	// okay, so loose typing DOES have its benefits...
			else {
				result[1]=parseInt(result[1]);
				result[2]=depluralize(result[2]);
			}
			result[2]=result[2].replace(/^Glowing /,'');
			if (wearsWithMovement(result[2])) {
				//GM_log('Putting on:'+result[2]+result[1]+', line '+i);
				putOnItem(result[2], result[1]);
			}
		}
		// similarly, if we took something off, update the worn items.
		else if ((result=removeRegEx.exec(lines[i]))) {
			if (result[1]=='a' || result[1]=='an') result[1]=1;
			else {
				result[1]=parseInt(result[1]);
				result[2]=depluralize(result[2]);
			}
			result[2]=result[2].replace(/^Glowing /,'');
			if (wearsWithMovement(result[2]) && result[2] != brokeItem) {	// when something wears out, you usually take it off, THEN it wears out.  So removal detection happens after breaking detection, and breaking detection prevents removal detection for that item.
				removeItem(result[2], result[1]);
			}
		}
	}
	
	
	// finally, the things that wear out on EVERY ap spent.  Things to test: changing disguise kits, any inventory gain (EXCEPT spells, gifts, and poop) (handles bags, crafting, Hubbard/Unicornucopia, etc), and lots of quest actions.
	// looking for any action we haven't covered that spends AP.  I'm still missing a few methods, and there are ways to carefully get a false negative, but this should be most of them.
	var spentAP=dataCount;
	var botheringWithAP=getItemCount('Gold Bling')||getItemCount('Healing Bling')||getItemCount('Major Healing Bling');
	if (!spentAP && botheringWithAP) {
		var questRegEx=/^You kneel before the 'king|^You celebrate with the other new initiates|^You are taught some new skills: Climb and Mine|^Tick!|^Your offering is consumed|^Wow, you actually did it!|^You read the inscription|^You have 15 minutes to connect|^You get trained\.|^Welcome to the ways of water|^You are now o(n|ff) call\.|^You lose 10 Blue Flowers\.|^You use some antidote|^Your? make the connection!|^Yoink! Tommy takes a tinny off you!|^Hi! says the wizard\. You wanna learn level 2 magic? Sure\.\. why not?|^Thor says, "I that under the thun-bed for too long. I'm really thor, all over\.|^You are transported to a strange land\.|^You place the |^A sharp white light fills your view, and you feel |^Jock probably says 'Just right. Thank you.'|^Paul says, 'Bonzer, cobber.'|^You learn how to forage for herbs|^Herman the Hermit says "Thanks for|^You sing a note off-key!|^The socks fling you|^Gah! Poison!|^Gah! Some kind of|^The ground opens up|^Something nasty oozes out.|^As you read the books, you start to understand|^You interrupt Tim's singing|^You sneak your camera into the throne|^You chill out for a bit, remembering to take a picture before you leave.|^You somehow manage to make Pandora smile|^Jock flips his kilt up to give you a better shot|^You just about get his beard in shot|^The Pimp shows off his bling for your lens|^You get a cool action shot of Paul putting|^You get a picture of Kirk|^You stand well back to get all of Thor|^You take a photo of the Prince|^You get a picture of Herman|^You share a pipe|^You check in and see some famous faces|^You are accepted to level|^You are raised up to corporal!|^You spend two days on the paper work/;
	
		var otherActionRegEx=/^You practice the|^You say |^You give |^You dig|^You change the disguise/;
		var gainRegEx=/^You gain/;
		var spellRegEx=/Spell/;
	
		// test in increasing order of the ratio (CPU agony/likelihood)
		for (var i=0;i<lines.length && !spentAP;i++) {
			// the big one: gained an item that wasn't a gift.  Covers crafting, a fair number of quests, Hubbards & Unicornucopias, and a number of other things.  Also check that the gained item wasn't poop or a spell.  The petpoop regex was defined earlier.
			spentAP=gainRegEx.test(lines[i]) && (i+1==lines.length || !giftRegEx.test(lines[i+1])) && !petPoopRegEx.test(lines[i]) && !spellRegEx.test(lines[i]);
		}
		for (var i=0;i<lines.length && !spentAP;i++) {
			// smaller likelihood: some other action not measured previously.
			spentAP=otherActionRegEx.test(lines[i]);
		}
		for (var i=0;i<lines.length && !spentAP;i++) {
			// quite small likelihood: one of the many quest-progress messages.
			spentAP=questRegEx.test(lines[i]);
		}
	}
	// finally, the report: each piece of Every-AP equipment reports its life.
	if (spentAP && botheringWithAP) {
		var specialItems=new Array('Gold Bling','Healing Bling','Major Healing Bling');
		var itemCounts=new Array();
		for (var i=0;i<3;i++) {
			if (getItemCount(specialItems[i])) {
				itemCounts.push(new Array(specialItems[i], getItemCount(specialItems[i])));
			}
		}
		
		if (args!='') args+='%';
		dataCount++;
		args+='dataType=MoveIntact&itemTypes='+itemCounts.length;
					
		for (var j=0;j<itemCounts.length;j++) {
			args+='&item'+j+'='+itemCounts[j][0]+'&numberWorn'+j+'='+itemCounts[j][1];
		}
		
	}
	
	// inventory management part 3: are we sitting on the equip screen, or is there an equip sidepanel?  If so, update our idea of what we're wearing.  I think that's what's screwing with the data: the game searches the equipment side panel, updates the number of items, and THEN starts searching the message pane for breakages.  Now that it's AFTER all the major data-reporting, it shouldn't screw with anything anymore.
	updateEquipment();
	
	
	// WIZARD "NIGHTLY" SPELL GAINS
	// can, in theory, occur with ANYTHING except combat.  Even ruin/storeroom searching turns up no spells.
	var wizardRank;
	if (!/Monster|Weapon/.test(args) && !/^Bag/.test(getHeldItem()) && (wizardRank=/Apprentice|Shaman|Wizard/.exec($('control_name').firstChild.firstChild.innerHTML))) {
		var itemPair;
		var itemTypes=0;
		var spells=new Array();
		for (i=0;i<lines.length;i++) {
			if (i+1<lines.length && giftRegEx.test(lines[i+1])) continue; // if somebody gave us something, ignore this item.
			// the OTHER possible source of spells: corpses.  Cancel everything if we found a corpse.
			if (/The corpse decays/.test(lines[i])) {
				spells=new Array();
				break;
			}
			var itemPair=parseGainedItem(lines[i]);
			if (!itemPair) continue; // this is one of many possible non-"you gain" lines.
			if (!/Spell$/.test(itemPair[1])) continue; // if it ain't a spell, I don't care.
			spells.push(itemPair);
			itemTypes++;
		}
		if (spells.length) {
			if (args!='') args+='%';
			args+='dataType=WizardSpell&rank='+wizardRank+'&itemTypes='+itemTypes;
			for (k=0;k<spells.length;k++) {
				args+='&count'+k+'='+spells[k][0]+'&item'+k+'='+spells[k][1];
			}
			itsDeadJim=false;
			dataCount++;
		}
	}
	

	// SUMMON STONES: DO THEY BREAK?
	var summonRegEx=/^A vortex sucks you up. *It seems that you have been summoned by (Pvt\. |Cpl\. |Sgt\. )?(Duke |Earl |Baron |Knight |Shaman |Apprentice |Wizard |Smith |Armourer |Master Armourer |Great Lord )?(.+?)( MD)?\. (You pick up|The )/;
	for (var i=0;i<lines.length;i++) {
		if (summonRegEx.test(lines[i])) {
			var summonResult='Stone broke completely';
			var summoner=summonRegEx.exec(lines[i])[3];
			itemPair=parseGainedItem(lines[i+1]);
			if (itemPair && /^Summon Stone/.test(itemPair[1])) {
				summonResult=itemPair[1];
			}
			if (args!='') args+='%';
			dataCount++;
			args+='dataType=SummonStone&result='+summonResult+'&summoner='+summoner;
		}
	}
	
	// in theory, this stuff could go in the spammed action listeners, but the only important part is that it's done after the data processing, so it might as well go here.  Spamming the action listeners before doing the data collection will only lead to race conditions, anyway.
	
	// remembering previous terrain (for the beanstalk)
	GM_setValue('oldTerrain',parseTerrain());
	
	// remembering the raw value of the previous terrain (for mine duration)
	GM_setValue('oldRawTerrain',getTerrain());
	
	// remembering previous held item (mostly for weird special-weapon breaking, though in theory I could use it for all weapon-breaking.)  Still don't trust it, though, since if the user acts too early, the script might stop before it gets here.  If I ever get around to putting action listeners on every button, then I guess I can trust this.
	GM_setValue('oldHeldItem', getHeldItem());
	
	// I give up.  We'll just store EVERYTHING we want to know about the weapon at the end of each page load.
	var accuracy=0;
	var heldItem=getHeldItem();
	var heldItemValue=getHeldItemValue();
	var accuracy=getHeldItemAccuracy();
	if ((accuracy || heldItemValue=='GorganHead') && !isArmor(heldItem)) {
		GM_setValue('oldIsWeapon', true);
	}
	else GM_setValue('oldIsWeapon', false);
	if (accuracy <0) accuracy=0;	// if it's negative accuracy, call it 0.
	GM_setValue('oldAccuracy',accuracy);

	GM_setValue('oldHeldItemCode', heldItemValue);	// remember the INTERNAL name of the item, for leeches.
	if (/^CustomWeapon/.test(heldItemValue)) {
		GM_setValue('holdingCustomWeapon', true);
		itemElement=$('item');
		if(debug)GM_log(itemElement.innerHTML);
		material=/Made of (Iron|Silver|Bronze)/.exec(itemElement.innerHTML);
		if (material) {
			material=material[1];
			GM_setValue('customWeaponMaterial',material);
		}
		lifespan=/([Ll]ifespan|[Dd]urability)[^,;.]+?(\d+)/.exec(itemElement.innerHTML);
		if (lifespan) {
			lifespan=lifespan[2];
			GM_setValue('customWeaponLifespan',parseInt(lifespan));
		}
		if (debug)GM_log('lifetime:'+lifespan+ 'material:'+material);
	}
	else {
		GM_setValue('holdingCustomWeapon',false);
	}
	
	if (debug) GM_log('got to the data-reporting part. DataCount: '+dataCount);
	// if we have something to say, SAY IT!
	if (dataCount) postData(args);  
	
	// logging the time it takes to run the data-detection stuff.
	if (debug) {
		var endTime=new Date();
		GM_log('data detection time: '+(endTime.getTime()-startTime.getTime()));
	}
}

function parseTerrain() {
	var rawTerrain=getTerrain();
	var finalTerrain='';
	var locDiv=$('location');
	var foundTerrain=false;
	// new terrain-identification method: search the wiki-link FIRST, so new/rare terrains don't blindside me all the time by showing up in all the charts.
	if (locDiv) {
		try {
			wikiLink=locDiv.firstChild.firstChild.getAttribute('href');
			foundTerrain=true;
			// Notable terrains NOT tested: Jungle.  (it could be a wizard retreat!)
			if (/title=Wild$/.test(wikiLink) || /Wilder$/.test(wikiLink)) finalTerrain='Wilderness Green';
			// Grr.  Graveyards are a special exception when foraging.
			else if (/title=Plain$/.test(wikiLink)) {
				finalTerrain='Civilized Green';
				if (rawTerrain=='Graveyard') finalTerrain='Graveyard';
			}
			else if (/title=Water$/.test(wikiLink)) finalTerrain='Lake';
			else if (/title=TForest$/.test(wikiLink)) finalTerrain='Thieves Forest';
			else if (/title=Forest$/.test(wikiLink)) finalTerrain='Forest';
			else if (/title=BadLands$/.test(wikiLink)) finalTerrain='Bad Lands';
			else if (/title=Mountain$/.test(wikiLink)) finalTerrain='Mountain';
			else if (/title=Crag$/.test(wikiLink)||/Crags$/.test(wikiLink)) finalTerrain='Crags';
			else if (/title=Outcrop$/.test(wikiLink)) finalTerrain='Outcrop';
			else if (/title=(?:Great)?Desert$/.test(wikiLink)) finalTerrain='Desert';
			else foundTerrain=false;
		}
		// marginally ugly, but if we couldn't navigate the location div properly, we obviously didn't find the terrain.
		catch (ex) { foundTerrain=false; }
	}
	// of course, we still need the old fallbacks to, er, fall back on if there's no GPS for this character, but we can clear out all the wikilink stuff from here now that it's at the beginning.
	if (!foundTerrain) {
		if (/^Lake|^Water Trap|^Pond|^Tarn|^Steaming Pool|^Fish Pond/.test(rawTerrain)) finalTerrain='Lake';
		else if (/^Plain|^Glade|^Golf Course|^Graveyard|^Meadow|^Pasture|^Sand Trap|^Track|^Field|^Valley|^Imemorial Park|^Hunters Trail|^Garden|^Ground|^Great Plain/.test(rawTerrain)) finalTerrain='Civilized Green';
		else if (/^Wilderness|^Downs|^Trail|^Heath/.test(rawTerrain)) finalTerrain='Wilderness Green';
		else if (rawTerrain=='Hills') {
			if (haveGPS()) {
				var xPos=getLongitude();
				var yPos=getLatitude();
				// Wilderness areas: anything inside the Cities past a certain point, the Frozen North (if any hills exist there), and a band from a little north of the Desert to a little south of the Desert.  Oz has no hills, and no wilderness to boot.
				if ((xPos >= 7 && xPos <= 92 && yPos >= 7 && yPos <= 92) || yPos > 110 || (yPos <=-1 && yPos>=-45)) {
					finalTerrain='Wilderness Green';
				}
				else finalTerrain='Civilized Green';
			}
			else finalTerrain='Unidentified Hills';
		}
		else if (/^Dusk Plain|^Bad Lands|^Dust|Crossroads/.test(rawTerrain)) finalTerrain='Bad Lands';
		// best guesses on the underlying terrain of a Mine.  Oz is a problem because the outcrops and crags are scattered randomly. (Okay, not quite randomly, but I'm too lazy to break it into degrees.) This section will still be used even if we have a terrain wiki-link, because the wiki-link is always "Mine".
		else if (rawTerrain=="Mine") {
			if (haveGPS()) {
				var xPos=getLongitude();
				var yPos=getLatitude();
				// The Interior and the Frozen North are mountains.
				if ((xPos > 20 && xPos < 80 && yPos > 20 && yPos < 80) || yPos > 110) {
					finalTerrain='Mountain';
				}
				// the Great Desert is always outcrops
				else if (yPos < -7 && yPos > -43) {
					finalTerrain='Outcrop';
				}
				// anything in the Thick Jungle is a mountain.
				else if (yPos <-100 && Math.sqrt((yPos+250)*(yPos+250) + (xPos-50)*(xPos-50)) <= 36) {
					finalTerrain='Mountain';
				}
				// if it ain't a mountain, it could be anything.  Within some limits, of course.
				else if (yPos < -100) {
					finalTerrain='Unknown Oz Mine';
				}
				else {
					finalTerrain='Crags';
				}
			}
		}
		else if (/Forest/.test(rawTerrain)) finalTerrain='Forest';	// Thieves' forest is already identified; this catches anything weird.
		else if (/Desert/.test(rawTerrain)) finalTerrain='Desert';
		// this section will always apply if we're in jungle, because I'm not sure if the Wizard part shows up in the wiki-link.
		else if (rawTerrain=='Jungle') {
			if (haveGPS()) {
				finalTerrain='Jungle';
			}
			// the jungle on the wizard's retreat is always in view of either Mt. Spooky, or a sandy beach.  It's possible to modify these via fireworks or mines, but I'll have to live with occasional "can't identify" errors.
			else {
				var mapping = new Array( "nw", "n", "ne", "w", "c", "e", "sw", "s", "se" );
	
				// Grab all the relevant 'tr's
				var locations = new Array(9);
				// we should error out if we don't have field glasses vision.
				try {
					for(i=0; i<9; i+=1) {
						locations[i] = getTerrain(mapping[i]);
					}
					wizRetreatRegEx=/^Sandy Beach|^Mt. Spooky/;
					for (i=0;i<9;i++) {
						if (wizRetreatRegEx.test(locations[i])) {
							finalTerrain='Wizard Retreat Jungle';
						}
					}
					if (finalTerrain=='') finalTerrain='Unidentified Jungle';
				}
				catch (ex) {
					finalTerrain='Unidentified Jungle';
				}
			}
		}
		else if (rawTerrain=='Roof Garden' || rawTerrain=='Roof garden') finalTerrain='Jungle';
		// The Fire Swamp can be tested in two ways: by looking at the wiki-link, or by checking the class of the terrain tile.  I'm too lazy to test the terrain tile right now, since the first case should cover 99.5% of people who would venture into the Fire Swamp.
		else finalTerrain=rawTerrain;
	}
	return finalTerrain;
}

// returns a pair: COUNT, ITEMNAME
function parseGainedItem(inputLine) {
	var singleItemRegEx=/^You gain an?/;
	var multipleItemRegEx=/^You gain \d+/;
	var itemCount;
	if (singleItemRegEx.test(inputLine)) {
		itemCount=1;
	}
	else if (multipleItemRegEx.test(inputLine)) {
		// this should handle 9 irons properly, because it's "a" 9 iron, or "you gain 2 9 irons."
		itemCount=/\d+/.exec(inputLine);
	}
	else return null;
	var item=inputLine.replace(/You gain (an? |\d+ )/,'');
	item=item.replace(/\.$/,'');
	item=item.replace(/'/g,'"');
	item=item.replace(/^Glowing /,'');

	// de-pluralize.
	if (itemCount>1) {
		item=depluralize(item);
	}
	
	var ret=new Array(itemCount,item);		
	return ret;
}

function postData(args) {
	args+='&key='+GM_getValue('CDC_key',0)+'&username='+getUsername()+'&version='+GM_getValue('version',0)+'&dataCount='+dataCount;
	// stupid umlauts.  Replace them with their standard characters, the hard way.  There is, alas, no standard for this.
	args=args.replace(/�/g,'A');
	args=args.replace(/�/g,'E');
	args=args.replace(/�/g,'I');
	args=args.replace(/[��]/g,'O');
	args=args.replace(/�/g,'U');
	args=args.replace(/�/g,'Y');
	args=args.replace(/�/g,'a');
	args=args.replace(/�/g,'e');
	args=args.replace(/�/g,'i');
	args=args.replace(/[��]/g,'o');
	args=args.replace(/�/g,'u');
	args=args.replace(/�/g,'y');
	GM_log('sent:'+args);
	try {
	GM_xmlhttpRequest({
		method: 'POST',
		url: 'http://potatoengineer.110mb.com/dbaseInserter.php',
		headers: {'Content-type': 'application/x-www-form-urlencoded'},
		data: args,
		onerror: function(details) {
			var messages=$('messages').innerHTML;
			messages.value += "Data Collector error: "+details.status+"\n";
		},
		onload: function (responseDetails) {
			strHTML = responseDetails.responseText;
			if (strHTML.substr(0,8)=='Bad Key!') {
				//GM_log(strHTML);
				var msgHandle=$('messages');
				msgHandle.value="Your Big Brother key is bad, or the Big Brother data server is down.  Try re-entering your key.  (Palantir Cor if you don't have a key.)\n"+msgHandle.value;
			}
		}
	});
	}
	catch (ex) {
		GM_log('Posting error: '+ex);
	}
}

function parseMonsterName(nameLine) {
	if (/^You att/.test(nameLine)) {
		if (debug) GM_log('%'+nameLine+'%');
		// strip plurality (and the trailing period)
		if (/ \d+ /.test(nameLine)) {
			nameLine=nameLine.replace(/s\.?$/,'');
		}
		else nameLine=nameLine.replace(/\.$/,'');
		// strip the text line down to just the name, without numbers
		nameLine=nameLine.replace(/^You attack (the )?\d* ?/,'');
		//nameLine=nameLine.replace(/\.$/,''); //Already stripped trailing period.  This would mess with ROUSs.
		
	}
	else if (/misses|causes/.test(nameLine)) {
		var plural=false;
		pluralRegEx=/^(The )?\d+/;
		plural=pluralRegEx.test(nameLine);
		nameLine=nameLine.replace(/^(The )?\d* ?/,'');
		nameLine=nameLine.replace(/ causes \d+ points of damage\.| misses you\./,'');
		if (plural) nameLine=nameLine.replace(/s$/,'');
	}
	
	return specialMonsterParse(nameLine);
}

function specialMonsterParse(name) {
	// check against multi-named or user-named monsters
	var playerName=$('username').nextSibling.firstChild.innerHTML;
	if (name==playerName) return 'Mimic';
	else if (playerName.split( '' ).reverse().join( '' )==name) return 'Mirror Monster';
	else if (/Brute/.test(name))return 'Brute';
	else if (/Fairy$/.test(name))return 'Fairy';
	else if (/Whale$/.test(name))return 'Whale';
	else if (/Djinn$/.test(name))return 'Djinn';
	else if (/^(Sneaky|Merry|Feckless|Greasy|Naughty|Smelly) Thief/.test(name))return 'Thief (Small)';
	else if (/^(Skilled|Evil|Cunning|Clever|Cruel|Fiendish) Thief/.test(name))return 'Thief (Large)';
	else if (/Pheonix/.test(name))return 'Pheonix';
	else if (/Hydra/.test(name))return 'Hydra';
	else if (/^Lepr[aeiou]ch?[ao][ruw]n/.test(name)) return 'Leprechaun';
	else if (/Parrot/.test(name)) return 'Parrot';
	else if (/\(\d Cannon\)/.test(name)) return 'Pirate';
	else if (/Evil Ooze/.test(name)) return 'Evil Ooze';
	else if (name=='Dying Earth Worm') return 'Earth Worm';
	else if (name=='Sick Earth Worm') return 'Earth Worm';
	else if (name=='Disabled Tripod') return 'Tripod';
	else if (/^Huge Reptile/.test(name)) return 'Huge Reptile';
	else if (/the Snowman$/.test(name)) return 'Snowman';

	var terr=getTerrain();
	if ((terr=='Plain of Idavoll' || terr=='Valhalla') && /^(Hakan|(Guth|Sig|Jat|Og|Ey)mundr|(Thorbr|Stig|Br)andr|(Ma|Ar)ni|Ragnar|F(ing|ridj)olf|Karli|Olafr?|(Ingo|U|Brandu|A)lfr|(Herl|L)eif|Thorkell|Guttormr|Gamli|Snorri|Eirikr|Halfdan|Egil|(Thorb|Asb|Hallb|B)j.rn|Arnolf|St(ig|ein)|Gunnarr|Alfvin|Thengill|Svartr|Thrainn|Eithr|Ingvarr|Yngvarr)/.test(name)) return 'Einherjar';

	return name;
}

// this function should look over the four move/kill/cut buttons in the viewport and collect a list of confused monsters.  It will be invalid if the player is smoking the free-based dusted shrooms, so monster attacks won't update if the user is hallucinating.  Note that if you killed the monster, it isn't attacking, and thus its "confused" state is irrelevant.  Therefore, I don't have to save any data between page refreshes.  (fortunately!  I hate managing arrays via GM's simple stored variables.)
function getConfusedCritters() {
	var viewport=$('control_pane');
	var buttons=viewport.getElementsByTagName('input');
	var critters=new Array();
	var confusedRegEx=/^[Cc]onfused /;
	
	var xpathMonsters=xpath('.//div[contains(@class,"monster")][following-sibling::input]',$('control_pane'));
	for ( var i=0 ; i < xpathMonsters.length; i++ )
	{
		var monster=xpathMonsters[i].innerHTML;
		monster=monster.replace(/ \(\d+\)$/,''); //strip opal-vision, if any
		monster=monster.replace(/\d+ /,''); //strip number from number-monsters
		monster.replace(confusedRegEx,'');
		monster=specialMonsterParse(monster); // it needs to be recognizeable later, so standardize its name
		critters.push(monster);
	}
	return critters;
}

function isConfused (testName) {
	var confused=getConfusedCritters();
	for (i=0;i>confused.length;i++) {
		if (testName==confused[i]) {
			if (debug) GM_log('None for me, thanks, this '+testName+' is confused.');
			return true;
		}
	}
	return false;
}

// nigh-identical to isConfused.  Now I'm tempted to re-work that one.
function isSnowman (testName) {
	var snowmen=getSnowmen();
	if (debug) GM_log('Number of snowmen: '+snowmen.length);
	for (i=0;i>snowmen.length;i++) {
		if (testName==snowmen[i]) {
			if (debug) GM_log('This '+testName+' is a snowman!  How dare it report a random name!');
			return true;
		}
	}
	return false;
}

// a simpler version of getConfusedCritters.  It simply collects all of the surrounding monsters, and does a much simpler version of processing, because we want the raw monster name to test against the raw name from the messagebox.
// A snowman is defined as this: a monster that constains a span whose style starts with "font-size: ".  Most monsters don't contain spans, so this should be good enough.
function getSnowmen() {
	var viewport=$('control_pane');
	var buttons=viewport.getElementsByTagName('input');
	var critters=new Array();
	var monsterRegEx=/^monster/;
	var fightRegEx=/^act_fight/;
	//GM_log('buttons.length:'+buttons.length);
	
	// I rock xpath. (Took me long enough.)
	var xpathMonsters=xpath('.//div[contains(@class,"monster")][following-sibling::input]/span[starts-with(@style,"font-size")]',$('control_pane'));
	for ( var i=0 ; i < xpathMonsters.length; i++ )
	{
		var monster=xpathMonsters[i].innerHTML;
		monster=monster.replace(/ \(\d+\)$/,''); //strip opal-vision, if any
		critters.push(monster);
	}
	return critters;
}

function makeDisp() {
	var dispfrag = document.createDocumentFragment();
	dispfrag.appendChild(document.createTextNode(" "));
	configbtn = document.createElement("input");
	configbtn.setAttribute("type", "button");
	configbtn.setAttribute("class", "button");
	configbtn.setAttribute("value", "Configure");
	configbtn.id='BigBrotherButton';
	
	configbtn.addEventListener(
		'click', function(){toggleConfig('BigBrother');}, true);
	dispfrag.appendChild(configbtn);

	dispfrag.appendChild(document.createTextNode(" "));
	
	
	linkToStats = document.createElement("input");
	linkToStats.setAttribute("type", "button");
	linkToStats.setAttribute("class", "button");
	linkToStats.setAttribute("value", "View Statistics");
	linkToStats.addEventListener('click',function(event) {window.open('http://potatoengineer.110mb.com/dropStats.php');},true);
	dispfrag.appendChild(linkToStats);
	
	// I'm not sure why, but keys get deleted from time to time.  It might be people reinstalling and forgetting the key, or it might be something else, but I'll do some "mild" bugging if your key is 0.  This is stolen from the tribble alert script, so it ain't exactly subtle.
	if (!GM_getValue('CDC_key',0)) {
		var bigHonkinWarning=document.createElement("div");
		bigHonkinWarning.innerHTML+='<div style="border: 2px solid red; color: red; font-weight: bold; padding: 1em; background: white">You have no data-reporting key!</div>';
		dispfrag.appendChild(bigHonkinWarning);

	}

	return dispfrag;
}

function makeConfig() {
	var confdiv = document.createElement("div");
	confdiv.class='controls';
	confdiv.id='BigBrother';

	var keyinp = document.createElement("input");
	keyinp.setAttribute("type", "text");
	keyinp.setAttribute("name", "keyinp");
	if(key)
	{
		keyinp.setAttribute("value", key);
	}
	
	var okbtn = document.createElement("input");
	okbtn.setAttribute("type", "button");
	okbtn.setAttribute("class", "button");
	okbtn.setAttribute("value", "OK");
	okbtn.addEventListener(
		'click',
		function(event)
		{
			if(keyinp.value != "")
			{
				GM_setValue("CDC_key", keyinp.value);
				alert("Key set.");
			}
			else
			{
				alert("Invalid key - please palantir Cor for one!");
			}
		},
		true);


	confdiv.appendChild(document.createElement("hr"));
	confdiv.appendChild(document.createTextNode("Key: "));
	confdiv.appendChild(keyinp);
	confdiv.appendChild(document.createTextNode(" "));
	confdiv.appendChild(okbtn);
	
	var debugCB=document.createElement('input');
    debugCB.setAttribute("type","checkbox");
    debugCB.setAttribute("name","Debug");
    debugCB.checked=GM_getValue('debug',false);
	debugCB.addEventListener(
		'click',
		function(event)
		{
			GM_setValue('debug',debugCB.checked);
			debug=GM_getValue('debug');	// that's a global variable declared at the top.
			GM_log('debug value is now '+GM_getValue('debug'));
		},
		true);
	confdiv.appendChild(document.createTextNode(" Debug Mode: "));
    confdiv.appendChild(debugCB);
	confdiv.appendChild(document.createTextNode(' Version '+GM_getValue('version')));
	
	confdiv.setAttribute("style", "display:none");
	return confdiv;
}


// and now for stolen functions from Cities Outfitter.  Oh woe!  I am plagiarizing myself!
// well, aside from the massive slashing I did.  If this section seems occasionally inefficient, it's because I was programming it when I really should have been sleeping.  I have a habit of doing that.
function countEquipment() {
	var equipDiv=$('equipment');
	
	if (equipDiv==null) return 0;	//if we didn't find equipment, there ain't none.
	
	var equipHTML=equipDiv.innerHTML;
	var numRegEx=/\d{1,3}/;
	var numEquipped=numRegEx.exec(equipHTML);
	//GM_log('amount of equipment: '+numEquipped+' raw value: '+$('equipment').innerHTML);
	//GM_log(parseInt(numEquipped)+1);
	return parseInt(numEquipped);
}

function findEquipmentDiv() {
	var onEquipmentScreen=document.getElementsByTagName('h1');
	if (onEquipmentScreen.length!=0 && onEquipmentScreen[0].innerHTML=='Equipped Items') {
		return $('control_pane');
	}
	var equipPaneButton=document.forms.namedItem('controls').elements.namedItem('act_eqpane');
	if (equipPaneButton) return $('equipment');
	
	return null;
}

function readEquipmentDiv(equipmentDiv) {
	//var divs=equipmentDiv.getElementsByTagName('div');
	var spans=new Array();
	
	// there's probably some xpath method to grab the first span of each div, but the 30 seconds I devoted to glancing at xpath seem to say that you grab EVERYTHING.
	// the xpath would be something like "div/span[@class='control_title']", applied only to the equipment div, though that identifies by class rather than "first span of each div".  But this works, even with horizontal blings and disguise kits, so run with it.  (side note: apparently, it doesn't work; there's some blanks and an href showing up in equipment.)
	var spanIter = xpath("div/span[@class='control_title']", equipmentDiv);
	if (debug) GM_log('number of spans:'+spanIter.length);
	for ( var i=0 ; i < spanIter.length; i++ ) {
		spans.push(spanIter[i]);
		if (debug) GM_log('snapshot item '+i+':'+spanIter[i].innerHTML);
	}
	
	// old method.  Better? kinda yes, kinda no; it grabs the first child span of each div.  Worse in that it doesn't check for finding controls, better in that it only looks at the FIRST child.
	/*for (var i=0;i<divs.length;i++) {
		try {
			if (divs[i].firstChild.nodeName=='SPAN') spans.push(divs[i].firstChild);
		}
		catch (ex) {} // flow control by exceptions. (bad spans get ignored) Horrible, horrible stuff.  Effective, though.
	}*/
	//GM_log('number of form[0] elements: '+document.forms[0].elements.length+'  Viewport inputs: '+inputs.length);
	var numberRegEx=/ x (\d+):/;
	var itemName;
	var rawCount;
	var multiplicity;
	var totalItems=0;
	var stripParenRegEx=/ \(.+/;
	
	// read over all the viewport buttons, and collect all the names.
	for (var i=0;i<spans.length ;i++) {
		//GM_log('span '+i+': '+spans[i].innerHTML);
		itemName=spans[i].firstChild.innerHTML;
		itemName=itemName.replace(stripParenRegEx,'');	// strip trailing parentheticals (just for T-Shirts & pets at the moment)
		itemName=itemName.replace(/^Glowing /,'');	// strip "glowing".  I swear, this is SO annoying...
		if (!wearsWithMovement(itemName)) continue;	// if it doesn't wear, it isn't worth tracking.
		
		rawCount=numberRegEx.exec(spans[i].innerHTML);
		if (rawCount) multiplicity=parseInt(rawCount[1]);
		else multiplicity=1;
		
		GM_setValue('wornItem'+getUsername()+totalItems,itemName);
		GM_setValue('wornItemCount'+getUsername()+totalItems, multiplicity);
		totalItems++;
		if (debug) GM_log('Found item in equip:'+itemName+multiplicity);
	}
	GM_setValue('totalItems'+getUsername(),totalItems);
}

// the function name is positive (does it wear with movement? YES!), but I'm going to test this only against the negatives, and then invert.  This should, in theory, mean that if a new, unknown item emerges, it'll get tracked.
//NOTE: Gold bling is tracked, but it'll be tracked much MORE once I figure out how to notice about 95% of the "use AP" methods.  Notably, crafting and quest-acting need to be added.  (most of the rest are already handled above, so datacount!=0 is good.)
var noWearWithMovement;
function wearsWithMovement(item) {
	var lumpedEquip=/^Jumper|^(Green|Blue|Red|Yellow) Party Hat|^Pirates? Hat|^Wizards? Hat/.test(item);
	if (lumpedEquip) return false;
	if (isPet(item)) return false;
	if (isArmor(item)) return false;

	if (!noWearWithMovement) noWearWithMovement = {"Baseball Cap":true,"Bonnet":true,"Bowler Hat":true,"Christmas Hat":true,"Crown":true,"Guard Hat":true,"Jesters Hat":true,"Lady Caroline Hat":true,"Naughty Hat":true,"Straw Hat":true,"Top Hat":true,"Red Hat Fedora":true,"Goldfish":true,"Kittin":true,"Kat":true,"Big Kat":true,"Rock":true,"Goose":true,"Pwny":true,"Ferret":true,"Bebe Goggles":true,'Legendary Hat':true,'Hot Swedish-Chick Hair':true};
	if (noWearWithMovement[item]) return false;
	return true;
}

function updateEquipment() {
	if (countEquipment()>0) {
		var equipDiv=findEquipmentDiv();
		if (equipDiv) readEquipmentDiv(equipDiv);
	}
	if (debug) {
		for (var i=0;i<GM_getValue('totalItems'+getUsername());i++) {
		if (debug) GM_log(GM_getValue('wornItemCount'+getUsername()+i)+','+GM_getValue('wornItem'+getUsername()+i));
		}	
	}
}

// something wore out or was taken off, so remove it from the list to be tracked.
function removeItem(item, count) {
	if (count==undefined) count=1; //This is a brutish hack to make a default value if nothing is passed.  There are other ways to do it, and they are, if anything, more awkward than this.

	if (debug) GM_log('trying to wear out/remove something:'+item+count);
	var totalItems=GM_getValue('totalItems'+getUsername(),0);
	for (var i=0;i<totalItems;i++) {
		if (GM_getValue('wornItem'+getUsername()+i)==item) {
			// if there's only one, then delete the item by shuffling the entire array back by one spot.
			if (GM_getValue('wornItemCount'+getUsername()+i)<=count) {
				for (var j=i;j<totalItems-1;j++) {
					GM_setValue('wornItem'+getUsername()+j,GM_getValue('wornItem'+getUsername()+(j+1)));
					GM_setValue('wornItemCount'+getUsername()+j,GM_getValue('wornItemCount'+getUsername()+(j+1)));
				}
				totalItems--;
				GM_setValue('totalItems'+getUsername(),totalItems);
				i--;
				if (debug) GM_log('completely taking off item:'+item+count);
			}
			// if there's more than one, just decrement the item count.
			else {
				GM_setValue('wornItemCount'+getUsername()+i,GM_getValue('wornItemCount'+getUsername()+i)-count);
				if (debug) GM_log('taking off only some:'+item+count);
			}
			return true;
		}
	}
	return false;
}

function putOnItem(item, count) {
	if (count==undefined) count=1; //This is a brutish hack to make a default value if nothing is passed.  There are other ways to do it, and they are, if anything, more awkward than this.

	var totalItems=GM_getValue('totalItems'+getUsername(),0);
	var foundItem=false;
	for (var i=0;i<totalItems;i++) {
		if (GM_getValue('wornItem'+getUsername()+i)==item) {
			// if it exists already, just increment the count.
			GM_setValue('wornItemCount'+getUsername()+i,GM_getValue('wornItemCount'+getUsername()+i)+count);
			foundItem=true;
			if (debug) GM_log('wearing existing item:'+item+count);
		}
	}
	// if it isn't in the list, stick it at the end.
	if (!foundItem) {
		GM_setValue('wornItem'+getUsername()+totalItems,item);
		GM_setValue('wornItemCount'+getUsername()+totalItems,count);
		GM_setValue('totalItems'+getUsername(),totalItems+1);
		if (debug) GM_log('wearing new item:'+item+count);
	}
}

function getItemCount(item) {
	for (var i=0;i<GM_getValue('totalItems'+getUsername(),0);i++) {
		if (GM_getValue('wornItem'+getUsername()+i)==item) {
			// if it exists already, just increment the count.
			return GM_getValue('wornItemCount'+getUsername()+i,0);
		}
	}
	return 0;
}
