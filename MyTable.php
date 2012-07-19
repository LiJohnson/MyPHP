<?php
class Message{ 
	var $table_name = 'message';
	var $message_id;
	var $text;
	var $created_at;
	var $sender_id;
	var $recipient_id;
	var $sender_name;
	var $recipient_name;
	var $is_read;
}

class Users
{
	var $table_name = 'users';
    var $id;
	var $name;
	var $screen_name;
	var $last_date;
	var $level;
	var $ip;
	var $province;
	var $city;
	var $location;
	var $description;
	var $url;
	var $profile_image_url;
	var $domain;
	var $gender;
	var $followers_count;
	var $friends_count;
	var $statuses_count;
	var $favourites_count;
	var $bookmarks_count;
	var $created_at;
	var $following;
	var $allow_all_act_msg;
	var $geo_enabled;
	var $verified;
	var $add_date;
	var $count;
	var $oauth_token;
	var $oauth_token_secret;
	var $access_token;
	var $idstr;
	var $profile_url;
	var $weihao;
	var $verified_type;
	var $allow_all_comment;
	var $avatar_large;
	var $verified_reason;
	var $follow_me;
	var $online_status;
	var $bi_followers_count;
	var $lang;
	var $mail;
	var $password;
}
        
?>