<?php

error_reporting(E_ALL);

//sendToIPModule("10.0.0.143", array("2a6100067a038001700d"));

$address = "10.0.0.143";
$service_port = "10001";
$in = hex2bin("2a6100067a038001700d");

$message = array();
$message[] = "2A6100057A3D91270D";
$message[] = "2A6100077A67940000F80D";
$message[] = "2A6100167ABF90014964656E746966696B756A74652073654D0D";
$message[] = "2A61001A7A2790026B617274612A6B6E6968612A7072756B617A6B614C0D";

sendToIpModule("10.0.0.143", $message);

/* Create a TCP/IP socket. */

function sendToIpModule($ip, $message) {
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
} else {
    echo "OK.\n";
}

$result = socket_connect($socket, $ip, 10001);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
    echo "OK.\n";
}

$in = hex2bin(implode($message));
echo "Sending Spinel data...";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Reading response:\n\n";
socket_read($socket, 2048, PHP_NORMAL_READ);

echo "Closing socket...";
socket_close($socket);
echo "OK.\n\n";
}
