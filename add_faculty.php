<?php
session_start();
require 'config.php'; // Include your database configuration

// Set $page to a default if it's not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize the form data
    $faculty_name = $_POST['faculty_name'];
    
    // Prepare and execute the SQL statement
    try {
        $stmt = $pdo->prepare("INSERT INTO faculty (faculty) VALUES (?)");
        $stmt->execute([$faculty_name]);

        // Redirect back to the original page with a success message
        $_SESSION['success_message'] = 'เพิ่มรายชื่อสาขาสำเร็จ';
        header("Location: admin.php?table=faculty&page=$page");
        exit();
    } catch (PDOException $e) {
        // Redirect back with an error message
        header("Location: admin.php?error_message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    echo "Invalid request method.";
}
?>
