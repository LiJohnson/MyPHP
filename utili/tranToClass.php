<?php
session_start();
include dirname(__file__)."/../MySql.php";

$m = new Mysql();
echo "<pre>";
$fields = $m->getData("desc ".$_GET['table']);

foreach( $fields as $field )
{
	echo "\tvar $".$field["Field"].";\n";
}
echo "</pre>";
var_dump($fields);