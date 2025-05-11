<?php
$host = 'fdb1029.awardspace.net';
$dbname = '4530371_trutsit';
$username = '4530371_trutsit';
$password = 'panuwit009';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to database: " . $e->getMessage());
}
?>
