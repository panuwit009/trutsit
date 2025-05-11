<?php
include 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Retrieve the reset token and expiration time
    $sql = "SELECT * FROM users WHERE reset_token = :token";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token' => $token]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Manually compare the token expiration time with the current time
        $current_time = new DateTime();
        $expiration_time = new DateTime($user['reset_token_expires_at']);

        if ($current_time < $expiration_time) {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the user's password and clear the reset token
                    $sql = "UPDATE users SET password = :password, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':password' => $hashed_password,
                        ':id' => $user['id']
                    ]);

                    echo "เปลี่ยนรหัสผ่านสำเร็จคุณสามารถ <a href='index.php'>เข้าสู่ระบบได้ที่นี่</a>";
                } else {
                    echo "รหัสผ่านไม่ตรงกัน";
                }
            } else {
                echo '
                <div id="resetPasswordPopup" class="popup">
                    <div class="popup-content">
                        <h2>เปลี่ยนรหัสผ่าน</h2>
                        <form id="resetPasswordForm" class="popup-form" action="reset_password_verify.php?token=' . htmlspecialchars($_GET['token']) . '" method="POST">
                            <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" required>
                            <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่" required>
                            <button type="submit">เปลี่ยนรหัสผ่าน</button>
                        </form>
                    </div>
                </div>
                ';
            }
        } else {
            echo "รหัสยืนยันหมดอายุ";
        }
    } else {
        echo "ไม่พบรหัสยืนยันในฐานข้อมูล";
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
