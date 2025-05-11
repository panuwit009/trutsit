<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($email, $token, $type) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'tsitsearchingsystem@gmail.com'; // SMTP username
        $mail->Password = 'sgbh psax gwuw tjlm'; // SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('tsitsearchingsystem@gmail.com', 'TRU T-Sit (Thepsatri Rajabhat University Senior Thesis Searching System for the Faculty of Information Technology)');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);

        if ($type === 'verification') {
            $mail->Subject = 'Email Verification';
            $mail->Body    = "คลิก <a href='http://trutsit.atwebpages.com/verify.php?token=$token'>ที่นี่</a> หรือที่ลิงค์เพื่อยืนยันตัวตน.  http://trutsit.atwebpages.com/verify.php?token=$token";
        } elseif ($type === 'reset') {
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "คลิก <a href='http://trutsit.atwebpages.com/reset_password_verify.php?token=$token'>ที่นี่</a> หรือที่ลิงค์เพื่อเปลี่ยนรหัสผ่าน.   http://trutsit.atwebpages.com/reset_password_verify.php?token=$token";
        }

        $mail->send();
        return true; // Indicate that the email was sent successfully
    } catch (Exception $e) {
        // Log the error message instead of echoing
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false; // Indicate that the email was not sent
    }
}
?>