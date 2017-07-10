<?php

require "socketserver/sock/SocketServer.php";
require "startup.php";

/**
* Connection handler
*/
function onConnect( $client ) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        die('could not fork');
    } else if ($pid) {
        // parent process
        return;
    }

    printf( "[%s] Connected at port %d\n", $client->getAddress(), $client->getPort() );

    while( true ) {
        $read = $client->read();
        if( $read != '' ) {
            //$client->send( '[' . date( DATE_RFC822 ) . '] ' . $read  );
        }
        else {
            break;
        }

        if( preg_replace( '/[^a-z]/', '', $read ) == 'exit' ) {
            break;
        }
        if( $read === null ) {
            printf( "[%s] Disconnected\n", $client->getAddress() );
            return false;
        }
        else {
            printf( "[%s] recieved: %s", $client->getAddress(), $read );
            $response = startup($read, $client->getAddress());
            if ($response) {
		//var_dump($response);
		$strdata = implode($response);
                //$client->send(hex2bin($strdata));
		foreach ($response as $r) {
		  var_dump($r);
		  usleep(53000);
		  $client->send(hex2bin($r));
		}
                sleep(3);
		$response = resetDisplay($client->getAddress());
	        if ($response)
			 foreach ($response as $r) {
	                  var_dump($r);
        	          usleep(53000);
                	  $client->send(hex2bin($r));
               		 }
          	    //$client->send(hex2bin(implode($response)));

            }
        //exit();
	}
    }
    $client->close();
    printf( "[%s] Disconnected\n", $client->getAddress() );
}

$ip = "192.168.30.91";
$server = new \Sock\SocketServer(3590, $ip);
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();
$server->destroy();
