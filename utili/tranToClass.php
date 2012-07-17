<?php
session_start();
include $_SERVER[DOCUMENT_ROOT]."/class/MyClient.php";
include $_SERVER[DOCUMENT_ROOT]."/class/MyLogin.php";

include $_SERVER[DOCUMENT_ROOT]."/class/MySql.php";

$m = new Mysql();
echo "<pre>";
$fields = $m->getData("desc ".$_GET['table']);

foreach( $fields as $field )
{
	echo "\tvar $".$field["Field"].";\n";
}
print_r($fileds);