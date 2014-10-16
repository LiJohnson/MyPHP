<?php
include 'config.php';
require("../MyKV.php");
$kv = new MyKV();

$kv->set("A" , $kv->get("A") . 'A');
echo $kv->get("A");
?>