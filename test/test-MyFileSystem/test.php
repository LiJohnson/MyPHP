<?php
include "../../MyFileSystem.php";

$fs = new MyFileSystem('f:/shit' , 'http://lcs.com/github/webFile/testFile'); 
//var_dump($fs->ls('f/.'));

$a = array();
array_pop($a);
var_dump(join($a,'/'));
echo preg_replace('/\/\//', '/', '///s//f///g//h///');

