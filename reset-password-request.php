<?php
session_start(); // Ensure session is started
include 'config.php';
include 'send_verification_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['email']; // Could be email or username
    $id_number = $_POST['id_number'];
    $student_id = $_POST['student_id'];
    $token = bin2hex(random_bytes(50));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Determine if input is email or username and prepare the query accordingly
    $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
    if ($isEmail) {
        // Input is an email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND id_number = :id_number AND student_id = :student_id");
        $stmt->execute([':email' => $input, ':id_number' => $id_number, ':student_id' => $student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Input is a username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND id_number = :id_number AND student_id = :student_id");
        $stmt->execute([':username' => $input, ':id_number' => $id_number, ':student_id' => $student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $input = $user['email']; // Get the email for the user
        }
    }

    if ($user) {
        // Update the reset token and expiration
        $sql = "UPDATE users SET reset_token = :token, reset_token_expires_at = :expires_at WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':expires_at' => $expires_at,
            ':id' => $user['id']
        ]);

        // Send the reset password email
        sendEmail($input, $token, 'reset'); // Adjust this function to handle reset emails

        // Censor the email if username was used
        if (!$isEmail) {
            $email = $user['email'];
            $emailParts = explode('@', $email);
            $emailParts[0] = substr($emailParts[0], 0, 2) . str_repeat('*', strlen($emailParts[0]) - 4) . substr($emailParts[0], -2);
            $censoredEmail = implode('@', $emailParts);
        } else {
            $censoredEmail = htmlspecialchars($input);
        }

        // Store the email in the session with the censored version if applicable
        $_SESSION['reset_success'] = 'รหัสยืนยันถูกส่งไปในอีเมล กรุณาตรวจสอบอีเมล :  ' . $censoredEmail . '';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['reset_error'] = 'ไม่พบข้อมูลผู้ใช้ที่ตรงกัน';
        header('Location: index.php');
        exit();
    }
}
?>
