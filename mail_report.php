<?php
include 'mail_report_config.php';

//open new connection to mysql
  $link = new mysqli("localhost", $mysql_user, $mysql_password, $mysql_database);

  if ($link->connect_errno) 
    {
      die('Could not connect: ' . $link->connect_errno);
    }
    
  $link->set_charset("utf8");

  //get date
  $sql = "select date from harvester_date;";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $date = $row["date"];
    }
  $result->close();

  //get number of transferred eag files
  $sql = "select count(*) as num from harvester_eag;";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $eag = $row["num"];
    }
  $result->close();
  
  //get number of archival institutions
  $sql = "select count(actor.id) as num from actor join repository on actor.id = repository.id;";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $ai = $row["num"];
    }
  $result->close();
  
  //get number of transferred ead files
  $sql = "select count(*) as num from harvester_ead;";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $ead = $row["num"];
    }
  $result->close();

  //get number of archival descriptions
  $sql = "select count(slug) as num from information_object join information_object_i18n on information_object.id=information_object_i18n.id join slug on information_object.id=slug.object_id where ((level_of_description_id>191 and level_of_description_id<199) or level_of_description_id=8190);";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $ad = $row["num"];
    }
  $result->close();

$subject='report on AtoM for '.$date;

$message='Last synchronization finished on: '.$date.PHP_EOL;
$message.='Transferred EAG files: '.$eag.PHP_EOL;
$message.='Number of archival institutions: '.$ai.PHP_EOL;
$message.='Transferred EAD files: '.$ead.PHP_EOL;
$message.='Number of archival descriptions: '.$ad.PHP_EOL;

mail($to,$subject,$message);
?>