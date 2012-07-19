<?php
include_once dirname(__FILE__)."/MySql.php";
include_once dirname(__FILE__)."/BaseModel.php";
include_once dirname(__FILE__)."/MyTable.php";

class MyLogin
{
	private $callbackUrl;
	private $mysql ;
	private $model ;
	public function __construct($callback = "", $uid = null)
	{
		$this->mysql = new MySql("gelivable");
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
		$this->getClient ()->end_session ();
		session_destroy ();
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
			} catch ( OAuthException $e )
			{
			}
			if ($token)
			{
				$_SESSION ['token'] = $token;
				$this->updateClientInfo ();
				
				// print_r ( $token );
			} else
			{
				exit ();
			}
		} else
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
		} elseif (! isset ( $_SESSION ['last_key'] ))
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
	
	
	public function initUser( $user )
	{
		
		if( !isset($_SESSION['user']) )
		{
			$sql = "SELECT *  FROM `users` WHERE `mail` LIKE '".$user['user_email']."' AND `password` LIKE '".md5($user['password'])."' ";
			//echo $sql;
			$user = $this->mysql->getLine($sql);
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
			$cip = "未知IP";
		}
		return $cip;
	
	}
	
	function updateClientInfo()
	{
		
		$table = "users";
		$userInfo = $this->getUserInfo ();
		
		if( $userInfo == null || isset($userInfo['error']) )
		{
			$userInfo = $_SESSION['user'];
		}
		
		$sql = " select `id`,`count` from `" . $table . "` where id = '" . $userInfo ['id'] . "' ";
		
		$msg = $this->mysql->getLine( $sql );
		// print_r($msg);
		
		if (isset ( $msg ['id'] ))
		{
			$sql = "";
			if( is_array($userInfo) )
			{
				foreach ( $userInfo as $k => $v ):
					if ( $v && !is_array ( $v ) && $k != 'id' && $k != 'users_id')
					{
						$sql .= ("`" . $k . "` =  '" . $v . "' , ");
					}
				endforeach;
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
		} else
		{
			$_k = '(';
			$_v = '(';
			
			foreach ( $userInfo as $k => $v ):
				if (! is_array ( $v ))
				{
					$_k .= ('`' . $k . '`' . ',');
					$_v .= ("'" . $v . "'" . ',');
				}
			endforeach;
			
			$_k .= "`last_date` , `ip` ,`add_date` ,`access_token` ) ";
			$_v .= "'" . date ( DATE_COOKIE ) . "' , '" . $this->getClientIp () . "','" . date ( "Y-m-d H:i:s" ) . "' , '" . $_SESSION ['token'] ['access_token'] . "' )";
			$sql = "INSERT INTO `" . $table . "` " . $_k . " VALUES " . $_v;
		
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
		if (class_exists ( "MyClient" ))
		{
			return new MyClient ();
			// return $c->verify_credentials();
		} else if (class_exists ( "MyClientV2" ))
		{
			return new MyClientV2 ();
		}
		return null;
	}
}
