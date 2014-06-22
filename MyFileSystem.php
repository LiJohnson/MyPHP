<?php
/**
 * 文件系统接口
 */
interface IWebFileSystem{
	/**
	 * 列出文件
	 * @param  string $path 路径
	 * @return array       文件列表
	 */
	function ls($path);
	/**
	 * 创建目录
	 * @param  string  $path 路径
	 * @param  boolean $r    是否设置递归模式
	 * @return boolean       是否成功
	 */
	function mkdir($path,$r=false);
	/**
	 * 创建一个文件
	 * @param  string $file 文件路径
	 * @return boolean       
	 */
	function touch($file);
	/**
	 * 进入目录
	 * @param  string $path 路径
	 * @return void       
	 */
	function cd($path);
	/**
	 * 复制文件
	 * @param  string $s 源文件
	 * @param  string $d 目标文件
	 * @return boolean    是否成功
	 */
	function cp($s,$d);
	/**
	 * 移动文件
	 * @param  string $s 源文件
	 * @param  string $d 目标文件
	 * @return boolean    
	 */
	function mv($s,$d);
	/**
	 * 删除文件（夹）
	 * @param  string $file 文件（夹）路径
	 * @return boolean       
	 */
	function rm($file);
	/**
	 * 文件查找
	 * @param  string $key 关键字
	 * @return array      相关的文件
	 */
	function find($key);
	/**
	 * 判断文件是否存在
	 * @param  string $file 路径
	 * @return boolean       
	 */
	function exsit($file);
	/**
	 * 写文件
	 * @param  string  $file    文件路径
	 * @param  string  $content 写入的内容
	 * @param  boolean $append  是否追加
	 * @return boolean
	 */
	function write($file,$content,$append = false);
	/**
	 * 文件上传
	 * @param  string $path 路径
	 * @return boolean       
	 */
	function upload($path);
	/**
	 * 读文件
	 * @param  string $file 文件路径 
	 * @return string       文件内容
	 */
	function read($file);
}

/**
 * 文件实体数据结构
 */
class WebFile{
	/**
	 * 文件名
	 * @var string
	 */
	public $name;
	/**
	 * 目录
	 * @var string
	 */
	public $path;
	/**
	 * 相对位置
	 * @var string
	 */
	public $localtion;
	/**
	 * 文件类型，目录用'dir'表示，否则用文件的后缀表示
	 * @var string
	 */
	public $type;
	/**
	 * 文件大小
	 * @var int
	 */
	public $size;
	/**
	 * 文件图标
	 * @var string
	 */
	public $icon;
	/**
	 * 是否为目录
	 * @var boolean
	 */
	public $isDir;
	/**
	 * 创建时间
	 * @var int
	 */
	public $createTime;
	/**
	 * 修改时间
	 * @var int
	 */
	public $modifyTime;
	/**
	 * 文件url
	 * @var string
	 */
	public $url;

	function __construct($path = '' ,$root = '' , $url = ""){
		$stat = stat($path);

		$this->name = basename($path);
		$this->path = str_replace($root, '', dirname($path));
		$this->path = preg_replace('/^\/*/', '', $this->path);
		$this->localtion = $this->path . '/' . $this->name;
		$this->size = $stat['size'];
		$this->createTime = $stat['ctime'];
		$this->isDir = is_dir($path);
		$this->type = $this->isDir ? 'dir' : @(array_pop(preg_split('/\./', $this->name)));
		$this->url = $url .'/' . $this->localtion;
	}
}
/**
 * 文件系统抽象部分，
 * 抽象一些公共方法
 */
class AbstractFileSytem{
	/**
	 * 记录当前路径
	 * @var array
	 */
	protected $path;
	public function __construct(){
		$this->path = array();
	}
	/**
	 * 格式化路径：
	 * 1.去除路径前后的斜杠'/'
	 * 2.处理掉连续的斜杠
	 * 3.处理路径上的'.'和'..'
	 * @param  string $path 路径 
	 * @return string       路径
	 */
	private function formatPath($path){
		
		$path = preg_replace('/^\/*|\/*$/', '', $path);
		while (preg_match('/\/\//', $path)) {
			$path = preg_replace('/\/\//', '/', $path);
		}

		$tmp = array();
		$path = preg_split('/\//', $path);
		foreach ($path as $k=> $value) {
			switch ($value) {
				case '.':
					//pass
					break ;
				case '..':
					array_pop($tmp);
					break ;
				default:
					$tmp[] = $value;
					break ;
			}
		}
		return join($tmp,'/');
	}
	/**
	 * 获取绝对路径
	 * @param  string $path 路径，以'/'开头表示绝对路径，否则为相对路径
	 * @return string       
	 */
	protected function getFilePath($path){
		if( $this->isRoot($path) ){
			return $this->formatPath($path);
		}

		return $this->formatPath(join($this->path , '/' ) . '/' . $path);

	}
	/**
	 * 是否绝对路径 
	 * @param  string  $path 路径
	 * @return boolean       
	 */
	protected function isRoot( $path ){
		return preg_match('/^\//', $path);
	}
}

/**
 * 本地的文件系统，利用php提供的文件函数实现
 */
class LocalFileSystem extends AbstractFileSytem implements IWebFileSystem {
	/**
	 * 根目录
	 * @var string
	 */
	private $root ;
	/**
	 * 访问根目录的url
	 * @var string
	 */
	private $baseUrl ;

	/**
	 * 构造函数
	 * @param string $base 根目录
	 * @param string  $url 访问根目录的url
	 */
	public function __construct( $base = false , $url = "" ){
		$this->root = preg_replace('/\/*$/', '', $base ? $base : dirname(__file__) ) ;
		$this->baseUrl = $url;
		parent::__construct();
	}

	public function ls( $path = false ){
		$path = $path ? $this->getFilePath($path) : $this->getCurPath() ;
		$fileList = array();
		$handle = opendir($path);

		while( $file = readdir($handle) ){
			if( $file == '.' || $file == '..' )continue;
			$sub_path = $path . '/' . $file;
			$fileList[] = new WebFile($sub_path,$this->root , $this->baseUrl);
		}
		return array( 'path' => preg_replace('/^\/*/', '', str_replace($this->root , '', $path)) , 'files' => $fileList );
	}

	public function mkdir($path,$r=false){
		$file = $this->getFilePath($path);

		if( $this->exsit($file) ){
			return true;
		}
		return @mkdir( $file , $r);
	}

	public function touch($file){
		$file = $this->getFilePath($file);
		if( $this->exsit( $file ) ){
			return true;
		}else{
			touch($file);
		}
	}
	public function cd($path){
		if( $this->isRoot($path) ){
			$this->path = array( parent::getFilePath($path) );
		}else{
			$this->path[] = $path;
		}
	}

	public function cp($s,$d){
		return copy($this->getFilePath( $s ), $this->getFilePath( $d ));
	}

	public function mv($s,$d){
		return $this->cp($s, $d) && $this->rm($s);
	}
	public function rm($file){
	
		if( !$this->exsit($file) )return 0;
		$file = $this->getFilePath($file);
		
		$count = 0;
		
		if( is_file($file) ){
			return unlink( $this->getFilePath($file) ) ? 1 : 0;	
		}else{
			$data =$this->ls($file) ;
			foreach ( $data['files'] as $key => $f) {
				$count += $this->rm('/' . $f->path . '/' . $f->name);
			}
			$this->ls($file);
			return $count + (rmdir($file) ? 1 : 0) ;
		}
		
	}
	/**
	 * !没有实现
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function find($key){
		#...
	}
	public function exsit( $file ){
		return file_exists( $this->getFilePath( $file ) );
	}
	public function write($file,$content,$append = false){
		$file = $this->getFilePath($file);
		return $append ? file_put_contents($file,$content,FILE_APPEND) :  file_put_contents($file,$content);
	}
	public function upload($path){
		$path = $this->getFilePath($path);
		$count = 0;
		foreach ($_FILES as $tmpFile) {
			$count += move_uploaded_file($tmpFile['tmp_name'],$path . '/' . $tmpFile['name']);
		}
		return $count;
	}
	public function read ($file){
		return file_get_contents($this->getFilePath($file));
	}

	protected function getFilePath($path){
		if( strpos($path , $this->root) !== 0 ){
			$path =  parent::getFilePath($path);
			$path = $path ? $this->root . '/' . $path :  $this->root;
		}
		//var_dump( $path );	
		return $path ;
	}

	private function getCurPath(){
		return $this->getFilePath('/'.join($this->path,'/'));
	}
}

/**
 * 新浪sae上的文件系统，利用SaeSorage[http://apidoc.sinaapp.com/sae/SaeStorage.html]实现
 */
class SaeFileSystem extends AbstractFileSytem implements IWebFileSystem {
	/**
	 * SaeStorage对象
	 * @var SaeStorage
	 */
	private $stor;
	/**
	 * domain
	 * @var string
	 */
	private $domain;
	public function __construct($domain){
		$this->stor = new SaeStorage();
		$this->domain = $domain;
		$this->curPath = array();
	}

	public function ls($path = NULL ){
		$path = $this->getFilePath($path);

		$fileList = array();
		$data = $this->stor->getListByPath( $this->domain , $path );
		foreach ($data['dirs'] as $file) {
			$fileList[] = $this->initFile($file,true);
		}
		foreach ($data['files'] as $file) {
			$fileList[] = $this->initFile($file,false);
		}
		return $fileList;
	}

	public function mkdir($path,$r=false){
		$file = $this->getFilePath($path) . '/' . '.mkdir';
		$res = $this->stor->write($this->domain , $file , '.mkdir');
		if( $res ){
            //$this->rm($file);
		}
		return $res;
	}

	public function touch($file){
		return $this->write($file,'');
	}
	public function cd($path){
		if( $this->isRoot($path) ){
			$this->path = array( parent::getFilePath($path) );
		}else{
			$this->path[] = $path;
		}
	}
	public function cp($s,$d){
		return $this->write( $d , $this->read( $s ) );
	}
	public function mv($s,$d){
		return $this->cp($s,$d) && $this->rm($s);
	}
	public function rm($file){
		return $this->stor->delete( $this->domain , $this->getFilePath($file) );
	}
	public function find($key){
		//todo
	}
	public function exsit($file){
		return $this->stor->fileExists($this->domain,$this->getFilePath($file));
	}
	public function write($file,$content,$append = false){
		$file = $this->getFilePath($file);
		return $this->stor->write($this->domain , $file , $content);
	}
	public function upload($path){
		$path = $this->getFilePath($path);
		$count = 0;
        
		foreach ($_FILES as $tmpFile) {var_dump($tmpFile);
			$count += $this->stor->upload($this->domain,$path . '/' . $tmpFile['name'],$tmpFile['tmp_name']);
		}
		return $count;
	}
	public function read($file){
		return $this->stor->read( $this->domain , $this->getFilePath($file) );
	}

	private function initFile( $file , $isDir = false ){
		$f = new WebFile();
/*
	$this->name = '';
	$this->path = '';
	$this->localtion = '';
	$this->type = '';
	$this->size = '';
	$this->icon = '';
	$this->isDir = '';
	$this->createTime = '';
	$this->modifyTime = '';
	$this->url = '';
 */
/*

array(4) {
  ["dirNum"]=>
  int(1)
  ["fileNum"]=>
  int(1)
  ["dirs"]=>
  array(1) {
    [0]=>
    array(2) {
      ["name"]=>
      string(7) "uploads"
      ["fullName"]=>
      string(8) "uploads/"
    }
  }
  ["files"]=>
  array(1) {
    [0]=>
    array(4) {
      ["Name"]=>
      string(5) "g.zip"
      ["fullName"]=>
      string(5) "g.zip"
      ["length"]=>
      int(19250)
      ["uploadTime"]=>
      int(1360226137)
    }
  }
}
 */		
		$file['fullName'] = preg_replace('/\/$/', '', $file['fullName'] );

		$f->isDir = $isDir;
		$f->name = $f->isDir ? $file['name'] : $file['Name'];

		$nameLen = strlen($f->name);
		$fullNameLen = strlen($file['fullName']);
		
		$end = $fullNameLen - $nameLen - 1;

		$f->path = $end < 0 ? '' : substr($file['fullName'], 0 , $end );
		$f->localtion = '/' . $file['fullName'];
		$f->type = $f->isDir ? 'dir' : (array_pop(preg_split('/\./', $f->name))); 
		$f->size = $file['length'];
		$f->createTime = $file['uploadTime'];
		$f->url = $this->stor->getUrl($this->domain , $file['fullName']);
		$f->path = $f->path ? '/' . $f->path : '';
		return $f;
	}
}

if( class_exists('SaeStorage') ){
	class MyFileSystem extends SaeFileSystem{}
}else{
	class MyFileSystem extends LocalFileSystem{}
}