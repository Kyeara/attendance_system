<?php
$dbHost = 'localhost';
$dbName = 'attendance_system';
$dbUser = 'root';
$dbPass = '';

try {
	$pdo = new PDO("mysql:host=" . $dbHost . ";dbname=" . $dbName, $dbUser, $dbPass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die('Database connection failed: ' . $e->getMessage());
}
?>
