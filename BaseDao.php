<?php 
include_once dirname(__FILE__)."/MySql.php"; 

class BaseDao {
	private $DB;
	
	public $printSQL = false;
	
	public function __construct( $app="" ) {
		$this->DB = new MySql ($app);
	}
	
	public function __destruct() {
		$this->DB->closeDb ();
	}
	
	public function save($model , $idName = null)
	{
		$tableName = "";
		$keyString = "";
		$valueString = "";
		$dot = "";
		foreach ( $model as $k => $v )
		{
			if ($v != null) 
			{
				if ($k === 'table_name') 
				{
					$tableName = $v;
				} 
				else
				{
					$keyString .= $dot . "`" . $k . "`";
					$valueString .= $dot . "'" . $v . "'";
					$dot = ",";
				}
			}
		}
		
		$sql = "insert into `" . $tableName . "`(" . $keyString . ") values(" . $valueString . ")";
		
		$ret = $this->runsql( $sql ) ;
		if( $ret && $idName != null )
		{
			return $this->getOneModel($model , ' and ' . $idName . '=' .$this->DB->lastId());
		}
		return $ret ;
		//
	}
	
	private function getSql($model, $order = "") {
		$tableName = '';
		$condition = '';
		
		foreach ( $model as $k => $v ) 
		{
			if ($v != null) 
			{
				if ($k === 'table_name')
				{
					$tableName = $v;
				} 
				else 
				{
					if( is_null($v) || trim($v) == "" )
						continue;
					if( is_integer($v) )
						$condition .= " and `" . $k . "` = " . $v." ";
					else
						$condition .= " and `" . $k . "` like '" . $v."' ";
				}
			}
		}
		
		$sql = "select * from " . $tableName . " where 1=1 " . $condition . " " . $order;
		
		return $sql;
	}
	
	function getModelList($model, $order = "") {
		return $this->getData( $this->getSql( $model, $order ) );
	}
	
	function getOneModel($model, $order = "") {
		return $this->getLine( $this->getSql( $model, $order ) );
	}
	
	function getUpdateSql($model, $condition = "") {
		$sql = "UPDATE  `" . $model->table_name . "` SET ";
		$dot = "";
		foreach ( $model as $k => $v ) {
			if ($v != null && $k != 'table_name')
			{
				if( is_null($v) )
					continue;
				$sql .= $dot . " `" . $k . "` = '" . $v . "'";
				$dot = ",";			
			}
		}		
		$sql = $sql . " where 1=1 " . $condition;
		
		return $sql;
	}
	
	function executeSql($sql) {
		// echo "!!!".$sql."!!!!";
		return $this->DB->runSql ( $sql );
	}
	
	function update($model, $condition = " and 1=2 ") {
		return $this->runSql ( $this->getUpdateSql ( $model, $condition ) );
	}
	
	function runSql( $sql )
	{
		if ($this->printSQL)
			echo $sql . "\n<br>";
		return $this->executeSql($sql);
	}
	function getData( $sql )
	{
		if ($this->printSQL)
			echo $sql . "\n<br>";
		return $this->DB->getData($sql);
	}
	function getLine( $sql )
	{
		if ($this->printSQL)
			echo $sql . "\n<br>";
		return $this->DB->getLine($sql);
	}
	function lastId()
	{
		return $this->DB->lastId();
	}
	function search() {
	}
}

?>
