<?php
/**
 *	Autoname-TV v-2.1
 *
 *	2011 - RE
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

require_once('class.thetvdbapi.php');

function processFile($fn, $seriesNameOverride="", $useOverrides=false, $dontMoveDir=false)
{
	global $formatStr, $punctuationCharsToKill, $targetDir, $overrides;

	$fileArray = explode("/",$fn);

	$file = $fileArray[count($fileArray)-1];

	//regex the filename
	$origFilename = $fn;

	$matches = array();

	if(!preg_match("/(.*?)[sS]?([0-9]+)[eExX\.]([0-9]+)(.*?)/",$file,$matches))
	{
		//echo "Can't do anything with this one, moving on";
		return false;
	}

	//$matches[1] = probably series name
	//$matches[2] = season
	//$matches[3] = episode

	//get filename info from datasource

	$tvSeriesName = trim(str_replace($punctuationCharsToKill," ",$matches[1]));

	//replace multiple whitespace characters with one
	$tvSeriesName = preg_replace("/ +/"," ",$tvSeriesName);
	$seriesNo = $matches[2]*1;
	$episodeNo = $matches[3]*1;

	//if the override has been specified on the command line
	if($seriesNameOverride!=="")
	$tvSeriesName = $seriesNameOverride;

	$tvSlo = strtolower($tvSeriesName);

	//If user uses the flag for overrides then utalise them!
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
	// get serie id for 'fringe'
	$serieid = $tvapi->GetSerieId($tvSeriesName);
	// get episode id for fringe S01E01
	$episodeid = $tvapi->GetEpisodeId($serieid,$seriesNo,$episodeNo);

	if(!$episodeid || !$serieid)
	{
		echo "#Could not find $fn or an error occurred, moving on\n";
		var_dump_errstream($tvapi);
		var_dump_errstream($serieid);
		var_dump_errstream($episodeid);
		var_dump_errstream($matches);
		return false;
	}

	// get information about the episode
	$ep_info = $tvapi->GetEpisodeData($episodeid);

	if($ep_info)
	{
		$SeriesNo 	= str_pad($ep_info['season'],2,"0",STR_PAD_LEFT);
		$EpisodeNo 	= str_pad($ep_info['episode'],2,"0",STR_PAD_LEFT);

		$EpisodeName = trim(str_replace(":","-",$ep_info['name']));

		//get the extension
		$extMatch = array();
		preg_match("/^.*\.([^\.]+)$/",$origFilename,$extMatch);

		$newFilename = str_replace(
						array("<SeriesName>","<SeriesNo>","<EpisodeNo>","<EpisodeName>"),
						array($tvSeriesName,$SeriesNo,$EpisodeNo,$EpisodeName),
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

		echo 'mv "'.$origFilename.'" "'.$pathdir.$newFilename.'"';
	}
	echo "\n";
}

$seriesNameOverride="";
//s is the series override specified on the command line
if($argv[1] != '""')
	$seriesNameOverride = $argv[1];

$useOverrides = (bool)$argv[2];
$dontMoveDir  = (bool)$argv[3];
$filenameIndex = 4;
/*
var_dump($argv[$filenameIndex]);
var_dump($seriesNameOverride);
var_dump($useOverrides);
var_dump($dontMoveDir);

var_dump($argv);
exit;
*/

if(!empty($argv[$filenameIndex]))
	processFile($argv[$filenameIndex], $seriesNameOverride, $useOverrides, $dontMoveDir);
else
{
	echo "Use the wrapping script, the input to this is way too intolerant to be used directly. Supply the filename as the argument\n";
}

?>
