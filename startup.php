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
use SecLib\Logger as Logger;

function startup($message, $ip) {
    $rpiAddress = "02";
    try {
        $database = new SecLib\ConnectionFactory();
    } catch (Exceptions\MysqliConnectException $e) {
        echo $e->getMessage();
        return false;
    }

    $logger = new Logger\Logger($database);
    try {
        $manager = new SecLib\Manager($database, $ip);
    } catch (Exceptions\QueryBuildException $e) {
        //$logger->log($e->getMessage());
        echo $e->getMessage();
        return false;
    }

    $response = array();
    try {
        $byte = unpack("H*", $message);
        $interpreter = new SecLib\Interpreter($byte[1]);
        //$logger->log("Spinel data: " . implode($interpreter->getMessage()));
    } catch (Exceptions\NotSpinelException $e) {
        $interpreter = new SecLib\BarcodeReader(preg_replace('/\s+/', '', $message));
        //$logger->log("Barcode data:" . $interpreter->getMessage());
    } catch (Exceptions\IncompleteMessageException $e) {
        //$logger->log("Incomplete message");
        return false;
    }

    if ($interpreter instanceof SecLib\Interpreter) {
        // WIE Card reader
        if ($interpreter->getInstruction() == "0c") {
            //$logger->log("Message instruction 0c - WIE485 Card Reader");
            $wie = new SecLib\Wie($interpreter->getAddress(), $rpiAddress, $interpreter->getMessage());
            $cardNumber = $wie->getCardNumber();
            //$logger->log("Card Number: $cardNumber");
            try {
                $apiResponse = new SecLib\ApiResponse($cardNumber);
            } catch (Exceptions\NetworkErrorException $e) {
                return $manager->networkError();
            }
        } else if ($interpreter->getInstruction() == "00") {
            //$logger->log("Message instruction 00 - ACK (Device " . $interpreter->getAddress() . ")");
            return false;
        } else {
            //$logger->log("Message instruction UNKNOWN! (" . $interpreter->getInstruction() . ")");
            return false;
        }
    } else if ($interpreter instanceof SecLib\BarcodeReader) {
        try {
            $apiResponse = new SecLib\ApiResponse($interpreter->getMessage());
        } catch (Exceptions\NetworkErrorException $e) {
            return false;
	    //return $manager->networkError();
        }
    } else {
        return false;
    }

    //$logger->log("API Response: " . $apiResponse->getMessage());
    //$logger->accessLog($manager->getModuleID(), $cardNumber, $apiResponse->isAuthenticated());

    // LCD
    $lcdText = $apiResponse->getLines();

    foreach ($manager->getLcdModules() as $module) {
        $lcd = new SecLib\Lcd($module['address'], $rpiAddress);
        $response[] = $lcd->clearLcd();
        $response[] = $lcd->setTime(3);
	if ($lcdText[0] != "")
        	$response[] = $lcd->writeToLcd($lcdText[0], 1);
	if ($lcdText[1] != "")
		$response[] = $lcd->writeToLcd($lcdText[1], 2);
    }

    // RELAY
    try {
        foreach ($manager->getRelayModules() as $module) {
            $quido = new SecLib\Quido($module['address'], $rpiAddress);
            $logger->log("Walking through quido address: ". $module['address']);
            foreach ($manager->getRelays($apiResponse->getGroup(), $module['id']) as $relay) {
                $logger->log("Walking through relay address: ". $relay['address']);
                $response[] = $quido->clickTimedRelay($relay['time'], $relay['address']);
            }
        }
    } catch (Exceptions\QueryBuildException $e) {
        //$logger->log("Exception: " . $e->getMessage());
        return false;
    }

    //$i = 1;
    //foreach ($response as $r) {
    //    $logger->log("Message $i ($r) added to queue");
    //    $i++;
    //}
    return $response;
}

function resetDisplay($ip) {
    $rpiAddress = "01";
    try {
        $database = new SecLib\ConnectionFactory();
    } catch (Exceptions\MysqliConnectException $e) {
        echo $e->getMessage();
        return false;
    }

    $logger = new Logger\Logger($database);
    try {
        $manager = new SecLib\Manager($database, $ip);
    } catch (Exceptions\QueryBuildException $e) {
        $logger->log($e->getMessage());
        echo $e->getMessage();
        return false;
    }

    try {
        $apiResponse = new SecLib\StatusApiResponse();
    } catch (Exceptions\NetworkErrorException $e) {
        //return $manager->networkError();
	return false;
    }

    $response = array();

    $lcdText = $apiResponse->getLines();
    foreach ($manager->getLcdModules() as $module) {
        $lcd = new SecLib\Lcd($module['address'], $rpiAddress);
        $response[] = $lcd->clearLcd();
        $response[] = $lcd->setTime(0);
	if ($lcdText[0] != "")
                $response[] = $lcd->writeToLcd($lcdText[0], 1);
        if ($lcdText[1] != "")
                $response[] = $lcd->writeToLcd($lcdText[1], 2);

        //for ($i = 0; $i < 2; $i++) {
        //    $response[] = $lcd->writeToLcd($apiResponse->getLine($i), $i + 1);
        //}
    }

    return $response;
}
