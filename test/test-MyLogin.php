<?php
session_start();
require("../MyLogin.php");
$o = new MyLogin();
var_dump($o->login());