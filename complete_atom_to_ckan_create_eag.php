<?php


function replace_special_characters($str)
{
$str1 = str_replace('"','&quot;',$str);
$str2 = str_replace('&','&amp;',$str1);
$str3 = str_replace("'",'&apos;',$str2);
$str4 = str_replace('<','&lt;',$str3);
$str5 = str_replace('>','&gt;',$str4);

return $str5;
}

function add_element($element, $values)
{
  $ret = '';
  
    foreach ($values as $value) if(!empty($value))$ret .= '<'.$element.'>'.replace_special_characters($value).'</'.$element.'>';
     
       return $ret;
       }
       
function add_person($name, $inst)
{
$ret='';

foreach ($name as $value) 
  if(!empty($value))
  {
  $i=strpos($value, ",");

  $firstname=substr($value,0,$i);
  $surname=substr($value,$i+2,strlen($value));
  
  $ret ='<person xmlns="http://www.tei-c.org/ns/1.0"><persName><forename>'.replace_special_characters($firstname).'</forename><surname>'.replace_special_characters($surname).'</surname></persName><affiliation><orgName type="institution">'.replace_special_characters($inst).'</orgName></affiliation></person>';
  }
return $ret;
}

function add_contact_person($name)
{
$ret='';

foreach ($name as $value) 
  if(!empty($value))
  {
  $i=strpos($value, ",");

  $firstname=substr($value,0,$i);
  $surname=substr($value,$i+2,strlen($value));
  
  $ret ='<resprepositor><person xmlns="http://www.tei-c.org/ns/1.0"><persName><forename>'.replace_special_characters($firstname).'</forename><surname>'.replace_special_characters($surname).'</surname></persName></person></resprepositor>';
  }
return $ret;
}

function add_lang($lang, $scr)
{

$ret=''; 
preg_match_all('~([\'"])(.*?)\1~s',$lang["value"], $ret_lang, PREG_SET_ORDER);
preg_match_all('~([\'"])(.*?)\1~s',$scr["value"], $ret_scr, PREG_SET_ORDER);
if(!empty($ret_lang)&&!empty($ret_scr)){
$ret .= '    <languagedecl>'."\n";
foreach($ret_lang as $array) $ret .= '<language langcode="'.$array[2].'" scriptcode="'.$ret_scr[0][2].'"/>'; 
$ret .= '    </languagedecl>'."\n";
}
return $ret;
}


function create_eag($link, $line,$inst_slug,$atom_url)
{
$country_name = array(
"AL" => "Albania",
"AD" => "Andorra",
"AM" => "Armenia",
"AU" => "Australia",
"AT" => "Austria",
"BY" => "Belarus",
"BE" => "Belgium",
"BA" => "Bosnia And Herzegovina",
"BG" => "Bulgaria",
"CA" => "Canada",
"HR" => "Croatia",
"CZ" => "Czech Republic",
"CN" => "China",
"DK" => "Denmark",
"EE" => "Estonia",
"FI" => "Finland",
"FR" => "France",
"DE" => "Germany",
"GB" => "United Kingdom",
"GR" => "Greece",
"HU" => "Hungary",
"IS" => "Island",
"IN"  => "India",
"IE" => "Ireland",
"IL" => "Israel",
"IT" => "Italy",
"JP" => "Japan",
"LI" => "Liechtenstein",
"LV" => "Latvia",
"LT" => "Lithuania",
"LU" => "Luxembourg",
"MK" => "Macedonia", 
"MT" => "Malta",
"MD" => "Moldova",
"MN" => "Montenegro", 
"MT" => "Malta",
"NL" => "Netherlands",
"NZ" => "New Zeland",
"NO" => "Norway",
"PL" => "Poland",
"PT" => "Portugal",
"RO" => "Romania",
"RU" => "Russia",
"SM" => "San Marino",
"RS" => "Serbia",
"SK" => "Slovakia",
"SI" => "Slovenia",
"ZA" => "South Africa",
"ES" => "Spain",
"SE" => "Sweden",
"CH" => "Switzerland",
"TZ" => "Tanzania",
"TG" => "Togo",
"TR" => "Turkey",
"UA" => "Ukraine",
"US" => "United States",
"UZ" => "Uzbekistan",
"AZ" =>	"Azerbaijan",
"KZ" =>	"Kazakhstan",
"KG" =>	"Kyrgyzstan",
"TJ" =>	"Tajikistan",
"TM" =>	"Turkmenistan",
"VA" => "Vatican City State");

$resp_inst = array("IT - FEF", "IT - UNICAS", "IT - SISMEL", "IT-FEF", "IT-UNICAS", "IT-SISMEL", "FUB");

//$link = new mysqli("localhost", $link, $line);

/*actor_i18n.id = 4227 - PAVIA*/
/*actor_i18n.id = 344  - Arhiv Firence*/
/*actor_i18n.id = 1926 - Biblioteka Vatikana*/

$row1 = array();
$row2 = array();
$row3 = array();
       
$q1 = "
select created_at, street_address, website, email, fax, telephone, contact_person, postal_code, city, country_code
from contact_information join contact_information_i18n on contact_information.id = contact_information_i18n.id
where actor_id =".$line."
;";

$res1 = $link->query($q1);

while($row = $res1->fetch_assoc())
{
$row1[] = $row;
}

$res1->close();

$q2 = "
select authorized_form_of_name, history
from actor_i18n 
where actor_i18n.id =".$line."
;";

$res2 = $link->query($q2);

while($row = $res2->fetch_assoc())
{
$row2[] = $row;
}

$res2->close();

$q3 = "
select desc_institution_identifier, identifier, holdings, finding_aids, desc_sources
from repository join repository_i18n on repository.id=repository_i18n.id
where repository.id =".$line."
;";

$res3 = $link->query($q3);

while($row = $res3->fetch_assoc())
{
$row3[] = $row;
}

$res3->close();

$q4 = "select content from note_i18n join note on note.id=note_i18n.id where note.object_id=".$line.";";

$res4 = $link->query($q4);

while($row = $res4->fetch_assoc())
{
$row4[] = $row;
}

$res4->close();

$q5 = "select name from term_i18n join term on term_i18n.id = term.id join object_term_relation on term_id=term.id join repository on object_id= repository.id where repository.id =".$line.";";

$res5 = $link->query($q5);

while($row = $res5->fetch_assoc())
{
$row5[] = $row;
}

$res5->close();

$q6 = 'select value from property_i18n join  property on property.id = property_i18n.id join repository on object_id = repository.id where repository.id = '.$line.' and name="language";';

$res6 = $link->query($q6);

while($row = $res6->fetch_assoc())
{
$row6[] = $row;
}

$res6->close();

$q7 = 'select value from property_i18n join  property on property.id = property_i18n.id join repository on object_id = repository.id where repository.id = '.$line.' and name="script";';

$res7 = $link->query($q7);

while($row = $res7->fetch_assoc())
{
$row7[] = $row;
}

$res7->close();

$q8 = 'select name form other_name_i18n join other_name on other_name_i18n.id=other_name.id and type_id=148 and object_id='.$line;
$res8 = $link->query($q8);
if(!empty($res8)){
while($row = $res8->fetch_assoc())
{
$row8[] = $row;
}
$res8->close();
}


$q9 = 'select name form other_name_i18n join other_name on other_name_i18n.id=other_name.id and type_id=149 and object_id='.$line;
$res9 = $link->query($q9);
if(!empty($res9)){
while($row = $res9->fetch_assoc())
{
$row9[] = $row;
}
$res9->close();
}


if (count($row1)>0) $created_at=$row1[0]["created_at"];
$street_address = array();
$website = array();
$email = array();
$fax = array();
$telephone = array();
$contact_person = array();
$postal_code = array();
$city = array();
if (count($row1)>0) $country_code = $row1[0]["country_code"];

foreach($row1 as $row)
{
$street_address[] = $row["street_address"];
$website[] = $row["website"];
$email[] = $row["email"];
$fax[] = $row["fax"];
$telephone[] = $row["telephone"];
$contact_person[] = $row["contact_person"];
$postal_code[] = $row["postal_code"];
$city[] = $row["city"];
}

$authorized_form_of_name = array();
$history  = array();

foreach($row2 as $row)
{
$authorized_form_of_name[] = $row["authorized_form_of_name"];
$history[]  = $row["history"];
}

$desc_institution_identifier = array();
if (count($row3)>0) $identifier = $row3[0]["identifier"];
$holdings = array();
$finding_aids = array();
$desc_sources = array();

foreach($row3 as $row)
{
$desc_institution_identifier[] = $row["desc_institution_identifier"];
$holdings[] = $row["holdings"];
$finding_aids[] = $row["finding_aids"];
$desc_sources[] = $row["desc_sources"]; 
}

if(!empty($row4)){
foreach($row4 as $row)
{
$name[]=$row["content"];
}
}

if(!empty($row5))$inst_type =((count($row5)>1)?$row5[1]["name"]:$row5[0]["name"]); 

$out='';

$out .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$out .= '<?xml-model href= "http://134.76.20.210/schemas/eag/v1.0/rnc/EAG-schema.rnc" type="application/relax-ng-compact-syntax"?>'."\n";
$out .= '<eag xmlns="http://www.ministryculture.es/">'."\n";
$out .= '  <eagheader countryencoding="iso3166-1" langencoding="iso639-1" scriptencoding="iso15924" repositoryencoding="" dateencoding="iso8601" status="draft">'."\n";
$out .= '   <mainhist>'."\n";
$out .= '      <mainevent maintype="create">'."\n";
if(!empty($created_at))$out .= '        <date normal="'.date("Y-m-d", strtotime($created_at)).'"/>'."\n";
$out .= '        <respevent>'."\n";
if(!empty($name))$out .= add_person($name,$desc_institution_identifier[0]);
$out .= '        </respevent>'."\n";
$out .= add_element("source",$desc_sources);
$out .= '      </mainevent>'."\n";
$out .= '    </mainhist>'."\n";
if(!empty($row6)&&!empty($row7)) $out .= add_lang($row6[0], $row7[0]);
$out .= '  </eagheader>'."\n";
$out .= '<eagid identifier="'.replace_special_characters($inst_slug).'" url="'.replace_special_characters($atom_url).'index.php/'.replace_special_characters($inst_slug).'" encodinganalog="identifier">'.replace_special_characters($line).'</eagid>'."\n";
$out .= '  <archguide>'."\n";
$out .= '    <identity>'."\n";
$out .= '      <repositorid countrycode="'.(!empty($country_code)? $country_code: '').'" repositorycode="'.$identifier.'"/>'."\n";
$out .= add_element("autform",$authorized_form_of_name);
if(!empty($row8))$out .= add_element("parform",$row8);
if(!empty($row9))$out .= add_element("nonparform",$row9);
$out .= '    </identity>'."\n";
$out .= '    <desc>'."\n";
$out .= '<location>';
if(!empty($country_code)) $out .= '      <country>'.$country_name[$country_code].'</country>'."\n";
$out .= add_element("postalcode",$postal_code);
$out .= add_element("municipality",$city);
$out .= add_element("street",$street_address);
$out .= '</location>';
$out .= add_element("telephone",$telephone);
$out .= add_element("fax",$fax);
foreach ($email as $em) if(!empty($em)) $out .= '<email href="'.replace_special_characters($em).'">'.replace_special_characters($em).'</email>'."\n";
foreach ($website as $ws) if(!empty($ws)) $out .= '<webpage href="'.replace_special_characters($ws).'">'.replace_special_characters($ws).'</webpage>'."\n";
if(!empty($contact_person)) $out .= add_contact_person($contact_person);
$out .= '      <repositorhist>'."\n";
$out .= '        <p>'.str_replace(PHP_EOL,'</p><p>',replace_special_characters($history[0])).'</p>'."\n";
$out .= '      </repositorhist>'."\n";
if(!empty($finding_aids)){
$out .= '      <repositorguides>'."\n";
foreach($finding_aids as $finding_aid) $out .= '        <repositorguide>'.replace_special_characters($finding_aid).'</repositorguide>'."\n"; 
$out .= '      </repositorguides>'."\n";}
if(!empty($holdings)) $out .= '      <holdings><p>'.add_element("p",str_replace(PHP_EOL,'</p><p>',$holdings)).'</p></holdings>';

$out .= '      <access question="yes"/>'."\n";
//$out .= '      <controlaccess><head>Theme</head><subject>'.($desc_institution_identifier[0] == 'FUB'? 'WW1':'MM').'</subject></controlaccess>'."\n";
if(!empty($inst_type))
{
$out .= '        <controlaccess>'.PHP_EOL.'<controlaccess>'."\n";
$out .= '          <head>Typology</head>'."\n";
$out .= '          <subject>'.replace_special_characters($inst_type).'</subject>'."\n";
$out .= '        </controlaccess>'."\n";
$out .= '      </controlaccess>'."\n";
}
$out .= '     </desc>'."\n";
$out .= '   </archguide>'."\n";
$out .= '</eag>';

/*
$xml = new XMLReader();
if (!$xml->xml($out, NULL, LIBXML_DTDVALID)) 
  echo $inst_slug." XML not valid".PHP_EOL;
*/
return $out;
/*
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($out);
return $dom->saveXML();
*/
}

?>