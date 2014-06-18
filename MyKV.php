<?php

function testMongo(){
	if( !class_exists('MongoClient') )return false;
	try{
		new MongoClient();
	}catch(Exception $e){
		return false;
	}
	return true;
}

if( class_exists("SaeKV") ){
	class MyKV extends SaeKV implements IKvDB{
		public function MyKV (){
			parent::__construct();
			$this->init();
		}
		
		public function autoFlush( $auto){}
		public function flush(){}
	}
}
else if( testMongo() ){

class MyKV implements IKvDB{
	private $collection;

	public function MyKV( $db = false ){
		$this->init(  $db );
	}
	
	/**
	 * 初始化Sae KV 服务
	 *
	 * @return bool 
	 */
	public function init( $db = false ){
		$db =  $db == false ? 'h' : $db;
		$mongo = new MongoClient();
		$this->collection = $mongo->$db->kv;
		$index = $this->collection->getIndexInfo();
		if( !isset( $index['key']) ){
			$this->collection->ensureIndex("key",array('uniqure'=>true , 'dropDups'=>true));
		}
	}
 	
 	private function getMonogo($key){
 		return $this->collection->findOne(array('key'=>$key));
 	}

	/**
	 * 获得key对应的value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节
	 * @return string|bool成功返回value值，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function get($key){
		$mongo = $this->getMonogo($key);
		if( $mongo ){
			return $mongo['value'];
		}
	}
 
	/**
	 * 更新key对应的value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function set($key, $value){
		$mongo = $this->getMonogo($key);
		if( count($mongo)){
			$mongo['value'] = $value;
			return !!$this->collection->update(array('key'=>$key),$mongo);
		}else{
			return !!$this->collection->insert(array( 'key'=>$key , 'value' => $value ));
		}
	}
 
	/**
	 * 增加key-value对，如果key存在则返回失败
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function add($key, $value){
		return $this->set($key , $value);
	}
 
	/**
	 * 替换key对应的value，如果key不存在则返回失败
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节，当不设置encodekey选项时，key中不允许出现非可见字符
	 * @param string $value 长度小于MAX_VALUE_LENGTH
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function replace($key, $value){
		return $this->set($key , $value);
	}
	
	/**
	 * 删除key-value
	 *
	 * @param string $key 长度小于MAX_KEY_LENGTH字节
	 * @return bool 成功返回true，失败返回false
	 *  时间复杂度 O(log N)
	 */
	public function delete($key){
		$res = $this->collection->remove(array('key'=>$key));
		return !!$res['n'];
	}

	
	public function autoFlush($auto){}
	public function flush(){}
	}
}
else{
	define(PATH,"d:/lcs/sae/");
	define(FILE,"KVDB.KV");
	class MyKV implements IKvDB{
		private $file;
		private $KVData;
		private $isAuto ;
		public function MyKV( $file=false  ){
			$this->init( $file );
		}
		
		private function serialize($data){
			return base64_encode( serialize( $data ));
		}
		private function unSerialize ($str){
			return unserialize( base64_decode( $str ) );
		}
		
		private function save(){
			return $this->isAuto ? file_put_contents( $this->file , $this->serialize( $this->KVData )) : false;
		}
		
		public function init( $file=false ){
			if( !file_exists(PATH) ){
				mkdir(PATH, 0777,true);
			}
			$this->file = PATH . ($file ? $file : FILE);
			if( !file_exists($this->file) ){
				file_put_contents($this->file,"");
			}
			$this->isAuto = true;
			$this->KVData = $this->unSerialize(file_get_contents( $this->file ));
			if( !is_array($this->KVData) ){
				$this->KVData = array();
			}
		}
		public function get($key) {
			return $this->KVData[$key];
		}
		public function set($key, $value){
			$this->KVData[$key] = $value;
			return $this->save();
		}
		public function add($key, $value){
			if( isset( $this->KVData[$key] ) )return false;
			
			return $this->set( $key , $value );
		}
		public function replace($key, $value){
			if( !isset( $this->KVData[$key] ) )return false;
			
			return $this->set( $key , $value );
		}
		public function delete($key){
			if( isset( $this->KVData[$key] ) ){
				unset( $this->KVData[$key] );
				return $this->save();
			}
			return true;
		}
		
		public function mget($ary){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function pkrget($prefix_key, $count, $start_key){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function errno(){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function errmsg(){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function get_info(){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function get_options(){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		public function set_options($options){throw new Exception(" 哥很忙，等有空再实现吧 ");}
		
		public function autoFlush( $auto ){
			$this->isAuto = $auto;
		}
		public function flush(){
			$tmp = $this->isAuto;
			$this->isAuto = true;
			$res = $this->save();
			$this->isAuto = $tmp;
			return $res;
		}
		
	}
}


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
	//const EMPTY_PREFIXKEY  = '';
 
	/**
	 * mget获取的最大KEY个数
	 */
	//const MAX_MGET_SIZE  = 32;
	
	/**
	 * pkrget获取的最大KEY个数
	 */
	//const MAX_PKRGET_SIZE  = 100;
 
	/**
	 * KEY的最大长度
	 */
	//const MAX_KEY_LENGTH   = 200;
 
	/**
	 * VALUE的最大长度 (4 * 1024 * 1024)
	 */
	//const MAX_VALUE_LENGTH = 4194304;
	

 
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
	//public function mget($ary);
 
	/**
	 * 前缀范围查找key-values
	 *
	 * @param string $prefix_key 前缀，长度小于MAX_KEY_LENGTH字节
	 * @param int $count 前缀查找最大返回的key-values个数，小于等于MAX_PKRGET_SIZE
	 * @param string $start_key 在执行前缀查找时，返回大于该$start_key的key-values；默认值为空字符串（即忽略该参数）
	 * @return array|bool成功返回key-value数组，失败返回false
	 *  时间复杂度 O(m + log N), m为获取key-value对的个数
	 */
	//public function pkrget($prefix_key, $count, $start_key);
 
	/**
	 * 获得错误代码
	 *
	 * @return int 返回错误代码
	 */
	//public function errno();
 
	/**
	 * 获得错误提示消息
	 *
	 * @return string 返回错误提示消息字符串
	 */
	//public function errmsg() ;
 
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
	//public function get_info() ;
	
	/**
	 * 获取选项值
	 *
	 * @return array 成功返回选项数组，失败返回false
	 *  array(1) {
	 *    "encodekey" => 1 // 默认为1
	 *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
	 *  }
	 */
	//public function get_options() ;
 
	/**
	 * 设置选项值
	 *
	 * @param array $options array (1) {
	 *    "encodekey" => 1 // 默认为1
	 *                     // 1: 使用urlencode编码key；0：不使用urlencode编码key
	 *  }
	 * @return bool 成功返回true，失败返回false
	 */
	//public function set_options($options);
	
	public function autoFlush($auto);
	public function flush();
}
?>