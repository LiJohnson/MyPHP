<?php
if( class_exists("SaeKV") ){
	class MyKVDB extends SaeKV{
		public function MyKVDB(){
			echo "test";
			$this->init();
		}
	}
}
else{
	class MyKVDB implements IKvDB{
		public function MyKVDB(){echo "test";}
		public function init(){}
		public function get($key) {}
		public function set($key, $value){}
		public function add($key, $value){}
		public function replace($key, $value){}
		public function delete($key){}
		public function mget($ary){}
		public function pkrget($prefix_key, $count, $start_key){}
		public function errno(){}
		public function errmsg(){}
		public function get_info(){}
		public function get_options(){}
		public function set_options($options){}
	}
}
$kv = new MyKVDB();
?>

<?php 
 /*
 *  	错误代码及错误提示消息：
 *
 *  - 0  "Success"
 *
 *  - 10 "AccessKey Error"
 *  - 20 "Failed to connect to KV Router Server"
 *  - 21 "Get Info Error From KV Router Server"
 *  - 22 "Invalid Info From KV Router Server"
 * 
 *  - 30 "KV Router Server Internal Error"
 *  - 31 "KVDB Server is uninited"
 *  - 32 "KVDB Server is not ready"
 *  - 33 "App is banned"
 *  - 34 "KVDB Server is closed"
 *  - 35 "Unknown KV status"
 *
 *  - 40 "Invalid Parameters"
 *  - 41 "Interaction Error (%d) With KV DB Server"
 *  - 42 "ResultSet Generation Error"
 *  - 43 "Out Of Memory"
 *  - 44 "SaeKV constructor was not called"
 *  - 45 "Key does not exist"
 *	<code>
		array(
			0 =>  "Success",
			10 => "AccessKey Error",
			20 => "Failed to connect to KV Router Server",
			21 => "Get Info Error From KV Router Server",
			22 => "Invalid Info From KV Router Server",
			30 => "KV Router Server Internal Error",
			31 => "KVDB Server is uninited",
			32 => "KVDB Server is not ready",
			33 => "App is banned",
			34 => "KVDB Server is closed",
			35 => "Unknown KV status",
			40 => "Invalid Parameters",
			41 => "Interaction Error (%d) With KV DB Server",
			42 => "ResultSet Generation Error",
			43 => "Out Of Memory",
			44 => "SaeKV constructor was not called",
			45 => "Key does not exist"
		);
 *	</code>
 *
 * @author lcs 
 * @version 1.0
 * @references http://apidoc.sinaapp.com/sae/SaeKV.html
 */
 
interface IKvDB
{
	/**
	 * 空KEY前缀
	 */
	const EMPTY_PREFIXKEY  = '';
 
	/**
	 * mget获取的最大KEY个数
	 */
	const MAX_MGET_SIZE  = 32;
	
	/**
	 * pkrget获取的最大KEY个数
	 */
	const MAX_PKRGET_SIZE  = 100;
 
	/**
	 * KEY的最大长度
	 */
	const MAX_KEY_LENGTH   = 200;
 
	/**
	 * VALUE的最大长度 (4 * 1024 * 1024)
	 */
	const MAX_VALUE_LENGTH = 4194304;
	

 
	/**
	 * 初始化Sae KV 服务
	 *
	 * @return bool 
	 */
	public function init();
 
	/**
	 * 获得key对应的value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节
	 * @return string|bool成功返回value值，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function get($key) ;
 
	/**
	 * 更新key对应的value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function set($key, $value);
 
	/**
	 * 增加key-value对，如果key存在则返回失败
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function add($key, $value);
 
	/**
	 * 替换key对应的value，如果key不存在则返回失败
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function replace($key, $value);
	
	/**
	 * 删除key-value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function delete($key);
 
	/**
	 * 批量获得key-values
	 *
	 * @param array $ary 一个包含多个key的数组，数组长度小于等于MAX_MGET_SIZE
	 * @return array|bool成功返回key-value数组，失败返回false
	 *  时间复杂度 O(m * log N), m为获取key-value对的个数
	 */
	public function mget($ary);
 
	/**
	 * 前缀范围查找key-values
	 *
	 * @param string $prefix_key 前缀，长度小于MAX_KEY_LENGTH字节
	 * @param int $count 前缀查找最大返回的key-values个数，小于等于MAX_PKRGET_SIZE
	 * @param string $start_key 在执行前缀查找时，返回大于该$start_key的key-values；默认值为空字符串（即忽略该参数）
	 * @return array|bool成功返回key-value数组，失败返回false
	 *  时间复杂度 O(m + log N), m为获取key-value对的个数
	 */
	public function pkrget($prefix_key, $count, $start_key);
 
	/**
	 * 获得错误代码
	 *
	 * @return int 返回错误代码
	 */
	public function errno();
 
	/**
	 * 获得错误提示消息
	 *
	 * @return string 返回错误提示消息字符串
	 */
	public function errmsg() ;
 
	/**
	 * 获得kv信息
	 *
	 * @return array 返回kv信息数组
	 *  array(2) {
	 *    ["total_size"]=>
	 *    int(49)
	 *    ["total_count"]=>
	 *    int(1)
	 *  }
	 */
	public function get_info() ;
	
	/**
	 * 获取选项值
	 *
	 * @return array 成功返回选项数组，失败返回false
	 *  array(1) {
	 *    "encodekey" => 1 // 默认为1
	 *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
	 *  }
	 */
	public function get_options() ;
 
	/**
	 * 设置选项值
	 *
	 * @param array $options array (1) {
	 *    "encodekey" => 1 // 默认为1
	 *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
	 *  }
	 * @return bool 成功返回true，失败返回false
	 */
	public function set_options($options);
}
?>