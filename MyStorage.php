<?php

/**
 * SAE数据存储服务
 *
 * @author quanjun
 * @version $Id$
 * @package sae
 *
 */
 
/**
 * SaeStorage class
 * Storage服务适合用来存储用户上传的文件，比如头像、附件等。不适合存储代码类文件，比如页面内调用的JS、CSS等，尤其不适合存储追加写的日志。使用Storage服务来保存JS、CSS或者日志，会严重影响页面响应速度。建议JS、CSS直接保存到代码目录，日志使用sae_debug()方法记录。
 *
 * <code>
 * <?php
 * $s = new SaeStorage();
 * $s->write( 'example' , 'thebook' , 'bookcontent!' );
 * 
 * echo $s->read( 'example' , 'thebook') ;
 * // will echo 'bookcontent!';
 *
 * echo $s->getUrl( 'example' , 'thebook' );
 * // will echo 'http://appname-example.stor.sinaapp.com/thebook';
 *
 * ?>
 * </code>
 *
 * 常见错误码参考：
 *  - errno: 0         成功
 *  - errno: -2        配额统计错误
 *  - errno: -3        权限不足
 *  - errno: -7        Domain不存在
 *  - errno: -12    存储服务器返回错误
 *  - errno: -18     文件不存在
 *  - errno: -101    参数错误
 *  - errno: -102    存储服务器连接失败
 * 注：可使用SaeStorage::errmsg()方法获得当前错误信息。
 * 
 * @package sae
 * @author  quanjun
 * 
 */
 
class LocalStorage
{
    private $domain ;
	 /**
     * 运行过程中的错误信息
     * @var string 
     */
    private $errMsg = 'success';
    /**
     * 运行过程中的错误代码
     * @var int 
     */
    private $errNum = 0;
    /**
     * 应用名
     * @var string 
     */
    private $appName = '';
    /**
     * @var string 
     */
    private $restUrl = '';
    /**
     * @var string 
     */
    private $filePath= '';
	
	
    public function __construct($domain = '' )
    {
		$this->domain = $domain ;
    }
   
 
    /**
     * 返回运行过程中的错误信息
     *
     * @return string 
     * @author Elmer Zhang
     */
    public function errmsg()
    {
        $ret = $this->errMsg." url(".$this->filePath.")";
        $this->restUrl = '';
        $this->errMsg = 'success!';
        return $ret;
    }
 
    /**
     * 返回运行过程中的错误代码
     *
     * @return int 
     * @author Elmer Zhang
     */
    public function errno()
    {
        $ret = $this->errNum;
        $this->errNum = 0;
        return $ret;
    }
 
    /**
     * 取得通过CDN访问存储文件的url
     *
     * @param string $domain 
     * @param string $filename 
     * @return string 
     * @author Elmer Zhang
     */
    public function getCDNUrl( $domain, $filename ) {
 
        // make it full domain
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        $domain = $this->getDom($domain);
 
        if ( SAE_CDN_ENABLED ) {
            $filePath = "http://".$domain.'.'.$this->cdndomain . "/$filename";
        } else {
            $filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
        }
        return $filePath;
    }
 
    /**
     * 取得访问存储文件的url
     *
     * @param string $domain 
     * @param string $filename 
     * @return string 
     * @author Elmer Zhang
     */
    public function getUrl( $domain, $filename ) {
 
        // make it full domain
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        $domain = $this->getDom($domain);
 
        $this->filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
        return $this->filePath;
    }
 
    private function setUrl( $domain , $filename )
    {
        $domain = trim($domain);
        $filename = trim($filename);
 
        $this->filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
    }
 
    /**
     * 将数据写入存储
     *
     * 注意：文件名左侧所有的'/'都会被过滤掉。
     *
     * @param string $domain 存储域,在在线管理平台.storage页面可进行管理
     * @param string $destFileName 文件名
     * @param string $content 文件内容,支持二进制数据
     * @param int $size 写入长度,默认为不限制
     * @param array $attr 文件属性，可设置的属性请参考 SaeStorage::setFileAttr() 方法
     * @param bool $compress 是否gzip压缩。如果设为true，则文件会经过gzip压缩后再存入Storage，常与$attr=array('encoding'=>'gzip')联合使用
     * @return string 写入成功时返回该文件的下载地址，否则返回false
     * @author Elmer Zhang
     */
    public function write(  $destFileName, $content, $size=-1, $attr=array(), $compress = false )
    {
        $destFileName = $this->formatFilename($destFileName);
 
        if ( $domain == '' || $destFileName == '' )
        {
            $this->errMsg = 'the value of parameter (domain,destFileName,content) can not be empty!';
            $this->errNum = -101;
            return false;
        }
 
        if ( $size > -1 )
            $content = substr( $content, 0, $size );
 
        $srcFileName = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
        if ($compress) {
            file_put_contents("compress.zlib://" . $srcFileName, $content);
        } else {
            file_put_contents($srcFileName, $content);
        }
 
        $re = $this->upload($domain, $destFileName, $srcFileName, $attr);
        unlink($srcFileName);
        return $re;
    }
 
    /**
     * 将文件上传入存储
     *
     * 注意：文件名左侧所有的'/'都会被过滤掉。
     *
     * @param string $domain 存储域,在在线管理平台.storage页面可进行管理
     * @param string $destFileName 目标文件名
     * @param string $srcFileName 源文件名
     * @param array $attr 文件属性，可设置的属性请参考 SaeStorage::setFileAttr() 方法
     * @param bool $compress 是否gzip压缩。如果设为true，则文件会经过gzip压缩后再存入Storage，常与$attr=array('encoding'=>'gzip')联合使用
     * @return string 写入成功时返回该文件的下载地址，否则返回false
     * @author Elmer Zhang
     */
    public function upload( $destFileName, $srcFileName, $attr = array(), $compress = false )
    {
       
    }
 
 
    /**
     * 获取指定domain下的文件名列表
     *
     * <code>
     * <?php
     * //遍历Domain下所有文件
     * $stor = new SaeStorage();
     *
     * $num = 0;
     * while ( $ret = $stor->getList("test", "*", 100, $num ) ) {
     *         foreach($ret as $file) {
     *             echo "{$file}\n";
     *             $num ++;
     *         }
     * }
     * 
     * echo "\nTOTAL: {$num} files\n";
     * ?>
     * </code>
     *
     * @param string $domain    存储域,在在线管理平台.storage页面可进行管理
     * @param string $prefix    如 *,abc*,*.txt
     * @param int $limit        返回条数,最大100条,默认10条
     * @param int $offset            起始条数。
     * @return array 执行成功时返回文件列表数组，否则返回false
     * @author Elmer Zhang
     */
    public function getList( $domain, $prefix='*', $limit=10, $offset = 0 )
    {
        
    }
 
    /**
     * 获取指定Domain、指定目录下的文件列表
     *
     * @param string $domain    存储域
     * @param string $path        目录地址
     * @param int $limit        单次返回数量限制，默认100，最大1000
     * @param int $offset        起始条数
     * @param int $fold            是否折叠目录
     * @return array 执行成功时返回列表，否则返回false
     * @author Elmer Zhang
     */
    public function getListByPath( $domain, $path = NULL, $limit = 100, $offset = 0, $fold = true )
    {
      
    }
 
    /**
     * 获取指定domain下的文件数量
     *
     *
     * @param string $domain    存储域,在在线管理平台.storage页面可进行管理
     * @param string $path        目录
     * @return array 执行成功时返回文件数，否则返回false
     * @author Elmer Zhang
     */
    public function getFilesNum( $domain, $path = NULL )
    {
       
    }
 
    /**
     * 获取文件属性
     *
     * @param string $domain     存储域
     * @param string $filename    文件地址
     * @param array $attrKey    属性值,如 array("fileName", "length")，当attrKey为空时，以关联数组方式返回该文件的所有属性。
     * @return array 执行成功以数组方式返回文件属性，否则返回false
     * @author Elmer Zhang
     */
    public function getAttr( $domain, $filename, $attrKey=array() )
    {
        
    }
 
    /**
     * 检查文件是否存在
     *
     * @param string $domain     存储域
     * @param string $filename     文件地址
     * @return bool 
     * @author Elmer Zhang
     */
    public function fileExists( $domain, $filename )
    {
       
    }
 
    /**
     * 获取文件的内容
     *
     * @param string $domain 
     * @param string $filename 
     * @return string 成功时返回文件内容，否则返回false
     * @author Elmer Zhang
     */
    public function read( $domain, $filename )
    {
    }
 
    /**
     * 删除目录
     *
     * @param string $domain    存储域
     * @param string $path        目录地址
     * @return bool 
     * @author Elmer Zhang
     */
    public function deleteFolder( $domain, $path )
    {
       
    }
 
 
    /**
     * 删除文件
     *
     * @param string $domain 
     * @param string $filename 
     * @return bool 
     * @author Elmer Zhang
     */
    public function delete( $domain, $filename )
    {
    }
 
 
    /**
     * 设置文件属性
     *
     * 目前支持的文件属性
     *  - expires: 浏览器缓存超时，功能与Apache的Expires配置相同
     *  - encoding: 设置通过Web直接访问文件时，Header中的Content-Encoding。
     *  - type: 设置通过Web直接访问文件时，Header中的Content-Type。
     *  - private: 设置文件为私有，则文件不可被下载。
     *
     * <code>
     * <?php
     * $stor = new SaeStorage();
     * 
     * $attr = array('expires' => 'access plus 1 year');
     * $ret = $stor->setFileAttr("test", "test.txt", $attr);
     * if ($ret === false) {
     *         var_dump($stor->errno(), $stor->errmsg());
     * }
     *
     * $attr = array('expires' => 'A3600');
     * $ret = $stor->setFileAttr("test", "expire/*.txt", $attr);
     * if ($ret === false) {
     *         var_dump($stor->errno(), $stor->errmsg());
     * }
     * ?>
     * </code>
     *
     * @param string $domain 
     * @param string $filename     文件名，可以使用通配符"*"和"?"
     * @param array $attr         文件属性。格式：array('attr0'=>'value0', 'attr1'=>'value1', ......);
     * @return bool 
     * @author Elmer Zhang
     */
    public function setFileAttr( $domain, $filename, $attr = array() )
    {
        
    }
 
    /**
     * 设置Domain属性
     */
    public function setDomainAttr( $domain, $attr = array() )
    {
        
    }
 
    /**
     * 获取domain所占存储的大小
     *
     * @param string $domain 
     * @return int 
     * @author Elmer Zhang
     */
    public function getDomainCapacity( $domain )
    {
       
    }
 
    // =================================================================
 
    /**
     * @ignore
     */
    protected function parseDomainAttr($attr) {
        
    }
 
    /**
     * @ignore
     */
    protected function parseFileAttr($attr) {
        $parseAttr = array();
 
        if ( !is_array( $attr ) || empty( $attr ) ) {
            return false;
        }
 
        foreach ( $attr as $k => $a ) {
            switch ( strtolower( $k ) ) {
                case 'expires':
                    $parseAttr['expires'] = $a;
                    break;
                case 'encoding':
                    $parseAttr['encoding'] = $a;
                    break;
                case 'type':
                    $parseAttr['type'] = $a;
                    break;
                case 'private':
                    $parseAttr['private'] = intval($a);
                    break;
                default:
                    break;
            }
        }
 
        return $parseAttr;
    }
 
    /**
     * @ignore
     */
    protected function parseExpires($expires) {
       
    }
 
    /**
     * @ignore
     */    
    protected function initOptUrlList( $_optUrlList=array() )
    {
       
    }
    /**
     * 构造函数运行时替换所有$this->optUrlList值里的accessKey与secretKey
     * @param string $_accessKey 
     * @param string $_secretKey 
     * @return void 
     * @ignore
     */ 
    protected function init( $_accessKey, $_secretKey )
    {
    }
 
    /**
     * 最终调用server端方法的rest函数封装
     * @ignore
     */
    protected function getJsonContentsAndDecode( $url, $postData = array(), $decode = true ) //获取对应URL的JSON格式数据并解码
    {
       
    }
 
    /**
     * 解析并验证server端返回的数据结构
     * @ignore
     */
    public function parseRetData( $retData = array() )
    {
       
    }
 
    /**
     * domain拼接
     * @param string $domain 
     * @param bool $concat 
     * @return string 
     * @author Elmer Zhang
     * @ignore
     */
    protected function getDom($domain, $concat = true) {
        return $domain;
    }
 
    private function formatFilename($filename) {
        $filename = trim($filename);
 
        $encodings = array( 'UTF-8', 'GBK', 'BIG5' );
 
        $charset = mb_detect_encoding( $filename , $encodings);
        if ( $charset !='UTF-8' ) {
            $filename = mb_convert_encoding( $filename, "UTF-8", $charset);
        }
 
        $filename = preg_replace('/\/\.\//', '/', $filename);
        $filename = ltrim($filename, '/');
        $filename = preg_replace('/^\.\//', '', $filename);
        while ( preg_match('/\/\//', $filename) ) {
            $filename = preg_replace('/\/\//', '/', $filename);
        } 
        return $filename;
    }
	
	public function getFilePath( $fileName )
	{
		return $this->domain ."/". $fileName;
	}
}



interface IStorage
{
	public function delete($file);
	public function deleteFolder($path);
	public function fileExists($file);
	public function getFilesNum($path);
	public function getUrl($file);
	public function read($file);
	public function upload(  $destFileName, $srcFileName, $attr = array(), $compress = false ) ;
	public function write(  $destFileName, $content,  $size = -1, $attr = array(),  $compress = false) ;
}

class MyStorage extends SaeStorage implements IStorage
{
	public function __construct()
	{
		parent::__construct("","");
	}
	public function delete($file){echo "$file";return parent::delete($file);}
	public function deleteFolder($path){}
	public function fileExists($file){}
	public function getFilesNum($path){}
	public function getUrl($file){}
	//public function read($file){}
	public function upload(  $destFileName, $srcFileName, $attr = array(), $compress = false ) {}
	public function write(  $destFileName, $content,  $size = -1, $attr = array(),  $compress = false) {}
}


?>