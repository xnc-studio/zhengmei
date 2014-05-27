<?php

class BaseController extends Controller {



	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}
	protected function getUpyunLink($hash,$type,$thum=''){
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
		if($thum){
			$thum='!'.$thum;
		}
		$host_name='';
		switch ($type) {
			case PHOTO_TYPE_PHOTO:
				$type='h';
				$host_name='fbgirls.b0.upaiyun.com';
				break;
			case PHOTO_TYPE_AVATAR:
				$type='a';
				$host_name='fbgirls-avatar.b0.upaiyun.com';
				break;
			default:
				# code...
				break;
		}
		return sprintf("http://%s/%s/%s.jpg%s",$host_name,$type,$name,$thum);
	}
	protected function sourceTypeName($type){
		$rst=array('1'=>'脸书','2'=>'微博');
		if(isset($rst[$type])){
			return $rst[$type];
		}
		return '未知来源';
	}
	protected function timeFormat($time){
		return date("Y-m-d H:i:s",$time);
	}
}
