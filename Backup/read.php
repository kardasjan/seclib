#!/usr/bin/php-cgi
<?php

$fp = fopen("/dev/ttyUSB0", "r+");
$file = fopen("rs485.log", "a+");
if(!$fp || !$file) {
    echo "Error";
    die();
}

while($fp) {

    $data = array();
    $response = "";
    fwrite($file, "Begin cycle!\n");

    // If reading nothing skip loop
    $byte = fread($fp, 1);
    if (!$byte) {
        fwrite($file, "Reading nothing!\n");
        continue;
    }

    // Prefix check 1
    $byte = unpack("H*", $byte);
    if ($byte[1] != "2a") {
        fwrite($file, "Prefix check 1 failed!\n");
        continue;
    }
    fwrite($file, "Reading first prefix: " . $byte[1] . "\n");

    // Prefix check 2
    $byte = unpack("H*", fread($fp, 1));
    if ($byte[1] != "61") {
        fwrite($file, "Prefix check 2 failed!\n");
        continue;
    }
    fwrite($file, "Reading second prefix: " . $byte[1] . "\n");

    // Get first part of length
    $byte = unpack("H*", fread($fp, 1));
    $length = $byte[1];
    fwrite($file, "Reading length 1: " . $length . "\n");

    // Get second part of length
    $byte = unpack("H*", fread($fp, 1));
    $length .= $byte[1];
    fwrite($file, "Reading length 2: " . $byte[1] . "\n");
    fwrite($file, "Reading whole length: " . hexdec($length) . "\n");

    // Read rest of data, calculated from length
    for($i = 0; $i < hexdec($length); $i++) {
        $byte = unpack("H*", fread($fp, 1));
        fwrite($file, "Reading data $i: " . $byte[1] . "\n");
        $data[] = $byte[1];
    }

    // If it is not a CardReader skip the cycle
    if($data[2] != "0c") {
        fwrite($file, "NOT CARD READER!\n");
        continue;
    }

    $address = $data[0];
    $fc = $data[5];
    $card = $data[6] . $data[7];

    $response = file_get_contents("http://access.npmk.cz/api/?id=" . hexdec($fc) . hexdec($card));

    if (strpos($response, 'ERR') !== false) {
        fwrite($file, "API Response: " . $response . "\n");
    }

    if (strpos($response, 'pristup povolen') !== false) {
        $content = substr($data, strpos($data, "#") + 1);
        $content = explode(" ", $content); // 0 is group ID
        fwrite($fp, hex2bin("2a610008310223018283100d"));                            // Relay
        fwrite($fp, hex2bin("2a610015780290015072697374757020706f766f6c656e3a0d"));  // LCD
        fwrite($file, "Pristup povolen\n");
    }

    if (strpos($response, "Pristup zamitnut") !== false) {
        fwrite($fp, hex2bin("2a6100167802900150726973747570207a616d69746e7574c00d"));  // LCD
        //fwrite($fp, hex2bin("2a610008310223018283100d"));
        fwrite($file, "Pristup zamitnut\n");
    }
}

fclose($fp);
fclose($file);

