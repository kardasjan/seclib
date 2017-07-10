<?php

namespace SecLib\App;
use SecLib\Exceptions as Exceptions;

/**
 * Interpreter of incoming messages
 */
class Interpreter extends Spinel
{
    private $message = "";
    /**
     * Interpreter constructor.
     */
    public function __construct($message) {
        parent::__construct(false, false);
        $hexArray = str_split($message, 2);
        if ($this->isSpinel($hexArray)) {
            $this->message = $hexArray;
        } else {
            throw new Exceptions\NotSpinelException("Message was not src!");
        }
    }

    /**
     * Checks if incoming message array is in src format
     *
     * @param $hexArray array Incoming message
     * @return bool true if Message is src
     * @throws Exceptions\IncompleteMessageException
     */
    private function isSpinel($hexArray) {
        if(!isset($hexArray[1]))
            throw new Exceptions\IncompleteMessageException("Message with only four bits.");
        return $hexArray[0] == $this->prefix[0] && $hexArray[1] == $this->prefix[1];
    }

    /**
     * Get length from src packet
     *
     * @param $hexArray array Incoming message
     * @return bool|number If src then returns length, false otherwise
     */
    public function getLength($hexArray) {
        return hexdec($hexArray[2] . $hexArray[3]);
    }

    public function getInstruction() {
        return $this->message[6];
    }

    public function getAddress() {
        return $this->message[4];
    }

    public function getMessage() {
        return $this->message;
    }
}

