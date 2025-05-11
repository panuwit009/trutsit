<?php
session_start();
require_once 'config.php';
require 'send_verification_email.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $student_id = $_POST['student_id'];
    $id_number = $_POST['id_number'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = 'รหัสผ่านไม่ตรงกัน';
        header('Location: index.php');
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate verification token
    $token = bin2hex(random_bytes(16));
    $token_expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));

    // Prepare and execute the SQL statement
    try {
        $pdo->beginTransaction();

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception('ชื่อผู้ใช้ (Username) หรือ อีเมล (email) ถูกใช้แล้ว');
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, surname, student_id, id_number, username, password, email, verification_token, token_expires_at) VALUES (:name, :surname, :student_id, :id_number, :username, :password, :email, :verification_token, :token_expires_at)");
        $stmt->execute([
            ':name' => $name,
            ':surname' => $surname,
            ':student_id' => $student_id,
            ':id_number' => $id_number,
            ':username' => $username,
            ':password' => $hashed_password,
            ':email' => $email,
            ':verification_token' => $token,
            ':token_expires_at' => $token_expires_at
        ]);

        $pdo->commit();
        sendEmail($email, $token, 'verification');

        // Set success message with the bold email
        $_SESSION['register_success'] = 'ลงทะเบียนสำเร็จ. ระบบได้ส่งอีเมลยืนยันไปที่ ' . htmlspecialchars($email) . ' กรุณาตรวจสอบข้อความในกล่องจดหมายของอีเมลท่านเพื่อยืนยันตัวตน';
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['register_error'] = 'Error: ' . $e->getMessage();
        header('Location: index.php');
        exit();
    }
}
?>
