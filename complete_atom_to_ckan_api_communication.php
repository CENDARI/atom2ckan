<?php 
include 'complete_atom_to_ckan_create_eag.php';

/*get ckan id for the given ead resource*/
function get_ckan_id_from_ckan($name,$ckan_api_url, $ckan_api_key)
  { //resource id comes from ckan
    $file_dict = array(
        'id' => $name.'.ead.xml',
        );
                            
    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_show');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
            ),
          CURLOPT_POSTFIELDS => $file_dict
          ));
                                                                                                                              
    // Send the request
    $response = curl_exec($ch2);
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
   
    return  $responseData['success'];  
  }

/*get ckan id for the given eag resource*/
function get_eag_ckan_id_from_ckan($name,$ckan_api_url, $ckan_api_key)
  { //resource id comes from ckan
    $file_dict = array(
        'id' => $name.'.eag.xml',
        );
                            
    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_show');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
            ),
          CURLOPT_POSTFIELDS => $file_dict
          ));
                                                                                                                              
    // Send the request
    $response = curl_exec($ch2);
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);

    return  $responseData['result']['id'];  
  }

/*create new dataset in ckan for the new organization*/
function ckan_create_new_dataset($authorized_form_of_name, $inst_slug, $ckan_api_url, $ckan_api_key, $atom_id, $link)
{
    // The data to send to the API
    $package_dict = array(
      'name' => $inst_slug,
      'title' => $authorized_form_of_name,
      'owner_org'=> 'cendari-archival-descriptions' 
      );

    // Setup cURL ckan_api =  
    $ch1 = curl_init($ckan_api_url.'package_create');
    curl_setopt_array($ch1, array(
	CURLOPT_POST => TRUE,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLOPT_HTTPHEADER => array(
	  'Authorization: '.$ckan_api_key,
	  'Content-Type: application/json;charset=UTF-8'
	),
	CURLOPT_POSTFIELDS => json_encode($package_dict)
      ));

    // Send the request
    $response = curl_exec($ch1);

    // Check for errors
    if($response == FALSE)
      {
	die(curl_error($ch1));
      }

    // Decode the response
    $responseData = json_decode($response, TRUE);
    curl_close ($ch1);
    
    //write to log and local database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$inst_slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully created';
        $sql = "insert into harvester_eag (atom_eag_id,atom_eag_slug,repository_resource_id,sync_date) values (".$atom_id.",'".$inst_slug."','".$responseData['result']['id']."',"."NOW());";
        $result = $link->query($sql);
      }
    else $l=$authorized_form_of_name.' '.$l.' '.$responseData['error']['name'];
    $l=$l.PHP_EOL;
    fwrite($log, $l);
    fclose($log);
}

/*transfer new ead file to ckan*/
function ckan_create_new_resource($identifier, $authorized_form_of_name, $inst_slug, $slug, $holdings, $ch, $atom_url, $ckan_api_url, $ckan_api_key, $ckan_url, $atom_id, $link)
  { 
    $tmp=fopen($slug.'.ead.xml', 'w');
    fwrite($tmp, get_ead($atom_url, $slug, $ch));
    fclose($tmp);
    
    $file_dict = array(
        'package_id' => $inst_slug,
        'url' => $ckan_url.$inst_slug.'/resource/'.$slug.'.ead.xml',
        'description' => $holdings,
        'name' => $slug.'.ead.xml',
        'webstore_url' => $atom_url.'/index.php/'.$slug,
        'format' => 'XML',
        'resource_type' => 'file.upload',
        'url_type' => 'upload',
        'upload' => '@'.$slug.'.ead.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_create');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);

    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
    
    //write to log and local database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully created';
        $sql = "insert into harvester_ead (atom_ead_id,atom_ead_slug,atom_eag_slug,repository_resource_id,sync_date) values (".$atom_id.",'".$slug."','".$inst_slug."','".$responseData['result']['id']."',"."NOW());";
        $result = $link->query($sql); 
      }
    else $l=$identifier.' '.$authorized_form_of_name.' '.$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;
    fwrite($log, $l);
    fclose($log); 
    
    //delete temporary local file           
    unlink($slug.'.ead.xml');      
  }

/*update existing ead ead file at ckan*/
function ckan_update_resource($identifier, $authorized_form_of_name, $inst_slug, $slug, $holdings, $ch, $atom_url, $ckan_api_url, $ckan_api_key, $ckan_url, $atom_id, $link)
  { 
    $tmp=fopen($slug.'.ead.xml', 'w');
    fwrite($tmp, get_ead($atom_url, $slug, $ch));
    fclose($tmp);
    
    $id= get_ckan_id($atom_id, $link);
    
    $file_dict = array(
        'id' => $id,
        'last_modified' => date("Y-m-d H:i:s"),
        'webstore_url' => $atom_url.'/index.php/'.$slug,
        'upload' => '@'.$slug.'.ead.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_update');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);


    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
            
    //write to log and database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully updated';
        $sql = "update harvester_ead set sync_date=NOW() where atom_ead_id=".$atom_id.";";
        $result = $link->query($sql); 
      }
                      
    else $l=$identifier.' '.$authorized_form_of_name.' '.$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;  
    fwrite($log, $l);
    fclose($log);
                                        
    //delete temporary local file 
    unlink($slug.'.ead.xml');      
  }

/*delete existing ead ead file at ckan*/
function ckan_delete_resource( $slug,  $ch, $atom_url, $ckan_api_url, $ckan_api_key, $ckan_url, $atom_id, $link)
  { 
    $tmp=fopen($slug.'.ead.xml', 'w');
    fwrite($tmp, get_ead($atom_url, $slug, $ch));
    fclose($tmp);
    
    $id= get_ckan_id($atom_id, $link);
    
    $file_dict = array(
        'id' => $id,
        'last_modified' => date("Y-m-d H:i:s"),
        'state' => 'deleted',
        'webstore_url' => $atom_url.'/index.php/'.$slug,
        'upload' => '@'.$slug.'.ead.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_update');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);


    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
            
    //write to log and database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully updated';
        $sql = "update harvester_ead set sync_date=NOW() where atom_ead_id=".$atom_id.";";
        $result = $link->query($sql); 
      }
                      
    else $l=$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;  
    fwrite($log, $l);
    fclose($log);
                                        
    //delete temporary local file 
    unlink($slug.'.ead.xml');      
  }  
  
/*create new eag file at ckan*/
function upload_eag($authorized_form_of_name, $inst_slug, $ckan_api_url, $ckan_api_key, $line, $link,$atom_url)
  {
    $tmp=fopen($inst_slug.'.eag.xml', 'w');
    fwrite($tmp, create_eag($link, $line,$inst_slug,$atom_url));
    fclose($tmp);
    
    $file_dict = array(
        'package_id' => $inst_slug,
        'url' => $ckan_api_url.$inst_slug.'/resource/'.$inst_slug.'.eag.xml',
        'description' => $inst_slug,
        'name' => $inst_slug.'.eag.xml',
        'webstore_url' => $atom_url.'/index.php/'.$inst_slug,
        'format' => 'XML',
        'resource_type' => 'file.upload',
        'url_type' => 'upload',
        'upload' => '@'.$inst_slug.'.eag.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_create');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);

    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
    
    //write to log and local database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$inst_slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully created';
        $sql = "insert into harvester_eag (atom_eag_id,atom_eag_slug,repository_resource_id,sync_date) values (".$line.",'".$inst_slug."','".$responseData['result']['id']."',"."NOW());";
        $result = $link->query($sql); 
      }
    else $l=$line.' '.$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;
    fwrite($log, $l);
    fclose($log); 
    
    //delete temporary local file           
    unlink($inst_slug.'.eag.xml');
    //echo create_eag($link, $line); 
  }

/*update existing eag file at ckan*/
function update_eag($authorized_form_of_name, $inst_slug, $ckan_api_url, $ckan_api_key, $line, $link,$atom_url)
  { 
    $tmp=fopen($inst_slug.'.eag.xml', 'w');
    fwrite($tmp, create_eag($link, $line,$inst_slug,$atom_url));
    fclose($tmp);
    
    $id= get_ckan_eag_id($line, $link);
    
    $file_dict = array(
        'id' => $id,
        'last_modified' => date("Y-m-d H:i:s"),
        'webstore_url' => $atom_url.'/index.php/'.$inst_slug,
        'upload' => '@'.$inst_slug.'.eag.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_update');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);


    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
            
    //write to log and database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$inst_slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully updated';
        $sql = "update harvester_eag set sync_date=NOW() where atom_eag_id=".$line.";";
        $result = $link->query($sql); 
      }
                      
    else $l=$line.' '.$authorized_form_of_name.' '.$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;  
    fwrite($log, $l);
    fclose($log);
                                        
    //delete temporary local file 
    unlink($inst_slug.'.eag.xml');      
  }
  
/*delete existing eag file at ckan*/
function delete_eag($inst_slug, $ckan_api_url, $ckan_api_key, $line, $link,$atom_url)
  { 
    $tmp=fopen($slug.'.eag.xml', 'w');
    fwrite($tmp, create_eag($link, $line));
    fclose($tmp);
    
    $id= get_ckan_eag_id($line, $link);
    
    $file_dict = array(
        'id' => $id,
        'last_modified' => date("Y-m-d H:i:s"),
        'state' => 'deleted',
        'webstore_url' => $atom_url.'/index.php/'.$inst_slug,
        'upload' => '@'.$inst_slug.'.ead.xml'
        );

    // Setup cURL ckan_api =  
    $ch2 = curl_init($ckan_api_url.'resource_update');
    curl_setopt_array($ch2, array(
          CURLOPT_POST => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_HTTPHEADER => array(
            'Authorization: '.$ckan_api_key,
            'Content-Type: multipart/form-data;charset=UTF-8',
	    ),
          CURLOPT_POSTFIELDS => $file_dict
	  ));

    // Send the request
    $response = curl_exec($ch2);


    // Check for errors
    if($response == FALSE)
      {
        die(curl_error($ch2));
      }
    
    $responseData = json_decode($response, TRUE);
    curl_close ($ch2);
            
    //write to log and database
    $log=fopen('ckan_transfer.log', 'a');
    $l=$slug;
    if ($responseData['success']) 
      {
        $l=$l.' successfully updated';
        $sql = "update harvester_eag set sync_date=NOW() where atom_eag_id=".$line.";";
        $result = $link->query($sql); 
      }
                      
    else $l=$line.' '.$authorized_form_of_name.' '.$l.' '.$responseData['error']['message'];
    $l=$l.PHP_EOL;  
    fwrite($log, $l);
    fclose($log);
                                        
    //delete temporary local file 
    unlink($slug.'.eag.xml');      
  }  
?>