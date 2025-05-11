<?php
session_start();
require_once 'config.php';
include 'send_verification_email.php';

// Function to censor the email address
function censorEmail($email) {
    list($local, $domain) = explode('@', $email);

    if (strlen($local) > 4) {
        $censored_local = substr($local, 0, 2) . str_repeat('*', strlen($local) - 4) . substr($local, -2);
    } else {
        $censored_local = str_repeat('*', strlen($local));
    }

    return $censored_local . '@' . $domain;
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Retrieve the user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['is_verified'] == 0) {
            $token = bin2hex(random_bytes(16));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));

            // Update the user's verification token and expiration time
            $stmt = $pdo->prepare("UPDATE users SET verification_token = :token, token_expires_at = :expires_at WHERE id = :user_id");
            $stmt->execute([
                ':token' => $token,
                ':expires_at' => $expires_at,
                ':user_id' => $user_id
            ]);

            // Send the verification email
            $email_sent = sendEmail($user['email'], $token, 'verification');

            if ($email_sent) {
                // Censor the user's email
                $censored_email = censorEmail($user['email']);
                $_SESSION['message'] = 'รหัสยืนยันตัวตนได้ถูกส่งไปที่ ' . htmlspecialchars($censored_email) . ' โปรดตรวจสอบอีเมลของคุณ';
            } else {
                $_SESSION['message'] = 'ไม่สามารถส่งอีเมลยืนยันได้';
            }
        } else {
            $_SESSION['message'] = 'อีเมลของคุณได้รับการยืนยันแล้ว';
        }
    } else {
        $_SESSION['message'] = 'ไม่พบผู้ใช้';
    }
} else {
    $_SESSION['message'] = 'ไม่พบผู้ใช้';
}

header('Location: index.php');
exit();
?>