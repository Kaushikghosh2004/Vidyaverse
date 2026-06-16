<?php
$servername = "localhost";
$username = "root"; // Change as per your setup
$password = ""; // Change as per your setup
$dbname = "lexclassroom";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");