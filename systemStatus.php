<?php

spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'SecLib\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use SecLib\App as SecLib;
use SecLib\Exceptions as Exceptions;
use SecLib\Exceptions\NetworkErrorException;
use SecLib\Logger as Logger;
use SecLib\App\StatusManager as Manager;

// START OF SCRIPT

$rpiAddress = "02";
$gnomeIps = array(
	0 => "192.168.30.93",
	1 => "192.168.30.94",
	2 => "192.168.30.95"
);

try {
    $database = new SecLib\ConnectionFactory();
} catch (Exceptions\MysqliConnectException $e) {
    echo $e->getMessage();
    return false;
}

$logger = new Logger\Logger($database);
try {
    $manager = new Manager($database);
} catch (Exceptions\QueryBuildException $e) {
    $logger->log($e->getMessage());
    echo $e->getMessage();
    return false;
}

try {
    $apiResponse = new \SecLib\App\StatusApiResponse();
    $lines = $apiResponse->getLines();
    $data = array(
        'row1'  =>  $lines[0],
        'row2'  =>  $lines[1]
    );
    $status = $apiResponse->getStatus();
} catch (NetworkErrorException $e) {
    exit();
}

$result = $manager->getAllLcdModules();
$lcd = array();
foreach ($result as $row) {
    $tmp = new SecLib\Lcd($row['address'], $rpiAddress);
    $tmp->setIP($row['ip']);
    $lcd[] = $tmp;
}

$quido = new SecLib\Quido("8c", $rpiAddress);
$response = array(
	$gnomeIps[0]	=> array(),
	$gnomeIps[1]	=> array(),
	$gnomeIps[2]	=> array()
);
var_dump($status);
if ($status == 0) {
    $response[$gnomeIps[0]][] = $quido->switchRelay("03");
    foreach ($lcd as $l) {
        $response[$l->getIP()][] = $l->clearLcd();
        $response[$l->getIP()][] = $l->setLight("00");
        sendToIpModule($l->getIP(), $response[$l->getIP()]);
    }
    exit();
} else {
    $response[$gnomeIps[0]][] = $quido->switchRelay("83");
}


foreach ($lcd as $l) {
    $response[$l->getIP()][] = $l->clearLcd();
    $response[$l->getIP()][] = $l->setLight("0a");
    $response[$l->getIP()][] = $l->setTime(0);
    if ($data['row1'] != "")
        $response[$l->getIP()][] = $l->writeToLcd($data['row1'], 1);
    if ($data['row2'] != "")
        $response[$l->getIP()][] = $l->writeToLcd($data['row2'], 2);

    //$response[$l->getIP()][] = $l->writeToLcd($data['row1'], 1);
    //$response[$l->getIP()][] = $l->writeToLcd($data['row2'], 2);

    sendToIpModule($l->getIP(), $response[$l->getIP()]);
}

function sendToIpModule($ip, $message) {
    /* Create a TCP/IP socket. */
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    //socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
    if ($socket === false) {
        echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    } else {
        echo "Socket created.\n";
    }

    $result = socket_connect($socket, $ip, 10001);
    if ($result === false) {
        echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        echo "Cancelling process.\n";
        return 0;
    } else {
        echo "Connected!.\n";
    }

    echo "Sending to IP: $ip\n";

    //var_dump($message);
    //$in = hex2bin(implode($message));

    foreach ($message as $m) {
        var_dump($m);
        $in = hex2bin($m);
        socket_write($socket, $in, strlen($in));
        usleep(53000);
    }

    $read = socket_read($socket, 2048, PHP_NORMAL_READ);
    echo "Read: $read\n";

    socket_shutdown($socket, 2);
    socket_close($socket);
    echo "Socket closed.\n";
}
