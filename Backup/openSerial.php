#!/usr/bin/php-cgi
<?php

// Definitions

$rpiAddress = "02";
$lcdAddress = "78";
$relayModuleAddress= "8c";
$prefix = array("2a", "61");
$endLine = "0d";
$cardReading = "0c";

$fp = fopen("/dev/ttyUSB0", "a+b");
$file = fopen("/home/pi/librfid/php/rs485.log", "a+");
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
    if ($byte[1] != $prefix[0]) {
        fwrite($file, "Prefix check 1 failed!\n");
        continue;
    }
    fwrite($file, "Reading first prefix: " . $byte[1] . "\n");

    // Prefix check 2
    $byte = unpack("H*", fread($fp, 1));
    if ($byte[1] != $prefix[1]) {
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
    fwrite($file, "Length: " . hexdec($length) . "\n");

    // Read rest of data, calculated from length
    for($i = 0; $i < hexdec($length); $i++) {
        $byte = unpack("H*", fread($fp, 1));
        fwrite($file, "Reading data $i: " . $byte[1] . "\n");
        $data[] = $byte[1];
    }

    // If it is not a CardReader skip the cycle
    if($data[2] != $cardReading) {
        fwrite($file, "NOT CARD READER!\n");
        continue;
    }

    $address = $data[0];
    $fc = $data[5];
    $card = $data[6] . $data[7];

    $response = file_get_contents("http://access.npmk.cz/api/?id=" . hexdec($fc) . hexdec($card));
    $content = substr($response, strpos($response, "\n") + 1);
    $content = explode(": ", $content);

    if (strpos($response, 'ERR') !== false) {
        fwrite($file, "API Response: " . $response . "\n");
    }

    if (strpos($response, 'pristup povolen') !== false) {
        writeToLcd($content[0], $lcdAddress, $rpiAddress, "01", $fp);
        writeToLcd($content[1], $lcdAddress, $rpiAddress, "02", $fp);
        clickRelay($relayModuleAddress, $rpiAddress, $fp, "03", "8182");
        fwrite($file, "Pristup povolen\n");
    }

    if (strpos($response, "Pristup zamitnut") !== false) {
        writeToLcd($content[0], $lcdAddress, $rpiAddress, "01", $fp);
        clickRelay($relayModuleAddress, $rpiAddress, $fp, "03", "81");
        fwrite($file, "Pristup zamitnut\n");
    }
}

fclose($fp);
fclose($file);

function writeToLcd($string, $address, $pi, $line, $fp) {
    $string = trim(preg_replace('/\s+/', ' ', $string));
    $message = $address . $pi . "90" . $line . implode("", stringToHexArray($string));
    $length = hexLength($message);
    $message = "2a61" . $length . $message;
    $message = $message . checksumCalc($message) . "0d";
    fwrite($fp, hex2bin($message));
}


/**
 * Calculates Check Sum, 255 - (All the bytes except endLine)
 * @param $hexString array Contains all hexadecimal characters of a command
 * @return string hexadecimal number
 */
function checksumCalc($hexString) {
    $sum = 0;
    $hexArray = str_split($hexString, 2);
    foreach($hexArray as $val) {
        $sum += hexdec($val);
    }
    return substr(dechex(255 - $sum), -2);
}

function hexLength($command) {
    $length = 2 + count(str_split($command, 2));
    return str_pad(dechex($length), 4, "0", STR_PAD_LEFT);
}

function stringToHexArray($string) {
    $nums = array();
    $convmap = array(0x0, 0xffff, 0, 0xffff);
    $strlen = mb_strlen($string, "UTF-8");
    for ($i = 0; $i < $strlen; $i++) {
        $ch = mb_substr($string, $i, 1, "UTF-8");
        $decimal = substr(mb_encode_numericentity($ch, $convmap, 'UTF-8'), -5, 4);
        $nums[] = base_convert($decimal, 10, 16);
    }
    return $nums;
}

/**
 * Switches relay for specified time
 * @param $address string Module address
 * @param $pi string RPI Address
 * @param $fp resource fopen()
 * @param $time string Hex number * 0,5s
 * @param $relays string 81; 8(16) = 1000(2) - Switch on, 1(16) = 0001(2) - Relay number
 */
function clickRelay($address, $pi, $fp, $time, $relays) {
    $message = $address . $pi . "23" . $time . $relays;
    $length = hexLength($message);
    $message = "2a61" . $length . $message;
    $message = $message . checksumCalc($message) . "0d";
    fwrite($fp, hex2bin($message));
}