<?php

$myfile = fopen("badCard.txt", "r") or die("Unable to open file!");
$str = fread($myfile,filesize("badCard.txt"));
fclose($myfile);

$res = bin2hex ( $str );
echo $res;

$byte = unpack("H*", $str);
var_dump($byte[1])
?>
