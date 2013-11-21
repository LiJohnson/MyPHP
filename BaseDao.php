<?php 
include_once dirname(__FILE__)."/MySql.php"; 

class BaseDao extends MySql {
	private $DB;	
	
	public function __construct( $app="" ) {		
		parent::__construct( $app );
	}
	
	public function __destruct() {
		$this->closeDb ();
	}
	
	/**
	 * 保存一个实体
	 * @param  object/array $model  [description]
	 * @param  string $idName 主键列名，如果不为空，则返回一个实体	
	 * @return array/int         [description]
	 */
	public function save($model , $idName = ''){
		$tableName = false;
		$key = array();
		$value = array();

		foreach ( $model as $k => $v ){
			if ($v == null)continue;

			if ( $this->isTablekey( $k )) {
				$tableName = $v;
			} else {
				$key[] = $k;
				$value[] = $v;
			}
		}
		if( !$tableName || count($key) == 0 ){
			return false;
		}

		$sql = 'insert into `' . $tableName . '` (`'.join($key,'`,`').'`) values (\''.join($value,'\',\'').'\')'  ;// values(" . $valueString . ")";
		
		$ret = $this->runsql( $sql ) ;
		if( $ret && $idName != '' ){
			return $this->getOneModel($model , ' and ' . $idName . '=' .$this->lastId());
		}
		return $ret ;
	}
	
	/**
	 * 判断key是否用来记录表名
	 * @param  string  $key
	 * @return boolean 
	 */
	private function isTablekey($key){
		return ($key === 'table_name' || $key === 'tableName') ;
	}

	/**
	 * 生成查询的sql语句
	 * @param  object/array $model [description]
	 * @param  string $order [description]
	 * @return string        [description]
	 */
	private function getSql($model, $order = "" , $isCount = false ) {
		$tableName = '';
		$condition = array('1=1');
		
		foreach ( $model as $k => $v ){
			if(is_null($v) || trim($v) == "")continue;

			if ($this->isTablekey($k)){
				$tableName = $v;
			} 
			else {
				if( is_numeric($v) )
					$condition[] = '`' . $k . '` = ' . $v;
				else
					$condition[] = '`' . $k . '` like \'' . $v .'\'';
			}
		}
		
		$sql = "select * from " . $tableName . ' where ' . join( $condition , ' and ');
		
		$sql .= ' ' .$order;
		return $sql;
	}
	
	function getModelList($model, $order = "" , &$page = false) {
		$sql = $this->getSql( $model, $order );
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
	
	function getOneModel($model, $order = "") {		
		return $this->getLine( $this->getSql( $model, $order ) );
	}

	function getList($model, $order = "" , &$page = false) {
		return $this->getModelList( $model , $order , $page );
	}
	function getOne($model, $order = "" ) {
		return $this->getOneModel( $model , $order );
	}
	
	function getUpdateSql($model, $condition = "") {
		$tableName = false;
		$sql = array();
		$dot = "";
		foreach ( $model as $k => $v ) {
			if ($v == null)continue;
			if( $this->isTablekey($k) ){
				$tableName = $v;
			}else{
				$sql[] = "`$k` = '$v'";
			}
		}
		if( !$tableName )return false;

		return "UPDATE `$tableName` SET " . join($sql,",") . " where 1=1 " . $condition;
	}
	
	function executeSql($sql) {
		$res = $this->runSql ( $sql );
		if( !$res ){
			echo "<textarea>$sql</textarea>";
			var_dump($this->error());
		}
		return $res;
	}
	
	function update($model, $condition = " and 1=2 ") {
		return $this->runSql ( $this->getUpdateSql ( $model, $condition ) );
	}

	function search() {
	}
}

?>
