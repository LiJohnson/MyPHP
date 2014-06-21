<?php
if( !defined('WB_AKEY') ){
	if( !defined("SAE_TMP_PATH") ) //SAE_TMP_PATH 为sae的预定义变量，用来区分本地环境还是sae环境
	{
		// app name ： developing
		/**/
		define( "WB_AKEY" , '3600693014' );
		define( "WB_SKEY" , '22325d36c32bc46cb553e87afc1b01be' );
		define(	"SRC_PATH" , 'http://lcs.com/sae/gtbcode/1/');
		define('MY_DB_NAME', 'test');
	}
	else
	{
		
		//app name : 给力
		/*
		define( "WB_AKEY" , '522446840' );
		define( "WB_SKEY" , '3c86c51f3095b49d97b08f00c85cad23' );
		/**/
		/*app name : theotherdoor*/
		define( "WB_AKEY" , '2514193462' );
		define( "WB_SKEY" , '6a957336c809666320421b44307b8a28' );
		/**/
		define(	"SRC_PATH" , 'http://1.gtbcode.sinaapp.com/');
		define('MY_DB_NAME', $_SERVER['HTTP_APPNAME']);
	}


}

