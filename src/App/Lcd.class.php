<?php

namespace SecLib\App;

/**
 * Functions to control Papouch LCD display 2002
 * @author Jan Kardaš
 */
class Lcd extends Spinel
{
    private $ipModuleAddress;

    /**
     * Creates command for writing to LCD
     * @param $message string Message to write, max 20 characters
     * @param $line int line, 1 or 2
     * @return string Hexadecimal command
     */
    public function writeToLcd($message, $line)
    {
        if ($line != 1 && $line != 2)
            return null;
        $message = trim(preg_replace('/\s+/', " ", $message));
        $message = "0" . $line . implode("", $this->stringToHexArray($this->fixMessage($message)));
        return $this->buildCommand($this->instruction['writeToLCD'], $message);
    }

    /**
     * Sets time before display text disappears
     * @param $time int How many seconds will be text displayed
     * @return string Hexadecimal command
     */
    public function setTime($time)
    {
        return $this->buildCommand($this->instruction['lcdTime'], $this->timeToHex($time));
    }

    /**
     * Clear LCD Display
     * @return string Hexadecimal command
     */
    public function clearLcd()
    {
        return $this->buildCommand($this->instruction['clearLCD'], "");
    }

    public function setLight($value)
    {
	return $this->buildCommand($this->instruction['setLight'], $value);
    }

    /**
     * Helper function for time conversion
     * @param $time int
     * @return string
     */
    private function timeToHex($time)
    {
        return str_pad(dechex($time), 4, "0", STR_PAD_LEFT);
    }

    /**
     * Checks if string has less than 20 characters which is maximum for single LCD line
     * @param $message string Message to display
     * @return string Fixed message
     */
    private function fixMessage($message)
    {
        if (strlen($message) > 20)
            return substr($message, 0, 20);
        return $message;
    }

    public function setIP($ip)
    {
        $this->ipModuleAddress = $ip;
    }

    public function getIP()
    {
        return $this->ipModuleAddress;
    }
}
