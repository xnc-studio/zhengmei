<?php
// http://curator.im/girl_of_the_day/2014-03-04/ test
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
	// $fobj=fopen('./data/get_facebook_list_from_curator.csv', 'a+');
	while (true) {

		$link = sprintf($temp,date('Y-m-d',$date));
		$html=crawl_html($link);
		$name = trim(strmid($html, "jpg\"/>\n<h1>","<a target=\"_blank\" onclick="));
		$facebook_link = trim(strmid($html, ");\" href=\"","/\"><img width=\"45\" height=45\""));
		$str = sprintf("%s\t%s\t%s\n",date('Y-m-d',$date),$name,$facebook_link);
		$fid = str_replace('https://facebook.com/', '', $facebook_link);
		db_query(sprintf("INSERT INTO `members`(`fid`,`type`,`name`) VALUES('%s','%s','%s');",$fid,1,$name));
		// fwrite($fobj, $str);
		echo $str;
		$date+=(24*60*60);
		if($date>$today) break;
	}
	db_close();
	// fclose($fobj);
}

// 虚幻获取当前可以获取到的所有facebook地址列表
function get_all_facebook_ids_while(){
	db_close();
	$index=432;
	while (true) {
		# code...
		echo sprintf("%s\n",$index);
		$rst = get_facebook_id_from_curator_img($index);
		$index++;
		if(!$rst) continue;
		
	}
	db_close();
}

// 单个正妹流照片照片获取facebook的id
function get_facebook_id_from_curator_img($img_id){
	// $img_id=7771;
	$temp='http://curator.im/item/%s/';
	$url =sprintf($temp,$img_id);
	// echo $url;
	$today = time();
	$html=crawl_html($url);
	// $fobj=fopen('./data/facebook_link.txt', 'a+');
	if($html){
		$name = trim(strmid($html, "<div class=\"page-header\">\n<h1>","</h1>\n</div>"));
		$facebook_link = trim(strmid($html, "<button class=\"btn btn-default btn-lg btn-block hidden-sm\">\n<a target=\"_blank\" href=\"","\"><i class=\"fa fa-facebook-square\"></i>"));
		$str = sprintf("%s\t%s\t%s\n",date('Y-m-d',$today),$name,$facebook_link);
		$fid = str_replace('http://facebook.com/', '', $facebook_link);
		$rst = db_query(sprintf("INSERT INTO `members`(`fid`,`type`,`name`) VALUES('%s','%s','%s');",$fid,1,$name));
		// fwrite($fobj, $str);
		echo sprintf("%s\t%s\n",$str,$rst);
		if($name&&$facebook_link) return true;
	}
	// fclose($fobj);
	// if()
	
	return false;
}

function init_fb(){
	static $facebook=null;
	if(!$facebook){
		require './class/facebook-php-sdk/src/facebook.php';
		$facebook = new Facebook(array(
		  'appId'  => '112161425513130',
		  'secret' => 'cab4f988e43e8cdb12ced08f4e7f4116',
		));
	}
	return $facebook;
	
}
// 批量某个人的所有fb未存储的新图
function batch_fetch_one_fb_photos($memberid=null){
	if(!$memberid){
		$memberid='243';
	}
	$sql = sprintf("SELECT fid from members where mid=%s and type=1",$memberid);
	$info = mysql_get_row($sql);
	$fid=$info['fid'];
	$facebook=init_fb();
	$api_tmp='/%s/photos?fields=id,source,name&limit=50&type=uploaded&after=%s';
	echo sprintf("-------\nMemberid:%s\n",$memberid);
	$after='';
	$index=0;
	while (true) {
		$api=sprintf($api_tmp,$fid,$after);
		// echo $api;
		$data=$facebook->api($api);
		$break=false;
		// var_dump($data);
		if($data){
			// print_r($data);
			// $data=json_decode($data,true);
			// print_r($data['data']);exit();
			if($data&&count($data['data'])>0){
				foreach ($data['data'] as $row) {
					$img_id=$row['id'];
					$source=$row['source'];
					$desc=(isset($row['name'])?$row['name']:'');
					$hash=md5($source);
					$sql = sprintf("SELECT count(1) as `count` from `photos` where `hash`='%s';",$hash);
					$d=mysql_get_row($sql);
					// var_dump($d);
					if(!$d||$d['count']>0){
						$break=true;
						break;
					}
					else{
						// echo 11;
						$sql = sprintf("INSERT INTO `photos`(`uid`,`hash`,`source_type`,`source_id`,`desc`) VALUES('%s','%s','%s','%s','%s');",$memberid,$hash,1,$img_id,$desc);
						// echo $sql;
						mysql_query($sql);
						$sql = sprintf("INSERT INTO `images_fetch`(`hash`,`url`) VALUES('%s','%s');",$hash,$source);
						mysql_query($sql);
						$index++;
						echo sprintf("add %s\n",$index);
					}

				}
			}
			$after=$data['paging']['cursors']['after'];
		}
		if($break) break;
	}

// print_r($naitik);

}


function db_get_instance($action=true){
	$db=null;
	if($action){
		if(!$db){
			$db = new SQLite3('./data/zhengmei.db');
			$db->busyTimeout(1000);
		}
		return $db;
	}
	else{
		$db && $db->close();
		unset($db);
		return true;
	}
}
function db_close(){
	return db_get_instance(false);
}
function db_query($sql){
	// echo $sql;
	$db=db_get_instance();
	if($db){
		$rst = @$db->exec($sql);
		if(!$rst){
			// var_dump($db->lastErrorCode());
			if($db->lastErrorCode()==19) return true;
			$seconds=rand(1,10)/4;
			echo 'db locked, sleep for '.$seconds .' seconds'."\n";
			db_close();
			sleep($seconds);
			return db_query($sql);
		}
		return $rst;
	}
	else{
		return false;
	}
	// db_close();

}

function mysql_get_instance(){
	
	$db=null;
	if(!$db){
		$db = @mysql_connect('112.124.8.144','beiliao','beiliao123') or die("Database error"); 
		@mysql_select_db('xnc_fbmm', $db); 

	}
	return $db;
}


function mysql_get_rows($sql){
	mysql_get_instance();
	$result=mysql_query($sql);
	$rst=array();
	if($result){

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	 		$rst[]=$row;
		}
	}
	return $rst;
}

function mysql_get_row($sql){
	mysql_get_instance();
	$result=mysql_query($sql);
	if($result){

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	 		return $row;
		}
	}
	return NULL;
}

function crawl_html($link){
	$html=@file_get_contents($link);
	return $html;
}
function crawl_html_proxy($link){
	require_once('./class/Curl.class.php');
  
	//使用代理  
	$setopt = array('proxy'=>true,'proxyHost'=>'127.0.0.1','proxyPort'=>'8087');  
	$cu = new Curl();  
	//得到 baidu 的首页内容  
	echo $cu->get('http://baidu.com/');  
	  
	//模拟登录  
	$cu->post('http://www.***.com',array('uname'=>'admin','upass'=>'admin'));  
	echo $cu->get('http://www.***.com');  
	  
	//上传内容和文件  
	echo $cu->post('http://a.com/a.php',array('id'=>1,'name'=>'yuanwei'),  
	array('img'=>'file/a.jpg','files'=>array('file/1.zip','file/2.zip')));  
	  
	//得到所有调试信息  
	echo 'ERRNO='.$cu->errno();  
	echo 'ERROR='.$cu->error();  
	print_r($cu->getinfo()); 
	return $html;
}

function strmid($html,$before,$after){
	$len_before = mb_strlen($before,'utf8');
	// mb_stripos(haystack, needle);
	$index_before = mb_strpos($html, $before,0,'utf8')+$len_before;
	$index_after = mb_strpos($html, $after,0,'utf8');
	return mb_substr($html,$index_before,$index_after-$index_before,'utf8');
}













