<?php 

if( class_exists('SaeMysql') )
{
	class Mysql extends SaeMysql
	{
		public function __construct( $appname = null , $do_replication = true  )
		{
			parent::__construct($do_replication);
			if( $appname == '598420668pic' )
			{
				$this->setAppname($appname);
				$this->setAuth("5k0zlkwxy1" , "2zz05ky3250h2wkhmyy1xhxyzmjzwmkl30hwwhlk");
			}
						elseif( $appname == 'gelivable' )
						{
				$this->setAppname($appname);
				$this->setAuth("4x5zzx0z40" , "1j0j1hwhjhk452y2k22jym55j34j4lh2x4jkhk3h");
			}
		}
		public function setDebug( $isOn ){
			//$this->debug = $isOn;
		}  
	}
}
else
{
	class Mysql extends Mysqli2{}
}

class BaseMysql
{
	protected $user ;
	protected $host ;
	protected $pass ;
	protected $dbName  ;
	protected $charset ;
	protected $port ;
	protected $debug ;
	public function __construct( $dbName){
		$this->port = 3306;
		$this->host = defined('MY_DB_HOST') ? MY_DB_HOST : 'lcs.com' ;
		$this->user = defined('MY_DB_USER') ? MY_DB_USER : 'lcs' ;
		$this->pass = defined('MY_DB_PASS') ? MY_DB_PASS : 'lcs' ;
		$this->dbName = $dbName ;
		$this->charset = 'UTF8';
		$this->debug = false;
	}

	/**
	 * 设置调试开关
	 * @param bool $isOn false/true
	 * @return void
	 */
	public function setDebug( $isOn ){
		$this->debug = $isOn;
	}
	
}

class Mysqli2 extends BaseMysql
{ 
	private $link ;
	private $dblink ;    
	private $error;
	private $errno;
	private $last_sql;
	
	/**
	 * 构造函数
	 * @return void 
	 */
	public function __construct( $dbName = 'gelivable' , $user = 'lcs' , $pass = 'lcs'){
		parent::__construct( $dbName , $user , $pass);
	}
	
	/**
	 * 设置Mysql服务器端口
	 *
	 * @param string $port 
	 * @return void 
	 */
	public function setPort( $port ){
		$this->port = $port; 
	} 
	
	/**
	 * 设置当前连接的字符集 , 必须在发起连接之前进行设置
	 *
	 * @param string $charset 字符集,如GBK,GB2312,UTF8
	 * @return void 
	 */
	public function setCharset( $charset ){
		return $this->charset = $charset;
	}
	
	/**
	 * 运行Sql语句,不返回结果集
	 *
	 * @param string $sql 
	 * @return mysqli_result|bool
	 */
	public function runSql( $sql )
	{
		$this->last_sql = $sql;
		$this->dblink = $this->getDBLink();
		$ret = mysqli_query( $this->dblink, $sql );
		$this->save_error( $this->dblink );
		if( $this->debug ){
			echo $sql .'\n<br>';
		}
		return $ret;
	}
 
	/**
	 * 运行Sql,以多维数组方式返回结果集
	 *
	 * @param string $sql 
	 * @return array 成功返回数组，失败时返回false
	 */
	public function getData( $sql )
	{
		$result = $this->runSql($sql);

		$data = Array();
		$i = 0;
	   
		if (is_bool($result)) {
			return $result;
		} else {
			while( $Array = mysqli_fetch_array( $result, MYSQL_ASSOC ) )
			{
				$data[$i++] = $Array;
			}
		}
 
		mysqli_free_result($result); 
 
		if( count( $data ) > 0 )
			return $data;
		else
			return NULL;
	} 
   
	/**
	 * 运行Sql,以数组方式返回结果集第一条记录
	 *
	 * @param string $sql 
	 * @return array 成功返回数组，失败时返回false
	 */
	public function getLine( $sql ){
		if( !preg_match('/limit/i', $sql) ){
			$sql .= ' limit 1';
		}

		$data = $this->getData( $sql );
		if ($data) {
			return @reset($data);
		} else {
			return false;
		}
	}  
 
	/**
	 * 运行Sql,返回结果集第一条记录的第一个字段值
	 *
	 * @param string $sql 
	 * @return mixxed 成功时返回一个值，失败时返回false
	 */
	public function getVar( $sql )
	{
	   $data = $this->getLine( $sql );
		if ($data) {
			return $data[ @reset(@array_keys( $data )) ];
		} else {
			return false;
		}
	} 
	/**
	 * 同mysqli_affected_rows函数
	 *
	 * @return int 成功返回行数,失败时返回-1
	 * @author Elmer Zhang
	 */
	public function affectedRows()
	{
		return mysqli_affected_rows( $this->getDBLink() );
	}
 
	/**
	 * 同mysqli_insert_id函数
	 *
	 * @return int 成功返回last_id,失败时返回false
	 */
	public function lastId()
	{
		$result = mysqli_insert_id( $this->getDBLink() );
		return $result;
	}
 
   
 
	/**
	 * 关闭数据库连接
	 *
	 * @return bool 
	 * @author EasyChen
	 */
	public function closeDb()
	{
		if( isset( $this->dblink ) )
			@mysqli_close( $this->dblink );
	}
 
	/**
	 *  同mysqli_real_escape_string
	 *
	 * @param string $str 
	 * @return string 
	 */
	public function escape( $str )
	{
		if( ! isset( $this->dblink ))
		{
			$this->dblink = $this->getDBLink();
		}
		return mysqli_real_escape_string( $this->dblink , $str );
	}
 
	/**
	 * 返回错误码
	 * 
	 * @return int 
	 */
	public function errno()
	{
		return  $this->errno;
	}
 
	/**
	 * 返回错误信息
	 *
	 * @return string 
	 */
	public function error()
	{
		return $this->error;
	}
 
	/**
	 * 返回错误信息,error的别名
	 *
	 * @return string 
	 */
	public function errmsg()
	{
		return $this->error();
	}
 
	/**
	 * @ignore
	 */
	private function connect( )
	{
		if ($this->port == 0) {
			$this->error = 13048;
			$this->errno = 'Not Initialized';
			return false;
		}
	   
		$db = mysqli_init();
		mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
 
		if( !mysqli_real_connect( $db, $this->host , $this->user , $this->pass , $this->dbName , $this->port ) )
		{
			$this->error = mysqli_connect_error();
			$this->errno = mysqli_connect_errno();
			return false;
		}
		
		mysqli_set_charset( $db, $this->charset);
 
		return $db;
	}
 

	private function getDBLink( $reconnect = false )
	{
		if( isset( $this->dblink ) && (!$reconnect) && mysqli_ping( $this->dblink ))
		{
			return $this->dblink;
		}
		$this->dblink = $this->connect();
		return $this->dblink;
	}
	/**
	 * @ignore
	 */
	private function save_error($dblink)
	{
		$this->error = mysqli_error($dblink);
		$this->errno = mysqli_errno($dblink);
	}
	
	/**
	 * 设置DB
	 *
	 * @return string 
	 */
	public function setDBName($dbName)
	{
		return $this->dbName = $dbName;
	}

}

/******************************************************************************************/
class MysqlPDO extends PDO
{ 
	private $link ;
	private $user ;
	private $host ;
	private $pass ;
	private $dbName  ;
	private $charset ;
	private $port ;
	private $dblink ;
	 
	private $error;
	private $errno;
	private $last_sql;
	
	/**
	 * 构造函数
	 * @return void 
	 */
	public function __construct( )
	{
		$this->port = 3306;
		$this->host = 'lcs.com';
		$this->user = 'lcs';
		$this->pass = 'lcs';
		$this->dbName = 'gelivable';
		$this->charset = 'UTF8';
		
		//DSN : 'mysql:dbname=testdb;host=127.0.0.1;port=3333'
		$DSN = "mysql:dbname=".$this->dbName.";host=".$this->host.";port=".$this->port ; 
		parent::__construct( $DSN, $this->user , $this->pass ,  array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '".$this->charset."'"));
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT , true);
		
	}
	
	/**
	 * 设置Mysql服务器端口
	 *
	 * @param string $port 
	 * @return void 
	 */
	public function setPort( $port )
	{
		$this->port = $port; 
	} 
	
	/**
	 * 设置当前连接的字符集 , 必须在发起连接之前进行设置
	 *
	 * @param string $charset 字符集,如GBK,GB2312,UTF8
	 * @return void 
	 */
	public function setCharset( $charset )
	{
		return $this->charset = $charset;
	}
	
	/**
	 * 运行Sql语句,不返回结果集
	 *
	 * @param string $sql 
	 * @return mysqli_result|bool
	 */
	public function runSql( $sql )
	{
		$this->last_sql = $sql;
		return $this->exec($sql);
	}
 
	/**
	 * 运行Sql,以多维数组方式返回结果集
	 *
	 * @param string $sql 
	 * @return array 成功返回数组，失败时返回false
	 */
	public function getData( $sql )
	{
		$this->last_sql = $sql;
		
		$t = $this->query($sql);
		return $t->fetchAll();
		
	} 
   
	/**
	 * 运行Sql,以数组方式返回结果集第一条记录
	 *
	 * @param string $sql 
	 * @return array 成功返回数组，失败时返回false
	 */
	public function getLine( $sql ){	
		if( !preg_match('/limit/i', $sql) ){
			$sql .= ' limit 1';
		}

		$data = $this->getData( $sql );
		if ($data) {
			return @reset($data);
		} else {
			return false;
		}
	}  
 
	/**
	 * 运行Sql,返回结果集第一条记录的第一个字段值
	 *
	 * @param string $sql 
	 * @return mixxed 成功时返回一个值，失败时返回false
	 */
	public function getVar( $sql )
	{
	   $data = $this->getLine( $sql );
		if ($data) {
			return $data[ @reset(@array_keys( $data )) ];
		} else {
			return false;
		}
	} 
	/**
	 * 同mysqli_affected_rows函数
	 *
	 * @return int 成功返回行数,失败时返回-1
	 * @author Elmer Zhang
	 */
	public function affectedRows()
	{
		return mysqli_affected_rows( $this->getDBLink() );
	}
 
	/**
	 * 同mysqli_insert_id函数
	 *
	 * @return int 成功返回last_id,失败时返回false
	 */
	public function lastId()
	{
		return $this->lastInsertId();
	}
 
   
 
	/**
	 * 关闭数据库连接
	 *
	 * @return bool 
	 */
	public function closeDb()
	{
	}
 
	/**
	 *  同mysqli_real_escape_string
	 *
	 * @param string $str 
	 * @return string 
	 */
	public function escape( $str )
	{
		if( ! isset( $this->dblink ))
		{
			$this->dblink = $this->getDBLink();
		}
		return mysqli_real_escape_string( $this->dblink , $str );
	}
 
	/**
	 * 返回错误码
	 * 
	 * @return int 
	 */
	public function errno()
	{
		return  $this->errorCode();
	}
 
	/**
	 * 返回错误信息
	 *
	 * @return string 
	 */
	public function error()
	{
		return $this->errorInfo();
	}
 
	/**
	 * 返回错误信息,error的别名
	 *
	 * @return string 
	 */
	public function errmsg()
	{
		return $this->error();
	}
	
	
}

interface iDataBase
{
	public function runSql( $sql );
	public function getData( $sql );
	public function getLine( $sql );
	public function getVar( $sql );
	public function errno();
	public function error();
	public function escape();
	public function closeDb();
}
?>