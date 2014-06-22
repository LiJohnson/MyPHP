<?php
session_start();
require("config.php");
require("../MyClientV2.php");
$o = new MyClientV2();
$o->wbOauth();

var_dump($o->getUserInfo());