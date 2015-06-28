<?php

require_once 'config.php';
require_once 'Twitter.php';

function getNow(){
	$api = "http://judge.u-aizu.ac.jp/onlinejudge/webservice/user_list?affiliation=meiji";
	$api = simplexml_load_file($api);
	for($i = 0; $i<count($api); $i++)
		$array[trim($api->user[$i]->id)] = trim($api->user[$i]->solved);
	return $array;
}

function getLast(){
	$array = parse_ini_file('aoj_last.ini');
	return $array;
}

function setNow($array){
	$fp = fopen('aoj_last.ini', 'w');
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
	$api = "http://judge.u-aizu.ac.jp/onlinejudge/webservice/status_log?user_id=".$user_id;
	$api = simplexml_load_file($api);
	for($i=0; $i<count($api); $i++){
		if(trim($api->status[$i]->status)=="Accepted"){
			return trim($api->status[$i]->problem_id);
		}
	}
}

function getRank($user_id){
	$api = "http://judge.u-aizu.ac.jp/onlinejudge/webservice/user_list?affiliation=meiji";
	$api = simplexml_load_file($api);
	for($i = 0; $i<count($api); $i++){
		if(trim($api->user[$i]->id)==$user_id)
			return trim($api->user[$i]->rank);
	}
}
$now = getNow();
$array = getDiff(getLast(), $now);
for($i=0; $i<count($array); $i++){
	$problem_id = getProblemID($array->user[$i]->user_id);
	$rank = getRank($array->user[$i]->user_id);
	$srv = new Twitter();
	$text = $array->user[$i]->user_id." が AOJ の ".$problem_id." 番 を Accepted 。 解けた問題は "
			.(($array->user[$i]->solved)+1)." 問で、学内 ".count($now)." 人中 ".$rank." 位。 "
					."http://judge.u-aizu.ac.jp/onlinejudge/description.jsp?id=".$problem_id;
	$srv->post($text);
	if((($array->user[$i]->solved)+1)%100==0){
		$text = "☆彡 ".$array->user[$i]->user_id." が AOJ ".(($array->user[$i]->solved)+1)." 問達成！ ☆彡";
		$srv->post($text);
	}
	unset($srv);
}
if(count($array)>0)
	setNow($now);
