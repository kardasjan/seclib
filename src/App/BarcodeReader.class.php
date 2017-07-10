<?php

namespace SecLib\App;

/**
 * SecLib BarcodeReader
 */
class BarcodeReader extends Spinel
{
    private $message;

    public function __construct($message) {
        // Remove whitespaces and trailing character, which is checksum
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }

}
