<?php
date_default_timezone_set('GMT');
if(isset($argv)&&count($argv)>=2){
	$_REQUEST['action']=$argv[1];
}
$restart=0;
while (true) {
	$restart=0;
	$fp = popen("php -f ./crawl.php ".$_REQUEST['action'], "r"); 
	while(!feof($fp)) 
	{ 
	    $msg=fread($fp, 1024); 
	    echo $msg;
	    if(preg_match("/exit with error 2/", $msg)){
	    	$restart=1;
	    	echo "%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%\nrestart the process %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%\n\n";
	    	break;
	    }
	    flush(); 
	} 
	fclose($fp); 
	if(!$restart){
		break;
	}
}

