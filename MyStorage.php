<?php

/**
 * SAE���ݴ洢����
 *
 * @author quanjun
 * @version $Id$
 * @package sae
 *
 */
 
/**
 * SaeStorage class
 * Storage�����ʺ������洢�û��ϴ����ļ�������ͷ�񡢸����ȡ����ʺϴ洢�������ļ�������ҳ���ڵ��õ�JS��CSS�ȣ����䲻�ʺϴ洢׷��д����־��ʹ��Storage����������JS��CSS������־��������Ӱ��ҳ����Ӧ�ٶȡ�����JS��CSSֱ�ӱ��浽����Ŀ¼����־ʹ��sae_debug()������¼��
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
 * ����������ο���
 *  - errno: 0         �ɹ�
 *  - errno: -2        ���ͳ�ƴ���
 *  - errno: -3        Ȩ�޲���
 *  - errno: -7        Domain������
 *  - errno: -12    �洢���������ش���
 *  - errno: -18     �ļ�������
 *  - errno: -101    ��������
 *  - errno: -102    �洢����������ʧ��
 * ע����ʹ��SaeStorage::errmsg()������õ�ǰ������Ϣ��
 * 
 * @package sae
 * @author  quanjun
 * 
 */
 
class LocalStorage
{
    private $domain ;
	 /**
     * ���й����еĴ�����Ϣ
     * @var string 
     */
    private $errMsg = 'success';
    /**
     * ���й����еĴ������
     * @var int 
     */
    private $errNum = 0;
    /**
     * Ӧ����
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
     * �������й����еĴ�����Ϣ
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
     * �������й����еĴ������
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
     * ȡ��ͨ��CDN���ʴ洢�ļ���url
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
     * ȡ�÷��ʴ洢�ļ���url
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
     * ������д��洢
     *
     * ע�⣺�ļ���������е�'/'���ᱻ���˵���
     *
     * @param string $domain �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
     * @param string $destFileName �ļ���
     * @param string $content �ļ�����,֧�ֶ���������
     * @param int $size д�볤��,Ĭ��Ϊ������
     * @param array $attr �ļ����ԣ������õ�������ο� SaeStorage::setFileAttr() ����
     * @param bool $compress �Ƿ�gzipѹ���������Ϊtrue�����ļ��ᾭ��gzipѹ�����ٴ���Storage������$attr=array('encoding'=>'gzip')����ʹ��
     * @return string д��ɹ�ʱ���ظ��ļ������ص�ַ�����򷵻�false
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
     * ���ļ��ϴ���洢
     *
     * ע�⣺�ļ���������е�'/'���ᱻ���˵���
     *
     * @param string $domain �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
     * @param string $destFileName Ŀ���ļ���
     * @param string $srcFileName Դ�ļ���
     * @param array $attr �ļ����ԣ������õ�������ο� SaeStorage::setFileAttr() ����
     * @param bool $compress �Ƿ�gzipѹ���������Ϊtrue�����ļ��ᾭ��gzipѹ�����ٴ���Storage������$attr=array('encoding'=>'gzip')����ʹ��
     * @return string д��ɹ�ʱ���ظ��ļ������ص�ַ�����򷵻�false
     * @author Elmer Zhang
     */
    public function upload( $destFileName, $srcFileName, $attr = array(), $compress = false )
    {
       
    }
 
 
    /**
     * ��ȡָ��domain�µ��ļ����б�
     *
     * <code>
     * <?php
     * //����Domain�������ļ�
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
     * @param string $domain    �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
     * @param string $prefix    �� *,abc*,*.txt
     * @param int $limit        ��������,���100��,Ĭ��10��
     * @param int $offset            ��ʼ������
     * @return array ִ�гɹ�ʱ�����ļ��б����飬���򷵻�false
     * @author Elmer Zhang
     */
    public function getList( $domain, $prefix='*', $limit=10, $offset = 0 )
    {
        
    }
 
    /**
     * ��ȡָ��Domain��ָ��Ŀ¼�µ��ļ��б�
     *
     * @param string $domain    �洢��
     * @param string $path        Ŀ¼��ַ
     * @param int $limit        ���η����������ƣ�Ĭ��100�����1000
     * @param int $offset        ��ʼ����
     * @param int $fold            �Ƿ��۵�Ŀ¼
     * @return array ִ�гɹ�ʱ�����б����򷵻�false
     * @author Elmer Zhang
     */
    public function getListByPath( $domain, $path = NULL, $limit = 100, $offset = 0, $fold = true )
    {
      
    }
 
    /**
     * ��ȡָ��domain�µ��ļ�����
     *
     *
     * @param string $domain    �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
     * @param string $path        Ŀ¼
     * @return array ִ�гɹ�ʱ�����ļ��������򷵻�false
     * @author Elmer Zhang
     */
    public function getFilesNum( $domain, $path = NULL )
    {
       
    }
 
    /**
     * ��ȡ�ļ�����
     *
     * @param string $domain     �洢��
     * @param string $filename    �ļ���ַ
     * @param array $attrKey    ����ֵ,�� array("fileName", "length")����attrKeyΪ��ʱ���Թ������鷽ʽ���ظ��ļ����������ԡ�
     * @return array ִ�гɹ������鷽ʽ�����ļ����ԣ����򷵻�false
     * @author Elmer Zhang
     */
    public function getAttr( $domain, $filename, $attrKey=array() )
    {
        
    }
 
    /**
     * ����ļ��Ƿ����
     *
     * @param string $domain     �洢��
     * @param string $filename     �ļ���ַ
     * @return bool 
     * @author Elmer Zhang
     */
    public function fileExists( $domain, $filename )
    {
       
    }
 
    /**
     * ��ȡ�ļ�������
     *
     * @param string $domain 
     * @param string $filename 
     * @return string �ɹ�ʱ�����ļ����ݣ����򷵻�false
     * @author Elmer Zhang
     */
    public function read( $domain, $filename )
    {
    }
 
    /**
     * ɾ��Ŀ¼
     *
     * @param string $domain    �洢��
     * @param string $path        Ŀ¼��ַ
     * @return bool 
     * @author Elmer Zhang
     */
    public function deleteFolder( $domain, $path )
    {
       
    }
 
 
    /**
     * ɾ���ļ�
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
     * �����ļ�����
     *
     * Ŀǰ֧�ֵ��ļ�����
     *  - expires: ��������泬ʱ��������Apache��Expires������ͬ
     *  - encoding: ����ͨ��Webֱ�ӷ����ļ�ʱ��Header�е�Content-Encoding��
     *  - type: ����ͨ��Webֱ�ӷ����ļ�ʱ��Header�е�Content-Type��
     *  - private: �����ļ�Ϊ˽�У����ļ����ɱ����ء�
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
     * @param string $filename     �ļ���������ʹ��ͨ���"*"��"?"
     * @param array $attr         �ļ����ԡ���ʽ��array('attr0'=>'value0', 'attr1'=>'value1', ......);
     * @return bool 
     * @author Elmer Zhang
     */
    public function setFileAttr( $domain, $filename, $attr = array() )
    {
        
    }
 
    /**
     * ����Domain����
     */
    public function setDomainAttr( $domain, $attr = array() )
    {
        
    }
 
    /**
     * ��ȡdomain��ռ�洢�Ĵ�С
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
     * ���캯������ʱ�滻����$this->optUrlListֵ���accessKey��secretKey
     * @param string $_accessKey 
     * @param string $_secretKey 
     * @return void 
     * @ignore
     */ 
    protected function init( $_accessKey, $_secretKey )
    {
    }
 
    /**
     * ���յ���server�˷�����rest������װ
     * @ignore
     */
    protected function getJsonContentsAndDecode( $url, $postData = array(), $decode = true ) //��ȡ��ӦURL��JSON��ʽ���ݲ�����
    {
       
    }
 
    /**
     * ��������֤server�˷��ص����ݽṹ
     * @ignore
     */
    public function parseRetData( $retData = array() )
    {
       
    }
 
    /**
     * domainƴ��
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