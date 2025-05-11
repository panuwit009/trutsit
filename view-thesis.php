<?php
session_start(); // Start the session

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config.php'; // Ensure this includes the correct database connection

use setasign\Fpdi\Fpdi;

$id = $_GET['id'];
$sql = "SELECT * FROM theses WHERE id = :id"; // Use a prepared statement
$stmt = $pdo->prepare($sql); // Use $pdo instead of $conn
$stmt->bindParam(':id', $id, PDO::PARAM_INT); // Bind the ID parameter
$stmt->execute(); // Execute the query
$thesis = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result

if ($thesis) {
    $filePath = $thesis['file_path'];

    // Increment the view count
    $updateStmt = $pdo->prepare("UPDATE theses SET most_searched = most_searched + 1 WHERE id = ?");
    $updateStmt->execute([$id]);

    // Record view history
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $viewStmt = $pdo->prepare("INSERT INTO view_history (user_id, thesis_id, view_time) VALUES (?, ?, NOW())");
        $viewStmt->execute([$user_id, $id]);
    }

    if (file_exists($filePath)) {
       
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
    } else {
        echo "ไม่พบไฟล์";
    }
} else {
    echo "ไม่พบปริญญานิพนธ์";
}
?>
