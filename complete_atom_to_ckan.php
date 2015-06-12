<?php
//turn off notices
//error_reporting(E_ALL ^ E_NOTICE);

include 'complete_atom_to_ckan_config.php';
include 'complete_atom_to_ckan_api_communication.php';
include 'complete_atom_to_ckan_database_communication.php';

/**********************************************************************************/
/*MAIN*/
/**********************************************************************************/
/*check date*/
  //open new connection to mysql
  $link = new mysqli("localhost", $mysql_user, $mysql_password, $mysql_database);

  if ($link->connect_errno) 
    {
      die('Could not connect: ' . $link->connect_errno);
    }
    
  $link->set_charset("utf8");
  
  $last_date = get_date($link);
  
  //open new connection to atom
  // INIT CURL
  $ch = curl_init();

  // SET URL FOR THE POST FORM LOGIN
  curl_setopt($ch, CURLOPT_URL, $atom_url.'/index.php/;user/login');

  // ENABLE HTTP POST
  curl_setopt ($ch, CURLOPT_POST, 1);

  // SET POST PARAMETERS : FORM VALUES FOR EACH FIELD
  $log=http_build_query(
    array(
      'email'=>$email,
      'password'=>$password
    ));
  curl_setopt ($ch, CURLOPT_POSTFIELDS, $log);

  // IMITATE CLASSIC BROWSER'S BEHAVIOUR : HANDLE COOKIES
  curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');

  # Setting CURLOPT_RETURNTRANSFER variable to 1 will force cURL
  # not to print out the results of its query.
  # Instead, it will return the results as a string return value
  # from curl_exec() instead of the usual true/false.
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

  // EXECUTE 1st REQUEST (FORM LOGIN)
  $store = curl_exec ($ch);

  //get institution from atom
  $sql = "select actor.id from actor join repository on actor.id = repository.id";// where actor.id = 1926"; /for tests
  $result = $link->query($sql);
  $rows = array();
  while($row = $result->fetch_assoc())
    {
      $rows[] = $row["id"];
    }
  $result->close();

  // for each institution
  foreach ($rows as $line)
  {
    //get identifier
    $q1 = "select identifier from repository where id =".$line.";";
    $res1 = $link->query($q1);
    while($row = $res1->fetch_assoc())
      {
	$row1[] = $row;
      }
    $res1->close();
    foreach($row1 as $row)
      {
	$identifier[] = $row["identifier"];
      }
    
    //get institution slug
    $q1a = "select slug from slug where slug.object_id =".$line.";";
    $res1a = $link->query($q1a);
    while($row = $res1a->fetch_assoc())
      {
	$row1a[] = $row;
      }
    $res1a->close();  
    foreach($row1a as $row)
      {
	$inst_slug[] = $row["slug"];
      }

    //get authorized_form_of_name
    $q2 = "select authorized_form_of_name from actor_i18n where actor_i18n.id =".$line.";";
    $res2 = $link->query($q2);
    while($row = $res2->fetch_assoc())
      {
	$row2[] = $row;
      }
    $res2->close();
    foreach($row2 as $row)
      {
	$authorized_form_of_name[] = $row["authorized_form_of_name"];
      }
    
    //get holdings of the institution
    $q3 = "select slug, title, information_object.id from information_object join information_object_i18n on information_object.id=information_object_i18n.id join slug on information_object.id=slug.object_id where ((level_of_description_id>191 and level_of_description_id<199) or level_of_description_id=8190) and repository_id=".$line.";";
    $res3 = $link->query($q3);
    $row3 = [];
    while($row = $res3->fetch_assoc())
      {
	$row3[] = $row;
      }
    $res3->close();
    
    $size=count($row3);
    
    if($size>0)
      {
    
      foreach($row3 as $row)
        {
	  $slug[] = $row["slug"];
	  $holdings[] = $row["title"];
	  $id[]= $row["id"];
        }
      }

      //check if this is a new institution at ckan
      $file_dict = array(
        'id' => $inst_slug[0]
        );

      // Setup cURL ckan_api  
      $ch2 = curl_init($ckan_api_url.'package_show');
	curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

      //Send the request
      $response = curl_exec($ch2); 
      $responseData = json_decode($response, TRUE);
      curl_close ($ch2);
      
      if(!$responseData['success'])
        {
          $responseAnswer=$responseData['error']['message'];
          if ($responseAnswer =='Not found') 
	    {
	      ckan_create_new_dataset($authorized_form_of_name[0], $inst_slug[0], $ckan_api_url, $ckan_api_key, $line, $link);
	      upload_eag($authorized_form_of_name[0], $inst_slug[0], $ckan_api_url, $ckan_api_key, $line, $link);
	    }
	  else 
	      {
		//check dates for the eag file
		$q5= "select updated_at from object where id=".$line.";";
		$res5 = $link->query($q5); 
		while($row = $res5->fetch_assoc())
		  {
		    $row4[] = $row;
		  }
		$res5->close();
		foreach($row5 as $row)
		  $updated_date = $row["updated_at"];
	  
		$last=new DateTime($last_date);
		$updated=new DateTime($updated_date);
		if ($last < $updated)
		  {
		    if(get_eag_ckan_id_from_ckan($inst_slug[0],$ckan_api_url, $ckan_api_key))
		      update_eag($authorized_form_of_name[0], $inst_slug[0], $ckan_api_url, $ckan_api_key, $line, $link);
		    else upload_eag($authorized_form_of_name[0], $inst_slug[0], $ckan_api_url, $ckan_api_key, $line, $link);
		  }
	      }
        }

    for($i=0;$i<$size;$i++)
      {
	//check dates for the ead file
	$q4= "select created_at, updated_at from object where id=".$id[$i].";";
	$res4 = $link->query($q4); 
	while($row = $res4->fetch_assoc())
	  {
	    $row4[] = $row;
	  }
	$res4->close();
	foreach($row4 as $row)
	  {
	    $creation_date = $row["created_at"];
	    $updated_date = $row["updated_at"];
	  }	
        
        $ld = new DateTime($last_date);
        $cd = new DateTime($creation_date);
        $ud = new DateTime($updated_date);

	//transfer new files to ckan
	if ($ld < $cd) ckan_create_new_resource($identifier[0], $authorized_form_of_name[0], $inst_slug[0], $slug[$i], $holdings[$i], $ch, $atom_url, $ckan_api_url, $ckan_api_key,$ckan_url, $id[$i], $link);
	//transfer updated files to ckan
	else if ($ld < $ud) 
	    {
	      if (get_ckan_id_from_ckan($slug[$i],$ckan_api_url, $ckan_api_key))
		ckan_update_resource($identifier[0], $authorized_form_of_name[0], $inst_slug[0], $slug[$i], $holdings[$i], $ch, $atom_url, $ckan_api_url, $ckan_api_key,$ckan_url, $id[$i], $link);
	      else ckan_create_new_resource($identifier[0], $authorized_form_of_name[0], $inst_slug[0], $slug[$i], $holdings[$i], $ch, $atom_url, $ckan_api_url, $ckan_api_key,$ckan_url, $id[$i], $link);
	    }    
      }
      
    //clear local variables
    unset($q1, $res1, $row1);
    unset($q1a, $res1a, $row1a);
    unset($q2, $res2, $row2);
    unset($q3, $res3, $row3);
    unset($q4, $res4, $row4);
    unset($q5, $res5, $row5);
    unset($identifier, $inst_slug, $authorized_form_of_name, $slug, $holdings, $id, $size, $creation_date, $updated_date, $ld, $cd, $ud);      
  }

  //put the new date and finalize
  curl_close ($ch);
  put_date($link);
  $link->close(); 
?>