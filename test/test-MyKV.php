<?php

require("../MyKV.php");
$kv = new MyKV();
echo $kv->get("A");
?>