<?php
session_start();
require 'config.php'; // Include your database configuration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize the form data
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $id_number = $_POST['id_number'];
    $student_id = $_POST['student_id'];
    $user_type = $_POST['user_type'];
    $verification_token = $_POST['verification_token'];
    $token_expires_at = $_POST['token_expires_at'];
    $reset_token = $_POST['reset_token'];
    $reset_token_expires_at = $_POST['reset_token_expires_at'];
    $is_verified = $_POST['is_verified'];
    

    // Prepare and execute the SQL statement
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, surname, email, verification_token, token_expires_at, is_verified, id_number, student_id, user_type, reset_token, reset_token_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $name, $surname, $email, $verification_token, $token_expires_at, $is_verified, $id_number, $student_id, $user_type, $reset_token, $reset_token_expires_at]);

        // Redirect back to the original page with a success message
        $_SESSION['success_message'] = 'เพิ่มผู้ใช้สำเร็จ';
        header("Location: usermanage.php?");
        exit();
    } catch (PDOException $e) {
        // Redirect back with an error message
        header("Location: usermanage.php?error_message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    echo "Invalid request method.";
}
?>
