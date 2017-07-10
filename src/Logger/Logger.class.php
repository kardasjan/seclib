<?php

namespace SecLib\Logger;
use SecLib\App\ConnectionFactory as Database;
use SecLib\Exceptions as Exceptions;

class Logger
{
    protected $database;

    public function __construct(Database $db) {
        $this->database = $db->getConnection();
    }

    public function log($message) {
        $query = "INSERT INTO appLog (text) VALUES (?)";
        if ($stmt = $this->database->prepare($query)) {
            $stmt->bind_param("s", $message);
            $stmt->execute();

            if (empty($stmt->error)) {
                $stmt->close();
                return true;
            }
            throw new Exceptions\QueryBuildException('Error while data insert. STMT Error - ' . $stmt->error);
        }
        throw new Exceptions\QueryBuildException("Could not prepare DB Query, check syntax. MySQL Error - " . $this->database->error);
    }

    public function accessLog($ipModule, $card, $auth) {
        $query = "INSERT INTO accessLog (ipModules_id, cardCode, authenticated) VALUES (?, ?, ?)";
        if ($stmt = $this->database->prepare($query)) {
            $stmt->bind_param("isi", $ipModule, $card, $auth);
            $stmt->execute();

            if (empty($stmt->error)) {
                $stmt->close();
                return true;
            }
            throw new Exceptions\QueryBuildException('Could not log access' . $stmt->error);
        }
        throw new Exceptions\QueryBuildException("DB Connect error while logging access - " . $this->database->error);
    }
}