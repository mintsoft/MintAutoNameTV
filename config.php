<?php

//Populate this with a TheTVDB_API key
define('THETVDB_APIKEY', 'DC9BAD6196023212');

//the target filename: total path will be $targetDir$formatStr
$formatStr = "<SeriesName>/Season <SeriesNo>/<SeriesName> - [<SeriesNo>x<EpisodeNo>] - <EpisodeName>";
//the base directory,
$targetDir = "/media/md1_storage/storage/TV/";

//punctuation characters to replace with whitespace (mainly for windows/samba compatibility)
$punctuationCharsToKill = array( ",", ".", "?", "!", "[", "]","-","_","'", ":", ";", '"',"(",")");

// Array of manual overrides & tweaks to series, episode numbers or series names
//
// in the format of $overrides[LookedUpNameFromFilename]["SeriesName"]  to Replace the Series Name
// in the format of $overrides[LookedUpNameFromFilename]["SeasonNo"]	to add or subtract to the season
// in the format of $overrides[LookedUpNameFromFilename]["EpisodeNo"]	to add or subtract to the episode
//

$overrides = array(
	"american dad" => array(
			"SeriesName"=> "",
			"SeasonNo" => +1,
			"EpisodeNo" => 0,
			),
	"the office" => array(
			"SeriesName" => "The Office US",
			"SeasonNo"   => 0,
			"EpisodeNo"   => 0,
			),
	"ghost in the shell: stand alone complex" => array(
			"SeriesName" => "Ghost in the Shell - Stand Alone Complex",
			"SeasonNo"   => 0,
			"EpisodeNo"   => 0,
			),
	"star trek tng" => array(
			"SeriesName" => "Star Trek: The Next Generation",
			"SeasonNo"   => 0,
			"EpisodeNo"  => 0
		),
	"kitchen nightmares us" => array(
			"SeriesName" => "Kitchen Nightmares",
			"SeasonNo"   => -1,
			"EpisodeNo"  => 0
		),
	"kitchen nightmares" => array(
			"SeriesName" => "Kitchen Nightmares",
			"SeasonNo"   => -1,
			"EpisodeNo"  => 0
		),
	);

?>
