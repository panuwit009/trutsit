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
$historyType = isset($_POST['history_type']) ? $_POST['history_type'] : 'download';
$sortOrder = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'history';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$specific_date = isset($_POST['specific_date']) ? $_POST['specific_date'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    if (!empty($start_date) && !empty($end_date)) {
        $whereClause = "WHERE DATE(%s) BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    } elseif (!empty($specific_date)) {
        $whereClause = "WHERE DATE(%s) = :specific_date";
        $params[':specific_date'] = $specific_date;
    }
}

// Set the correct time column based on the history type
$timeColumn = $historyType === 'download' ? 'download_time' : 'view_time';

// Replace the placeholder with the correct time column
if (!empty($whereClause)) {
    $whereClause = sprintf($whereClause, $timeColumn);
}

// Fetch view/download history
if ($historyType === 'download') {
    $historySql = $sortOrder === 'most' ?
        "SELECT theses.thesis_name, COUNT(download_history.thesis_id) AS event_count
         FROM download_history
         JOIN theses ON download_history.thesis_id = theses.id
         $whereClause
         GROUP BY download_history.thesis_id
         ORDER BY event_count DESC" :
        "SELECT users.name, users.surname, theses.thesis_name, download_history.download_time AS event_time
         FROM download_history
         JOIN users ON download_history.user_id = users.id
         JOIN theses ON download_history.thesis_id = theses.id
         $whereClause
         ORDER BY download_history.download_time DESC";
} else {
    $historySql = $sortOrder === 'most' ?
        "SELECT theses.thesis_name, COUNT(view_history.thesis_id) AS event_count
         FROM view_history
         JOIN theses ON view_history.thesis_id = theses.id
         $whereClause
         GROUP BY view_history.thesis_id
         ORDER BY event_count DESC" :
        "SELECT users.name, users.surname, theses.thesis_name, view_history.view_time AS event_time
         FROM view_history
         JOIN users ON view_history.user_id = users.id
         JOIN theses ON view_history.thesis_id = theses.id
         $whereClause
         ORDER BY view_history.view_time DESC";
}

try {
    $stmt = $pdo->prepare($historySql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching history: " . $e->getMessage());
}

// Calculate total events
$totalEvents = count($history);

function formatThaiDate($datetime) {
    $months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน', '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    $date = new DateTime($datetime);
    $day = $date->format('d');
    $month = $months[$date->format('m')];
    $year = $date->format('Y') ; // Convert to Thai Buddhist year
    $time = $date->format('H:i');
    return "$day $month พ.ศ. $year เวลา $time นาฬิกา";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการค้นหาและดาวน์โหลด</title>
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
    flex-wrap: wrap; /* Allow wrapping for small screens */
    gap: 10px;
    margin-bottom: 20px;
}

form div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

form label {
    font-weight: bold;
}

form input[type="date"] {
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 150px;
}

form button {
    background-color: #28a745;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

form button[type="reset"] {
    background-color: #dc3545;
}

form button:hover {
    background-color: #218838;
}

form button[type="reset"]:hover {
    background-color: #c82333;
}
        .category-buttons,
        .sort-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .category-buttons button,
        .sort-buttons button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .category-buttons button:hover,
        .sort-buttons button:hover {
            background-color: #0056b3;
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
        .no-results {
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
            <button onclick="window.location.href='report2.php'">ประวัติการค้นหา</button>
            <button onclick="window.location.href='index.php'">หน้าหลัก</button>
        </div>

        <h2>ประวัติการดาวน์โหลด</h2>

        <form method="POST" action="report2-2.php" oninput="toggleDateInputs()">
            <input type="hidden" name="history_type" value="<?php echo htmlspecialchars($historyType); ?>">
            <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sortOrder); ?>">
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
                <button type="reset" onclick="window.location.href='report2-2.php'">ล้าง</button>
            </div>
        </form>


        <div class="sort-buttons">
            <form method="POST" action="report2-2.php">
                <input type="hidden" name="history_type" value="<?php echo htmlspecialchars($historyType); ?>">
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="specific_date" value="<?php echo htmlspecialchars($specific_date); ?>">
                <button type="submit" name="sort_order" value="history">ประวัติ</button>
                <button type="submit" name="sort_order" value="most">ความนิยม<?php echo $historyType === 'download' ? 'ในการดาวน์โหลด' : 'ในการค้นหา'; ?></button>
            </form>
        </div>

        <div class="count-info">
            <p>จำนวนกิจกรรม: <?php echo $totalEvents; ?> ครั้ง</p>
        </div>

        <?php if ($sortOrder === 'most'): ?>
    <h3>ปริญญานิพนธ์ที่ได้รับความนิยม<?php echo $historyType === 'download' ? 'ในการดาวน์โหลด' : 'ในการค้นหา'; ?></h3>
    <table>
        <tr>
            <th>ชื่อปริญญานิพนธ์</th>
            <th>จำนวน</th>
        </tr>
        <?php foreach ($history as $entry): ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['thesis_name']); ?></td>
                <td><?php echo htmlspecialchars($entry['event_count']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <h3>ประวัติ<?php echo $historyType === 'download' ? 'การดาวน์โหลด' : 'การค้นหา'; ?></h3>
    <table>
        <tr>
            <th>ชื่อ</th>
            <th>นามสกุล</th>
            <th>ชื่อปริญญานิพนธ์</th>
            <th>เวลา</th>
        </tr>
        <?php foreach ($history as $entry): ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                <td><?php echo htmlspecialchars($entry['surname']); ?></td>
                <td><?php echo htmlspecialchars($entry['thesis_name']); ?></td>
                <td><?php echo formatThaiDate($entry['event_time']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

    </div>
</body>
</html>