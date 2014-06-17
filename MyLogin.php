<?php 
include_once dirname(__FILE__)."/MyClientV2.php";
include_once dirname(__FILE__)."/BaseDao.php";

class MyLogin{

	private $dao ;
	private $debug  = false ;
	private $client ;
	public function MyLogin($callback = false ){

		$this->dao = new BaseDao('gelivable');

		if( !$callback ){
			if (! $_SERVER ['SCRIPT_URI']){
				$_SERVER ['SCRIPT_URI'] = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
			}
			$callback = $_SERVER ['SCRIPT_URI'];
		}
		$this->client = new MyClientV2();
		$this->callbackUrl = $callback;
	}
		
	public function setDebug( $on = true ){
		$this->debug = $on ;
		$this->dao->setDebug($on);
	}
	
	public function login($callback = false )
	{
		if (isset($_SESSION ['user'] ) && $_SESSION ['user'] != false)
			return $_SESSION ['user'];
		
		$user = null;
		if( is_array($callback) ){
			$user = $this->initUser($callback);
		}
		else{
			$user = $this->initWeiboV2();
		}

		if( $user != null ){
			$_SESSION['user'] = $user;
		}
		return $user;
	}

	public function logout(){
		try{
			unset($_SESSION);
			session_destroy();
			$this->client->end_session();
		}
		catch(Exception1 $e){}
	}

	private function initWeiboV2(){
		$c = new MyClientV2();
		if( !$c->isOauthed() ){
			$c->wbOauth();
		}
		return $this->getUserInfo();
	}
	
	/*
	*
	*/
	private function initUser( $loginData ){
		//..........
		return $this->updateClientInfo();
	}
	
	public function register( $user ){
		$u = new Users();
		$u->mail = $u->name = $u->screen_name = $user['user_email'] ;
		$u->password = md5($user['password']) ;
		$_SESSION['user'] = $this->dao->save($u , 'users_id');
		$_SESSION['user']['id'] = $_SESSION['user']['users_id'];
		
		$u = new Users();
		$u->id = $_SESSION['user']['id'] = $_SESSION['user']['users_id'] ; 
		$this->dao->update($u , " and `users_id`=".$_SESSION['user']['users_id']);
		return $this->updateClientInfo();
	}
	
	function getClientIp(){
		$names = array('HTTP_CLIENT_IP' , 'REMOTE_ADDR' , 'HTTP_X_FORWARDED_FOR');
		foreach ($names as $name) {
			if( $_SERVER [ $name ] ){
				return $_SERVER [ $name ];
			}
		}
		return '0.0.0.0';
	}
	
	private function updateClientInfo()
	{
		$userInfo = $this->getUserInfo ();

		if( $userInfo == null || isset($userInfo['error']) )
		{
			$userInfo = $_SESSION['user'];
		}
		
		if(!isset($userInfo ['id']))
			return  false;
		
		$user = new Users();
		
		$user->id = $userInfo ['id'] ;
		try{
			$ret = $this->dao->getOneModel( $user );
		}catch(Exception $e){
			return false;
		}

		if( ! is_array($userInfo) )
			$userInfo = array();
		
		unset($user);
		$user = new Users();
		
		foreach ( $user as $k => $v )
		{
			$v = $userInfo[$k];
			if ( is_null($v) || is_array ( $v ) || $k == 'users_id')
				continue;
			$user->$k = $v ;
		}

		$user->last_date = date ( "Y-m-d H:i:s" ) ;
		$user->ip = $this->getClientIp () ;
		
		if (isset ( $_SESSION ['last_key'] ))
		{
			$user->oauth_token = $_SESSION ['last_key'] ['oauth_token'] ;
			$user->oauth_token_secret = $_SESSION ['last_key'] ['oauth_token_secret'] ;
		}
		if (isset ( $_SESSION ['token'] ))
		{
			$user->access_token = $_SESSION ['token'] ['access_token'] ;
		}
		
		if($ret == false)
		{
			$user->id = $userInfo['id'];
			$user->add_date = date ( "Y-m-d H:i:s" ) ;
			return $this->dao->save($user , "users_id");
		} 
		else
		{
			$user->count = $msg ['count'] + 1 ;
			$this->dao->update( $user  , " and `id` LIKE  '$userInfo[id]' ");
			return $this->dao->getOneModel(new Users() , " and `id` LIKE '$userInfo[id]' ");
		}
	}
	
	function getUserInfo(){	
		$c = $this->getClient();
		if( $c ){
			$uid_get = $c->get_uid ();
			return $c->show_user_by_id ( $uid_get ['uid'] );
		}
		return null;
	}
		
	function getClient(){
		if( isset($_SESSION['token']) ){
			return new MyClientV2 ();
		}
		return null;
	}

}
