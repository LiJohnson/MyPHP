<?php 

class MyCurl{ 
	public $curlHandle ;
	function __construct( $url = null ) {
		 $this->curlHandle = curl_init($url);
		 $this->setOption(CURLOPT_RETURNTRANSFER, 1);
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
		return curl_exec($this->curlHandle);
	}
	
	function getInfo(){
		return curl_getinfo($this->curlHandle);
	}
	function setOption( $key , $value ){
		curl_setopt( $this->curlHandle,  $key, $value );
	}
	
	function setCookieOn($value = "tmp_cookies")
	{
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEJAR, $value );
		curl_setopt( $this->curlHandle,  CURLOPT_COOKIEFILE, $value );
	}
}

?>
