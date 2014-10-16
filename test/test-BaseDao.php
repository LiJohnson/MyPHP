<?php
require("config.php");
define('MY_DB_NAME', 'test');

require("../BaseDao.php");
$o = new BaseDao();
$o->setDebug();
$o->setTable('users');
echo $o->save(array('name'=>'log'),'user_id');
var_dump($o->update(array('name'=>'pow') , 'user_id = 14'));

var_dump($o->getOne(array('user_id'=>14)));

