<?php 
include_once dirname(__FILE__)."/MySql.php"; 

class BaseDao extends MySql {
	private $table;	
	
	public function __construct( $app = false ) {		
		parent::__construct( $app );
	}
	
	public function __destruct() {
		$this->closeDb ();
	}
	
	/**
	 * 生成查询的sql语句
	 * @param  object/array $param [description]
	 * @param  string $order [description]
	 * @return string        [description]
	 */
	private function getSql($param, $order = "" , $isCount = false ) {
		$tableName = '';
		$condition = array('1=1');
		
		foreach ( $param as $k => $v ){
			if(is_null($v) || trim($v) == "")continue;

			if( is_numeric($v) )
				$condition[] = '`' . $k . '` = ' . $v;
			else
				$condition[] = '`' . $k . '` like \'' . $v .'\'';
		}
		
		$sql = "select * from " . $this->table . ' where ' . join( $condition , ' and ');
		
		$sql .= ' ' .$order;
		return $sql;
	}

	/**
	 * 保存一个实体
	 * @param  object/array $param  [description]
	 * @param  string $idName 主键列名，如果不为空，则返回一个实体	
	 * @return array/int         [description]
	 */
	public function save($param , $idName = ''){

		$key = array();
		$value = array();

		foreach ( $param as $k => $v ){
			if ($v == null)continue;
			
			$key[] = $k;
			$value[] = $v;
		}

		$sql = 'insert into `' . $this->table . '` (`'.join($key,'`,`').'`) values (\''.join($value,'\',\'').'\')'  ;
		
		$ret = $this->runsql( $sql ) ;
		if( $ret && $idName != '' ){
			return $this->getOneModel($param , ' and ' . $idName . '=' .$this->lastId());
		}
		return $ret ;
	}

	
	
	public function getModelList($param, $order = "" , &$page = false) {
		$sql = $this->getSql( $param, $order );
		if( $page && $page['pageSize'] > 0 ){
			$page['page'] = $page['page'] ? $page['page'] : 1;
			$totalRecord = $this->getVar( preg_replace('/^select[\s\*]+from/', 'select count(1) from', $sql)   );
			$page['totalRecord'] = $totalRecord;
			$page['total'] = floor( $totalRecord / $page['pageSize'] ) + (  $totalRecord % $page['pageSize'] ? 1 : 0);
			$page['total'] = !$page['total'] ? 1 :$page['total'] ;
			$page['page'] = $page['page'] < $page['total'] ?  $page['page'] : $page['total'] ;
			$sql .= ' limit ' .($page['page']-1)*$page['pageSize'] . ',' .$page['pageSize'];
			
		}
		return $this->getData( $sql );
	}
	
	public function getOneModel($param, $order = "") {		
		return $this->getLine( $this->getSql( $param, $order ) );
	}

	public function getList($param, $order = "" , &$page = false) {
		return $this->getModelList( $param , $order , $page );
	}
	public function getOne($param, $order = "" ) {
		return $this->getOneModel( $param , $order );
	}
	
	public function getUpdateSql($param, $condition = "") {
		$tableName = false;
		$sql = array();
		$dot = "";
		foreach ( $param as $k => $v ) {
			if ($v == null)continue;
			$sql[] = "`$k` = '$v'";
		}

		return "UPDATE `$this->table` SET " . join($sql,",") . " where " . $condition;
	}
	
	public function executeSql($sql) {
		$res = $this->runSql ( $sql );
		return $res;
	}
	
	public function update($param, $condition = "1=2") {
		return $this->runSql ( $this->getUpdateSql ( $param, $condition ) );
	}

	public function delete( $condition = "1=2") {
		return $this->runSql ( "DELETE FROM `$this->table` WHERE " . $condition );
	}

	public function search() {
	}

	public function setTable($table){
		$this->table = $table;
	}

	public function getTable(){
		return $this->table;
	}
}

?>
