<?php
//turn off notices
//error_reporting(E_ALL ^ E_NOTICE);

include 'complete_atom_to_ckan_config.php';

//open new connection to mysql
  $link = new mysqli("localhost", $mysql_user, $mysql_password, $mysql_database);

  if ($link->connect_errno) 
    {
      die('Could not connect: ' . $link->connect_errno);
    }
    
  $link->set_charset("utf8");
  
  $sql = "select id from information_object where ((level_of_description_id>191 and level_of_description_id<199) or level_of_description_id=8190 or level_of_description_id is NULL and id<>1)";
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $rows[] = $row["id"];
    }
  $result->close();
  
  foreach ($rows as $line)
  {
    $q2 = "select date from event_i18n join event on event.id=event_i18n.id where information_object_id=".$line.";";
    $res2 = $link->query($q2);
    while($row = $res2->fetch_assoc())
      {
	$date = $row["date"];
      }
    $res2->close();
    
    if(!empty($date)){  
    if (strpos($date,"19")!== false) $term_id=???;//ww1
    else $term_id=???;//mm

    $sql="insert into object values ('QubitObjectTermRelation',NOW(),NOW(),NULL,0)";
    $result = $link->query($sql);
    
    $key=$link->insert_id;
    
    $sql="insert into object_term_relation values(".$key.", ".$line.", ".$term_id.", null, null)";
    $result = $link->query($sql);
    
    $sql="update object set updated_at=NOW() where id=".$line;
    $result = $link->query($sql);   
    
     }
    unset($date);
  }

echo "DONE";
?>