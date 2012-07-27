<?php
include_once dirname(__FILE__)."/BaseModel.php";
include_once dirname(__FILE__)."/MyTable.php";

class MyLogin
{
	private $callbackUrl;
	private $model ;
	public function __construct($callback = "", $uid = null)
	{
		$this->model = new BaseModel("gelivable");
		if (! $_SERVER ['SCRIPT_URI'])
			$_SERVER ['SCRIPT_URI'] = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
		$this->callbackUrl = $_GET ['callback'] ? $_GET ['callback'] : $callback;
	}
	
	public function printSQL($b){$this->model->printSQL = $b;}
	
	public function login($callback = '')
	{
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
			session_destroy();
		}
		catch(Exception1 $e){}
	}
	function initWeiboV2($callback = '')
	{
		if ($_SESSION ['token'])
			return;
		
		$o = new SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
		
		if (isset ( $_REQUEST ['code'] ))
		{
			$keys = array ();
			$keys ['code'] = $_REQUEST ['code'];
			$keys ['redirect_uri'] = $callback;
			try
			{
				$token = $o->getAccessToken ( 'code', $keys );
			}
			catch ( OAuthException $e ){}
			if ($token)
			{
				$_SESSION ['token'] = $token;
				$this->updateClientInfo();
			} else
			{
				exit ();
			}
		}
		else
		{
			$code_url = $o->getAuthorizeURL ( $_SERVER ['SCRIPT_URI'] );
			header ( "refresh:1;url=" . $code_url );
			exit ();
		}
	}
	
	public function initWeibo($callback = '')
	{
		if (! isset ( $_SESSION ['keys'] ) )
		{
			$callbackUrl = $_SERVER ['SCRIPT_URI'] . "?callback=" . $_GET ['callback'];
			
			$o = new SaeT ( WB_AKEY, WB_SKEY );
			$keys = $o->getRequestToken ();
			$_SESSION ['keys'] = $keys;
                 	$aurl = $o->getAuthorizeURL ( $keys ['oauth_token'], false, $callbackUrl );//echo 0;
			header("refresh:0;url=".$aurl);
			return $aurl;
		} 
		elseif (! isset ( $_SESSION ['last_key'] ))
		{
			$o = new SaeT ( WB_AKEY, WB_SKEY, $_SESSION ['keys'] ['oauth_token'], $_SESSION ['keys'] ['oauth_token_secret'] );//print_r($o);
			$last_key = $o->getAccessToken ( $_REQUEST ['oauth_verifier'] );
			$_SESSION ['last_key'] = $last_key;
			$this->updateClientInfo ();
			if (strlen ( $this->callbackUrl ))
				header ( "refresh:1;url=" . $this->callbackUrl );
                        return $this->updateClientInfo ();
		}
		return $callbackUrl;
	}
	
	/*
	
	*/
	public function initUser( $user )
	{
		if( !isset($_SESSION['user']) )
		{
			$user = new Users();
			$user->mail = $user['user_email'] ;
			$user->password = md5($user['password']) ;
			$user = $this->model->getOneModel( $user );
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
		$_SESSION['user'] = $this->model->save($u , 'users_id');
		$_SESSION['user']['id'] = $_SESSION['user']['users_id'];
		
		$u = new Users();
		$u->id = $_SESSION['user']['id'] = $_SESSION['user']['users_id'] ; 
		$this->model->update($u , " and `users_id`=".$_SESSION['user']['users_id']);
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
	
	function updateClientInfo()
	{
		$userInfo = $this->getUserInfo ();
		if( $userInfo == null || isset($userInfo['error']) )
		{
			$userInfo = $_SESSION['user'];
		}
		
		$user = new Users();
		$user->id = $userInfo ['id'] ;
		
		$ret = $this->model->getOneModel( $user );
		
		if(ret == false)
		{
		
		$_k = '(';
			$_v = '(';
			
			foreach ( $userInfo as $k => $v )
			{
				if (! is_array ( $v ))
				{
					$_k .= ('`' . $k . '`' . ',');
					$_v .= ("'" . $v . "'" . ',');
				}
			}
			
			$_k .= "`last_date` , `ip` ,`add_date` ,`access_token` ) ";
			$_v .= "'" . date ( "Y-m-d H:i:s" ) . "' , '" . $this->getClientIp () . "','" . date ( "Y-m-d H:i:s" ) . "' , '" . $_SESSION ['token'] ['access_token'] . "' )";
			$sql = "INSERT INTO `" . $table . "` " . $_k . " VALUES " . $_v;
			/************/
			$sql = "";
			
		} else
		{
			if( is_array($userInfo) )
			{
				foreach ( $userInfo as $k => $v )
				{
					if ( $v && !is_array ( $v ) && $k != 'id' && $k != 'users_id')
					{
						$sql .= ("`" . $k . "` =  '" . $v . "' , ");
					}
				}
			}
			
			$sql .= "`count` = " . ($msg ['count'] + 1) . ", ";
			$sql .= "`last_date` = '" . date ( "Y-m-d H:i:s" ) . "' , `ip` = '" . $this->getClientIp () . "'";
			
			if (isset ( $_SESSION ['last_key'] ))
			{
				$sql .= ",`oauth_token` = '" . $_SESSION ['last_key'] ['oauth_token'] . "' , `oauth_token_secret` = '" . $_SESSION ['last_key'] ['oauth_token_secret'] . "'";
			}
			if (isset ( $_SESSION ['token'] ))
			{
				$sql .= ",`access_token` = '" . $_SESSION ['token'] ['access_token'] . "' ";
			}
			
			$sql = "UPDATE `" . $table . "` SET  " . $sql . " WHERE  `" . $table . "`.`id` =  '" . $userInfo ['id'] . "';";
		
		}
		//echo $sql;
		$this->model->runSql ( $sql );
		return $this->model->getLine(" SELECT * from `users` where `id` = '" . $userInfo ['id'] . "'");
	}
	
	function getUserInfo()
	{
		$c = null;
		if (class_exists ( "MyClient" ))
		{
			$c = new MyClient ();
			return $c->verify_credentials ();
		} else if (class_exists ( "MyClientV2" ))
		{
			$c = new MyClientV2 ();
			$uid_get = $c->get_uid ();
			return $c->show_user_by_id ( $uid_get ['uid'] );
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
