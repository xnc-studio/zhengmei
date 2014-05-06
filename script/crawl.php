<?php
date_default_timezone_set('GMT');
if(isset($argv)&&count($argv)>=2){
	$_REQUEST['action']=$argv[1];
}
$action=isset($_REQUEST['action'])?$_REQUEST['action']:null;
if($action){
	call_user_func($action);
}

function get_lastest_daily_date(){
	$sql = sprintf("SELECT `value` FROM `settings` where `key`='lastest_daily_date';");
	$rst = mysql_get_cell($sql);
	if(!$rst){
		$rst=strtotime('2014-03-30');
	}
	return $rst;
}
function set_lastest_daily_date($date){
	$sql = sprintf("UPDATE `settings` SET `value`='%s' where `key`='lastest_daily_date';",$date);
	mysql_query($sql);
}

function get_facebook_list_from_curator(){
	$temp = 'http://curator.im/girl_of_the_day/%s/';
	$date=get_lastest_daily_date();
	// $date=strtotime('20140320');
	$today = strtotime(date('Y-m-d'));
	while (true) {
		$date+=(24*60*60);
		if($date>$today) break;
		$link = sprintf($temp,date('Y-m-d',$date));
		$html=crawl_html($link);
		$name = trim(strmid($html, "<title>"," | 一天一妹 | 小海嚴選正妹</title>"));
		$facebook_link = trim(strmid($html, ");\" href=\"https://facebook.com/","/\"><img width=\"45\" height=45\""));
		$str = sprintf("%s\t%s\t%s\n",date('Y-m-d',$date),$name,$facebook_link);
		$fid = str_replace('https://facebook.com/', '', $facebook_link);
		mysql_query(sprintf("INSERT INTO `members`(`fid`,`type`,`name`) VALUES('%s','%s','%s');",$fid,1,$name));
		// var_dump($name,mb_detect_encoding($name));
		set_lastest_daily_date($date);
		echo $str;
	}
	db_close();
	// fclose($fobj);
}

// 每天获取facebook账号逻辑
function get_fb_account_daily(){
	echo "-----------------fetch members-----------------\n";
	echo "fecth from daily\n";
	get_facebook_list_from_curator();
	echo "fecth from eachpage\n";
	get_all_facebook_ids_while();
	echo "finished fetch\n";

	echo "-----------------fetch photos-----------------\n";
	get_all_fb_photos();


	

}

function get_lastest_stream_id(){
	$sql = sprintf("SELECT `value` FROM `settings` where `key`='lastest_stream_id';");
	$rst = mysql_get_cell($sql);
	if(!$rst){
		$rst=432;
	}
	return $rst;
}
function set_lastest_stream_id($id){
	$sql = sprintf("UPDATE `settings` SET `value`='%s' where `key`='lastest_stream_id';",$id);
	mysql_query($sql);
}

function get_lastest_stream_index(){
	$html=crawl_html('http://curator.im/stream/');
	if($html){
		$id = trim(strmid($html, "<div id=\"items\">\n<div class=\"item\">\n<div class=\"box\" itemscope itemtype=\"http://schema.org/ImageObject\">\n<div style=\"width: 250px; height: 368px\">\n<a class=\"popup\" href=\"/item/","/\"><img alt=\""));
		if($id) return (int)$id;
	}
	return 0;
}

// 虚幻获取当前可以获取到的所有facebook地址列表
function get_all_facebook_ids_while(){
	db_close();
	$index=get_lastest_stream_id();
	echo sprintf("now_index is %s\n",$index);
	$latest_index=get_lastest_stream_index();
	echo sprintf("latest_index is %s\n",$latest_index);
	while (true) {
		$index++;
		if($index>$latest_index) break;
		echo sprintf("fetching_index is %s/%s\n",$index,$latest_index);
		$rst = get_facebook_id_from_curator_img($index);
		set_lastest_stream_id($index);
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
		
		$fid = str_replace('http://facebook.com/', '', $facebook_link);
		$str = sprintf("\t%s\t%s\t%s\n",date('Y-m-d',$today),$name,$fid);
		if(!$fid) return false;
		$rst = mysql_query(sprintf("INSERT INTO `members`(`fid`,`type`,`name`) VALUES('%s','%s','%s');",$fid,1,$name));
		// fwrite($fobj, $str);
		echo $str;
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

function get_all_fb_photos(){
	$sql = sprintf("SELECT mid from members where type=1 order by mid desc;");
	$data=mysql_get_rows($sql);
	if($data){
		foreach ($data as $row) {
			$mid=$row['mid'];
			batch_fetch_one_fb_photos($mid);
		}
	}
}
// 获取单个图片的原始图片
function get_fb_original_image($imgae_id=null){
	if(!$imgae_id){
		$imgae_id='301581799998247';
	}
	$url=sprintf('https://www.facebook.com/photo.php?fbid=%s',$imgae_id);
	$html=crawl_html_proxy($url);
	if($html){
		$ourl = trim(strmid($html, "Open Photo Viewer</a><a class=\"fbPhotosPhotoActionsItem\" href=\"","dl=1\" rel=\"ignore\" target=\"_blank\">Download</a><a class=\""));
		// $ourl=str_replace("_n", "_o", $ourl);
		$ourl=preg_replace("/\?$/", '', $ourl);
		$ourl=str_replace("&amp;", "&", $ourl);
		$ourl=preg_replace("/&$/", '', $ourl);
		return $ourl;
	}
	return false;
}
function is_same_image($url1='',$url2=''){
	// echo  'dss';
	if(!$url1) $url1='https://fbcdn-sphotos-c-a.akamaihd.net/hphotos-ak-ash3/v/t1.0-9/1479299_762789460401923_525712666_n.jpg?oh=3d87bb876d4d47f5e301f8e66ffd99fd&oe=53DB7976&__gda__=1406873376_ad691456ac1908ed1dc9ece10c9b68d8';
	if(!$url2) $url2='https://fbcdn-sphotos-c-a.akamaihd.net/hphotos-ak-ash3/1479299_762789460401923_525712666_o.jpg?oh=5ad7f6cdcb5fbaa4e584295648723fa0&oe=53C18E1D&__gda__=1407320694_b71e90d3d3130926843d574555b9fdc';
	if(preg_match("/([\d_]+)_[on]\.jpg/", $url1,$m)){
		$id1=$m[1];
		if(preg_match("/([\d_]+)_[on]\.jpg/", $url2,$m)){
			$id2=$m[1];
			if($id1==$id2){
				// echo 1;
				return true;
			}
		}
	}
	// echo 2;
}

//获取所有图片的原始地址
function get_fb_original_image_all(){
	$index_limit=20;
	$page=0;
	$facebook=init_fb();
	while (true) {
		$sql = sprintf("SELECT P.source_id,M.url,P.hash,P.id FROM `photos` P left join images_fetch M  on P.hash=M.hash where P.source_type=1 and M.ofetched=0 order by P.id asc limit %s,%s;",$page*$index_limit,$index_limit);
		// echo "\n".$sql."\n";
		$data=mysql_get_rows($sql);
		if($data){
			foreach ($data as $row) {
				$source_id=$row['source_id'];
				echo sprintf("\n\n\nphoto %s :\n",$row['id']);
				$ourl=get_fb_original_image($source_id);
				if($ourl===false){
					echo sprintf("can not get the data\n");
					break;
				}
				echo sprintf("(%s)\n(%s)\n(%s)\n",$source_id,$ourl,$row['url']);
				if(is_same_image($ourl,$row['url'])){
					$sql = sprintf("UPDATE images_fetch set ofetched=1,onew=0 where hash='%s';",$row['hash']);
					mysql_query($sql);
					echo 'has no bigger image';
				}
				else{
					$sql = sprintf("UPDATE images_fetch set ofetched=1,onew=1 where hash='%s';",$row['hash']);	
					mysql_query($sql);
					$ohash=md5($ourl);
					$sql = sprintf("UPDATE photos set ohash='%s' where hash='%s';",$ohash,$row['hash']);
					mysql_query($sql);
					$sql = sprintf("INSERT INTO`images_fetch`(`hash`,`url`,`uploaded`,`onew`,`ofetched`) VALUES('%s','%s',0,0,1);",$ohash,$ourl);
					mysql_query($sql);
					echo 'get a bigger image';
				}
			}
		}
		else{
			break;
		}
		$page++;
	}
}


// function
// 数量为0 的每10分钟都要计算一次
// 连续10次
function get_fb_likes_number_0(){
	$index_limit=2;
	$page=0;
	$facebook=init_fb();
	while (true) {
		$sql = sprintf("SELECT id,source_id from `photos` where source_type=1 and count_like=0  order by id asc limit %s,%s;",$page*$index_limit,$index_limit);
		// echo $sql."\n";
		$data=mysql_get_rows($sql);
		if($data){
			foreach ($data as $row) {
				$source_id=$row['source_id'];
				$count_like=0;
				$likes=$facebook->api(sprintf("/%s/likes?summary=true",$source_id));
				if($likes&&isset($likes['summary']['total_count'])){
					$count_like=(int)$likes['summary']['total_count'];
				}
				echo sprintf("image %s 's like_count is %s\n",$row['id'],$count_like);
				if($count_like){
					$sql = sprintf("UPDATE `photos` set `count_like`='%s' where id='%d';",$count_like,$row['id']);
					mysql_query($sql);
				}
			}
		}
		else{
			echo 'do the job 10 min\'s later\n';
			break;
		}
		$page++;
	}
}
function get_big_url($url){
	$url=preg_replace("/(\/v)?\/t([\d-\.]+?)\/(s([\dx]+)\/)?/", '/', $url);
	$url=str_replace("_n", "_o", $url);
	return $url;
}

function parse_all_url(){
	$index=0;
	$page_count=1000;
	while (true) {
		$max_id=0;
		$sql = sprintf("SELECT id,url from images_fetch order by id desc limit %s,%s;",$index*$page_count,$page_count);
		// echo $sql;exit();
		$data=mysql_get_rows($sql);
		foreach ($data as $row) {
			$sql = sprintf("UPDATE images_fetch set url='%s' where id='%s';",get_big_url($row['url']),$row['id']);
			mysql_query($sql);
			$max_id=$row['id'];
		}
		echo sprintf("finished:%s\t max_id:%s\n",$index,$max_id);
		$index++;
	}
	// exit();
}

// 批量某个人的所有fb未存储的新图
function batch_fetch_one_fb_photos($memberid=null){
	if(!$memberid){
		$memberid='244';
	}
	$sql = sprintf("SELECT fid from members where mid=%s and type=1",$memberid);
	$info = mysql_get_row($sql);
	$fid=$info['fid'];
	$facebook=init_fb();
	$api_tmp='/%s/photos?fields=images,id,source,name,created_time,height,width,link&limit=50&type=uploaded&after=%s';
	
	$after='';
	$index=0;
	$page=1;
	echo sprintf("memberid:%s\t",$memberid);
	echo "page:\t";
	while (true) {
		$api=sprintf($api_tmp,$fid,$after);
		$data=$facebook->api($api);
		$break=false;

		echo $page."\t";
		$page++;
		// var_dump($data);
		if($data){
			// echo 'sss';
			// var_dump($data);exit();
			if($data&&count($data['data'])>0){
				// echo 222;
				foreach ($data['data'] as $row) {

					$img_id=$row['id'];
					$source=$row['source'];


					$desc=(isset($row['name'])?$row['name']:'');
					$hash=md5($source);
					$width=(int)$row['width'];
					$height=(int)$row['height'];
					$time_create=strtotime($row['created_time']);
					$count_like=0;

					// 这个之后看能否批量获取
					// $likes=$facebook->api(sprintf("/%s/likes?summary=true",$img_id));
					// if($likes&&isset($likes['summary']['total_count'])){
					// 	$count_like=(int)$likes['summary']['total_count'];
					// }
					// var_dump($like_num);exit();

					// var_dump($width,$height,date('Y-m-d H:i',$time_create),$row['created_time'],$desc,mb_detect_encoding($desc));
					

					$sql = sprintf("SELECT count(1) as `count` from `photos` where `hash`='%s';",$hash);
					$d=mysql_get_row($sql);
					// var_dump($d);
					if(!$d||$d['count']>0){
						$break=true;
						break;
					}
					else{
						// $numbers++;
						// echo 11;
						$sql = sprintf("INSERT INTO `photos`(`uid`,`hash`,`source_type`,`source_id`,`desc`,`width`,`height`,`time_create`,`count_like`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s');",$memberid,$hash,1,$img_id,$desc,$width,$height,$time_create,$count_like);
						// echo $sql;
						
						if(mysql_query($sql)){
							$sql = sprintf("INSERT INTO `images_fetch`(`hash`,`url`) VALUES('%s','%s');",$hash,$source);
							mysql_query($sql);
						}
						
						// exit();
						$index++;
						// echo sprintf("add %s\n",$index);
					}

				}
			}
			else{
				break;
			}
			$after=$data['paging']['cursors']['after'];
		}
		else{
			// echo 'ddd';
			break;
		}
		if($break) break;
	}
	echo sprintf("\n\tresults:%s\n",$index);
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
		$db = @mysql_connect('127.0.0.1','root','root') or die("Database error"); 
		@mysql_select_db('xnc_fbmm', $db); 
		mysql_query("SET NAMES utf8");  

	}
	return $db;
}

function mysql_get_count($sql){
	$result=mysql_get_row($sql);
	if(isset($result['count'])){
		return $result['count'];
	}
	return null;
}
function mysql_get_cell($sql){
	$result=mysql_get_row($sql);
	if($result){
		$values= array_values($result);
		if(isset($values[0])){
			return $values[0];
		}
	}
	

	return null;
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
	static $curl=null;
	if(!$curl){
		require_once('./class/Curl.class.php');
		$setopt = array('proxy'=>true,'proxyHost'=>'127.0.0.1','proxyPort'=>'8087');  
		$curl = new Curl($setopt);  
	}
	return $curl->get($link);  
}

function strmid($html,$before,$after){
	$len_before = mb_strlen($before,'utf8');
	// mb_stripos(haystack, needle);
	$index_before = mb_strpos($html, $before,0,'utf8')+$len_before;
	$index_after = mb_strpos($html, $after,0,'utf8');
	// var_dump(mb_strpos($html, $before,0,'utf8'));
	return mb_substr($html,$index_before,$index_after-$index_before,'utf8');
}

// 4、抓取图片，打马赛克 ，上传
function download_mark_upload(){
	$index=0;
	$page_num=10;
	$img_mask=@imagecreatefrompng('./data/fbgirls_mask_30.png');
	if(!$img_mask){
		echo "mask image error";
		exit();
	}
	else{
		$width_mask=imagesx($img_mask);
		$height_mask=imagesy($img_mask);
	}
	while (true) {
		$sql = sprintf("SELECT * from images_fetch where uploaded=0 order by id asc limit %s,%s;",$index*$page_num,$page_num);
		$data=mysql_get_rows($sql);
		if($data){
			foreach ($data as $row) {
				$id=$row['id'];
				$hash=$row['hash'];
				$url=$row['url'];
				// var_dump($id,$hash,$url);
				$image_data=crawl_html_proxy($url);
				// var_dump($image_data,$url);exit();
				if($image_data){
					$img = imagecreatefromstring($image_data);
					$width=imagesx($img);
					$height=imagesy($img);
					$dst_x=abs(ceil($width-$width_mask-2));
					$dst_y=abs(ceil($height-$height_mask-2));
					if(imagecopy ($img , $img_mask, $dst_x , $dst_y , 0 , 0 , $width_mask , $height_mask)){
						imagejpeg($img,'./data/images/'.$row['hash'].'.jpg',100);	
						echo sprintf("image %s saved\n",$row['id']);
					}
					else{
						echo sprintf("image %s cannot make mask\n",$row['id']);
					}
				}
				else{
					echo sprintf("image %s cannot get data\n",$row['id']);
					continue;
				}
				exit();
				// var_dump($img);exit();
			}

		}
		else{
			break;
		}

	}

}

// 












