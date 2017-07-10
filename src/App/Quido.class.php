<?php

namespace SecLib\App;

/**
 * Papouch Quido relay module
 * @author Jan Kardaš
 */
class Quido extends Spinel
{
    /**
     * Switches on relay or relays for certain time
     *
     * @param $time string hexadecimal number; meaning - number * 0,5s
     * @param $relays string hexadecimal number of relays 1000 0001(2) = 81(16) = Relay #1; 82 = #2
     * @param $address string Relay address
     * @return string Message to send
     */
    public function clickTimedRelay($time, $relays) {
        $message = $this->address . $this->rpiAddress . $this->instruction['timedRelayOn']
            . $time . $relays;
        $length = $this->lengthCalc($message);
        $message = implode("", $this->prefix) . $length . $message;
        $message = $message . $this->checksumCalc($message) . $this->endLine;
        return $message;
    }
  
    public function switchRelay($relay) {
        return $this->buildCommand("20", $relay);
    }
}
