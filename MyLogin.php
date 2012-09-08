<?php 
include_once dirname(__FILE__)."/MyClientV2.php";
include_once dirname(__FILE__)."/BaseDao.php";
include_once dirname(__FILE__)."/MyTable.php";

class MyLogin
{
	private $callbackUrl;
	private $dao ;
	private $debug  = false ;
	public function __construct($callback = "", $uid = null)
	{
		$this->dao = new BaseDao("gelivable");
		if (! $_SERVER ['SCRIPT_URI'])
			$_SERVER ['SCRIPT_URI'] = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
		$this->callbackUrl = $_GET ['callback'] ? $_GET ['callback'] : $callback;
	}
		
	public function setDebug( $on = true )
	{
		$this->debug = $on ;
		$this->dao->printSQL = $on;
	}
	
	public function login($callback = '')
	{
		if (isset($_SESSION ['user'] ) && $_SESSION ['user'] != false)
			return $_SESSION ['user'];
		
		
		$user = null;
		if( is_array($callback) )
		{
			$user = $this->initUser($callback);
		}
		else if (class_exists ( "MyClient" ))
		{
			$user = $this->initWeibo ( $callback );
		} else if (class_exists ( "MyClientV2" ))
		{
			$user = $this->initWeiboV2 ( $callback );
		}
		if( $user != null )
		{
			$_SESSION['user'] = $user;
		}
		return $user;
	}
	public function logout()
	{
		try
		{
			$client = $this->getClient();
			if(method_exists($client , "end_session"))
			{
				$client->end_session();
			}
			unset($_SESSION);
			session_destroy();
		}
		catch(Exception1 $e){}
	}
	function initWeiboV2($callback = '')
	{
		if ($_SESSION ['user'] != false)
			return $_SESSION ['user'];
		
		$o = new SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
		
		if(!isset ( $_REQUEST ['code'] ))
		{
			$code_url = $o->getAuthorizeURL ( $_SERVER ['SCRIPT_URI'] );
			header( "refresh:1;url=" . $code_url );
			exit();
		}
		else
		{
			$keys = array ();
			$keys ['code'] = $_REQUEST ['code'];
			$keys ['redirect_uri'] = $callback != "" ? $callback : $_SERVER['SCRIPT_URI'] ;
			try{
			$token = $o->getAccessToken ( 'code', $keys );
			if ($token)
			{
				$_SESSION ['token'] = $token;
				$_SESSION['user'] = $this->updateClientInfo();
				return $_SESSION['user'];
			}
			}catch(OAuthException $e)
			{
				if( $this->debug )
				{
					var_dump($e);
					echo $e->xdebug_message;
				}
			}
		}
	}
	
	public function initWeibo($callback = '')
	{
		if ($_SESSION ['user'] != false)
			return $_SESSION ['user'];
	
		if (! isset ( $_SESSION ['keys'] ) )
		{
			$callbackUrl = $_SERVER ['SCRIPT_URI'] . "?callback=" . $_GET ['callback'];
			
			$o = new SaeT ( WB_AKEY, WB_SKEY );
			$keys = $o->getRequestToken ();
			$_SESSION ['keys'] = $keys;
            $aurl = $o->getAuthorizeURL ( $keys ['oauth_token'], false, $callbackUrl );//echo 0;
			header("refresh:0;url=".$aurl);
			return null;
		} 
		elseif (! isset ( $_SESSION ['last_key'] ))
		{
			$o = new SaeT ( WB_AKEY, WB_SKEY, $_SESSION ['keys'] ['oauth_token'], $_SESSION ['keys'] ['oauth_token_secret'] );//print_r($o);
			$last_key = $o->getAccessToken ( $_REQUEST ['oauth_verifier'] );
			$_SESSION ['last_key'] = $last_key;
			$_SESSION['user'] = $this->updateClientInfo();
			if (strlen ( $this->callbackUrl))
				header ( "refresh:1;url=" . $this->callbackUrl );
            return $_SESSION['user'];
		}
		return $_SESSION['user'];
	}
	
	/*
	*
	*/
	public function initUser( $user )
	{
		if( !isset($_SESSION['user']) )
		{
			$u = new Users();
			$u->mail = $user['user_email'] ;
			$u->password = md5($user['password']) ;
			$user = $this->dao->getOneModel( $u );
			if($user)
			{
				$_SESSION['user'] = $user ;
			}
			else 
			{
				return $user ;
			}
		}		
		return $this->updateClientInfo();
	}
	
	public function register( $user )
	{
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
	
	function getClientIp()
	{
		$cip = '';
		if (! empty ( $_SERVER ["HTTP_CLIENT_IP"] ))
		{
			$cip = $_SERVER ["HTTP_CLIENT_IP"];
		} else if (! empty ( $_SERVER ["HTTP_X_FORWARDED_FOR"] ))
		{
			$cip = $_SERVER ["HTTP_X_FORWARDED_FOR"];
		} else if (! empty ( $_SERVER ["REMOTE_ADDR"] ))
		{
			$cip = $_SERVER ["REMOTE_ADDR"];
		} else
		{
			$cip = "unknow IP";
		}
		return $cip;
	
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
		
		$ret = $this->dao->getOneModel( $user );
		
		if( ! is_array($userInfo) )
			$userInfo = array();
		
		unset($user);
		$user = new Users();
		
		foreach ( $userInfo as $k => $v )
		{
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
	
	function getUserInfo()
	{
		$c = null;
		try{
			if (class_exists ( "MyClient" ) && isset($_SESSION['last_key']))
			{
				$c = new MyClient ();
				return $c->verify_credentials ();
			} else if (class_exists ( "MyClientV2" ) && isset($_SESSION['token']))
			{
				$c = new MyClientV2 ();
				$uid_get = $c->get_uid ();
				return $c->show_user_by_id ( $uid_get ['uid'] );
			}
		}catch(Exception $e)
		{
			if( $this->debug )
			{
				var_dump($e);
				echo $e->xdebug_message;
			}
		}
		return null;
	}
	function getClient()
	{
		if (class_exists( "MyClient" ))
		{
			return new MyClient ();
		} else if (class_exists ( "MyClientV2" ))
		{
			return new MyClientV2 ();
		}
		return null;
	}
}
