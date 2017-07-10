#!/usr/bin/php-cgi

<?php

$response = file_get_contents("http://access.npmk.cz/api/?id=249900002244");
//$content = substr($response, strpos($response, "\n") + 1);
//$content = preg_split("/[\n#]/", $response);
var_dump($response);

?>