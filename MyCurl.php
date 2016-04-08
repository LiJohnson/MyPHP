<?php 
/**
 * 	封装一些curl操作
 *	@author lcs 
 *  @version 2.0.1
 *  @since 2011-10-8
 */
class MyCurl{
	/**
	 * 请求header
	 * @var array
	 */
	private $header;
	/**
	 * 返回header
	 * @var array
	 */
	private $responseHeader;
	/**
	 * cookie
	 * @var array
	 */
	private $cookie;

	/**
	 * 请求数据
	 * @var array
	 */
	private $postData ;
	/**
	 * curl handle
	 * @var resource
	 */
	public $curlHandle ;
	/**
	 * 调试开关
	 * @var [type]
	 */
	private $debug;

	/**
	 * 构造方法
	 * @param string $url 请求的url,默认为null
	 */
	public function __construct( $url = null ) {
		$this->curlHandle = curl_init($url);
		$this->setOption(CURLOPT_RETURNTRANSFER, 1);
		$this->setOption(CURLOPT_AUTOREFERER, 1);
		$this->init();
		$this->debug = false;
	}

	/**
	 * 析构方法
	 */
	public function __destruct(){
		//curl_close($this->curlHandle);
		return $this->close() ;
	}

	/**
	 * 初始化变量
	 * @return void
	 */
	private function init(){
		$this->header = array();
		$this->responseHeader = array();
		$this->cookie = array();
		$this->postData = array();
	}
	
	/**
	 * 关闭 curl handle
	 * @return void
	 */
	private function close(){
		try{
			return curl_close($this->curlHandle);
		}catch(Exception  $e ){return null;}
	}
	
	/**
	 * 进行一次请求
	 * @param  string $url 请求的url
	 * @return [type]      [description]
	 * @deprecated 使用get/post代替
	 * @see MyCurl::get()
	 * @see MyCurl::post()
	 */
	public function fetch( $url = null ){
		return $this->http($url);
	}

	/**
	 * http 请求
	 * @param  String $url 请求url
	 * @return string
	 */
	private function http( $url = null ){
		if( $url != null){
			$this->setOption(CURLOPT_URL,$url);
		}

		//cookie
		$cookie = array();
		$this->setCookie($this->header['Cookie']);
		foreach ($this->cookie as $key => $value) {
			$cookie[] = $key . '=' . $value;
		}
		$cookie = join($cookie,';');
		$this->setHeader('Cookie' , $cookie);

		//header
		$header = array();
		foreach ($this->header as $key => $value) {
			$header[] = $key . ':' .$value;
		}
		$this->setOption(CURLOPT_HTTPHEADER,$header);

		$this->setOption(CURLOPT_HEADERFUNCTION,array($this,'readResponseHeader'));
		
		$response = curl_exec($this->curlHandle);
		if( $this->debug ){
			echo '=============request URL ==============<br>'."\n";
			var_dump($url);
			echo '=============request header ==============<br>'."\n";
			var_dump($this->header);			
			echo '=============post data ==============<br>'."\n";
			var_dump($this->postData);			
			echo '=============response header ==============<br>'."\n";
			var_dump($this->responseHeader);
			echo '=============body ==============<br>'."\n";
			var_dump($response);			
		}
		return $response;
	}
	
	/**
	 * 获取 curl 信息
	 * @param  int $key 指定的信息
	 * @return String/array
	 * @link http://php.net/manual/en/function.curl-getinfo.php
	 */
	public function getInfo($key = 0){
		if( $key ){
			return curl_getinfo($this->curlHandle , $key);
		}
		return curl_getinfo($this->curlHandle );
	}

	/**
	 * 设定option
	 * @param int $key   
	 * @param mixed $value
	 * @link http://php.net/manual/en/function.curl-setopt.php
	 */
	public function setOption( $key , $value ){
		curl_setopt( $this->curlHandle,  $key, $value );
	}
	/**
	 * 开启cookie
	 * @param string $value 保存cookie的文件
	 */
	public function setCookieOn($value = "tmp_cookies"){
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEJAR, $value );
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEFILE, $value );
	}

	/**
	 * 设置cookie
	 * 1.以key-value的形式设置
	 * 		$c->setCookie('cookieName' ,'cookieValue')
	 * 2.以array形式设置 ，封装key-value到array中
	 * 		$c->setCookie(array('cookieName1' => 'cookieValue1' , 'cookie2' => 'cookieValue2'))
	 * 3.以文本形式设置，cookie之间用分号(;)隔开
	 * 		$c->setCookie('cookieName1=cookieValue1;cookieName2=cookieValue2');
	 * @param string|array $cookie 
	 * @param string $value  
	 */
	public function setCookie($cookie,$value=null){
		if( is_string($cookie) && is_string($value) ){
			$this->cookie[$cookie] = $value;
		}else{
			$this->cookie = self::praseData($this->cookie,$cookie,"/;/","/=/");
		}
	}
	/**
	 * 设置请求header
	 * 1.以key-value的形式设置
	 * 		$c->setHeader('headerType' ,'headerValue')
	 * 2.以array形式设置 ，封装key-value到array中
	 * 		$c->setHeader(array('headerType1' => 'headerValue1' , 'cookie2' => 'headerValue2'))
	 * 3.以文本形式设置，每个header为一行
	 * 		$header =<<<'EOT'
	 * 			User-Agent: Mozilla/5.0 Chrome/38.0.2125.101 Mycurl/2.0.1
	 * 			Host:http://lcs.io
	 * 	  	EOT;
	 * 		$c->setHeader($header);
	 * @param string|array $header 
	 * @param string $value  
	 */
	public function setHeader($header , $value=null){
		if( is_string($header) && is_string($value) ){
			$this->header[$header] = $value;
		}else{
			$this->header = self::praseData($this->header , $header , "/\n/","/:/");
		}
	}
	/**
	 * <pre>
	 * 设置请求数据
	 * 1.以key-value的形式设置
	 * 		$c->setPostData('fieldName' ,'value')
	 * 2.以array形式设置 ，封装key-value到array中
	 * 		$c->setPostData(array('fieldName1' => 'value1' , 'fieldName2' => 'value2'))
	 * 3.以文本形式设置，各值之间用(&)隔开
	 * 		$c->setPostData('fieldName1=value1&fieldName2=value2');
	 * 	</pre>
	 * @param string|array $postData 
	 * @param string $value    
	 */
	public function setPostData($postData , $value = null){
		if( is_string($postData) && is_string($value) ){
			$this->postData[$postData] = $value;
		}else{
			$this->postData = self::praseData($this->postData,$postData,"/&/","/=/");
		}
	}

	/**
	 * 是否为异步请求，即是否添加 header'X-Requested-With:XMLHttpRequest'
	 * @param  boolean $on 
	 * @return boolean
	 */
	public function isAjax( $on = true){
		if( $on ){
			$this->setHeader('X-Requested-With','XMLHttpRequest');
		}else{
			unset($this->header['X-Requested-With']);
		}
	}

	/**
	 * 格式化数据
	 * @param  array 		$oldData 原来数据
	 * @param  array/string $data    新新数据
	 * @param  string 		$split1  拆分规则1
	 * @param  string 		$split2  拆分规则3
	 * @return array
	 */
	private static function praseData( $oldData , $data , $split1=" ",$split2="="){
		$result = array();
		if( is_array($data) ){
			$result = $data;
		}else{
			$data = preg_split($split1, $data);
			if( !count($data) )return $result;

			foreach ($data as $value) {
				$par = preg_split($split2, $value,2);
				$par[0] = trim($par[0]);
				if( $par[0] ){
					$result[$par[0]] = trim($par[1]);
				}
			}
		}
		return array_merge($oldData , $result);
	}
	/**
	 * 获取请求数据
	 * @return string
	 */
	private function getPostField(){
		$postField = array();
		foreach ($this->postData as $key => $value) {
			$postField[] = $key .'=' . $value;
		}
		return join($postField,'&');
	}

	/**
	 * 读取response header
	 * 参考[CURLOPT_HEADERFUNCTION]
	 * @param  resource $c    curl handle
	 * @param  string $header header
	 * @return int         
	 */
	public function readResponseHeader($c,$header){
		if( preg_match('/^HTTP/', $header) ){
			preg_match('/\d{3}/', $header,$status);
			$this->responseHeader['status'] = $status[0];

		}else if( preg_match('/^Set\-Cookie/', $header) ){
			$cookie =  preg_replace('/(^Set\-Cookie:\s?)|(;.*$)/', '', $header);
			$this->setCookie( $cookie );
			$this->responseHeader['Set-Cookie'][] =  $cookie;
		}else{
			$this->responseHeader = self::praseData( $this->responseHeader , $header,'/\n/','/:/');
		}
		return strlen($header);
	}

	/**
	 * get请求
	 * @param  string $url      请求url
	 * @param  array  $postData 请求数据
	 * @return 
	 */
	public function get( $url , $postData = array() ){
		$this->setPostData($postData);
		$url .= (strpos($url , '?' ) === false ? '?' : '&');
		$url .= $this->getPostField();

		$this->setOption(CURLOPT_POST,false);
		$this->setOption(CURLOPT_POSTFIELDS,null);
		$this->setOption(CURLOPT_HTTPGET,true);

		return $this->http($url);
	}

	/**
	 * post请求
	 * @param  string $url      请求url
	 * @param  array  $postData 请求数据
	 * @return 
	 */
	public function post($url , $postData = array()){
		$this->setPostData($postData);
		$this->setOption(CURLOPT_POST,true);
		$this->setOption(CURLOPT_POSTFIELDS,$this->getPostField());
		return $this->http($url);
	}

	/**
	 * post请求,文件上传
	 * 文件路径前要加`@`,或直接传一个CURLFILE对象
	 * $c->upload('http://uploadUrl',['file' => '@c:/test.png', 'file2' => curl_file_create($filename) ]);
	 * @link http://php.net/manual/en/function.curl-file-create.php
	 * @param  string $url      请求url
	 * @param  array  $postData 请求数据
	 * @return 
	 */
	public function upload($url , $postData = array()){
		$tmpfiles = array();
		foreach ($postData as $key => $value) {
			if( is_string( $value ) &&  preg_match('/^@/', $value) ){
				$value = preg_replace('/^@/', '' , $value);

				$filename = explode( '?', basename( $value ) );
				$filename = $filename[0];

				if( !is_file($value) ){
					$tmp = ini_get('upload_tmp_dir') . '/f' . rand(1000,9999) . $key .'.tmp';
					file_put_contents($tmp, file_get_contents($value));
					$value = $tmp;
					$tmpfiles[] = $value;
				}
				$value = curl_file_create($value,null,$filename);
			}
			$this->postData[$key] = $value;
		}

		$this->setOption(CURLOPT_POST,true);
		$this->setOption(CURLOPT_POSTFIELDS,$this->postData);
		$response = $this->http($url);
		
		foreach ($tmpfiles as $tmp) {
			@unlink($tmp);
		}

		return $response;
	}

	/**
	 * 开启/关闭调试
	 * @param boolean $on 
	 */
	public function setDebug( $on = true ){
		$this->debug = $on;
	}
}
