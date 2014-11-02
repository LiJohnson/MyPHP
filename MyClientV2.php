<?php
require_once( dirname(__file__).'/lib/saetv2.ex.class.php' );
if( !defined('WB_AKEY') )die('"WB_AKEY" not defined' );
if( !defined('WB_SKEY') )die('"WB_SKEY" not defined' );
/**
 * 新浪微博API
 * @author lcs
 * @version 1.0.5
 * @since 2011年5月14日 17:08:17
 *
 */
//class MyClient extends SaeTClient
class MyClientV2 extends SaeTClientV2 {
	/**
	 * 构造函数
	 */
	public function MyClientV2( $token=null ){
		if( $token == null ){
			$token['access_token']= $_SESSION['token']['access_token'] ;
		}
		
		if( $token['access_token'] ){
			parent::__construct( WB_AKEY , WB_SKEY , $token['access_token'] );
		}
		
		if( $token != null && $token['ip'] ){
			$this->set_remote_ip($token['ip']);                  
		}
		$this->oauth = $this->getOAuth();
	}
	
	/**
	 * 授权
	 * @param  boolean $forcelogin 中否强制登录
	 * @return 
	 */
 	public function wbOauth( $forcelogin = fasle ){
 		$url = defined('WB_CALLBACL_URL') ? WB_CALLBACL_URL :  "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
 		
 		$o = new SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
 		if(!isset ( $_REQUEST ['code'] )){
			$code_url = $o->getAuthorizeURL ( $url );
			header( "Location:" . $code_url . ( $forcelogin ? '&forcelogin=true' : '' ) );
			exit();
		}
		else{
			$keys = array ();
			$keys ['code'] = $_REQUEST ['code'];
			$keys ['redirect_uri'] = $url ;
			try{
				$token = $o->getAccessToken ( 'code', $keys );
				$this->oauth = $this->getOAuth($token);
				if ($this->oauth){
					$_SESSION['token'] = $token;
					return $token;
				}
			}catch(OAuthException $e){
				throw ($e);
				die();
			}
		}
	}

	/**
 	 * 是否已经授权
 	 * @return boolean [description]
 	 */
	public function isOauthed(){
		return !!$this->oauth->access_token;
	}

	/**
	 * 随便找几个微博用户 , 并根据 $isFollw 是否对其进行关注
	 * @param unknown_type $n
	 * @param unknown_type $isFollow
	 * @return String
	 */
	public function getPublicUser( $n = 5 , $isFollow = false ){
		$ms = $this->public_timeline($n);
		$u = "" ;
		foreach( $ms as $s  ){
			$u .= "@".$s['user']['screen_name'] ." ";
			$re = $isFollow ? $this->follow( $s['user']['id'] ) : Array();

		}
		return $u ;
	}
	
	/**
	 * 获取所有关注用户的ID
	 * @return Array
	 */
	public function get_all_Friends_ids( $uid = null ){
		$ids = Array();
		do{
			$fr =$this->friends_ids_by_id ( $uid ,$fr['next_cursor'] , 200 ) ;		
			$ids  = array_merge($ids , $fr['ids']);
		
		}
		while($fr['next_cursor'] !=  0);
		return $ids ;
	}

	/**
	 * 获取所有粉丝的ID
	 * @return Array
	 */
	public function get_all_Followers_ids( $uid = null ){
		$ids = Array();
		do
		{
			$fr =$this->followers_ids_by_id( $uid , $fr['next_cursor'] , 200 ) ;		
			$ids  = array_merge($ids , $fr['ids']);
		
		}
		while($fr['next_cursor'] !=  0);
		return $ids ;
	}
	
	/**
	 * 去除图片下方的水印
	 * @param  string $img_url 图片url
	 * @param  string $sy_url  水印图片
	 * @return string          图片地址
	 */
	private function changeImg( $img_url , $sy_url = NULL ){
		if( !class_exists('SaeImage') ){
			$tmpFileIn = ini_get('upload_tmp_dir')."/wbimage";
			$tmpFileOut = $tmpFileIn;
			
			$file = file_get_contents($img_url);
			$img = imagecreatefromstring( $file );
			$info = getimagesizefromstring($file);

			if( $info[2] == IMG_GIF ){
				file_put_contents($tmpFileOut, $file);
			}else{
				$img = imagecrop($img, ['x' => 0 , 'y' => 0 , 'width' => $info[0] , 'height' => $info[1] * 0.9]);
				imagejpeg($img, $tmpFileOut);
			}
			@imagedestroy($img);

			return $tmpFileOut;
		}

		$base_img_data = file_get_contents($img_url);
		$img = new SaeImage( $base_img_data );
		$imgAttr = $img->getImageAttr();   		//var_dump($imgAttr);
		if( $imgAttr['mime'] == "image/gif" ){
			//echo "0<br>";
			return $img_url ;
		}

		if( $imgAttr[0] < 300 || $imgAttr[1] < 300 ){
			//echo "0<br>";
			//return $img_url ;
		}
		$img->crop(0 , 1, 0  , 1-16/$imgAttr[1]);
		$base_img_data = $img->exec();
		
		$img->clean();
		$sy_img_data = $sy_url != null ? file_get_contents($sy_url) : "";
		$img->setData( array(
		 			array( $base_img_data , 0, 0, 1, SAE_TOP_LEFT ),
		  			array( $sy_img_data , 0, 0, 0.3, SAE_CENTER_CENTER)
		 	    ) );
		$img->composite($imgAttr[0], $imgAttr[1]);
		$new_data = $img->exec();
		if( $new_data === false ){
			return 	$img_url;
		}
		
		$stor = new SaeStorage();
		$url  = $stor->write(DOMAIN ,"1.jpg" , $new_data);
		if( $url == false ){
			return 	$img_url;
		}
		return $url;	
	}
	
	/**
	 * 重新发微博
	 * @param  array  $weibo  微博原始数据
	 * @param  boolean $isSend 是否立马发送
	 * @return array
	 */
	public function resendWeibo( $weibo , $isSend = null ){
		if( $isSend === true ){
			if( $weibo['pic'] ){
				$weibo = $this->upload($weibo['text'], $weibo['pic']);
			}
			else{
				$weibo = $this->update($weibo['text']);
			}
			return $weibo ;
		}

		$text = "";
		$pic  = false;

		if( $weibo['retweeted_status']['text'] ){
			$weibo = $weibo['retweeted_status'];
		}

		$text =  $weibo['text'];
		if( $weibo['original_pic'] ){
			$pic =  $this->changeImg( $weibo['original_pic'] );
		}

		$text = preg_replace('/@/', '', $text);
		$weibo = array('text' => $text , 'pic' => $pic );
		return $isSend === null ? $this->resendWeibo( $weibo , true ) : $weibo ;
		
	}
	
	/**
	 * 重新发微博
	 * @param  int  $id  微博原始id
	 * @param  boolean $isSend 是否立马发送
	 * @return array
	 */
	public function resendWeiboById( $id ,$isSend = null ){
		return $this->resendWeibo($this->show_status ($id) , $isSend);
	}

	/**
	 * 获取用户名信息
	 * @param  boolean $id 用户id
	 * @return array
	 */
	public function getUserInfo( $id = false ){
		if( $id ){
			return $this->show_user_by_id( $id );
		}
		$uid_get = $this->get_uid();
		return $this->show_user_by_id( $uid_get['uid']);
	}
	
	/**
	 * get请求
	 * @link http://open.weibo.com/wiki/%E5%BE%AE%E5%8D%9AAPI
	 * @param  string $api    
	 * @param  array  $params 
	 * @return 
	 */
	public function get( $api , $params = array() ){
		return $this->oauth->get( $api, $params );	
	}
	
	/**
	 * post请求
	 * @link http://open.weibo.com/wiki/%E5%BE%AE%E5%8D%9AAPI
	 * @param  string $api    
	 * @param  array  $params 
	 * @return 
	 */
	public function post( $api , $params = array() ){
		return $this->oauth->post( $api, $params );	
	}

	/**
	 * 退出登录
	 * @return [type] [description]
	 */
	public function end_session(){
		return $this->get('account/end_session');
	}

	/**
	 * 获取一个oauth
	 * @param  boolean $token [description]
	 * @return [type]         [description]
	 */
	private function getOAuth( $token = false ){
		if( $this->oauth )return $this->oauth;
		if( !$token ){
			$token = $_SESSION['token'];
		}

		if( $token && $token['access_token'] ){
			return  new SaeTOAuthV2( WB_AKEY, WB_SKEY, $token['access_token'], $refresh_token );
		}
		return false;
	}
}