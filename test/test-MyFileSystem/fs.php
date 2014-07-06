<?php
include "../../MyFileSystem.php";
$file = dirname(__file__);
$param = json_decode($GLOBALS["HTTP_RAW_POST_DATA"]);

if( defined('SAE_TMP_PATH') ){
	exit();
	$fs = new MyFileSystem('wp');
}else{
	$fs = new MyFileSystem( $_POST['basePath'] ? $_POST['basePath'] : $param->basePath , $_POST['baseUrl'] ? $_POST['baseUrl'] : $param->baseUrl );	
}

//var_dump($_POST);

if( $_POST['cmd'] == 'upload' ){
	echo $fs->upload($_POST['path']);
	exit();
}

$param = json_decode($GLOBALS["HTTP_RAW_POST_DATA"]);
echo json_encode (call_user_func('cmd_'.$param->cmd, $fs , $param));

function cmd_ls( $fs , $param ){
	return $fs->ls($param->path);
}

function cmd_rm($fs,$param){
	$count = 0 ;
	foreach ($param->paths as $path) {
		$count += $fs->rm($path);
	}
	return array('count' => $count);
}
function cmd_mkdir($fs,$param){
	return $fs->mkdir($param->path);
}


?>