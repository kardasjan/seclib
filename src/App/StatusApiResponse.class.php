<?php

namespace SecLib\App;
use SecLib\Exceptions\NetworkErrorException;

/**
 * SecLib ApiResponse
 */
class StatusApiResponse
{
    private $lines;
    private $status;


    public function __construct() {
        $url = "http://access.npmk.cz/api?status=1";
        $ch = curl_init();
        $timeout = 3; // 3 seconds

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $response = curl_exec($ch);
        if (!$response)
            throw new NetworkErrorException("Could not connect to Status API");
        $lines = preg_split("/[\n]/", $response);
        $this->lines = $lines[1];
        $this->status = $lines[0];
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getLines()
    {
	$content = preg_split("/[;]/", $this->lines);
        if (isset($content[0]) && isset($content[1]))
                return array($content[0], $content[1]);
        if (isset($content[0]) && !isset($content[1]))
                return array($content[0], "");
        return array("", "");
    }
}
