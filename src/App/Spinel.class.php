<?php

namespace SecLib\App;

/**
 * This is abstract class for each src-operating device
 * @author Jan Kardaš
 */
abstract class Spinel
{
    protected $address;
    protected $rpiAddress;

    /** @var array src prefix */
    public $prefix = array("2a", "61");

    /** @var string Hexadecimal value of <CR> */
    public $endLine = "0d";

    /** @var array Instruction definitions */
    public $instruction = array(
        'wieRead'           => "0c",
        'writeToLCD'        => "90",
        'timedRelayOn'      => "23",
        'lcdTime'           => "94",
        'clearLCD'          => "91",
	'setLight'	    => "93"
    );

    /** @var array src device addresses */
    public $addresses = array(
        'lcd'               => "78",
        'relayModule'       => "8c"
    );

    public function __construct($address, $rpiAddress) {
        $this->address = $address;
        $this->rpiAddress = $rpiAddress;
    }

    /**
     * Calculates Check Sum, 255 - (All the bytes except endLine)
     *
     * @param $hexString array Contains all hexadecimal characters of a command
     * @return string hexadecimal number
     */
    public function checkSumCalc($hexString) {
        $sum = 0;
        $hexArray = str_split($hexString, 2);
        foreach($hexArray as $val) {
            $sum += hexdec($val);
        }
        return substr(dechex(255 - $sum), -2);
    }

    /**
     * Calculates length of packet
     *
     * @param $data string Data part of src packet
     * @return string hexadecimal length - 4 chars
     */
    public function lengthCalc($data) {
        $length = 2 + count(str_split($data, 2));
        return str_pad(dechex($length), 4, "0", STR_PAD_LEFT);
    }

    /**
     * Converts string message to hexadecimal array
     *
     * @param $string string Message to convert
     * @return array Hexadecimal converted array
     */
    public function stringToHexArray($string) {
        $nums = array();
        $convmap = array(0x0, 0xffff, 0, 0xffff);
        $strlen = mb_strlen($string, "UTF-8");
        for ($i = 0; $i < $strlen; $i++) {
            $ch = mb_substr($string, $i, 1, "UTF-8");
            $decimal = substr(mb_encode_numericentity($ch, $convmap, 'UTF-8'), -5, 4);
            $nums[] = base_convert($decimal, 10, 16);
        }
        return $nums;
    }

    /**
     * Sends data to RS232/485
     *
     * @param $data string Data to send
     * @param $address string Receiving device address
     * @param $instr string src instruction
     * @param $pi string Raspberry address
     * @return string Command to send
     */
    public function writeToSerial($data, $address, $instr, $pi) {
        $message = $address . $pi . $instr . $data;
        $length = $this->lengthCalc($message);
        $message = implode("", $this->prefix) . $length . $message;
        $message = $message . $this->checksumCalc($message) . "0d";
        return $message;
    }

    public function buildCommand($instruction, $data)
    {
        $message = $this->address . $this->getUnique() . $instruction . $data;
        $length = $this->lengthCalc($message);
        $message = implode("", $this->prefix) . $length . $message;
        $message = $message . $this->checksumCalc($message) . $this->endLine;
        return $message;
    }

    private function getUnique() {
	return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }
}
