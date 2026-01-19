<?php
// Database configuration - Railway compatible
// Uses environment variables on Railway, falls back to local values for development

$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'exam_timetable';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

define('DB_SERVER', $db_host);
define('DB_USER', $db_user);
define('DB_PASSWORD', $db_pass);
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

// Create connection with port
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME, (int) DB_PORT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>