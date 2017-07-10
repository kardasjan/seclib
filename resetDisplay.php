<?php

$response = array(
    "Test" => "10.0.0.141",
    "lol"  => "10.0.0.142",
    "OMFG" => "10.0.0.143");

foreach ($response as $msg => $ip) {
    if(!$sock = socket_create(AF_INET, SOCK_STREAM, 0)) {
        $errorCode = socket_last_error();
        $errorMsg = socket_strerror($errorCode);
        die("Couldn't create socket: [$errorCode] $errorMsg \n");
    }
    if(!socket_connect($sock, $ip, 3590)) {
        $errorCode = socket_last_error();
        $errorMsg = socket_strerror($errorCode);
        die("Could not connect: [$errorCode] $errorMsg \n");
    }
    if(!socket_send($sock, $msg ,strlen($msg), 0)) {
        $errorCode = socket_last_error();
        $errorMsg = socket_strerror($errorCode);
        die("Could not send data: [$errorCode] $errorMsg \n");
    }
    socket_close($sock);
}