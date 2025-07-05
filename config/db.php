<?php
// Set the default timezone for all date/time functions in PHP
date_default_timezone_set('UTC');

$host = "localhost";
$dbname = "exam_portal";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set the timezone for the database connection to UTC
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
