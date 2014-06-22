<?php
session_start();
include 'config.php';
require("../MyLogin.php");
$o = new MyLogin();
$o->setDebug();
var_dump($o->login());