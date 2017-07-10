#!/usr/bin/php-cgi
<?php
$servername = "localhost";
$username = "root";
$password = "imagination5386";
$dbname = "seclib";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
