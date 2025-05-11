<?php
session_start();
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute query to find user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check email verification status
        if ($user['is_verified'] == 0) {
            $_SESSION['error_message'] = "กรุณายืนยันตัวตนก่อนเข้าสู่ระบบ <a href='resend_verification_email.php?user_id=" . urlencode($user['id']) . "'>คลิกที่นี่</a> เพื่อส่งรหัสยืนยันตัวตนไปที่อีเมล";
        } elseif (password_verify($password, $user['password'])) {
            // Password is correct, log in the user
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['surname'] = $user['surname'];
            $_SESSION['user_type'] = $user['user_type'];

            $loginStmt = $pdo->prepare("INSERT INTO login_history (user_id) VALUES (:user_id)");
            $loginStmt->bindValue(':user_id', $user['id']);
            $loginStmt->execute();
            
            header("Location: index.php");
            exit();
        } else {
            // Incorrect password
            $_SESSION['error_message'] = "ชื่อผู้ใช้หรือรหัสผ่านผิด";
        }
    } else {
        // Username does not exist
        $_SESSION['error_message'] = "ชื่อผู้ใช้หรือรหัสผ่านผิด";
    }

    // Redirect to index.php with error message
    header("Location: index.php");
    exit();
}
?>
