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

	echo "-----------------fetch profile-----------------\n";

	echo "fecth from profile\n";
	fetch_one_fb_avatar_all();
	echo "finished fetch\n";


	echo "-----------------update wrong fid-----------------\n";
	update_wrong_fid_all();


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
	// var_dump($html);exit();
	if($html){
		$id = trim(strmid($html, "<div id=\"items\">\n<div class=\"item\">\n<div class=\"box\" itemscope itemtype=\"http://schema.org/ImageObject\">\n<div style=\"width: 250px; height:","/\"><img alt=\""));

		$id=str_replace("\">\n<a class=\"popup\" href=\"/item/", "", $id);
		
		$id = preg_replace("/\d+px/", "",$id);
		// var_dump($id);exit();
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

//从curator上抓取图片
// function get_all_cu_photos(){

// }

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
					$sql = sprintf("INSERT INTO`images_fetch`(`hash`,`url`,`uploaded`,`onew`,`ofetched`,`iso`) VALUES('%s','%s',0,0,1,1);",$ohash,$ourl);
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

function update_wrong_fid_all(){
	$sql = sprintf("SELECT mid from members where fid_changed=1 and status=1 and username_fb is not null order by mid asc;");
	$d=mysql_get_rows($sql);
	if($d||empty($d)){
		echo "no fid to update\n";
	}
	foreach ($d as $row) {
		$memberid=$row['mid'];
		echo "update  ".$memberid."'s fid"."\n";
		update_wrong_fid($memberid);
		echo "-----------------------------\n\n\n";
	}
}

function update_wrong_fid($memberid=null){
	if(!$memberid){
		$memberid='243';
	}
	// and fid_changed=1 and status=1 and username_fb is not null
	$sql = sprintf("SELECT username_fb,fid from members where mid=%s ",$memberid);
	$info = mysql_get_row($sql);
	if(!$info||empty($info)){
		echo "no such user\n";
		return;
	}
	$facebook=init_fb();
	$username_fb=$info['username_fb'];
	if(!$username_fb) return;
	$api_tmp='/%s';
	$api=sprintf($api_tmp,$username_fb);
	// echo $api;
	$data=$facebook->api($api);
	if($data&&isset($data['id'])){
		if($info['fid']!=$data['id']){
			$new_fid=$data['id'];	
			$sql = sprintf("UPDATE members set fid='%s',fid_changed='0' where mid='%s';",$new_fid,$memberid);
			// echo $sql;
			if(!mysql_query($sql)){
				// 存在重复项目

				$sql = sprintf("SELECT * FROM members where fid='%s';",$new_fid);
				$d=mysql_get_row($sql);
				$dup_id=$d['mid'];

				$sql=sprintf("UPDATE members set status=3 where mid=%s",$memberid);
				mysql_query($sql);

				$sql = sprintf("UPDATE `photos` set `uid`='%s' where uid='%s';",$dup_id,$memberid);
				mysql_query($sql);
				echo sprintf("migrated %s to %s\n",$memberid,$dup_id);
				return true;
			}
			else{
				echo "facebook id changed to ".$new_fid."\n";
				return true;
			}
		}
		else{
			echo "no new fid";
		}
		
	}
	else{
		echo $data['error']['message']."\n";
		return;	
	}
	
	// $sql = sprintf(format)
	// print_r($data);exit();

}



//每天运行一次
function fetch_one_fb_avatar_all(){
	$today=strtotime(date('Y-m-d'));
	$sql = sprintf("SELECT mid from members where type=1 and status=1 and fid_changed=0 and (time_info_fetched=0 or time_info_fetched>%s) order by mid asc;",$today);
	$d=mysql_get_rows($sql);
	if($d||empty($d)){
		echo "no avatar to download\n";
	}
	foreach ($d as $row) {
		$memberid=$row['mid'];
		echo "fetch ".$memberid."'s avatar"."\n";
		fetch_one_fb_avatar($memberid);
		echo "-----------------------------\n\n\n";
	}
}

function fetch_one_fb_avatar($memberid=null){
	if(!$memberid){
		$memberid='244';
	}
	$sql = sprintf("SELECT fid,avatar,cover from members where mid=%s and type=1 and status=1",$memberid);
	$info = mysql_get_row($sql);
	if(!$info||empty($info)){
		echo "no such user\n";
		return;
	}
	$fid=$info['fid'];
	$avatar_old=$info['avatar'];
	$cover_old=$info['cover'];
	$facebook=init_fb();
	$api_tmp='/%s';
	$api=sprintf($api_tmp,$fid);
	$data=$facebook->api($api);
	// print_r($data);exit();
	if(!$data){
		echo "can not get profile info\n";
		return;
	}
	$today=strtotime(date('Y-m-d'));
	if($data['error']){
		if($data['error']['code']==100){
			$sql = sprintf("UPDATE members set fid_changed=1,`time_info_fetched`=%d where mid=%s;",$today,$memberid);
			mysql_query($sql);
			echo "facebook id changed,need refetch the fid\n";
			return;
		}
		elseif($data['error']['code']==21){
			if(preg_match("/Page ID \d+ was migrated to page ID (\d+)/",$data['error']['message'],$match)){
				$new_fid=$match[1];
				$sql = sprintf("UPDATE members set fid='%s',fid_changed=1 where mid=%s;",$new_fid,$memberid);
				// echo $sql;
				if(!mysql_query($sql)){
					// 存在重复项目

					$sql = sprintf("SELECT * FROM members where fid='%s';",$new_fid);
					$d=mysql_get_row($sql);
					$dup_id=$d['mid'];

					$sql=sprintf("UPDATE members set status=3 where mid=%s",$memberid);
					mysql_query($sql);

					$sql = sprintf("UPDATE `photos` set `uid`='%s' where uid='%s';",$dup_id,$memberid);
					mysql_query($sql);
					echo sprintf("migrated %s to %s\n",$memberid,$dup_id);
					return fetch_one_fb_avatar($dup_id);
				}
				else{
					echo "facebook id changed to ".$new_fid."\n";
					return fetch_one_fb_avatar($memberid);			
				}
				
			}
		}
		else{
			echo $data['error']['message']."\n";
			return;	
		}
		
	}
	// var_dump($data);

	$about=@mysql_escape_string((isset($data['about'])?$data['about']:''));
	$awards=@mysql_escape_string((isset($data['awards'])?$data['awards']:''));
	$bio=@mysql_escape_string(isset($data['bio'])?$data['bio']:'');
	$birth=(isset($data['birthday'])?strtotime($data['birthday']):0);;
	$cover=(isset($data['cover'])?(md5($data['cover']['source'])):'');
	$likes_fb=(isset($data['likes'])?$data['likes']:'');
	$link_fb=(isset($data['link'])?$data['link']:'');
	$username_fb=(isset($data['username'])?$data['username']:'');
	$interests=@mysql_escape_string(isset($data['personal_interests'])?$data['personal_interests']:'');
	$website=@mysql_escape_string(isset($data['website'])?$data['website']:'');
	$avatar_source=sprintf("https://graph.facebook.com/%s/picture?type=large",$fid);
	$avatar=md5($avatar_source);
	
	if($avatar!=$avatar_old&&$avatar_source){
		echo "upload avatar"."\n";
		$image_data=crawl_html_proxy($avatar_source);
		$hash=md5($avatar_source);	
		$file_name='./data/images/'.$hash.'.jpg';
		file_put_contents($file_name, $image_data);
		if(!upload_toyoupai($hash,'a','fbgirls-avatar')){
			return;
		}
		unlink($file_name);

		$img_id=0;
		$sql = sprintf("INSERT INTO `photos`(`uid`,`hash`,`source_type`,`source_id`,`desc`,`width`,`height`,`time_create`,`count_like`,`is_avatar`,`uploaded`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','1');",$memberid,$hash,1,$img_id,'',0,0,time(),0,1);
		if(mysql_query($sql)){
			$sql = sprintf("INSERT INTO `images_fetch`(`hash`,`url`,`ofetched`,`uploaded`) VALUES('%s','%s','1','1');",$hash,$data['cover']['source']);
			mysql_query($sql);
		}
	}
	else{
		echo "no new avatar\n";
	}

	if(($cover_old!=$cover)&isset($data['cover'])&&$data['cover']['source']){
		echo "upload conver"."\n";
		$source=$data['cover']['source'];
		$hash=md5($data['cover']['source']);
		$image_data=crawl_html_proxy($source);
		$file_name='./data/images/'.$hash.'.jpg';
		file_put_contents($file_name, $image_data);
		if(!upload_toyoupai($hash,'a','fbgirls-avatar')){
			return;
		}
		unlink($file_name);

		$img_id=0;
		$sql = sprintf("INSERT INTO `photos`(`uid`,`hash`,`source_type`,`source_id`,`desc`,`width`,`height`,`time_create`,`count_like`,`is_avatar`,`uploaded`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','1');",$memberid,$hash,1,$img_id,'',0,0,time(),0,1);
		if(mysql_query($sql)){
			$sql = sprintf("INSERT INTO `images_fetch`(`hash`,`url`,`ofetched`,`uploaded`) VALUES('%s','%s','1','1');",$hash,$data['cover']['source']);
			mysql_query($sql);
		}
	}
	else{
		echo "no new conver\n";
	}


	$sql = sprintf("UPDATE members set time_info_fetched='%s',avatar='%s',about='%s',awards='%s',bio='%s',birth='%s',cover='%s',likes_fb='%s',link_fb='%s',interests='%s',website='%s',username_fb='%s' where mid='%s';",$today,$avatar,$about,$awards,$bio,$birth,$cover,$likes_fb,$link_fb,$interests,$website,$username_fb,$memberid);
	// echo $sql;
	mysql_query($sql);

	

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
		$db = @mysql_connect('127.0.0.1','crawl','crawl') or die("Database error"); 
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

function upload_api($path,$file_name,$space='fbgirls',$reset=0){

	echo "by api\n";
	static $upyun=null;
	if(!$upyun||$reset){
		require_once('./class/upyun.class.php');
		$upyun = new UpYun($space, 'scriptupload', 'scriptupload', UpYun::ED_TELECOM);	
	}
	try{
		$fh = @fopen($file_name, 'rb');
		if(!$fh){
			echo "can not find the download file\n";
			return false;
		}
		$rsp = $upyun->writeFile($path, $fh, true);   // 上传图片，自动创建目录
		if(!$rsp){
			echo "can not upload file\n";
			return false;
		}
		echo 'succeed uploaded'."\n";
		return true;
	}
	catch(Exception $ex){
		echo sprintf("error:%s\n",$ex);
		echo "try reconnect api\n";
		echo "exit with error 2";
		exit();//重新启动当前进程
		$upyun=null;

		return upload_api($path,$file_name,'fbgrils',true);
	}
}
function upload_ftp($path,$file_name,$space,$reset=0){
	echo "by ftp\n";
	static $conn_id=null;
	if(!$conn_id||$reset){
		$conn_id&&ftp_close($conn_id);
		$conn_id=null;
		$conn_id = ftp_connect("v1.ftp.upyun.com",21,180);
		// if($space=='')
		if($conn_id&&!ftp_login($conn_id, "scriptupload/".$space, "scriptupload")){
			// var_dump($conn_id);
			echo "failed to connect ftp\n";
			sleep(2);
			return upload_ftp($path,$file_name,$space,true);
			// return false;
		}
	}
	if ($conn_id&&ftp_put($conn_id, $path, $file_name, FTP_BINARY)) {
		$conn_id=null;
		echo "file is uploaded\n";
		return true;
	} else {
		echo "can not upload file\n";
		echo "try reconnect ftp\n";

		return upload_ftp($path,$file_name,$space,true);
		// return false;
	}

}

function upload_toyoupai($hash='',$iso='n',$space='fbgrils'){
	
	if(!$hash){
		$hash='d1149425baa81d5919cb598176ecbb92';
	}
	
	$file_name='./data/images/'.$hash.'.jpg';
	
	
	$path=sprintf("/%s%s",$iso,get_upyun_name($hash));
	// var_dump($path,$file_name);exit();
	// upload_api($path,$file_name);
	if(upload_api($path,$file_name,$space)){
		echo sprintf("http://%s.b0.upaiyun.com%s\n",$space,$path);	
		return true;
	}
	echo "failed to upload\n";
	
	
}

// 4、抓取图片，打马赛克 ，上传
function download_mark_upload(){
	$index=0;
	$page_num=10;
	static $img_mask=null;
	static $width_mask=null;
	static $height_mask=null;
	if(!$img_mask){
		$img_mask=@imagecreatefrompng('./data/mm.png');
		if(!$img_mask){
			echo "mask image error";
			exit();
		}
		else{
			$width_mask=imagesx($img_mask);
			$height_mask=imagesy($img_mask);
		}

	}
	$break=false;
	while (true) {
		if($break) break;
		$sql = sprintf("SELECT * from images_fetch where uploaded=0 order by `hash` asc limit %s,%s;",$index*$page_num,$page_num);
		$data=mysql_get_rows($sql);
		if($data){
			foreach ($data as $row) {
				$id=$row['id'];
				$hash=$row['hash'];
				$url=$row['url'];
				echo $row['id']."\n";
				echo $row['hash']."\n";
				echo "try download image\n";
				$image_data=crawl_html_proxy($url);
				
				if($image_data){
					$file_name='./data/images/'.$row['hash'].'.jpg';
					if($row['iso']){
						echo "is an orgi image \n";
						@file_put_contents($file_name, $image_data);
					}
					else{
						echo "is a maskable image \n";
						$img = @imagecreatefromstring($image_data);
						if(!$img){
							echo "is not a ok image \n";
							continue;
						}
						$width=imagesx($img);
						$height=imagesy($img);
						$dst_x=abs(ceil($width-$width_mask-2));
						$dst_y=abs(ceil($height-$height_mask-2));
						if(imagecopy ($img , $img_mask, $dst_x , $dst_y , 0 , 0 , $width_mask , $height_mask)){
							imagejpeg($img,$file_name,100);	
							echo sprintf("saved to disk\n");
						}
						else{
							echo sprintf("cannot make mask\n");
						}
					}
					echo "begin upload\n";
					$rst = upload_toyoupai($row['hash']);
					unlink($file_name);
					$uploaded=1;
					if(!$rst) $uploaded=2;//上传失败
					// $uploaded=3;//ftp later
					$sql  = sprintf("UPDATE images_fetch set uploaded=%s where hash='%s';",$uploaded,$row['hash']);
					mysql_query($sql);
					$sql  = sprintf("UPDATE photos set uploaded=%s where hash='%s';",$uploaded,$row['hash']);
					mysql_query($sql);

				}
				else{
					echo sprintf("image %s cannot get data\npls check the goagent",$row['id']);
					$break=true;
					break;
				}
				echo "\n-------------------------------------\n";
				// $index++;
				// if($index==10){
				// 	exit();
				// }
				
				// var_dump($img);exit();
			}

		}
		else{
			break;
		}

	}

}

function mv_file_too_hash(){
	$path="./data/images";
	$fobj=opendir($path);
	while ($file=readdir($fobj)) {
		$file=$path.'/'.$file;
		if(is_file($file)){
			$pathinfo=pathinfo($file);
			$hash=$pathinfo['filename'];
			$target=get_upyun_name($hash);
			$target='./data/images/ftp'.$target;
			echo sprintf("move %s to %s\n",$file,$target);
			mv_file($file,$target);
		}
		
	}

}

function mv_file($source,$target){
	
	$pathinfo=pathinfo($target);
	$dirname=$pathinfo['dirname'];
	
	@mkdir($dirname,0700,true);
	// var_dump($dirname);return;
	copy($source,$target);
	unlink($source);
	return true;
} 

function get_upyun_name($hash){
	$len=4;
	$index=0;
	$name=array();
	while (true) {
		if($sub=substr($hash, $index*$len,$len)){
			$name[]=$sub;	
			$index++;
		}
		else{
			break;
		}
	}
	$name=implode('/', $name);
	return sprintf("/%s.jpg",$name);
}


function get_yahoo_list(){
	// https://tw.celebrity.yahoo.com/_xhr/mediamosaiclistlpca/?list_source=&mod_id=mediamosaiclistlpca&list_id=41cd0fcb-3695-4a3d-870e-e21dff579b81&list_style=&apply_filter=&filters=&show_author=0&show_date=1&show_popup=0&show_views=&show_tags=0&template=mosaic_8u_frame&title_length=35&show_provider=1&content_id=4be3067f-b1bb-37bc-a737-cb6551f2acf8&desc_length=165&popup_desc_length=&cache_ttl=TTL_LEVEL_30&instanceUuid=ad3e2f2e-68c3-356e-aeb9-69d9b3af1713&list_start=71&list_count=10
}









