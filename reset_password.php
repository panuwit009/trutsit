<?php
include 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    echo "Token received: " . htmlspecialchars($token) . "<br>";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Validate token and reset password
            $sql = "SELECT * FROM users WHERE verification_token = :token AND token_expires_at > NOW()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':token' => $token]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "Token is valid. User ID: " . htmlspecialchars($user['id']) . "<br>";

                $sql = "UPDATE users SET password = :password, verification_token = NULL, token_expires_at = NULL WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':id' => $user['id']
                ]);

                echo "เปลี่ยนรหัสผ่านสำเร็จตอนนี้คุณสามารถ <a href='index.php'>เข้าสู่ระบบได้ที่นี่</a>";
            } else {
                echo "รหัสยืนยันหมดอายุ";
            }
        } else {
            echo "รหัสผ่านไม่ตรงกัน";
        }
    } else {
        echo '
        <div id="resetPasswordPopup" class="popup">
            <div class="popup-content">
                <span class="close" onclick="closeResetPasswordPopup()">&times;</span>
                <h2>รีเปลี่ยนรหัสผ่าน</h2>
                <form id="resetPasswordForm" class="popup-form" action="reset_password.php?token=' . htmlspecialchars($_GET['token']) . '" method="POST">
                    <input type="รหัสผ่าน" name="new_password" placeholder="รหัสผ่านใหม่" required>
                    <input type="รหัสผ่าน" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
                    <button type="submit">เปลี่ยนรหัสผ่าน</button>
                </form>
            </div>
        </div>
        ';
    }
} else {
    echo "ไม่พบรหัสยืนยัน";
}
?>

<script>
    function openResetPasswordPopup() {
        document.getElementById('resetPasswordPopup').style.display = 'block';
    }

    function closeResetPasswordPopup() {
        document.getElementById('resetPasswordPopup').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target === document.getElementById('resetPasswordPopup')) {
            closeResetPasswordPopup();
        }
    }
</script>
