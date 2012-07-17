<?php
//include "MyStorage.php";
include "MyMail.php";

//$s = new MyStorage();
$m = new MyMail();
$ret = $m->quickSend( '598420668@163.com' , '邮件标题' , '邮件内容' , 'zxcvbnqwe1@163.com' , '362514' );
echo "<pre>";
print_r($m);
var_dump($m->errno(), $m->errmsg());
?>