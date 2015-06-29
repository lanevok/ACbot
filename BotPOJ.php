<?php

require_once 'config.php';
require_once 'Twitter.php';

function getNow(){
  $api = "http://poj.org/searchuser?key=meiji&field=school";
  $api = file_get_contents($api);
  preg_match_all("/(userstatus\?user_id=)(.*?)(\>)(.*?)(meiji)"
		 ."(.*?)(td)(.*?)(td)(.*?)(td)(.*?)(td\>)(.*?)(\<\/td)/is"
		 ,$api,$match);
  for($i=0; $i<count($match[2]); $i++)
    $array[$match[2][$i]] = $match[14][$i];
  return $array;
}

function getLast(){
  $array = parse_ini_file('poj_last.ini');
  return $array;
}

function setNow($array){
  $fp = fopen('poj_last.ini', 'w');
  foreach ($array as $k => $v)
    fputs($fp, "$k=$v\n");
  fclose($fp);
}

function getDiff($src, $dst){
  $idx = 0;
  foreach($src as $key => $value){
    if($src[$key]<$dst[$key])
      $array[$idx++] = array("user_id"=>$key,"solved"=>$value);
  }
  if($idx==0) return;
  $xmlstr = "<?xml version=\"1.0\" ?><root></root>";
  $xml = new SimpleXMLElement($xmlstr);
  foreach($array as $arr){
    $xmlitem = $xml -> addChild("user");
    foreach($arr as $key => $value){
      $xmlitem -> addChild($key, $value);
    }
  }
  return $xml;
}

function getProblemID($user_id){
  $api = "http://poj.org/status?result=0";
  $api = file_get_contents($api);
  preg_match_all("/(userstatus\?user_id=)(.*?)(\>)(.*?)(problem\?id=)(.*?)(\>)/is"
		 ,$api,$match);
  for($i=0; $i<count($match[2]); $i++){
    if($match[2][$i]==$user_id)
      return $match[6][$i];
  }
}

function getRank($user_id){
  $api = "http://poj.org/searchuser?key=meiji&field=school";
  $api = file_get_contents($api);
  preg_match_all("/(userstatus\?user_id=)(.*?)(\>)(.*?)(meiji)"
		 ."(.*?)(td)(.*?)(td)(.*?)(td)(.*?)(td\>)(.*?)(\<\/td)/is"
		 ,$api,$match);
  for($i=0; $i<count($match[2]); $i++){
    if($match[2][$i]==$user_id){
      return ($i+1);
    }
  }
}

$now = getNow();
$array = getDiff(getLast(), $now);
for($i=0; $i<count($array); $i++){
  $problem_id = getProblemID($array->user[$i]->user_id);
  if(strlen($problem_id)!=4) continue;
  $rank = getRank($array->user[$i]->user_id);
  $srv = new Twitter();
  $text = $array->user[$i]->user_id." が POJ の ".$problem_id." 番 を Accepted 。 解けた問題は "
    .(($array->user[$i]->solved)+1)." 問で、学内 ".count($now)." 人中 ".$rank." 位。 "
    ."http://poj.org/problem?id=".$problem_id;
  $srv->post($text);
  if((($array->user[$i]->solved)+1)%100==0){
    $text = "☆彡 ".$array->user[$i]->user_id." が POJ ".(($array->user[$i]->solved)+1)." 問達成！ ☆彡";
    $srv->post($text);
  }
  unset($srv);
}
if(count($array)>0)
  setNow($now);
