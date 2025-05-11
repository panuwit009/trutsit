<?php
include 'config.php';

// Check if token is provided in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Prepare SQL statement to find the user with the provided token and check if the token is not expired
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = :token AND token_expires_at > NOW()");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update the user's verification status
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);

        // Display a success message with username and email
        $username = htmlspecialchars($user['username']); // Sanitize for HTML output
        $email = htmlspecialchars($user['email']);
        echo "ชื่อผู้ใช้ (Username) <strong>$username</strong>, อีเมล (Email) <strong>$email</strong> ยืนยันสำเร็จ คุณสามารถเข้าสู่ระบบได้ <a href='index.php'>ที่นี่</a>. ระบบจะไปยังหน้าหลักอัตโนมัติภายใน 10 วินาที";

        // Redirect to index.php after 10 seconds
        echo "
        <script>
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 10000); // 10 seconds
        </script>";
    } else {
        // Token is invalid or expired
        echo "รหัสยืนยันหมดอายุ";
    }
} else {
    echo "ไม่พบรหัสยืนยัน";
}
?>
