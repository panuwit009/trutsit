<?php
session_start();
require 'config.php'; // Assuming this is your database connection file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch the user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, name, surname, email, id_number, student_id FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input data
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $id_number = trim($_POST['id_number']);
    $student_id = trim($_POST['student_id']);
    
    // Handle password update if provided
    $password = !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_BCRYPT) : null;

    // Check if passwords match
    if (!empty($password) && (trim($_POST['password']) !== trim($_POST['confirm_password']))) {
        $error_message = "รหัสผ่านไม่ตรงกัน กรุณาลองอีกครั้ง.";
    } else {
        // Update the user information in the database
        if ($password) {
            $update_sql = "UPDATE users SET name = ?, surname = ?, email = ?, id_number = ?, student_id = ?, password = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            if ($stmt->execute([$name, $surname, $email, $id_number, $student_id, $password, $user_id])) {
                // Update session variables
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;

                // Fetch the updated user information
                $stmt = $pdo->prepare($sql); // Reuse the original SQL query
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC); // Update the $user variable with fresh data

                $success_message = "ข้อมูลของคุณได้รับการอัปเดตเรียบร้อยแล้ว!";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล กรุณาลองใหม่อีกครั้ง.";
            }
        } else {
            $update_sql = "UPDATE users SET name = ?, surname = ?, email = ?, id_number = ?, student_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            if ($stmt->execute([$name, $surname, $email, $id_number, $student_id, $user_id])) {
                // Update session variables
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;

                // Fetch the updated user information
                $stmt = $pdo->prepare($sql); // Reuse the original SQL query
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC); // Update the $user variable with fresh data

                $success_message = "ข้อมูลของคุณได้รับการอัปเดตเรียบร้อยแล้ว!";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล กรุณาลองใหม่อีกครั้ง.";
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผู้ใช้</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 10px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        button {
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            border-radius: 3px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .error {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
        .back-button {
            text-align: center;
            margin-top: 20px;
        }
        .back-button a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .back-button a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>ข้อมูลผู้ใช้</h1>
        
        <?php if (isset($success_message)): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="user_info.php" method="POST">
            <div>
                <label for="username">ชื่อผู้ใช้ (Username)</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            
            <div>
                <label for="name">ชื่อ (Name)</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div>
                <label for="surname">นามสกุล (Surname)</label>
                <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
            </div>

            <div>
                <label for="email">อีเมล (Email)</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div>
                <label for="id_number">รหัสบัตรประชาชน (ID Number)</label>
                <input type="text" id="id_number" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>" required>
            </div>

            <div>
                <label for="student_id">รหัสนักศึกษา (Student ID)</label>
                <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($user['student_id']); ?>">
            </div>

            <div>
                <label for="password">รหัสผ่านใหม่ (New Password)</label>
                <input type="password" id="password" name="password">
                <small>หากคุณต้องการเปลี่ยนรหัสผ่าน โปรดกรอกช่องนี้</small>
            </div>

            <div>
                <label for="confirm_password">ยืนยันรหัสผ่าน (Confirm Password)</label>
                <input type="password" id="confirm_password" name="confirm_password">
                <small>โปรดยืนยันรหัสผ่านใหม่ของคุณ</small>
            </div>

            <button type="submit">บันทึกการเปลี่ยนแปลง</button>
        </form>
        
        <div class="back-button">
            <a href="index.php">กลับไปที่หน้าหลัก</a>
        </div>
    </div>

</body>
</html>
