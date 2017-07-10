<?php

namespace SecLib\App;

/**
 * Papouch Wie485 - Wiegand -> RS485 converter device
 * @author Jan Kardaš
 */
class Wie extends Spinel
{
    private $message;
    private $bits;

    public function __construct($address, $rpiAddress, $message) {
        parent::__construct($address, $rpiAddress);
        $this->message = $message;
	$this->bits = hexdec($this->message[8]);
    }

    public function reverseCard($card) {
       $arr = str_split($card, 2);
       $arr = array_reverse($arr);
       return implode("", $arr);
    }
    public function getCardNumber() {
        $cardNumber = "";
        for($i = 9; $i < (9 + ($this->bits / 8)); $i++) {
            $cardNumber .= $this->message[$i];
        }
        if ($this->bits == 56)
	    return $this->reverseCard($cardNumber);
        return $cardNumber;
    }
}
