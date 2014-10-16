<?php

/**
 * 本地文件读写 
 */
class LocalStorage implements IStorage{

	public function delete($file){echo "$file";return parent::delete($file);}
	public function deleteFolder($path){}
	public function fileExists($file){}
	public function getFilesNum($path){}
	public function getUrl($file){}
	public function read($file){}
	public function upload(  $destFileName, $srcFileName, $attr = array(), $compress = false ) {}
	public function write(  $destFileName, $content,  $size = -1, $attr = array(),  $compress = false) {}
}


interface IStorage{

	public function delete($file);
	public function deleteFolder($path);
	public function fileExists($file);
	public function getFilesNum($path);
	public function getUrl($file);
	public function read($file);
	public function upload(  $destFileName, $srcFileName, $attr = array(), $compress = false ) ;
	public function write(  $destFileName, $content,  $size = -1, $attr = array(),  $compress = false) ;
}

if( class_exists('SaeStorage') ){
	class MyStorage extends SaeStorage{}
}else{
	class MyStorage extends LocalStorage{

		public function __construct(){
			parent::__construct("","");
		}
	}
}


?>