<?php

/*get the timestamp from database of the last transfer*/
function get_date($link)
  {
    $sql = "select date from harvester_date;";
    $result = $link->query($sql);
    $rows = array();
    while($row = $result->fetch_assoc())
      {
	$date[] = $row["date"];
      }
    $result->close();
  
    //write to log
    $log=fopen('ckan_transfer.log', 'a');
    $l='***************************'.PHP_EOL.'STARTING NEW SYNCHRONIZATION:'.date("Y-m-d H:i:s").PHP_EOL.'***************************'.PHP_EOL;            
    fwrite($log, $l);
    fclose($log);
  
    //this is the date
    return $date[0];
    //return date("M-d-Y", mktime(0, 0, 0, 12, 32, 1997)); for tests
  }
  
/*put the timestamp of the currently finished transfer*/
function put_date($link)
  {
    $sql = "update harvester_date set harvester_date.date=NOW();";
    $result = $link->query($sql);
  }

/*get the ead content of the given slug*/
function get_ead($atom_url, $slug, $ch)
  { 
    $page = $atom_url.'/index.php/'.$slug.';ead?sf_format=xml';

  // SET FILE TO DOWNLOAD
  curl_setopt($ch, CURLOPT_URL, $page);

  // EXECUTE 2nd REQUEST (FILE DOWNLOAD)
  $content = curl_exec ($ch);

  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($content);
  return $dom->saveXML();
  }

/*get ckan id for the given resource*/
function get_ckan_id($name, $link)
  {     
    //get resource ckan id from local database
    $sql = "select repository_resource_id from harvester_ead where atom_ead_id=".$name.";";
    $result = $link->query($sql);  
    $rows = array();
    while($row = $result->fetch_assoc())
      {
        $rows[] = $row["repository_resource_id"];
      }
    $result->close();
    
    return $rows[0];
  }

/*get ckan id for the given eag*/
function get_ckan_eag_id($name, $link)
  {     
    //get resource ckan id from local database
    $sql = "select repository_resource_id from harvester_eag where atom_eag_id=".$name.";";
    $result = $link->query($sql);  
    $rows = array();
    while($row = $result->fetch_assoc())
      {
        $rows[] = $row["repository_resource_id"];
      }
    $result->close();
    
    return $rows[0];
  }

?>
