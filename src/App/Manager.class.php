<?php

namespace SecLib\App;

use SecLib\Exceptions as Exceptions;

/**
 * SecLib Manager defines basic functions and for script.
 */
class Manager
{

    private $database;
    private $moduleId;
    private $apiUrl = "http://access.npmk.cz/api/?id=";

    public function __construct(ConnectionFactory $database, $ip)
    {
        $this->database = $database->getConnection();

        // Get Module ID
        $query = "SELECT id FROM ipModule WHERE ip = '" . $ip . "'";
        if ($result = $this->database->query($query)) {
            if (!$this->moduleId = $result->fetch_object()->id)
                throw new Exceptions\QueryBuildException("Failed to fetch ipModule ID from IP, " . $this->database->error);
        } else {
            throw new Exceptions\QueryBuildException("Connecting to DB to get ipModule ID " . $this->database->error);
        }
    }

    /*
    public function getRelays($group) {
        $query =
            "SELECT rmAddress, relayAddress, time FROM " .
            "(SELECT id as relayID, address as relayAddress, time, relay.relayModule_id FROM relay " .
            "LEFT JOIN relay_has_group ON relay_has_group.relay_id = relay.id WHERE group_id = $group) relays " .
            "INNER JOIN (SELECT id as rmID, address as rmAddress FROM relayModule WHERE ipModule_id = $this->moduleId) rm " .
            "ON rm.rmID = relays.relayModule_id";
        if ($result = $this->database->query($query)) {
            return $result;
        } else {
            throw new Exceptions\QueryBuildException("Failed to fetch relays for group ($group)");
        }
    }
    */

    public function getRelays($group, $relayModuleId)
    {
        $query = "SELECT * FROM (SELECT * FROM relay where relayModule_id = $relayModuleId) rels JOIN
                  (SELECT * FROM relay_has_group where group_id = $group) relGroup ON rels.id = relGroup.relay_id ORDER BY time";
        if ($result = $this->database->query($query)) {
            return $result;
        } else {
            throw new Exceptions\QueryBuildException("Failed to fetch relays");
        }
    }

    public function getApiResponse($code) {
        return file_get_contents($this->apiUrl . $code);
    }

    public function getModuleID() {
        return $this->moduleId;
    }

    public function getRelayModules()
    {
        $query = "SELECT * FROM relayModule JOIN ipModule_has_relayModule on relayModule.id = relayModule_id WHERE ipModule_id = $this->moduleId";
        if ($result = $this->database->query($query)) {
            return $result;
        } else {
            throw new Exceptions\QueryBuildException("Failed to fetch relayModules");
        }
    }

    public function getLcdModules()
    {
        $query = "SELECT * FROM lcdModule WHERE ipModule_id = $this->moduleId";
        if ($result = $this->database->query($query)) {
            return $result;
        } else {
            throw new Exceptions\QueryBuildException("Failed to fetch LCD Modules");
        }
    }

    public function networkError()
    {
        $response = array();
        foreach ($this->getLcdModules() as $lcdModuleRow) {
            $lcd = new Lcd($lcdModuleRow['address'], '01');
            $response[] = $lcd->clearLcd();
            $response[] = $lcd->setTime(0);
            $response[] = $lcd->writeToLcd("Chyba site", 1);
            $response[] = $lcd->writeToLcd("Pouzijte zvonek", 2);
        }
        return $response;
    }
}
