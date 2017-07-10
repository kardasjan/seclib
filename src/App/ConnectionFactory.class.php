<?php

namespace SecLib\App;
use SecLib\Exceptions as Exceptions;

/**
 *      This class creates database connection.
 */
Class ConnectionFactory
{
    protected $mysqli;

    public function __construct()
    {
        $this->mysqli = new \mysqli('localhost', 'root', 'imagination5386', 'seclib');
        $this->mysqli->set_charset("utf8");

        if($this->mysqli->connect_error) {
            throw new Exceptions\MysqliConnectException(__METHOD__ . ' on line ' . __LINE__ . '. Error: ' . $this->mysqli->connect_error);
        }
    }

    public function getConnection()
    {
        if (false === $this->mysqli instanceof \mysqli) {
            self::__construct();
        }
        return $this->mysqli;
    }

    function __destruct() {
        $this->mysqli->close();
    }
}
