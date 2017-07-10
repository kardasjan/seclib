#!/usr/bin/php-cgi
<?php


$string = "Pristup zamitnutankdldksnkkldnfldknkfdsnflksdnfdlsknfsdklfsdnlkfsdnklfdsnlklsndlksnlkdnslkndlksdsl";
//var_dump(utf8StringToHexArray($string));
echo str_pad(dechex(count(utf8StringToHexArray($string))), 4, "0", STR_PAD_LEFT);
function utf8StringToHexArray($string) {
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