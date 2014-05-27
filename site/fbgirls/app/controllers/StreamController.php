<?php

class StreamController extends BaseController {
	public function index()
	{
		$pid = Input::get('pid',0);
		$page_size = Input::get('page_size',20);

		if($pid){
			$sql = sprintf("SELECT M.name,M.fid,M.type,P.id,P.uid,P.hash,P.desc,P.width,P.height,P.time_create FROM photos P left join members M on P.uid=M.mid WHERE P.id<%d AND P.uploaded=1 ORDER BY P.time_create DESC LIMIT %s ",$pid,$page_size);	
		}
		else{
			$sql = sprintf("SELECT M.name,M.fid,M.type,P.id,P.uid,P.hash,P.desc,P.width,P.height,P.time_create FROM photos P left join members M on P.uid=M.mid WHERE P.uploaded=1 ORDER BY P.time_create DESC LIMIT %s ",$page_size);
		}
		
		$data=DB::select($sql);
		$photos_list=array();
		if($data){
			foreach ($data as $row) {
				$row=(array)$row;
				$photo=array();
				$photo['url']=$this->getUpyunLink($row['hash']);
				$photo['type']=$row['type'];
				$photo['type_name']=$this->sourceTypeName($photo['type']);
				$photo['uid']=$row['uid'];
				$photo['pid']=$row['id'];
				$photo['uname']=$row['name'];
				$photo['desc']=$row['desc'];
				$photo['width']=$row['width'];
				$photo['height']=$row['height'];
				$photo['time_create']=$this->timeFormat($row['time_create']);
				$photos_list[]=$photo;
			}
		}
		$view = View::make('stream/index');
		$view->photos_list=$photos_list;
		return $view;
	}

}
