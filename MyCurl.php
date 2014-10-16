<?php 

class MyCurl{
	private $headers;
	public $curlHandle ;
	function __construct( $url = null ) {
		 $this->curlHandle = curl_init($url);
		 $this->setOption(CURLOPT_RETURNTRANSFER, 1);
		 $this->headers = array();
	}
	function __destruct(){
		//curl_close($this->curlHandle);
		return $this->close() ;
	}
	
	function close(){
		try{
			return curl_close($this->curlHandle);
		}catch(Exception  $e ){return null;}
	}
	
	function fetch( $url = null ){
		if( $url != null){
			$this->setOption(CURLOPT_URL,$url);
		}
		$headerArr = array();
		foreach ($this->headers as $key => $value) {
			$headerArr[] = $key . ":".$value;
		}
		$this->setOption(CURLOPT_HTTPHEADER , $headerArr);
		return curl_exec($this->curlHandle);
	}
	
	function getInfo($key = false){
		if( $key == false )return curl_getinfo($this->curlHandle);
		return curl_getinfo($this->curlHandle , $key);

	}
	function setOption( $key , $value ){
		curl_setopt( $this->curlHandle,  $key, $value );
	}
	
	function setCookieOn($value = "tmp_cookies"){
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEJAR, $value );
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEFILE, $value );
	}

	function setHeader($key , $value){
		$this->headers[$key] = $value;
	}
}

?>
