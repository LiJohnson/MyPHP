<?php
if( class_exists("SaeKV") ){
	class MyKV extends SaeKV implements IKvDB{
		public function MyKVDB(){
			echo "test";
			$this->init();
		}
		
		public function autoFlush( $auto){}
		public function flush(){}
	}
}
else{
	define(PATH,"g:/sae/KV/");
	define(FILE,"KVDB.KV");
	class MyKV implements IKvDB{
		private $file;
		private $KVData;
		private $isAuto ;
		public function MyKVDB( $file=false  ){
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
			$this->file = PATH . ($file ? $file : FILE);
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
		
		public function mget($ary){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function pkrget($prefix_key, $count, $start_key){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function errno(){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function errmsg(){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function get_info(){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function get_options(){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		public function set_options($options){throw new Exception(" ���æ�����п���ʵ�ְ� ");}
		
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

?>

<?php 
 /*
 *  	������뼰������ʾ��Ϣ��
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
	 * ��KEYǰ׺
	 */
	const EMPTY_PREFIXKEY  = '';
 
	/**
	 * mget��ȡ�����KEY����
	 */
	const MAX_MGET_SIZE  = 32;
	
	/**
	 * pkrget��ȡ�����KEY����
	 */
	const MAX_PKRGET_SIZE  = 100;
 
	/**
	 * KEY����󳤶�
	 */
	const MAX_KEY_LENGTH   = 200;
 
	/**
	 * VALUE����󳤶� (4 * 1024 * 1024)
	 */
	const MAX_VALUE_LENGTH = 4194304;
	

 
	/**
	 * ��ʼ��Sae KV ����
	 *
	 * @return bool 
	 */
	public function init();
 
	/**
	 * ���key��Ӧ��value
	 *
	 * @param string $key ����С��MAX_KEY_LENGTH�ֽ�
	 * @return string|bool�ɹ�����valueֵ��ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(log N)
	 */
	public function get($key) ;
 
	/**
	 * ����key��Ӧ��value
	 *
	 * @param string $key ����С��MAX_KEY_LENGTH�ֽڣ���������encodekeyѡ��ʱ��key�в�������ַǿɼ��ַ�
	 * @param string $value ����С��MAX_VALUE_LENGTH
	 * @return bool �ɹ�����true��ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(log N)
	 */
	public function set($key, $value);
 
	/**
	 * ����key-value�ԣ����key�����򷵻�ʧ��
	 *
	 * @param string $key ����С��MAX_KEY_LENGTH�ֽڣ���������encodekeyѡ��ʱ��key�в�������ַǿɼ��ַ�
	 * @param string $value ����С��MAX_VALUE_LENGTH
	 * @return bool �ɹ�����true��ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(log N)
	 */
	public function add($key, $value);
 
	/**
	 * �滻key��Ӧ��value�����key�������򷵻�ʧ��
	 *
	 * @param string $key ����С��MAX_KEY_LENGTH�ֽڣ���������encodekeyѡ��ʱ��key�в�������ַǿɼ��ַ�
	 * @param string $value ����С��MAX_VALUE_LENGTH
	 * @return bool �ɹ�����true��ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(log N)
	 */
	public function replace($key, $value);
	
	/**
	 * ɾ��key-value
	 *
	 * @param string $key ����С��MAX_KEY_LENGTH�ֽ�
	 * @return bool �ɹ�����true��ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(log N)
	 */
	public function delete($key);
 
	/**
	 * �������key-values
	 *
	 * @param array $ary һ���������key�����飬���鳤��С�ڵ���MAX_MGET_SIZE
	 * @return array|bool�ɹ�����key-value���飬ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(m * log N), mΪ��ȡkey-value�Եĸ���
	 */
	public function mget($ary);
 
	/**
	 * ǰ׺��Χ����key-values
	 *
	 * @param string $prefix_key ǰ׺������С��MAX_KEY_LENGTH�ֽ�
	 * @param int $count ǰ׺������󷵻ص�key-values������С�ڵ���MAX_PKRGET_SIZE
	 * @param string $start_key ��ִ��ǰ׺����ʱ�����ش��ڸ�$start_key��key-values��Ĭ��ֵΪ���ַ����������Ըò�����
	 * @return array|bool�ɹ�����key-value���飬ʧ�ܷ���false
	 *  ʱ�临�Ӷ� O(m + log N), mΪ��ȡkey-value�Եĸ���
	 */
	public function pkrget($prefix_key, $count, $start_key);
 
	/**
	 * ��ô������
	 *
	 * @return int ���ش������
	 */
	public function errno();
 
	/**
	 * ��ô�����ʾ��Ϣ
	 *
	 * @return string ���ش�����ʾ��Ϣ�ַ���
	 */
	public function errmsg() ;
 
	/**
	 * ���kv��Ϣ
	 *
	 * @return array ����kv��Ϣ����
	 *  array(2) {
	 *    ["total_size"]=>
	 *    int(49)
	 *    ["total_count"]=>
	 *    int(1)
	 *  }
	 */
	public function get_info() ;
	
	/**
	 * ��ȡѡ��ֵ
	 *
	 * @return array �ɹ�����ѡ�����飬ʧ�ܷ���false
	 *  array(1) {
	 *    "encodekey" => 1 // Ĭ��Ϊ1
	 *                     // 1: ʹ��urlencode����key��0����ʹ��urlencode����key
	 *  }
	 */
	public function get_options() ;
 
	/**
	 * ����ѡ��ֵ
	 *
	 * @param array $options array (1) {
	 *    "encodekey" => 1 // Ĭ��Ϊ1
	 *                     // 1: ʹ��urlencode����key��0����ʹ��urlencode����key
	 *  }
	 * @return bool �ɹ�����true��ʧ�ܷ���false
	 */
	public function set_options($options);
	
	public function autoFlush($auto);
	public function flush();
}
?>