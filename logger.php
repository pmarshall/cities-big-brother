<?php
// This PHP page started as an "error-logging library" kind of thing, but it's starting to become "BB's general function library."  I'll try to keep it down to a dull roar.


function logErrorAndDie($text) {
	$filename = 'errorLog.txt';
	$fp = fopen($filename, "a");
	$write = fputs($fp, $text);
	fclose($fp);
	die($text);
}

function logData($text) {
	$filename = 'log.txt';
	$fp = fopen($filename, "a");
	$write = fputs($fp, $text);
	fclose($fp);
	
	//There is a 10-MB filesize limit on 110mb.com, and I like downloading smallish logfiles, anyway.
	// limit it to about 9 megs, and do it automatically.
	// On further review, even though the limit was dropped to 5MB, it appears there is NO limit for PHP file-writing.
	// I could probably take advantage of that if I felt like it, but I really don't.
	$logIterator=1;
	if (filesize($filename)>9000000) {
		while (file_exists('log'.$logIterator.'.txt')) {
			$logIterator++;
		}
		rename('log.txt','log'.$logIterator.'.txt');
	}
}

function logError($text) {
	$filename = 'errorLog.txt';
	$fp = fopen($filename, "a");
	$write = fputs($fp, $text);
	fclose($fp);
	
	// stripped the log-renaming, because the error log should be short, and I'll delete it from time to time.
	/*$logIterator=1;
	if (filesize($filename)>9000000) {
		while (file_exists('log'.$logIterator.'.txt')) {
			$logIterator++;
		}
		rename('log.txt','log'.$logIterator.'.txt');
	}*/
}

// stuff the results of a query into an array I can manipulate at will, rather than the annoying query object.  I'm sure I'll look back on this someday and post it to The Daily WTF, but for now, it makes data manipulation easier.
function mysql_evaluate_array($query, $arrayType=MYSQL_NUM) {
   $result = mysql_query($query) or logErrorAndDie('Array Evaluation failure: '.mysql_error()."\n");
   $values = array();
   for ($i=0; $i<mysql_num_rows($result); ++$i)
       array_push($values, mysql_fetch_array($result,$arrayType));
   return $values;
}

// I don't want too many sigfigs, and 2 sigfigs is right for a lot of data, but once there's enough trials, I will be able to get VERY precise.  So let's just auto-set the sigfigs of a number depending on how many trials we have, as a measure of precision.  (Note: there will be many cases where it only deserves 1 sigfig, but it'll be a double-digit number, and will get 2 sigfigs by default.  It sucks to be me.)
function precisePercentile($successes, $trials) {
	if ($successes==0) return '0';
	return sigFigs(100.0*(float)$successes/(float)$trials, calcSigFigs($successes, $trials));
}

// I started using this elsewhere, so rather than repeating myself, here it is in its own function: the standard deviation of the estimate.
function calcStdDev($successes, $trials) {
	$pEstimate=calcEstimate($successes,$trials);
	$deviation=sqrt($pEstimate*(1.0-$pEstimate)/$trials);
	// quick hack to make 100% with one hit a small number.  0% and 100% kinda fall apart with the standard deviation calculations.
	if ($deviation==0 || $pEstimate>=.99) $deviation=1.0/$trials;
	
	return $deviation;
}

// and what the hell, while we're separating functions, why not go all the way?
function calcEstimate($successes,$trials) {
	$pEstimate=(float)$successes/(float)$trials;
	if ($pEstimate==0) $pEstimate=.001;
	if ($pEstimate>=1.0) $pEstimate=0.99;	//gold in treasure chests was giving a funny number.
	
	return $pEstimate;
}

function calcSigFigs($successes, $trials) {
	$pEstimate=calcEstimate($successes,$trials);
	$deviation=calcStdDev($successes, $trials);
	
	//The logic here is that the last digit is +/- 1, 68% confidence. So +/- 1 deviation should be +/- 1 on the last digit.  So we divide the standard deviation by our probability estimate to see how big the deviation is relative to the estimate, and then take the log of that to see how many digits we have accurately.  We add one because if there's a 1/10 ratio between the deviation and the number, that's a log of -1, but a precision of 2 digits.
	$sigFigs=ceil(log($deviation/$pEstimate,10) * -1.0 + .8);
	if ($sigFigs<1) $sigFigs=1;
	if ($sigFigs>4) $sigFigs=4;  //any more than 4, and it just gets silly.
	
	return $sigFigs;
}

// return a string of the number formatted into X significant figures.  I did the 0.2 format originally because I didn't want to lose small numbers, but it's annoying (and misleading) to have 4 sigfigs for 98.34%.
function sigFigs($number, $figs=2) {
	if (!$number) $number=0.0;
	$magnitude=floor(1.0+log($number,10));
	
	$format="%01.";
	if ($magnitude>0) {
		if ($figs>$magnitude) $format .= ($figs-$magnitude);
		else $format.="0";
	}
	else {
		$format.=($magnitude*-1+$figs);
	}
	$format.="f";
	return sprintf($format,$number);
}
?>