#!/usr/bin/php-cgi
<?php

$string = "047667cacd2c80";
$array = str_split($string, 2);
var_dump($array);
$array = array_reverse($array);
var_dump($array);

?>
