<?php

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

function processFile($fn)
{
    global $formatStr, $punctuationCharsToKill, $targetDir;

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

	// create object
	$tvapi = new Thetvdb('DC9BAD6196023212');
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
//	var_dump($ep_info);
	/*
	// get information about the serie, without the episodes
	$serie_info = $tvapi->GetSerieData($serieid);

	// get information about the serie, including the episodes
	$serie_info = $tvapi->GetSerieData($serieid,true);
	*/

	if($ep_info)
	{
		$SeriesNo 	= str_pad($ep_info['season'],2,"0",STR_PAD_LEFT);
        $EpisodeNo 	= str_pad($ep_info['episode'],2,"0",STR_PAD_LEFT);

        $EpisodeName = trim(str_replace(":","-",$ep_info['name']));

        //get the extension
        $extMatch = array();
        preg_match("/^.*\.([^\.]+)$/",$origFilename,$extMatch);

		$newFilename = str_replace(array("<SeriesName>","<SeriesNo>","<EpisodeNo>","<EpisodeName>"),array($tvSeriesName,$SeriesNo,$EpisodeNo,$EpisodeName),$formatStr).".".$extMatch[1];

		$pathdir = $targetDir;
		echo "dir=`dirname \"$pathdir$newFilename\"`; ";
		echo '[[ -d "$dir" ]] || mkdir -p "$dir"; '."\n";
		echo 'mv "'.$origFilename.'" "'.$pathdir.$newFilename.'"';
	}
	echo "\n";
}

if(!empty($argv[1]))
	processFile($argv[1]);
else
{
	echo "Supply the filename as the argument\n";
}
?>
