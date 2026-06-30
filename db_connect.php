<?php
// db_connect.php
// $host = 'sql207.infinityfree.com';
// $dbname = 'if0_42289423_cinemaworld';
// $username = 'if0_42289423'; // default XAMPP MySQL username
// $password = 'SeatAndScreen25';     // default XAMPP MySQL password (empty)

$host = 'localhost';
$dbname = 'cinemaworld';
$username = 'root'; // default XAMPP MySQL username
$password = '';     // default XAMPP MySQL password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>