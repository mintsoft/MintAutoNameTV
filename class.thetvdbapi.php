<?php
/*
 * This class is using the thetvdb.com API to get all kinds of 
 * information about tv-series.
 * 
 * Autor: Roel Gerrits <roel.gerrits.0@gmail.com>
 * Version: 1.0
 * Created: 18 March 2009
 * Modified: 28 March 2009
 * Licence: GPLv3
 ** http://87.248.112.8/search/srpcache?ei=UTF-8&p=class.thetvdbapi.php&vm=r&fr=yfp-t-702&u=http://cc.bingj.com/cache.aspx?q=class.thetvdbapi.php&d=4924042867706657&mkt=en-GB&setlang=en-GB&w=117fdff6,47b6a3e9&icp=1&.intl=uk&sig=_oGRVQdm_7._AHF3D8gVQA--
 */

class Thetvdb
{
   /*
    * thetvdb.com settings
    */
   private $api_key = '';
   private $lang = '';
   private $tvdbapiurl = '';
   
   
   /*
    * constructor
    * Initializes the class
    */
   public function __construct($apikey)
   {
      $this->api_key = $apikey;
      $this->lang = 'en';
      $this->tvdbapiurl = 'http://www.thetvdb.com/api/';
   }
   
   
   /*
    * This method returns the serie id for the given seriename
    */
   public function GetSerieId($seriename)
   {
      $seriename = urlencode($seriename);
      $url = $this->tvdbapiurl . 'GetSeries.php?seriesname=' . $seriename;
      
      $feed = self::DownloadUrl($url);
      $xml = simplexml_load_string($feed);
      
      $node = $xml->Series->seriesid;

      if($node !== NULL){
         $serieid = (int) $node;
         return $serieid;
      }else{
         return false;
      }
   }
   
   
   /*
    * This method returns the episode id for the
    * given serieid and season/episode number
    */
   public function GetEpisodeId($serieid,$s,$e)
   {
      $url = $this->tvdbapiurl . $this->api_key . '/series/' . $serieid . '/default/' . $s . '/' . $e . '/' . $this->lang. '.xml';
      
      $feed = self::DownloadUrl($url);
      $xml = simplexml_load_string($feed);
      
      $node = $xml->Episode->id;

      if($node !== NULL){
         $episodeid = (int) $node;
         return $episodeid;
      }else{
         return false;
      }
   }
   
   
   /*
    * This method returns information about the specified serie
    */
   public function GetSerieData($serieid,$getepisodes = false)
   {
      // get feed
      if($getepisodes === true){
         $url = $this->tvdbapiurl . $this->api_key. '/series/' . $serieid . '/all/' .$this->lang. '.xml';
      }else{
         $url = $this->tvdbapiurl . $this->api_key. '/series/' . $serieid . '/' .$this->lang. '.xml';
      }
      
      $feed = self::DownloadUrl($url);
      if($feed){
         $xml = simplexml_load_string($feed);
         
         $serie['id'] = $serieid;
         $serie['name'] = (string) $xml->Series->SeriesName;
         $serie['description'] = (string) $xml->Series->Overview;
         
         if($getepisodes === true){
            $episodes = Array();
            foreach($xml->Episode as $ep){
               $episode['id'] = (int) $ep->id;
               $episode['season'] = (int) $ep->SeasonNumber;
               $episode['episode'] = (int) $ep->EpisodeNumber;
               $episode['airdate'] = (string) $ep->FirstAired;
               $episode['name'] = (string) $ep->EpisodeName;
               $episode['description'] = (string) $ep->Overview;
               $episodes[] = $episode;
            }
            $serie['episodes'] = $episodes;
         }
         
         return $serie;
      }else{
         return false;
      }
   }
   
   
   /*
    * This method returns information about the specified episode
    */
   public function GetEpisodeData($episodeid)
   {
      // get feed
      $url = $this->tvdbapiurl .$this->api_key. '/episodes/' . $episodeid . '/' .$this->lang. '.xml';
      
      $feed = self::DownloadUrl($url);
      if($feed){
         $xml = simplexml_load_string($feed);
         
         $episode['id'] = $episodeid;
         $episode['serieid'] = (int) $xml->Episode->seriesid;
         $episode['season'] = (int) $xml->Episode->SeasonNumber;
         $episode['episode'] = (int) $xml->Episode->EpisodeNumber;
         $episode['airdate'] = (string) $xml->Episode->FirstAired;
         $episode['name'] = (string) $xml->Episode->EpisodeName;
         $episode['description'] = (string) $xml->Episode->Overview;
         
         return $episode;
      }else{
         return false;
      }
   }
   
   
   /*
    * This method downloads a file by an url,
    * if the download fails it will retry, until the number of
    * retrys specified is reached. When the last try fails the
    * method will return false.
    */
   private static function DownloadUrl($url,$retrys = 1)
   {
   /*
      $buffer = '';
      $chunksize = 8192;
      
      // try to open link
      $remotefile = @fopen($url,'r');
      if($remotefile){
         
         // download file
         while(!feof($remotefile)){
            $buffer .= fread($remotefile,$chunksize);
         }
         
         // return the downloaded stuff
         return $buffer;
      }else
      if($retrys > 0){
         
         // retry
         return self::DownloadUrl($url,$retrys -1);
      }else{
         
         // maximum of retrys reached, return false
         return false;
      }
     */
     $ch = curl_init($url);
     curl_setopt_array($ch, array( 
     	CURLOPT_HEADER => false,
     	CURLOPT_RETURNTRANSFER => true,
     	CURLOPT_FOLLOWLOCATION => true,
     	CURLOPT_MAXREDIRS  => 16,
     	CURLOPT_BUFFERSIZE => 65535,
     	CURLOPT_ENCODING => "identity"
     ));
     
     $returnVal = curl_exec($ch);
     
     curl_close($ch);
     var_dump_errstream($returnVal);
     return $returnVal;
   }
   
}

?>
