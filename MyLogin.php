<?php 
include_once dirname(__FILE__)."/MyClientV2.php";
include_once dirname(__FILE__)."/BaseDao.php";
if( !defined('MY_DB_NAME') )die('"MY_DB_NAME" not defined ');
/*
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `id` varchar(32) NOT NULL COMMENT '登录Id(股email)',
  `name` varchar(32) NOT NULL COMMENT '昵称',
  `ip` varchar(32) NOT NULL COMMENT '登录IP ',
  `last_date` timestamp NULL  COMMENT '登录时间',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `shit` varchar(10) NOT NULL COMMENT '屎',
  `data` longtext NOT NULL COMMENT '其它数据',
  `type` varchar(10) NOT NULL COMMENT '用户类型',
  `access_token` text NOT NULL COMMENT 'tocken',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/
/**
 * 登录
 */
class MyLogin{
	/**
	 * dao，数据库操作
	 * @var [type]
	 */
	private $dao ;
	/**
	 * 调试开关
	 * @var boolean
	 */
	private $debug  = false ;
	/**
	 * 微博SDK
	 * @var [type]
	 */
	private $client ;

	/**
	 * 构造方法
	 */
	public function MyLogin(){

		$this->dao = new BaseDao(MY_DB_NAME);
		$this->dao->setTable('users');

		//if (! $_SERVER ['SCRIPT_URI']){
		//	$_SERVER ['SCRIPT_URI'] = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
		//}
		$this->callbackUrl = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];;
		$this->client = new MyClientV2();
	}
	/**
	 * 调试开关
	 * @param boolean $on [description]
	 */
	public function setDebug( $on = true ){
		$this->debug = $on ;
		$this->dao->setDebug($on);
	}
	
	/**
	 * 登录
	 * @param  boolean $callback 类型为array时，则通过用户句密码进行登录，否则使用微博授权登录
	 * @return [type]            [description]
	 */
	public function login($callback = false ){
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

	/**
	 * 退出登录
	 * @return [type] [description]
	 */
	public function logout(){
		try{
			unset($_SESSION);
			session_destroy();
			$this->client->end_session();
		}
		catch(Exception1 $e){}
	}

	/**
	 * 微博授权
	 * @return [type] [description]
	 */
	private function initWeiboV2(){
		if( !$this->client->isOauthed() ){
			$this->client->wbOauth();
		}
		return $this->updateClientInfo();
	}
	
	/**
	 * 用户名密码登录
	 * @param  array  $loginData [description]
	 * @return [type]            [description]
	 */
	private function initUser( $loginData =array()){
		//..........
		//return $this->updateClientInfo();
	}
	
	/**
	 * 用户注册
	 * @param  [type] $user [description]
	 * @return [type]       [description]
	 */
	public function register( $user ){
		/*
		$u = new Users();
		$u->mail = $u->name = $u->screen_name = $user['user_email'] ;
		$u->password = md5($user['password']) ;
		$_SESSION['user'] = $this->dao->save($u , 'users_id');
		$_SESSION['user']['id'] = $_SESSION['user']['users_id'];
		
		$u = new Users();
		$u->id = $_SESSION['user']['id'] = $_SESSION['user']['users_id'] ; 
		$this->dao->update($u , " and `users_id`=".$_SESSION['user']['users_id']);
		return $this->updateClientInfo();
		*/
	}

	/**
	 * 获取IP
	 * @return [type] [description]
	 */
	public function getClientIp(){
		$names = array('HTTP_CLIENT_IP' , 'REMOTE_ADDR' , 'HTTP_X_FORWARDED_FOR');
		foreach ($names as $name) {
			if( $_SERVER [ $name ] ){
				return $_SERVER [ $name ];
			}
		}
		return '0.0.0.0';
	}
	
	/**
	 * 更新用户信息
	 * @return [type] [description]
	 */
	private function updateClientInfo(){
		//user_id
		//id
		//name
		//ip
		//last_date
		//passwoed
		//shit
		//data
		//type
		//token
		//var_dump($this->client->isOauthed());
		$userInfo = $this->getUserInfo();

		if( $userInfo == null || isset($userInfo['error']) ){
			$userInfo = $_SESSION['user'];
		}
		
		if(!isset($userInfo ['id']))
			return  false;

		$ret = $this->dao->getOne( array('id' => $userInfo ['id'] ) );
		
		$user = array( 'last_date'=> date ( "Y-m-d H:i:s" ) , 'ip' => $this->getClientIp() , 'access_token' => $_SESSION ['token'] ? $_SESSION ['token']['access_token'] : null );
		$user['data'] = json_encode($userInfo);

		if( !$ret ){
			$user['id'] = $userInfo['id'];
			$user['name'] = $userInfo['name'];
			$user['type'] = 'weibo';
			$this->dao->save($user);
		}else{
			$this->dao->update($user,'user_id = ' . $ret['user_id']);
		}

		return $this->dao->getOne( array('id' => $userInfo ['id'] ) );
	}
	
	/**
	 * 获取用户信息
	 * @return [type] [description]
	 */
	function getUserInfo(){	
		if( $this->client->isOauthed() ){
			return $this->client->getUserInfo();
		}
		return null;
	}
}
