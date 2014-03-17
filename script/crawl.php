<?php
// http://curator.im/girl_of_the_day/2014-03-04/
if(isset($argv)&&count($argv)>=2){
	$_REQUEST['action']=$argv[1];
}
$action=isset($_REQUEST['action'])?$_REQUEST['action']:null;
if($action){
	call_user_func($action);
}

function get_facebook_list_from_curator(){
	$temp = 'http://curator.im/girl_of_the_day/%s/';
	$date='2014-01-01';
	$date=strtotime($date);
	$today = time();
	$fobj=fopen('./data/get_facebook_list_from_curator.csv', 'a+');
	while (true) {

		$link = sprintf($temp,date('Y-m-d',$date));
		$html=crawl_html($link);
		$name = trim(strmid($html, "jpg\"/>\n<h1>","<a target=\"_blank\" onclick="));
		$facebook_link = trim(strmid($html, ");\" href=\"","\"><img width=\"45\" height=45\""));
		$str = sprintf("%s\t%s\t%s\n",date('Y-m-d',$date),$name,$facebook_link);
		fwrite($fobj, $str);
		echo $str;
		$date+=(24*60*60);
		if($date>$today) break;
	}
	fclose($fobj);
}




function crawl_html($link){
	$html=file_get_contents($link);
	return $html;
}
function crawl_html_proxy($link){
	$html=file_get_contents($link);
	return $html;
}

function strmid($html,$before,$after){
	$len_before = mb_strlen($before,'utf8');
	// mb_stripos(haystack, needle);
	$index_before = mb_strpos($html, $before,0,'utf8')+$len_before;
	$index_after = mb_strpos($html, $after,0,'utf8');
	return mb_substr($html,$index_before,$index_after-$index_before,'utf8');
}













