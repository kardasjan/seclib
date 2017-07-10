<?php

namespace SecLib\App;

use SecLib\Exceptions as Exceptions;

/**
 * SecLib StatusManager defines basic functions and for script.
 */
class StatusManager
{

    private $database;

    public function __construct(ConnectionFactory $database)
    {
        $this->database = $database->getConnection();
    }

    public function getAllLcdModules()
    {
        $query = "SELECT * FROM lcdModule JOIN ipModule ON ipModule.id = lcdModule.ipModule_id ";
        return $this->database->query($query);
    }
}