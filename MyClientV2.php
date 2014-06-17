<?php
require_once( dirname(__file__).'/config.php' );
require_once( dirname(__file__).'/lib/saetv2.ex.class.php' );
/**
 * date 2011年5月14日 17:08:17
 * Enter description here ...
 * @author lcs
 *
 */
//class MyClient extends SaeTClient
class MyClientV2 extends SaeTClientV2 
{
	/**
	 * 
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
	}
 	
 	function wbOauth(){
 		$url =  $_SERVER ['SCRIPT_URI'] ? $_SERVER ['SCRIPT_URI'] :  "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
 		
 		$o = new SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
 		if(!isset ( $_REQUEST ['code'] )){
			$code_url = $o->getAuthorizeURL ( $url );
			header( "refresh:0;url=" . $code_url );
			exit();
		}
		else{
			$keys = array ();
			$keys ['code'] = $_REQUEST ['code'];
			$keys ['redirect_uri'] = $url ;
			try{
				$token = $o->getAccessToken ( 'code', $keys );
				if ($token){
					$this->oauth = new SaeTOAuthV2( WB_AKEY, WB_SKEY, $token['access_token'], $refresh_token );
					$_SESSION['token'] = $token;
					return $token;
				}
			}catch(OAuthException $e){
				var_dump($e);
				echo $e->xdebug_message;
			}
		}
 	}

 	function isOauthed(){
 		return !!$this->oauth->access_token;
 	}

	/**
	 * 随便找几个微博用户 , 并根据 $isFollw 是否对其进行关注
	 * @param unknown_type $n
	 * @param unknown_type $isFollow
	 * @return String
	 */
	function getPublicUser( $n = 5 , $isFollow = false ){
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
	function get_all_Friends_ids( $uid = null )
	{
		$ids = Array();
		do
		{
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
	function get_all_Followers_ids( $uid = null )
	{
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
	 * @param unknown_type $img_url
	 */
	function changeImg( $img_url , $sy_url = NULL ){		
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
			//echo "1<br>";
			return 	$img_url;
		}
		
		$stor = new SaeStorage();
		$url  = $stor->write(DOMAIN ,"1.jpg" , $new_data);
		if( $url == false ){
			//echo $stor->errMsg().$g_domain.$new_data;
			
			//	echo "2<br>";
			
			return 	$img_url;
		}
		return $url;	
	}
	
	/**
	 * 重新发微博
	 * @param unknown_type $weibo
	 */
	function resendWeibo( $weibo ){
		$text = "";
		$pic  = "";
		if(  $weibo['retweeted_status']['text'] ){
			$text =  $weibo['retweeted_status']['text'] ;
			if( $weibo['retweeted_status']['original_pic'] ){
				$pic = $weibo['retweeted_status']['original_pic'] ;
			}
			else{
				$pic = null;
			}
		}
		else{
			$text =  $weibo['text'];
			if( $weibo['original_pic'] ){
				$pic = $weibo['original_pic'] ;
			}
			else{
				$pic = null ;
			}
		}
		if( $pic ){
			$weibo = $this->upload($text, changeImg($pic));
		}
		else{
			$weibo = $this->update($text);
		}
		return $weibo ;
	}
	
	function resendWeiboById( $id ){
		return resendWeibo($this->show_status ($id));
	}
        
	function getUserInfo( $id = false ){
		if( $id ){
			return $this->show_user_by_id( $id );
		}
		$uid_get = $this->get_uid();
		return $this->show_user_by_id( $uid_get['uid']);
	}
	
	function get( $api , $params = array() ){
		return $this->oauth->get( $api, $params );	
	}
	
	function post( $api , $params = array() ){
		return $this->oauth->post( $api, $params );	
	}
	
}