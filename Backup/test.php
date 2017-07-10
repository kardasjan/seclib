#!/usr/bin/php-cgi
<?php

$fp = fopen("/dev/ttyUSB0", "r+");
$file = fopen("logfile.log", "w+");

if(!$fp) {
    echo "Error";
    die();
}

while($fp) {
    $data = array();
    $byte = array(
        0 => "",
        1 => ""
    );
    while ($byte[1] != "0a") {
        $byte = unpack("H*", fread($fp, 1));
        $data[] = $byte[1];
    }

    if ($data[0] != "2a" && $data[1] != "61") {
        fwrite($file, "NOT SPINEL!\n");
        continue;
    }

    if($data[6] != "0c") {
        fwrite($file, "NOT CARD READER!\n");
        continue;
    }

    $fc = $data[9];
    $card = $data[10] . $data[11];

    $response = file_get_contents("http://access.npmk.cz/api/?id=" . $fc . $card);

    if (strpos($response, 'ERR') !== false) {
        fwrite($file, $response);
        continue;
    }

    $response = explode("\n", $response);

    if (strpos($response[1], 'pristup povolen') !== false) {
        //sendToLCD($response[1], $fp);
        fwrite($fp, hex2bin("2a610015780290015072697374757020706f766f6c656e3a0d"));  // LCD
        //openRelay($data[4], $fp);
        fwrite($fp, hex2bin("2a610008310223018283100d"));                           // Relay
        fwrite($file, "Pristup povolen\n");
    }

    if (strpos($response[1], "Pristup zamitnut") !== false) {
        fwrite($fp, hex2bin("2a6100157802900150726973747570207a616d69746e7574c00d"));  // LCD
        fwrite($file, "Pristup zamitnut\n");
    }
}

fclose($fp);

function sendToLCD($message, $fp) {
    //fwrite($fp, hex2bin(""));
}

function openRelay($address, $fp) {
    // Check DB for command of $address
    //fwrite($fp, hex2bin(""));
}