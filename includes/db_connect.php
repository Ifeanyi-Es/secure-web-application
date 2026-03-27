<?php
$host = "localhost";        // localhost
$dbname = "dbname";  //database name
$username = "dbusername";
$password = "dbpassword";

$conn = new mysqli($host, $username, $password, $dbname);

// If the connection fails, log the error but don't output HTML or die here.
// This file is included by API endpoints which expect to control the HTTP response.
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    // Expose the error to the including script via a variable for debugging
    $db_connect_error = $conn->connect_error;
    // Set $conn to null so callers can check and return a proper JSON error
    $conn = null;
}

// Connected successfully when $conn is not null

