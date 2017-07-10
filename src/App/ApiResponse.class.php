<?php

namespace SecLib\App;
use SecLib\Exceptions\NetworkErrorException;

/**
 * SecLib ApiResponse
 */
class ApiResponse
{
    private $message;

    public function __construct($message) {
	$cardNumber = preg_replace('/\s+/', '', $message);
        $url = "http://access.npmk.cz/api/?id=" . strtoupper($cardNumber);
        $ch = curl_init();
        $timeout = 3; // 3 seconds

	var_dump($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $this->message = curl_exec($ch);
        if (!$this->message)
            throw new NetworkErrorException("Could not connect to Database API");
    }

    public function getGroup() {
        $content = preg_split("/[;]/", $this->message);
        return $content[1];
    }

    public function getCard() {
        $content = preg_split("/[;]/", $this->message);
        return $content[0];
    }

    public function getLines() {
        $content = preg_split("/[;]/", $this->message);
	if (isset($content[3]) && isset($content[4]))
        	return array($content[3], $content[4]);
	if (isset($content[3]) && !isset($content[4]))
		return array($content[3], "");
	return array("", "");
    }

    public function getColor() {
        $content = preg_split("/[;]/", $this->message);
        return $content[2];
    }

    public function getMessage() {
        return $this->message;
    }

    public function isAuthenticated() {
        if ($this->getGroup() != 0)
            return true;
        return false;
    }
}
