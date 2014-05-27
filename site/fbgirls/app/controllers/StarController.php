<?php

class StarController extends BaseController {
	public function index()
	{
		$page = (int)Input::get('page',1);
		$page_size = Input::get('page_size',60);

		// $sql = sprintf("SELECT M.name,M.fid,M.type,P.id,P.uid,P.hash,P.desc,P.width,P.height,P.time_create FROM photos P left join members M on P.uid=M.mid WHERE P.uploaded=1   group by M.mid ORDER BY P.time_create  DESC LIMIT %s,%s ",($page-1)*$page_size,$page_size);
		
		$sql = sprintf("SELECT * FROM `members` ORDER BY mid asc LIMIT %s,%s;",($page-1)*$page_size,$page_size);

		$data=DB::select($sql);
		$photos_list=array();
		if($data){
			$photos_list_row=array();
			foreach ($data as $row) {
				$row=(array)$row;
				$photo=array();
				if($row['avatar']){
					$photo['url']=$this->getUpyunLink($row['avatar'],PHOTO_TYPE_AVATAR,'s');
				}
				else{
					$photo['url']='';
				}
				
				$photo['uname']=$row['name'];
				$photo['mid']=$row['mid'];
				
				$photos_list_row[]=$photo;
				if(count($photos_list_row)>=3){
					$photos_list[]=$photos_list_row;
					$photos_list_row=array();
				}
			}
			if($photos_list_row){
				$photos_list[]=$photos_list_row;	
			}
		}
		// var_dump($photos_list);exit();
		$view = View::make('star/list');
		$view->photos_list=$photos_list;
		return $view;
	}

}
