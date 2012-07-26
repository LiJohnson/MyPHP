<?php
include_once( dirname(__file__).'/../config.php' );
include_once( 'saetv2.ex.class.php' );
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
  
	public function __construct( $token=null )
	{
          	if( $token == null )//
          	{
			$token['access_token']= $_SESSION['token']['access_token'] ;
          	}
                if( $token['access_token'] )
                {
			parent::__construct( WB_AKEY , WB_SKEY , $token['access_token'] );
                }
                else
                {
                	
                }
                if( $token != null && $token['ip'] )
                {
                  $this->set_remote_ip($token['ip']);                  
                }
	}
  /*
        public function __construct( $token=null )
	{
        	if( $token == null )
                {
                	$token['oauth_token'] = $_SESSION['last_key']['oauth_token'] ;
                        $token['oauth_token_secret'] = $_SESSION['last_key']['oauth_token_secret'] ;
                }
		parent::__construct( WB_AKEY , WB_SKEY , $token['oauth_token'] , $token['oauth_token_secret'] );
	}
*/
  /*public function __construct( $oauth_token , $oauth_token_secret )
	{
		parent::__construct( WB_AKEY , WB_SKEY , $oauth_token , $oauth_token_secret );
	}
*/
	/**
	 * 随便找几个微博用户 , 并根据 $isFollw 是否对其进行关注
	 * @param unknown_type $n
	 * @param unknown_type $isFollow
	 * @return String
	 */
	function getPublicUser( $n = 5 , $isFollow = false )
	{
		$ms = $this->public_timeline($n);
	//	print_r($m);
		$u = "" ;
		foreach( $ms as $s  ):
			$u .= "@".$s['user']['screen_name'] ." ";
			$re = $isFollow ? $this->follow( $s['user']['id'] ) : Array();
			//echo "fl:".$s['user']['id'] . ($re['Error'])."<br>";
			/*print_r($re);
			echo "<br>";/***/
		endforeach;
		return $u ;
	}
	
	/**
	 * 获取所有关注用户的ID
	 * @return Array
	 */
	function get_all_Friends_ids()
	{
		$ids = Array();
		do
		{
			$fr =$this->friends_ids( $fr['next_cursor'] ) ;		
			$ids  = array_merge($ids , $fr['ids']);
		
		}
		while($fr['next_cursor'] !=  0);
		return $ids ;
	}
	/**
	 * 获取所有粉丝的ID
	 * @return Array
	 */
	function get_all_Followers_ids()
	{
		$ids = Array();
		do
		{
			$fr =$this->followers_ids( $fr['next_cursor'] ) ;		
			$ids  = array_merge($ids , $fr['ids']);
		
		}
		while($fr['next_cursor'] !=  0);
		return $ids ;
	}
	
	/**
	 * 去除图片下方的水印
	 * @param unknown_type $img_url
	 */
	function changeImg( $img_url , $sy_url = NULL )
	{
		$base_img_data = file_get_contents($img_url);
		$img = new SaeImage( $base_img_data );
		$imgAttr = $img->getImageAttr();   		//var_dump($imgAttr);
		if( $imgAttr['mime'] == "image/gif" )
		{
			//echo "0<br>";
			return $img_url ;
		}
		if( $imgAttr[0] < 300 || $imgAttr[1] < 300 )
		{
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
		if( $new_data === false )
		{
			//echo "1<br>";
			return 	$img_url;
		}
		
		$stor = new SaeStorage();
		$url  = $stor->write(DOMAIN ,"1.jpg" , $new_data);
		if( $url == false )
		{
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
	function resendWeibo( $weibo )
	{
		$text = "";
		$pic  = "";
		if(  $weibo['retweeted_status']['text'] )
		{
			$text =  $weibo['retweeted_status']['text'] ;
			if( $weibo['retweeted_status']['original_pic'] )
			{
				$pic = $weibo['retweeted_status']['original_pic'] ;
			}
			else
			{
				$pic = null;
			}
		}
		else
		{
			$text =  $weibo['text'];
			if( $weibo['original_pic'] )
			{
				$pic = $weibo['original_pic'] ;
			}
			else
			{
				$pic = null ;
			}
		}
		if( $pic )
		{
			$weibo = $this->upload($text, changeImg($pic));
		}
		else
		{
			$weibo = $this->update($text);
		}
		return $weibo ;
	}
	
	function resendWeiboById( $id )
	{
		return resendWeibo($this->show_status ($id));
	}
        
        function getMyInfo()
        {
        	$uid_get = $this->get_uid();
		return $this->show_user_by_id( $uid_get['uid']);
        }
	
}