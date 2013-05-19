<?php
/**
 *	Autoname-TV v-2.1
 *
 *	2011-2012 - RE
 */

require_once("config.php");

function var_dump_str($var)
{
	ob_start();
	var_dump($var);
	$dump = ob_get_clean();
	return $dump;
}

function var_dump_errstream($var)
{
	$str = var_dump_str($var);
	fwrite(STDERR,"$str");
}

function echo_errstream($str)
{
	fwrite(STDERR, "$str");
}

require_once('class.thetvdbapi.php');

function processFile($fn, $seriesNameOverride="", $seriesNumOverride="", $episodeNumOverride="", $targetDirOverride="",
						$formatStrOverride="", $useOverrides=false, $dontMoveDir=false, $forcemv=false, $interactivemv=false,
						$doCopy=false, $takeTMDBSeriesName=false)
{
	global $formatStr, $punctuationCharsToKill, $targetDir, $overrides;

	$invalidOutputFilenameChars = array(":","/","\\","?");
	$invalidOutputFilenameReplacement = "-";

	if($targetDirOverride)
		$targetDir=$targetDirOverride;

	if($formatStrOverride)
		$formatStr=$formatStrOverride;

	$fileArray = explode("/",$fn);

	$file = $fileArray[count($fileArray)-1];

	//regex the filename
	$origFilename = $fn;

	$matches = array();

	// if doesn't match the regex and the user hasn't overridden all the argumentos
	if(	!preg_match("/(.*?)[sS]?([0-9]+)[eExX\.]([0-9]+)(.*?)/",$file,$matches) &&
		!($seriesNameOverride!=="" && $seriesNumOverride!=="" && $episodeNumOverride!=="") )
	{
		echo_errstream("# File ($file) does not match pattern and not all properties were overriden, moving on\n");
		return false;
	}

	//$matches[1] = probably series name
	//$matches[2] = season
	//$matches[3] = episode

	//get filename info from datasource
	$tvSeriesName = isset($matches[1])?$matches[1]:"";
	$tvSeriesName = trim( str_replace( $punctuationCharsToKill, " ", $tvSeriesName ) );

	//replace multiple whitespace characters with one
	$tvSeriesName = preg_replace("/ +/"," ",$tvSeriesName);
	$seriesNo = isset($matches[2])?($matches[2]*1):"";
	$episodeNo = isset($matches[3])?($matches[3]*1*1):"";

	//if the override has been specified on the command line
	if ($seriesNameOverride!=="")
		$tvSeriesName = $seriesNameOverride;
	if ($seriesNumOverride!=="")
		$seriesNo = $seriesNumOverride;
	if ($episodeNumOverride!=="")
		$episodeNo = $episodeNumOverride;

	$tvSlo = strtolower($tvSeriesName);

	//If user uses the flag for overrides then utilise them!
	if($useOverrides)
	{
		//Manually override the series name
		if(!empty($overrides[$tvSlo]["SeriesName"]))
			$tvSeriesName = $overrides[$tvSlo]["SeriesName"];

		//tweak the Season and Episode numbers by the defined delta
		if(!empty($overrides[$tvSlo]["SeasonNo"]))
			$seriesNo += $overrides[$tvSlo]["SeasonNo"];

		if(!empty($overrides[$tvSlo]["EpisodeNo"]))
			$episodeNo += $overrides[$tvSlo]["EpisodeNo"];
	}
	// create object
	$tvapi = new Thetvdb(THETVDB_APIKEY);

	$seriesObject = $tvapi->GetSeriesObjectByName($tvSeriesName);
	$serieid = $seriesObject->id;
	$episodeid = $tvapi->GetEpisodeId($serieid,$seriesNo,$episodeNo);

	#$serieid = $tvapi->GetSerieId($tvSeriesName);

	if(!$episodeid || !$serieid)
	{
		echo_errstream("Could not find $fn or an error occurred, moving on\n");
		var_dump_errstream($tvapi);
		var_dump_errstream($serieid);
		var_dump_errstream($episodeid);
		var_dump_errstream($matches);
		return false;
	}

	if ($takeTMDBSeriesName)
	{
		$tvSeriesName = trim(str_replace($invalidOutputFilenameChars, $invalidOutputFilenameReplacement, $seriesObject->SeriesName));
	}

	// get information about the episode
	$ep_info = $tvapi->GetEpisodeData($episodeid);

	if($ep_info)
	{
		$SeriesNo 	= str_pad($ep_info['season'],2,"0",STR_PAD_LEFT);
		$EpisodeNo 	= str_pad($ep_info['episode'],2,"0",STR_PAD_LEFT);

		$EpisodeName = trim(str_replace($invalidOutputFilenameChars, $invalidOutputFilenameReplacement, $ep_info['name']));

		//get the extension
		$extMatch = array();
		preg_match("/^.*\.([^\.]+)$/",$origFilename,$extMatch);

		$newFilename = str_replace(
						array("<SeriesName>", "<SeriesNo>", "<EpisodeNo>", "<EpisodeName>"),
						array($tvSeriesName, $SeriesNo, $EpisodeNo, $EpisodeName),
						$formatStr).".".$extMatch[1];

		if($dontMoveDir)
		{
			$pathdir="./";
			$newFNArray = explode("/", $newFilename);
			$newFilename = $newFNArray[count($newFNArray)-1];
		}
		else
		{
			$pathdir = $targetDir;
			echo "dir=`dirname \"$pathdir$newFilename\"`; ";
			echo '[[ -d "$dir" ]] || mkdir -p "$dir"; '."\n";
		}
		echo ($doCopy?'cp ':'mv ').($forcemv?"-f ":($interactivemv?"-i ":"-n ")).'"'.$origFilename.'" "'.$pathdir.$newFilename.'"';
	}
	echo "\n";
}

$seriesNameOverride="";

$options = getopt( "s:S:e:t:m:confida" );

$seriesNameOverride = $options['s'];
$seriesNum = $options['S'];
$episodeNum = $options['e'];
$targetDirOverride = $options['t'];
$formatStrOverride = $options['m'];

$takeTMDBSeriesName = isset($options['a']);
$useOverrides = isset($options['o']);
$dontMoveDir  = isset($options['n']);
$forcemv = isset($options['f']);
$interactivemv = isset($options['i']);
$filenameIndex = count($argv)-1;
$doCopy = isset($options['c']);
define('SHOWDEBUG',isset($options['d']));

if(!empty($argv[$filenameIndex]))
{
	processFile( $argv[$filenameIndex], $seriesNameOverride, $seriesNum, $episodeNum, $targetDirOverride,
				 $formatStrOverride, $useOverrides, $dontMoveDir, $forcemv, $interactivemv, $doCopy, $takeTMDBSeriesName );
}
else
{
	echo "Use the wrapping script, the input to this is way too intolerant to be used directly. Supply the filename as the argument\n";
}

?>
