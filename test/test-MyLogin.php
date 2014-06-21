<?php
//session_start();
require("../MyLogin.php");
$o = new MyLogin();
$o->setDebug();
var_dump($o->login());