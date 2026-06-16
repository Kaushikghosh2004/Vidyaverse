<?php
// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'lexclassroom');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$password = DB_PASS;
$port = DB_PORT;

try {
    $dbh = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed.");
}
?>
