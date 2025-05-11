<?php
session_start();
require 'config.php'; // Ensure this includes the correct database connection

// Check if the user is logged in and has 'admin' privileges
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied");
}

// Handle the date filtering
$whereClause = '';
$params = [];

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$specific_date = isset($_POST['specific_date']) ? $_POST['specific_date'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    if (!empty($start_date) && !empty($end_date)) {
        $whereClause = "WHERE DATE(login_time) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    } elseif (!empty($specific_date)) {
        $whereClause = "WHERE DATE(login_time) = :specific_date";
        $params[':specific_date'] = $specific_date;
    }
}

// Fetch login history
$sql = "SELECT users.name, users.surname, login_history.login_time 
        FROM login_history 
        JOIN users ON login_history.user_id = users.id 
        $whereClause 
        ORDER BY login_history.login_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loginHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total login count
$totalLogins = count($loginHistory);

function formatThaiDate($datetime) {
    $months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน', '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    $date = new DateTime($datetime);
    $day = $date->format('d');
    $month = $months[$date->format('m')];
    $year = $date->format('Y') ;
    $time = $date->format('H:i');
    return "$day $month พ.ศ. $year เวลา $time นาฬิกา";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าสู่ระบบ</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .navigation button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .navigation button:hover {
            background-color: #0056b3;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }
        form {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        form label {
            margin-right: 10px;
            font-weight: bold;
        }
        form input[type="date"] {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 150px;
        }
        form button[type="submit"],
        form button[type="reset"] {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        form button[type="reset"] {
            background-color: #dc3545;
        }
        form button[type="submit"]:hover {
            background-color: #218838;
        }
        form button[type="reset"]:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
    <script>
        // JavaScript to disable specific date input when start or end date is selected and vice versa
        function toggleDateInputs() {
            var startDate = document.querySelector('input[name="start_date"]').value;
            var endDate = document.querySelector('input[name="end_date"]').value;
            var specificDate = document.querySelector('input[name="specific_date"]').value;

            if (startDate || endDate) {
                document.querySelector('input[name="specific_date"]').disabled = true;
            } else {
                document.querySelector('input[name="specific_date"]').disabled = false;
            }

            if (specificDate) {
                document.querySelector('input[name="start_date"]').disabled = true;
                document.querySelector('input[name="end_date"]').disabled = true;
            } else {
                document.querySelector('input[name="start_date"]').disabled = false;
                document.querySelector('input[name="end_date"]').disabled = false;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <button onclick="window.location.href='admin.php'">ย้อนกลับ</button>
            <button onclick="window.location.href='index.php'">หน้าหลัก</button>
        </div>

        <h2>ประวัติการเข้าสู่ระบบ</h2>

        <form method="POST" action="report1.php" oninput="toggleDateInputs()">
            <div>
                <label for="start_date">เลือกวันที่:</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div>
                <label for="end_date">ถึงวันที่:</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div>
                <label for="specific_date">เลือกวันที่แบบเฉพาะเจาะจง:</label>
                <input type="date" name="specific_date" value="<?php echo htmlspecialchars($specific_date); ?>">
            </div>
            <div>
                <button type="submit" name="filter">เลือกวันที่</button>
                <button type="reset" onclick="window.location.href='report1.php'">ล้าง</button>
            </div>
        </form>

        <div class="count-info">
            <p>จำนวนการเข้าสู่ระบบ: <?php echo $totalLogins; ?> ครั้ง</p>
        </div>

        <?php if ($totalLogins > 0): ?>
            <table>
                <tr>
                    <th>รายละเอียดการเข้าสู่ระบบ</th>
                </tr>
                <?php foreach ($loginHistory as $entry): ?>
                    <tr>
                        <td>คุณ <?php echo htmlspecialchars($entry['name']); ?> <?php echo htmlspecialchars($entry['surname']); ?> เข้าสู่ระบบเมื่อวันที่ <?php echo formatThaiDate($entry['login_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="no-data">ไม่มีข้อมูลการเข้าสู่ระบบในช่วงเวลานี้</div>
        <?php endif; ?>
    </div>
</body>
</html>