<?php

define('SHOWDEBUG', false);

//the target filename: total path will be $targetDir$formatStr
$formatStr = "<SeriesName>/Season <SeriesNo>/<SeriesName> - [<SeriesNo>x<EpisodeNo>] - <EpisodeName>";
//the base directory, 
$targetDir = "/media/SG-2TB/storage/TV/";

//punctuation characters to replace with whitespace (mainly for windows/samba compatibility)
$punctuationCharsToKill = array( ",", ".", "?", "!", "[", "]","-","_","'", ":", ";", '"',"(",")");

?>