/<?php
include('HTTPheader.php');
include('logger.php');

include('dbConnect.php');

// here's a wild shot at a completely bare-bones error reporting method: just write everything in a try, and on catch, log the error.
try {

	// the big question: stuff variables into the URL, or the body?  Body.  It'll be good practice.
	$http_request = new http_request();
	$content_type = $http_request->header('Content-type');
	// I'm not sure why, but I'm getting an odd case where the content-type string includes the next attribute as well.
	if (!preg_match('%^application/x-www-form-urlencoded%',$content_type)) {
		logError('Away wi\' ye, impostor!'.$content_type);
		die('Away wi\' ye, impostor!'.$content_type);
	}
	//else {
	//	logError($http_request->body()."\n");
	//}
	//don't forget: preg_replace is ALWAYS global.  Bloody PHP.
	$body = $http_request->body();
	$body=preg_replace('/[;<>,]/','',$body); // make it secret.  make it safe!
	
	$idKey=getVar('key',$body);
	$query="SELECT id_key FROM validation WHERE id_key='$idKey'";
	$results = mysql_query($query) or logErrorAndDie('Error, getting key failed'.mysql_error()."\n".$http_request->body()."\n");
	$row=mysql_fetch_assoc($results);
	if ($row['id_key']!=$idKey) {
		echo 'Bad Key!';
		logErrorAndDie('invalid key! Bad:'.$idKey."\n".$http_request->body()."\n");
	}
	//else logError('good key:'.$idKey."\n");
	
	$version=getVar('version',$body);
	$script=getVar('script',$body);
	if ($version<3.0 && !($version>=1.0 && $script=='GoldenCondorNavigator')) logErrorAndDie('Old version!  UPDATE, HEATHEN!');
	
	$dataScore=0;	// let the competition begin!
	
	$commands=preg_split("/%/",$body);
	$username=getVar('username',$body);
	$totalSuccess=true;
	foreach ($commands as $content) {
		$dataType=getVar('dataType',$content);
		$success=true;
		// MONSTER ACCURACY: TESTED AND FUNCTIONAL.
		// for monster accuracy results, we need two bits of information: number of attacks, and number of hits.
		if ($dataType=='MonsterAccuracy') {
			mysql_query('START TRANSACTION');
			
			$critter=getVar('monster',$content);
			$hits=getVar('hits',$content);
			$attacks=getVar('attacks',$content);
			//logError('datatype='.$dataType.' monster='.$critter.' hits='.$hits.' attacks='.$attacks."\n");
		
			$query = "SELECT hits,attacks FROM monster_accuracy WHERE monster='$critter'";
			//logError($query."\n");
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$oldHits=$row['hits'];
				$oldAttacks=$row['attacks'];
				$newHits=$oldHits+$hits;
				$newAttacks=$oldAttacks+$attacks;
				$query="UPDATE monster_accuracy SET hits='$newHits',attacks='$newAttacks' WHERE monster='$critter'";
				mysql_query($query) or $success=false;
	
				if ($row['hits']<100*$attacks) {
					$dataScore+=3;
				}
				else if ($row['hits']<1000*$attacks) {
					$dataScore+=2;
				}
				else $dataScore+=1;
			}
			else {
				$newHits=$hits;
				$newAttacks=$attacks;
				$query = "INSERT INTO monster_accuracy (monster, hits, attacks) VALUES ('$critter', '$newHits', '$newAttacks')";
				$dataScore+=10;
				mysql_query($query) or $success=false;
			}
			if ($success) {
				mysql_query('COMMIT');
				logData('   Monster Accuracy Data: '.$critter.' hit?'.$hits.' attacks:'.$attacks."\n");
			}
			else {
				logError('Monster accuracy rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// MONSTER DROPS: TESTED AND FUNCTIONAL.
		// per monster, or aggregate?  Per monster means some moderately serious computation to get results, and some kind of don't-update-too-often script on the display page (plus increasing load time as the data increases).  Aggregate means losing out on finding clusters of equipment.
		// compromise: each monster gets a table (or just one big table, where each row is a monster and one item it drops?), but each item row also gets a "minimum dropped", "maximum dropped", and "average dropped" statistic.
		// further compromise: also add a row for "minimum item types dropped","maximum item types dropped", and "total number of item types dropped" (for processing into an average).
		elseif ($dataType=='MonsterDrop') {
			mysql_query('START TRANSACTION');
			$monster=getVar('monster',$content);
			$itemTypes=getVar('itemTypes',$content);
			$itemCounts=array();
			$items=array();
			$kills=0;
			//logError('found monster drop.  monster='.$monster.', itemTypes='.$itemTypes."\n");
			for ($i=0;$i<$itemTypes;$i++) {
				$itemCounts[]=getVar('count'.$i,$content);
				$items[]=getVar('item'.$i,$content);
			}
			// update/insert row for total number of $monster killed
			$query="SELECT count FROM monster_kills WHERE monster='$monster'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$kills=$row['count']+1;
				$query="UPDATE monster_kills SET count='$kills' WHERE monster='$monster'";
				mysql_query($query) or $success=false;
		
				if ($row['count']<100) {
					$dataScore+=3;
				}
				else if ($row['count']<1000) {
					$dataScore+=2;
				}
				else $dataScore+=1;
			}
			else {
				$query = "INSERT INTO monster_kills (monster,count) VALUES ('$monster','1')";
				mysql_query($query) or $success=false;
				$kills=1;
				
				$dataScore+=10;
			}
			//quick insert for timestampy method.
			mysql_query("INSERT INTO monster_kills_timed (monster) VALUES('$monster')");
			
			// update/insert rows for individual items for a monster
			for ($i=0;$i<$itemTypes;$i++) {
				$query="SELECT count, maxDrop, minDrop, totalDrop FROM monster_drop WHERE monster='$monster' AND item='$items[$i]'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					//logError('found item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$maxDrops=$row['maxDrop'];
					$minDrops=$row['minDrop'];
					$totalDrops=$row['totalDrop']+$itemCounts[$i];
					if ($maxDrops<$itemCounts[$i]) $maxDrops=$itemCounts[$i];
					if ($minDrops>$itemCounts[$i]) $minDrops=$itemCounts[$i];
					$count=$row['count']+1;
	
					if ($kills==0) $kills=1;
					$pEstimate=$count/$kills;
					if ($pEstimate>1.0) $pEstimate=1.0;	//gold in treasure chests was giving a funny number.
					$deviation=sqrt($pEstimate*(1.0-$pEstimate)/$kills);
					// quick hack to make 100% with one hit a small number.  0% and 100% kinda fall apart with the standard deviation calculations.
					if ($deviation==0) $deviation=1.0/$kills;
					$probability=$pEstimate-$deviation;
					if ($probability <0) $probability=0;
	
	
					$query="UPDATE monster_drop SET maxDrop='$maxDrops',minDrop='$minDrops',count='$count', totalDrop='$totalDrops', probability='$probability' WHERE monster='$monster' AND item='$items[$i]'";
					mysql_query($query) or $success=false;
				}
				else {
					//logError('didn\'t find item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query = "INSERT INTO monster_drop (monster,item,count,maxDrop,minDrop,totalDrop, probability) VALUES ('$monster','$items[$i]','1','$itemCounts[$i]','$itemCounts[$i]', '$itemCounts[$i]','0')";
					mysql_query($query) or $success=false;
					
					$dataScore+=1;
				}
				// the new, ultra simple, ultra easy method with the timed monster-drop database.  Easy on insert, more painful on withdrawal, but now I can, in theory, track stuff over time.
				mysql_query("INSERT INTO monster_drop_timed (monster, item, count) VALUES ('$monster','$items[$i]','$itemCounts[$i]')") or $success=false;
			}
			// update/insert row for total number of item types in a drop
			$query="SELECT count, maxDrop, minDrop, totalDrop FROM monster_drop WHERE monster='$monster' AND item='different items'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$maxDrops=$row['maxDrop'];
				$minDrops=$row['minDrop'];
				$totalDrops=$row['totalDrop']+$itemTypes;
				if ($maxDrops<$itemTypes) $maxDrops=$itemTypes;
				if ($minDrops>$itemTypes) $minDrops=$itemTypes;
				$count=$row['count']+$itemTypes;
				$query="UPDATE monster_drop SET maxDrop='$maxDrops',minDrop='$minDrops',count='$count' WHERE monster='$monster' AND item='different items'";
				mysql_query($query) or $success=false;
			}
			else {
				$query = "INSERT INTO monster_drop (monster,item,count,maxDrop,minDrop) VALUES ('$monster','different items','$itemTypes','$itemTypes','$itemTypes')";
				mysql_query($query) or $success=false;
			}
			// new, easy method of counting item types per monster thingummy with timed table.
			mysql_query("INSERT INTO monster_drop_timed (monster, item, count) VALUES('$monster','different items','$itemTypes')") or $success=false;
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Monster Drop Data: '.$monster.' item types:'.$itemTypes."\n");
			}
			else {
				logError('Monster Drops rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// MINING TESTED & FUNCTIONAL.
		// terrain, item.  Do mines screw with the crags/mountains/etc label?
		// ooh.  Add a bit for mines, too, to check how long they last.  In theory, a simple count of mines destroyed vs. mines used would work, if everyone used it, and it'll be asymptotically true if some people use it.
		elseif ($dataType=='Mining') {
			mysql_query('START TRANSACTION');
			$terrain=getVar('terrain',$content);
			if ($terrain=='Unknown Oz Mine') continue;
			if ($terrain=='Outback Outcrop') $terrain='Outcrop';
			$item=getVar('item',$content);
		
			//logError('entering mining');
			$query="SELECT BronzeOre, CopperOre, GoldOre, IronOre, LeadOre, SilverOre, Stone, BitofGold, Flint, Scone FROM mining WHERE Terrain='$terrain'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			//logError('got previous record');
			if (!$row) {
				//logError("didn't find $terrain");
				$query = "INSERT INTO mining (Terrain, BronzeOre, CopperOre,GoldOre,IronOre,LeadOre,SilverOre,Stone,BitofGold, Flint, Scone) VALUES ('$terrain','0', '0','0','0','0','0','0','0','0','0')";
				mysql_query($query) or $success=false;
				$oreCount=1;
			}
			else {
				$oreCount=$row["$item"]+1;
				
				$dataScore+=7;
			}
			//logError('mining ore: '.$oreCount.'  '.$item."\n");
			$query="UPDATE mining SET $item='$oreCount' WHERE Terrain='$terrain'";
			mysql_query($query) or $success=false;
			
			if ($oreCount<30) {
				$dataScore+=6;
			}
			else if ($oreCount<300) {
				$dataScore+=4;
			}
			else $dataScore+=2;
				
			if ($success) {
				mysql_query('COMMIT');
				logData('   Mining Data: '.$terrain.' item:'.$item."\n");
			}
			else {
				logError('Mining rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
	
		// FISHING: TESTED AND FUNCTIONAL.
		// item: fish or boot.  
		// updated to a) modernize, b) get all data, and c) handle fishing from Murky Lakes.
		elseif ($dataType=='Fishing') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$terrain=getVar('terrain',$content);
			$alignment=getVar('alignment',$content);
			if (!$alignment || !$item || !$terrain) continue;	// this section was just updated in BB, so keep it clean.
			$query = "INSERT INTO fishing (item, count, terrain, alignment) VALUES ('$item', '1','$terrain','$alignment') ON DUPLICATE KEY UPDATE count=count+1";
			$results = mysql_query($query) or $success=false;
			
			$results=mysql_query("SELECT SUM(count) as total FROM fishing WHERE terrain='$terrain'");
			$row=mysql_fetch_assoc($results);
			if ($row) {
				if ($row['total']<100) {
					$dataScore+=9;
				}
				else if ($row['total']<1000) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			if ($success) {
				mysql_query('COMMIT');
				logData('   Fishing Data: '.$item.' '.$terrain."\n");
			}
			else {
				logError('Fishing rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// FARMING: TESTED AND FUNCTIONAL.
		// alignment, terrain, item or beanstalk.  (just call a beanstalk another item for data purposes.)
		elseif ($dataType=='Farming') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$terrain=getVar('terrain',$content);
			if ($item=='Beanstalk') $foundBeanstalk=1;
			//logError('Farming found: got a '.$item.' Beanstalk:'.$foundBeanstalk."\n");
			$query="SELECT * FROM beanstalk WHERE terrain='$terrain'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			// beanstalk and total plantings update
			if ($row) {
				$newTotal=$row['total']+1;
				$newBeanstalks=$row['beanstalks']+$foundBeanstalk;
				$query="UPDATE beanstalk SET total='$newTotal', beanstalks='$newBeanstalks' WHERE terrain='$terrain'";
				mysql_query($query) or $success=false;
			}
			else {
				logError('didn\'t find beanstalk row, creating beanstalk');
				$query = "INSERT INTO beanstalk (beanstalks,total, terrain) VALUES ('$foundBeanstalk', '1','$terrain')";
				mysql_query($query) or $success=false;
			}
			//logError('finished beanstalk.  Now for general planting');
			// non-beanstalk item update
			if ($item!='Beanstalk') {
				//logError('found general planting');
				$alignment=getVar('alignment',$content);
				$count=getVar('count',$content);
				//logError('updating non-beanstalkiness. alignment:'.$alignment.' terrain:'.$terrain."\n");
				$query="SELECT count, minDrop, maxDrop, totalDrop FROM farming WHERE alignment='$alignment' AND terrain='$terrain' AND item='$item'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					$newCount=$row['count']+1;
					$total=$row['totalDrop']+$count;
					if ($row['minDrop']>$count) $minDrop=$count;
					else $minDrop=$row['minDrop'];
					if ($row['maxDrop']<$count) $maxDrop=$count;
					else $maxDrop=$row['maxDrop'];
					
					$query="UPDATE farming SET count='$newCount', minDrop='$minDrop', maxDrop='$maxDrop', totalDrop='$total' WHERE alignment='$alignment' AND terrain='$terrain' AND item='$item'";
					mysql_query($query) or $success=false;
					if ($count<100) {
						$dataScore+=6;
					}
					else if ($count<1000) {
						$dataScore+=4;
					}
					else $dataScore+=2;
					
				}
				else {
					$query="INSERT INTO farming (count, minDrop, maxDrop, totalDrop, alignment, terrain, item) VALUES ('1','$count','$count','$count','$alignment','$terrain','$item')";
					mysql_query($query) or $success=false;
					$dataScore+=20;
				}
			}
			
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Farming Data: '.$terrain.' item:'.$item."\n");
			}
			else {
				logError('farming rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		//FORAGING: TESTED AND FUNCTIONAL.
		elseif ($dataType=='Foraging') {
			mysql_query('START TRANSACTION');
			//logError('found general planting');
			$alignment=getVar('alignment',$content);
			$terrain=getVar('terrain',$content);
			$item=getVar('item',$content);
			//logError('updating non-beanstalkiness. alignment:'.$alignment.' terrain:'.$terrain."\n");
			$query="SELECT count FROM foraging WHERE terrain='$terrain' AND item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE foraging SET count='$count' WHERE terrain='$terrain' AND item='$item'";
				mysql_query($query) or $success=false;
				
				if ($count<100) {
					$dataScore+=12;
				}
				else if ($count<1000) {
					$dataScore+=8;
				}
				else $dataScore+=4;
			}
			else {
				$query="INSERT INTO foraging (count, terrain, item) VALUES ('1','$terrain','$item')";
				mysql_query($query) or $success=false;
				
				$dataScore+=40;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Foraging Data: '.$terrain.' item types:'.$item."\n");
			}
			else {
				logError('Foraging rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		//FLYING HEALER STATIONS
		elseif ($dataType=='FlyingHealer') {
			mysql_query('START TRANSACTION');
			$healer=getVar('status',$content);
			//logError('status: '.$healer);
			$query="SELECT heal, count FROM flying_healer";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				//logError("old heal: ".$row['heal']." old count: ".$row['count']."\n");
				$heal=$row['heal'];
				$count=$row['count']+1;
				if ($healer=='heal') $heal=$heal+1;
				//logError("new heal: ".$heal." new count: ".$count." \n");
				$query="UPDATE flying_healer SET heal='$heal',count='$count'";
				mysql_query($query) or $success=false;
				
				if ($count<50) {
					$dataScore+=15;
				}
				else if ($count<500) {
					$dataScore+=10;
				}
				else $dataScore+=5;
			}
			else {
				if ($healer=='heal') $heal=1;
				else $heal=0;
				$query="INSERT INTO flying_healer (count, heal) VALUES ('1','$heal')";
				mysql_query($query) or $success=false;
				
				$dataScore+=50;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Flying Healer Data: '.$heal."\n");
			}
			else {
				logError('Flying Healer rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		
		//MILKING
		elseif ($dataType=='Milking') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$location=getVar('location',$content);
			
			if ($item=='Pint of Cream') $cream=1;
			else $cream=0;
			
			$query="SELECT count,cream FROM milking WHERE location='$location'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$oldCream=$row['cream'];
				$newCream=$oldCream+$cream;
				$oldCount=$row['count'];
				$newCount=$oldCount+1;
				$query="UPDATE milking SET count='$newCount',cream='$newCream' WHERE location='$location'";
				mysql_query($query) or $success=false;
				if ($newCount<100) {
					$dataScore+=2;
				}
				else $dataScore+=1;
			}
			else {
				$query = "INSERT INTO milking (location, cream, count) VALUES ('$location', '$cream','1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=10;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Milking Data: '.$location.'  cream:'.$cream."\n");
			}
			else {
				logError('Milking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		//WEAPON BREAKAGE
		elseif ($dataType=='WeaponBreak') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$break=getVar('break',$content);
			$attacks=getVar('attacks',$content);
			$bane=getVar('bane',$content);
			$isWeapon=getVar('isWeapon',$content);
			if ($break!='false') $breakCount=1;
			else $breakCount=0;
			
			$pillState=getVar('bluePill',$content);
			
			$query="SELECT count,breaks,is_weapon, is_bane, pill_state FROM weapon_break WHERE item='$item' AND is_bane=$bane AND pill_state='$pillState'";
			$results = mysql_query($query) or logErrorAndDie('Error, getting previous weapon break info failed'.mysql_error()."\n");
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+$attacks;
				$newBreaks=$row['breaks']+$breakCount;
				if ($row['is_weapon']) $isWeapon='true';	// once a weapon, always a weapon.  I'm getting some weird results with weapons becoming non-weapons.  It probably has to do with the lag between the first data appearing on the screen and the execution of BB.
				$query="UPDATE weapon_break SET count='$newCount',breaks=$newBreaks, is_weapon=$isWeapon WHERE item='$item' AND is_bane=$bane AND pill_state='$pillState'";
				mysql_query($query) or $success=false;
				
				if ($breakCount==1) {
					$dataScore+=5;
				}
				else if ($count<50) {
					$dataScore+=2;
				}
				else if ($count<500) {
					$dataScore+=1;
				}
	
			}
			else {
				// isWeapon does no harm here if it's blank.
				$query = "INSERT INTO weapon_break (item, count, breaks, is_weapon, is_bane, pill_state) VALUES ('$item', '$attacks', '$breakCount','$isWeapon',$bane,'$pillState')";
				mysql_query($query) or $success=false;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Weapon Breaking Data: '.$item.'  attacks:'.$attacks.' broke:'.$breakCount."\n");
			}
			else {
				logError('Weapon-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		//WOOD-GATHERING FRUITS & ACORNS
		elseif ($dataType=='Wood') {
			mysql_query('START TRANSACTION');
			$terrain=getVar('terrain',$content);
			$itemTypes=getVar('itemTypes',$content);
			$itemCounts=array();
			$items=array();
			//logError('gathered wood.  terrain='.$terrain.', itemTypes='.$itemTypes."\n");
			for ($i=0;$i<$itemTypes;$i++) {
				$itemCounts[]=getVar('count'.$i,$content);
				$items[]=getVar('item'.$i,$content);
			}
			// update/insert rows for individual items in gathering
			for ($i=0;$i<$itemTypes;$i++) {
				$query="SELECT count FROM wood_gathering WHERE terrain='$terrain' AND item='$items[$i]'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					//logError('found item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$count=$row['count']+$itemCounts[$i];
					$query="UPDATE wood_gathering SET count='$count' WHERE terrain='$terrain' AND item='$items[$i]'";
					mysql_query($query) or $success=false;
				}
				else {
					//logError('didn\'t find item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query = "INSERT INTO wood_gathering (terrain,item,count) VALUES ('$terrain','$items[$i]','$itemCounts[$i]')";
					mysql_query($query) or $success=false;
				}
			}
			// update/insert row for total number of $terrain gatherings
			$query="SELECT count FROM gathering_count WHERE terrain='$terrain'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE gathering_count SET count='$count' WHERE terrain='$terrain'";
				mysql_query($query) or $success=false;
				
				if ($count<50) {
					$dataScore+=15;
				}
				else if ($count<500) {
					$dataScore+=10;
				}
				else $dataScore+=5;
				
			}
			else {
				$query = "INSERT INTO gathering_count (terrain,count) VALUES ('$terrain','1')";
				mysql_query($query) or $success=false;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Wood-gathering Data: '.$terrain."\n");
			}
			else {
				logError('wood-gathering rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// CORNUCOPIA, FOOD
		elseif ($dataType=='CornucopiaFood') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$query="SELECT count FROM cornucopia_food WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE cornucopia_food SET count='$newCount' WHERE item='$item'";
				mysql_query($query) or $success=false;
				if ($newCount<10) {
					$dataScore+=6;
				}
				else if ($newCount<100) {
					$dataScore+=4;
				}
				else $dataScore+=2;
			}
			else {
				$query = "INSERT INTO cornucopia_food (item, count) VALUES ('$item', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Cornucopia Food Data: '.$item."\n");
			}
			else {
				logError('Cornucopia food rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// CORNUCOPIA, DRINK
		elseif ($dataType=='CornucopiaDrink') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$query="SELECT count FROM cornucopia_drink WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE cornucopia_drink SET count='$newCount' WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=6;
				}
				else if ($newCount<100) {
					$dataScore+=4;
				}
				else $dataScore+=2;
			}
			else {
				$query = "INSERT INTO cornucopia_drink (item, count) VALUES ('$item', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Cornucopia Drink Data: '.$item."\n");
			}
			else {
				logError('Cornucopia drinks rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// ANGRY BOW UPGRADES
		elseif ($dataType=='AngryUpgrade') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$query="SELECT count FROM angry_upgrade WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE angry_upgrade SET count='$newCount' WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<20) {
					$dataScore+=30;
				}
				else if ($newCount<200) {
					$dataScore+=20;
				}
				else $dataScore+=10;
				
			}
			else {
				$query = "INSERT INTO angry_upgrade (item, count) VALUES ('$item', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=100;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Angry Upgrade Data: '.$item."\n");
			}
			else {
				logError('Angry Bow Upgrades rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// ARMOR BREAKAGE
		elseif ($dataType=='ArmorBreak') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$break=getVar('break',$content);
			if ($break=='true') $breakCount=1;
			else $breakCount=0;
			
			$pillState=getVar('bluePill',$content);
			
			$query="SELECT count,breaks FROM armor_break WHERE item='$item' AND pill_state='$pillState'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$newBreaks=$row['breaks']+$breakCount;
				$query="UPDATE armor_break SET count='$newCount',breaks=$newBreaks WHERE item='$item' AND pill_state='$pillState'";
				mysql_query($query) or $success=false;
				
				if ($newCount<100) {
					$dataScore+=3;
				}
				else if ($newCount<1000) {
					$dataScore+=2;
				}
				else $dataScore+=1;
			}
			else {
				$query = "INSERT INTO armor_break (item, count, breaks, pill_state) VALUES ('$item', '1', '$breakCount','$pillState')";
				mysql_query($query) or $success=false;
				
				$dataScore+=10;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Armor Breakage Data: '.$item."\n");
			}
			else {
				logError('Armor-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// DAEMONIC WEAPON MELTING: DONE
		elseif($dataType=='DaemonicMelt') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$melt=getVar('melt',$content);
			if ($melt=='true') $meltCount=1;
			else $meltCount=0;
			$query="SELECT count,melts FROM daemonic_melt WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$newMelts=$row['melts']+$meltCount;
				$query="UPDATE daemonic_melt SET count='$newCount',melts=$newMelts WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<20) {
					$dataScore+=9;
				}
				else if ($newCount<200) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query = "INSERT INTO daemonic_melt (item, count, melts) VALUES ('$item', '1', '$meltCount')";
				mysql_query($query) or $success=false;
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Daemonic Weapon Data: '.$item."\n");
			}
			else {
				logError('Daemonic Melting rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// SEARCHING STOREROOMS
		elseif ($dataType=='Searching') {
			mysql_query('START TRANSACTION');
			//logError('found searching');
			$terrain=getVar('terrain',$content);
			$item=getVar('item',$content);
			$query="SELECT count FROM searching WHERE terrain='$terrain' AND item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE searching SET count='$count' WHERE terrain='$terrain' AND item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=9;
				}
				else if ($newCount<100) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query="INSERT INTO searching (count, terrain, item) VALUES ('1','$terrain','$item')";
				mysql_query($query) or $success=false;
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Searching Data: '.$terrain.'  item:'.$item."\n");
			}
			else {
				logError('Storeroom Searching rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// WHIP SNATCHING
		elseif ($dataType=='WhipGrab') {
			mysql_query('START TRANSACTION');
			$query="SELECT count FROM whip_grab";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE whip_grab SET count='$newCount'";
				mysql_query($query) or $success=false;
				if ($row['count']<100) {
					$dataScore+=9;
				}
				else if ($row['count']<1000) {
					$dataScore+=6;
				}
				else $dataScore+=3;
				
			}
			else {
				$query = "INSERT INTO whip_grab (count) VALUES ('1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData("   Whip Snatching Data.\n");
			}
			else {
				logError('Whip-snatching rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// BOWLING
		elseif ($dataType=='Bowling') {
			mysql_query('START TRANSACTION');
			//logError('found searching');
			$item=getVar('item',$content);
			$query="SELECT count FROM bowling WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE bowling SET count='$count' WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=9;
				}
				else if ($newCount<100) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query="INSERT INTO bowling (count, item) VALUES ('1','$item')";
				mysql_query($query) or $success=false;
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Bowling Data: '.$item."\n");
			}
			else {
				logError('Bowling rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		
		// GOING TO THE BALL
		elseif ($dataType=='Ball') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$query="SELECT count FROM ballroom WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE ballroom SET count='$newCount' WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=30;
				}
				else if ($newCount<100) {
					$dataScore+=20;
				}
				else $dataScore+=10;
			}
			else {
				$query = "INSERT INTO ballroom (item, count) VALUES ('$item', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=100;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Ballroom Data: '.$item."\n");
			}
			else {
				logError('Ball rolled back.  No, really.'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// TRAGIC DEATH; for a lark, this is a data score PENALTY.
		elseif ($dataType=='Death') {
			mysql_query('START TRANSACTION');
			//logError('found searching');
			$monster=getVar('monster',$content);
			
			// stupid, stupid death alts!
			if ($username=='owe my balls' || $username=='Deathlock') {
				$dataScore-=1000;
				continue;	
			}
			$query="SELECT count FROM death WHERE monster='$monster' AND username='$username'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE death SET count='$count' WHERE monster='$monster' AND username='$username'";
				mysql_query($query) or $success=false;
				
				// yes, I am an evil, evil man.  If you die, we penalize you.  The penalties only get REALLY nasty if you die to the SAME MONSTER TYPE 9 times or more.
				if ($count>3) {
					$dataScore-=1000;
				}
				else if ($count>8) {
					$dataScore-=10000;
				}
				else $dataScore-=200;
			}
			else {
				$query="INSERT INTO death (count, monster, username) VALUES ('1','$monster','$username')";
				mysql_query($query) or $success=false;
				
				$dataScore-=100;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Death: '.$username."\n");
			}
			else {
				logError('Death rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		
		//SAND-GATHERING
		elseif ($dataType=='Sand') {
			mysql_query('START TRANSACTION');
			$terrain=getVar('terrain',$content);
			$itemTypes=getVar('itemTypes',$content);
			$itemCounts=array();
			$items=array();
			//logError('gathered wood.  terrain='.$terrain.', itemTypes='.$itemTypes."\n");
			for ($i=0;$i<$itemTypes;$i++) {
				$itemCounts[]=getVar('count'.$i,$content);
				$items[]=getVar('item'.$i,$content);
			}
			// update/insert rows for individual items in gathering
			for ($i=0;$i<$itemTypes;$i++) {
				$query="SELECT count FROM sand_gathering WHERE terrain='$terrain' AND item='$items[$i]'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					//logError('found item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$count=$row['count']+$itemCounts[$i];
					$query="UPDATE sand_gathering SET count='$count' WHERE terrain='$terrain' AND item='$items[$i]'";
					mysql_query($query) or $success=false;
				}
				else {
					//logError('didn\'t find item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query = "INSERT INTO sand_gathering (terrain,item,count) VALUES ('$terrain','$items[$i]','$itemCounts[$i]')";
					mysql_query($query) or $success=false;
				}
			}
			// update/insert row for total number of $terrain gatherings
			$query="SELECT count FROM sand_count WHERE terrain='$terrain'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE sand_count SET count='$count' WHERE terrain='$terrain'";
				mysql_query($query) or $success=false;
				
				if ($count<50) {
					$dataScore+=9;
				}
				else if ($count<500) {
					$dataScore+=6;
				}
				else $dataScore+=3;
				
			}
			else {
				$query = "INSERT INTO sand_count (terrain,count) VALUES ('$terrain','1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=15;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Sand-Gathering Data: '.$terrain.'  item types:'.$itemTypes."\n");
			}
			else {
				logError('sand-gathering rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// FAIRY NUFFS
		elseif ($dataType=='Fairy') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$query="SELECT count FROM fairy WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE fairy SET count='$newCount' WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=6;
				}
				else if ($newCount<100) {
					$dataScore+=4;
				}
				else $dataScore+=2;
			}
			else {
				$query = "INSERT INTO fairy (item, count) VALUES ('$item', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Fairy Nuff Data: '.$item."\n");
			}
			else {
				logError('Fairy Nuffs rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		elseif ($dataType=='HarveyDig') {
			mysql_query('START TRANSACTION');
			//logError('found searching');
			$item=getVar('item',$content);
			$spade=getVar('spade',$content);
			$query="SELECT count FROM harvey_dig WHERE item='$item' AND spade=$spade";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE harvey_dig SET count='$count' WHERE item='$item' AND spade=$spade";
				mysql_query($query) or $success=false;
				
				if ($newCount<10) {
					$dataScore+=9;
				}
				else if ($newCount<100) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query="INSERT INTO harvey_dig (count, item, spade) VALUES ('1','$item',$spade)";
				mysql_query($query) or $success=false;
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Harvey Dig Data: '.$item."\n");
			}
			else {
				logError('Digging Harvey rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		elseif ($dataType=='ThiefAction') {
			mysql_query('START TRANSACTION');
			$action=getVar('action',$content);
			$monster=getVar('monster',$content);
			//if ($monster=='') $monster='Thief';	// brief backwards compatibility
			$attack=0;
			$stab=0;
			$steal=0;
			if ($action=='Attack') $attack=1;
			else if ($action=='Stab')$stab=1;
			else if ($action=='Steal')$steal=1;
			
			$query="SELECT * FROM thief_action WHERE monster='$monster'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				
				$query="UPDATE thief_action SET attack=attack+$attack, stab=stab+$stab, steal=steal+$steal WHERE monster='$monster'";
				mysql_query($query) or $success=false;
				
				if ($row['attack']<50) {
					$dataScore+=9;
				}
				else if ($row['attack']<500) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query="INSERT INTO thief_action (attack, stab, steal, monster) VALUES ('$attack','$stab','$steal','$monster')";
				mysql_query($query) or $success=false;
				
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Thief Action Data: '.$action."\n");
			}
			else {
				logError('Thief actions rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		elseif ($dataType=='Poison') {
			mysql_query('START TRANSACTION');
			$monster=getVar('monster',$content);
			
			$query="SELECT * FROM poison WHERE monster='$monster'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				
				$query="UPDATE poison SET count=count+1 WHERE monster='$monster'";
				mysql_query($query) or $success=false;
				
				if ($row['count']<50) {
					$dataScore+=9;
				}
				else if ($row['count']<500) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
			// I considered deleting the monster accuracy data when new poison data comes in (I've cleared tables often enough), but I rather like data, so I'll just stick an offset in.  And pray that I remember to clear poison whenever I clear monster accuracy.
				$query="SELECT hits FROM monster_accuracy WHERE monster='$monster'";
				$monsterResults=mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($monsterResults);
				if ($row) $offset=$row['hits'];
				else $offset=0;	
				
				
				$query="INSERT INTO poison (monster, count, offset) VALUES ('$monster','1','$offset')";
				mysql_query($query) or $success=false;
				
				
				$dataScore+=30;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Poison Data: '.$monster."\n");
			}
			else {
				logError('Poison rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		elseif ($dataType=='SealKudoLoss') {
			mysql_query('START TRANSACTION');
			$query="SELECT count FROM seal_kudo_loss";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE seal_kudo_loss SET count='$newCount'";
				mysql_query($query) or $success=false;
				
				if ($row['count']<100) {
					$dataScore+=6;
				}
				else if ($row['count']<1000) {
					$dataScore+=4;
				}
				else $dataScore+=2;
			}
			else {
				$query="SELECT count FROM monster_kills where monster='Cute Seal'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) $offset=$row['count'];
				else $offset=0;	
	
				$query = "INSERT INTO seal_kudo_loss (count, offset) VALUES ('1','$offset')";
				mysql_query($query) or $success=false;
				
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData("   Seal Kudo Loss.\n");
			}
			else {
				logError('Seal kudos loss rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		elseif ($dataType=='SilverVsDragon') {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$hit=getVar('hit',$content);
			if ($hit=='true') $hit=1;
			else if ($hit=='false')$hit=0;
			else continue;	// if we can't find a hit or a miss, just move on to the next data.
			$accuracy=getVar('accuracy',$content);
			
			if ($accuracy=="0") continue;	// if there was no way to hit, no data score for you!
			
			$query="SELECT * FROM silver_vs_dragon WHERE item='$item'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$count=$row['count']+1;
				$query="UPDATE silver_vs_dragon SET accuracy=accuracy+$accuracy, hits=hits+$hit, count=count+1 WHERE item='$item'";
				mysql_query($query) or $success=false;
				
				if ($row['count']<100) {
					$dataScore+=15;
				}
				else if ($row['count']<1000) {
					$dataScore+=10;
				}
				else $dataScore+=5;
			}
			else {
				$query="INSERT INTO silver_vs_dragon (item, hits, accuracy, count) VALUES ('$item','$hit','$accuracy','1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=50;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Silver vs. Dragon Data: '.$hit.'  item:'.$item."\n");
			}
			else {
				logError('silver vs. dragons rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// BREAKING THINGS WHEN YOU MOVE
		elseif ($dataType=="MoveBreak") {
			mysql_query('START TRANSACTION');
			$item=getVar('item',$content);
			$numberWorn=getVar('numberWorn',$content);
			$query="SELECT * FROM move_break WHERE item='$item' AND numberWorn='$numberWorn'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$query="UPDATE move_break SET count=count+1, breaks=breaks+1 WHERE item='$item' AND numberWorn='$numberWorn'";
				mysql_query($query) or $success=false;
				
				if ($row['breaks'] <20 && $item !='Pair of Daedalus Wings') {
					$dataScore+=60;
				}
				else if ($row['breaks']<200 && $item !='Pair of Daedalus Wings') {
					$dataScore+=40;
				}
				else if ($item !='Pair of Daedalus Wings') $dataScore+=20;
				else $dataScore+=5;	//Daedalus Wings break at the drop of a hat.  Don't reward them too well.
			}
			else {
				$query="INSERT INTO move_break (item, numberWorn, breaks, count) VALUES ('$item','$numberWorn','1','1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=200;
				
			}
			if ($success) {
				mysql_query('COMMIT');
				logData('   Move-Breaking Data: '.$item."\n");
			}
			else {
				logError('Move-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// NOT BREAKING THINGS WHEN YOU MOVE
		// (broken into two types because breaking only reports one item; not breaking reports many, and the data organization is a bit different.)
		elseif ($dataType=='MoveIntact') {
			mysql_query('START TRANSACTION');
			$itemTypes=getVar('itemTypes',$content);
			$itemCounts=array();
			$items=array();
			//logError('found monster drop.  monster='.$monster.', itemTypes='.$itemTypes."\n");
			for ($i=0;$i<$itemTypes;$i++) {
				$itemCounts[]=getVar('numberWorn'.$i,$content);
				$items[]=getVar('item'.$i,$content);
			}
			// update/insert rows for individual items for a monster
			for ($i=0;$i<$itemTypes;$i++) {
				$query="SELECT count FROM move_break WHERE numberWorn='$itemCounts[$i]' AND item='$items[$i]'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					//logError('found item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query="UPDATE move_break SET count=count+1 WHERE numberWorn='$itemCounts[$i]' AND item='$items[$i]'";
					mysql_query($query) or $success=false;
					
					// no data-reporting rewards here (counts get awfully high, awfully fast), but the rewards are pretty steep when you actually break something.
				}
				else {
					//logError('didn\'t find item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query = "INSERT INTO move_break (item,numberWorn,count,breaks) VALUES ('$items[$i]','$itemCounts[$i]','1','0')";
					mysql_query($query) or $success=false;
				}
			}
			if ($success) {
				mysql_query('COMMIT');
				logData('   Move-Not-Breaking Data: '.$itemTypes."\n");
			}
			else {
				logError('Moving intact rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// NIGHTLY WIZARD SPELLS
		elseif ($dataType=='WizardSpell') {
			mysql_query('START TRANSACTION');
			$itemTypes=getVar('itemTypes',$content);
			$itemCounts=array();
			$items=array();
			$rank=getVar('rank',$content);
			//logError('found monster drop.  monster='.$monster.', itemTypes='.$itemTypes."\n");
			for ($i=0;$i<$itemTypes;$i++) {
				$itemCounts[]=getVar('count'.$i,$content);
				$items[]=getVar('item'.$i,$content);
			}
			// update/insert rows for individual items for a monster
			for ($i=0;$i<$itemTypes;$i++) {
				$query="SELECT count FROM wizard_spell WHERE rank='$rank' AND item='$items[$i]'";
				$results = mysql_query($query) or $success=false;
				$row=mysql_fetch_assoc($results);
				if ($row) {
					//logError('found item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query="UPDATE wizard_spell SET count=count+$itemCounts[$i] WHERE rank='$rank' AND item='$items[$i]'";
					mysql_query($query) or $success=false;
					
					if ($row['count']<10) {
						$dataScore+=9;
					}
					else if ($row['count']<100) {
						$dataScore+=6;
					}
					else $dataScore+=3;
				}
				else {
					//logError('didn\'t find item:'.$itemCounts[$i].' '.$items[$i]."\n");
					$query = "INSERT INTO wizard_spell (item,rank,count) VALUES ('$items[$i]','$rank','$itemCounts[$i]')";
					mysql_query($query) or $success=false;
					
					$dataScore+=30;
				}
			}
			if ($success) {
				mysql_query('COMMIT');
				logData('   Nightly Wizard Spell Data: '.$itemTypes."\n");
			}
			else {
				logError('Nightly wizard spells rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// SUMMON STONES
		elseif ($dataType=='SummonStone') {
			mysql_query('START TRANSACTION');
			$result=getVar('result',$content);
			$summoner=getVar('summoner',$content);
			
			// if the summoner is an alt of the summoned, then the stone will always break.  Eliminate that from the data.
			$query="SELECT id_key FROM contributors WHERE username='$summoner'";
			$summonID=mysql_query($query) or $success=false;
			$summonRow=mysql_fetch_assoc($summonID);
			if ($summonRow['id_key']==$idKey) {
				mysql_query('ROLLBACK');
				continue;
			}
			
			$query="INSERT INTO summon_stone (result, date_col) VALUES ('$result', CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM summon_stone";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=15;
			}
			elseif ($row['total']<500) {
				$dataScore+=10;
			}
			else {
				$dataScore+=5;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Summon Stone Data: '.$result."\n");
			}
			else {
				logError('summon-stone-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// WORMHOLES
		elseif ($dataType=='Wormhole') {
			mysql_query('START TRANSACTION');
			$break=getVar('break',$content);
			$query="INSERT INTO wormhole (break, date_col) VALUES ($break, CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM wormhole";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=15;
			}
			elseif ($row['total']<500) {
				$dataScore+=10;
			}
			else {
				$dataScore+=5;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Wormhole Data: '.$break."\n");
			}
			else {
				logError('Wormhole-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// PLAYERS STEALING FROM EACH OTHER
		elseif ($dataType=='PlayerTheft') {
			mysql_query('START TRANSACTION') or $success=false;
			$theft=getVar('theft',$content);
			$victim=getVar('victim',$content);
			$query="INSERT INTO player_theft (theft, username, victim, date_col) VALUES ($theft, '$username','$victim', CURDATE() )";
			mysql_query($query);
			
			$query="SELECT COUNT(*) as total FROM player_theft";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=15;
			}
			elseif ($row['total']<500) {
				$dataScore+=10;
			}
			else {
				$dataScore+=5;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Player Theft Data: '.$theft."\n");
			}
			else {
				logError('Player Theft rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// MINES BREAKING
		elseif ($dataType=='MineDuration') {
			mysql_query('START TRANSACTION');
			$collapse=getVar('collapse',$content);
			$terrain=getVar('terrain',$content);
			$query="INSERT INTO mine_duration (collapse, terrain, date_col) VALUES ($collapse, '$terrain',CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM mine_duration";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=9;
			}
			elseif ($row['total']<500) {
				$dataScore+=6;
			}
			else {
				$dataScore+=3;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Mine-Breaking Data: '.$terrain.'  collapse:'.$collapse."\n");
			}
			else {
				logError('Mine-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// CUSTOM WEAPONS BREAKING.  Get these out of my normal weapon-break data!
		elseif ($dataType=='CustomWeaponBreak') {
			mysql_query('START TRANSACTION');
			$material=getVar('material',$content);
			$lifespan=getVar('lifespan',$content);
			$break=getVar('break',$content);
			$attacks=getVar('attacks',$content);
			$bane=getVar('bane',$content);
			$isWeapon=getVar('isWeapon',$content);
			if ($break!='false') $breakCount=1;
			else $breakCount=0;
			$pillState=getVar('bluePill',$content);
			
			$query="SELECT count,breaks FROM custom_weapon_break WHERE lifespan='$lifespan' AND material='$material' AND pill_state='$pillState' AND is_bane=$bane";
			$results = mysql_query($query) or logErrorAndDie('Error, getting previous custom weapon break info failed:'.mysql_error()."\n");
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+$attacks;
				$newBreaks=$row['breaks']+$breakCount;
				$query="UPDATE custom_weapon_break SET count='$newCount',breaks=$newBreaks WHERE lifespan='$lifespan' AND material='$material' AND pill_state='$pillState' AND is_bane=$bane";
				mysql_query($query) or $success=false;
				
				if ($breakCount==1) {
					$dataScore+=5;
				}
				else if ($count<50) {
					$dataScore+=2;
				}
				else if ($count<500) {
					$dataScore+=1;
				}
	
			}
			else {
				// isWeapon does no harm here if it's blank.
				$query = "INSERT INTO custom_weapon_break (lifespan, material, count, breaks, pill_state, is_bane) VALUES ('$lifespan', '$material','$attacks', '$breakCount','$pillState',$bane)";
				mysql_query($query) or $success=false;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Custom-weapon-breaking Data: '.$material.'  lifespan:'.$lifespan."\n");
			}
			else {
				logError('Custom Weapons rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
			
		}
		// Frequency of various veins in the SE tunnels
		elseif ($dataType=='TunnelMining') {
			mysql_query('START TRANSACTION');
			$vein=getVar('terrain',$content);
			$guild=getVar('guild',$content);
			
			$query="SELECT count FROM tunnel_mining WHERE terrain='$vein' AND guild=$guild";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE tunnel_mining SET count='$newCount' WHERE terrain='$vein' AND guild=$guild";
				mysql_query($query) or $success=false;
				
				if ($newCount<100) {
					$dataScore+=30;
				}
				else if ($newCount<1000) {
					$dataScore+=20;
				}
				else $dataScore+=10;
			}
			else {
				$query = "INSERT INTO tunnel_mining (terrain, count, guild) VALUES ('$vein', '1', $guild)";
				mysql_query($query) or $success=false;
				
				$dataScore+=100;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Tunnel Mining Data: '.$vein.'  guild:'.$guild."\n");
			}
			else {
				logError('Tunnel mining rolled back.'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// FLASH GUNS
		elseif ($dataType=='FlashGun') {
			mysql_query('START TRANSACTION') or $success=false;
			$break=getVar('break',$content);
			$query="INSERT INTO flashgun (break, date_col) VALUES ($break, CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM flashgun";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=9;
			}
			elseif ($row['total']<500) {
				$dataScore+=6;
			}
			else {
				$dataScore+=3;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Flashgun Data: '.$break."\n");
			}
			else {
				logError('Flashgun-breaking rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// ASTRAL PLAIN TRAVEL
		elseif ($dataType=='AstralPlain') {
			mysql_query('START TRANSACTION');
			$shrimp=getVar('shrimp',$content);
			$query="INSERT INTO astral_plain (shrimp, date_col) VALUES ($shrimp, CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM astral_plain";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=60;
			}
			elseif ($row['total']<500) {
				$dataScore+=40;
			}
			else {
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Astral Plain Data: '.$shrimp."\n");
			}
			else {
				logError('Astral Plain rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// HARVEST SONG: falling rocks
		elseif ($dataType=='HarvestSong') {
			mysql_query('START TRANSACTION');
			$starfall=getVar('starfall',$content);
			$query="INSERT INTO harvest_song (starfall, date_col) VALUES ($starfall, CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM harvest_song";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=60;
			}
			elseif ($row['total']<500) {
				$dataScore+=40;
			}
			else {
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Harvest Song Data: '.$starfall."\n");
			}
			else {
				logError('Harvest Song rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// RAINBOW WAND: what alignment?
		elseif ($dataType=='RainbowWand') {
			mysql_query('START TRANSACTION');
			$alignment=getVar('alignment',$content);
			
			$query="SELECT count FROM rainbow_wand WHERE alignment='$alignment'";
			$results = mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row) {
				$newCount=$row['count']+1;
				$query="UPDATE rainbow_wand SET count='$newCount' WHERE alignment='$alignment'";
				mysql_query($query) or $success=false;
				
				if ($newCount<100) {
					$dataScore+=9;
				}
				else if ($newCount<1000) {
					$dataScore+=6;
				}
				else $dataScore+=3;
			}
			else {
				$query = "INSERT INTO rainbow_wand (alignment, count) VALUES ('$alignment', '1')";
				mysql_query($query) or $success=false;
				
				$dataScore+=100;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Rainbow Wand Data: '.$alignment."\n");
			}
			else {
				logError('Rainbow Wand rolled back.'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// NORTHERN ROCK: money or teleport?
		elseif ($dataType=='NorthernRock') {
			mysql_query('START TRANSACTION');
			$darling=getVar('darling',$content);
			$query="INSERT INTO northern_rock (darling, date_col) VALUES ($darling, CURDATE() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM northern_rock";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<50) {
				$dataScore+=60;
			}
			elseif ($row['total']<500) {
				$dataScore+=40;
			}
			else {
				$dataScore+=20;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Northern Rock Data: '.$darling."\n");
			}
			else {
				logError('Northern Rock rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// VORPAL BLADE: snicker-snack?
		elseif ($dataType=='VorpalBlade') {
			mysql_query('START TRANSACTION');
			$vorp=getVar('vorp',$content);
			$monster=getVar('monster',$content);
			$query="INSERT INTO vorpal_blade (vorp, monster, time) VALUES ($vorp, '$monster', NOW() )";
			mysql_query($query) or $success=false;
			
			$query="SELECT COUNT(*) as total FROM vorpal_blade";
			$results=mysql_query($query) or $success=false;
			$row=mysql_fetch_assoc($results);
			if ($row['total']<100) {
				$dataScore+=30;
			}
			elseif ($row['total']<1000) {
				$dataScore+=20;
			}
			else {
				$dataScore+=10;
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Vorpal Blade Data: '.$vorp.' '.$monster."\n");
			}
			else {
				logError('Vorpal Blade rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// CONDOR LOCATION: technically part of the Golden Condor Navigator, but it's easier to just put this in than to add a whole new data-collection script.
		elseif ($dataType=='CondorLocation') {
			mysql_query('START TRANSACTION');
			$x=getVar('x',$content);
			$y=getVar('y',$content);
			$query="INSERT INTO condor_location (x,y,time, username) VALUES ($x, $y, NOW(), '$username') ON DUPLICATE KEY UPDATE time=NOW(), x=values(x), y=values(y)";
			mysql_query($query) or $success=false;
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Condor Location: '.$x.'  '.$y."\n");
			}
			else {
				logError('Condor location rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		// VENT EXPLORATION: technically part of the Golden Condor Navigator, but it's easier to just put this in than to add a whole new data-collection script.
		elseif ($dataType=='VentRoads') {
			mysql_query('START TRANSACTION');
			$x=getVar('x',$content);
			$y=getVar('y',$content);
			$dirCount=getVar('dirCount',$content);
			// round down to nearest hour
			$timestamp=time();
			// BST correction.  Yes, we're playing with UNIX timestamps here in ways that weren't intended, but since this is always intended to be Cities time, not GMT, we're slightly less evil.  Technically, this is European Time Correction, but since BST and EDT happens over the same dates, no worries.
			if (intval(date('I'))==1) {
				$timestamp+=3600;
			}
			$timestamp/=3600.0;	
			$timestamp=$timestamp-fmod($timestamp,1.0);
			$timestamp*=3600.0; 
			
			$directions=array('n'=>true,'e'=>true,'w'=>true,'s'=>true);
			for ($i=0;$i<$dirCount;$i++) {
				$dir=getVar('dir'.$i,$content);
				$tableX=$x;
				$tableY=$y;
				$directions[$dir]=false;
				// in the table, directions are only north or east.
				switch($dir){
					case 'w':
						$tableX=$x-1;
						$dir='e';
						break;
					case 'e':
						break;
					case 'n':
						break;
					case 's':
						$tableY=$y-1;
						$dir='n';
						break;
				}
				$query="INSERT INTO vent_roads (x,y,dir, time, open) VALUES ($tableX, $tableY, '$dir', $timestamp, TRUE) ON DUPLICATE KEY UPDATE x=values(x)";
				mysql_query($query) or $success=false;
			}
			
			// now report the closed paths.  (i.e., everything not reported earlier.)
			foreach($directions as $path => $open) {
				if ($open) {
					$tableX=$x;
					$tableY=$y;
					// in the table, directions are only north or east.
					switch($path){
						case 'w':
							$tableX=$x-1;
							$path='e';
							break;
						case 'e':
							break;
						case 'n':
							break;
						case 's':
							$tableY=$y-1;
							$path='n';
							break;
					}
					$query="INSERT INTO vent_roads (x,y,dir, time, open) VALUES ($tableX, $tableY, '$path', $timestamp, FALSE) ON DUPLICATE KEY UPDATE open=values(open)";
					mysql_query($query) or $success=false;
				}
			}
			
			if ($success) {
				mysql_query('COMMIT');
				logData('   Vent Roads: '.$x.'  '.$y.'  '.$dirCount.'  '.gmdate('h:i',$timestamp)."  $timestamp\n");
				
			}
			else {
				logError('Vent roads rolled back'.mysql_error());
				mysql_query('ROLLBACK');
			}
		}
		if (!$success) {
			$totalSuccess=false;
		}
	}
	
	
	// fetch the username, so we can give credit where it's due
	$username=getVar('username',$body);
	if ($username) {
		mysql_query('START TRANSACTION');
		if ($dataScore<1)$dataScore=1; //in case I missed something.
		$success=true;
		$query="SELECT * FROM contributors WHERE username='$username'";
		$results = mysql_query($query) or $success=false;
		$row=mysql_fetch_assoc($results);
		if (!$row) {
			$query="INSERT INTO contributors (username, count, id_key) VALUES ('$username','$dataScore','$idKey')";
			mysql_query($query) or $success=false;
		}
		else {
			$newCount=$row['count']+$dataScore;
			$query="UPDATE contributors SET count='$newCount', id_key='$idKey' WHERE username='$username'";
			mysql_query($query);
		}
		
		$query="SELECT count FROM validation WHERE id_key='$idKey'";
		$results=mysql_query($query) or $success=false;
		$row=mysql_fetch_assoc($results);
		if ($row) {
			$newCount=$row['count']+$dataScore;
			$query="UPDATE validation SET count='$newCount' WHERE id_key='$idKey'";
			mysql_query($query) or $success=false;
		}
		
		if ($success) {
			mysql_query('COMMIT');
		}
		else {
			mysql_query('ROLLBACK');
			logError('Data score rolled back');
		}
		
		// experimental new time-stampy method.  Now highly successful.  I wonder if I should remove the old method some day?
		$query="INSERT INTO data_score (username, id_key, count, time) values('$username','$idKey','$dataScore',NOW() ) ON DUPLICATE KEY UPDATE count=count+values(count)";
		mysql_query($query) or LogErrorAndDie('New data insertion on timed database failed. '.mysql_error()."\n");
	}
	if ($totalSuccess) {
		logData(date('Y-m-d H:i:s').' Total success:'.$http_request->body()."\n");
	}
	else {
		$results=mysql_evaluate_array("SELECT NOW()");
		logError("ERROR! ".date()."Repeating query below:".$results[0][0]."\n".$http_request->body()."\n");
	}
}
catch(Exception $e){
  logError($e->getMessage());
  logError('Code :'.$e->getCode());
  logError('File :'.$e->getFile());
  logError('Line :'.$e->getLine());
  exit();
}

mysql_close($conn);
// I'm SURE something like this function exists.  There MUST be a decent way to get URL-encoded parameters from a generic string.  I no longer care, and this works all right.
function getVar($targetVar, $text) {
	$varPairs=preg_split("/&/",$text);
	foreach($varPairs as $pair) {
		$variable=preg_split("/=/",$pair);
		if ($variable[0]==$targetVar) return $variable[1];
	}
	return '';
}
?>
