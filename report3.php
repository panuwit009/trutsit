<?php
session_start();
// Database connection
include 'config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied");
}

// Fetch unique years and faculties for the dropdown menus
$years = $pdo->query("SELECT DISTINCT year FROM theses ORDER BY year DESC")->fetchAll(PDO::FETCH_ASSOC);
$faculties = $pdo->query("SELECT DISTINCT faculty FROM theses ORDER BY faculty ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get the total number of theses (unfiltered)
$total_theses_unfiltered = $pdo->query("SELECT COUNT(*) AS total_theses FROM theses")->fetchColumn();

// Initialize filters
$selected_start_year = $_POST['start_year'] ?? null;
$selected_end_year = $_POST['end_year'] ?? null;
$selected_specific_year = $_POST['specific_year'] ?? null;
$selected_faculty = $_POST['faculty'] ?? null;

// Build the SQL query with the filters
$sql = "SELECT thesis_name FROM theses WHERE 1=1";

// Use specific year if provided
if ($selected_specific_year) {
    $sql .= " AND year = :specific_year";
} else {
    // Use year range if provided
    if ($selected_start_year && $selected_end_year) {
        $sql .= " AND year BETWEEN :start_year AND :end_year";
    }
}

// Use faculty filter if provided
if ($selected_faculty) {
    $sql .= " AND faculty = :faculty";
}

// Prepare the query
$stmt = $pdo->prepare($sql);

// Bind parameters if applicable
if ($selected_specific_year) {
    $stmt->bindParam(':specific_year', $selected_specific_year);
} else {
    if ($selected_start_year && $selected_end_year) {
        $stmt->bindParam(':start_year', $selected_start_year);
        $stmt->bindParam(':end_year', $selected_end_year);
    }
}
if ($selected_faculty) {
    $stmt->bindParam(':faculty', $selected_faculty);
}

// Execute the query
$stmt->execute();

// Fetch the thesis names
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the total number of filtered theses (this is for showing total count of filtered results)
$sql_count = "SELECT COUNT(*) AS total_theses FROM theses WHERE 1=1";

if ($selected_specific_year) {
    $sql_count .= " AND year = :specific_year";
} else {
    if ($selected_start_year && $selected_end_year) {
        $sql_count .= " AND year BETWEEN :start_year AND :end_year";
    }
}

if ($selected_faculty) {
    $sql_count .= " AND faculty = :faculty";
}

$stmt_count = $pdo->prepare($sql_count);

// Bind parameters for the count query if applicable
if ($selected_specific_year) {
    $stmt_count->bindParam(':specific_year', $selected_specific_year);
} else {
    if ($selected_start_year && $selected_end_year) {
        $stmt_count->bindParam(':start_year', $selected_start_year);
        $stmt_count->bindParam(':end_year', $selected_end_year);
    }
}
if ($selected_faculty) {
    $stmt_count->bindParam(':faculty', $selected_faculty);
}

// Execute the count query
$stmt_count->execute();
$total_theses_filtered = $stmt_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปจำนวนปริญญานิพนธ์</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2, h3 {
            text-align: center;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        label, select, button {
            font-size: 16px;
            margin-bottom: 10px;
        }
        select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 30%;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .nav-buttons a {
            text-decoration: none;
            padding: 10px 20px;
            color: white;
            background-color: #28a745;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
        }
        .nav-buttons a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-buttons">
            <a href="admin.php">ย้อนกลับ</a>
            <a href="index.php">หน้าหลัก</a>
        </div>
        
        <h2>จำนวนปริญญานิพนธ์ทั้งหมด: <?= $total_theses_unfiltered ?> เล่ม</h2>
        
        <form method="POST">
            <label for="start_year">ปีการศึกษาเริ่มต้น:</label>
            <select name="start_year" id="start_year">
                <option value="">--เลือกปีการศึกษาเริ่มต้น--</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year['year'] ?>" <?= ($selected_start_year == $year['year']) ? 'selected' : '' ?>>
                        <?= $year['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="end_year">ปีการศึกษาสิ้นสุด:</label>
            <select name="end_year" id="end_year">
                <option value="">--เลือกปีการศึกษาสิ้นสุด--</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year['year'] ?>" <?= ($selected_end_year == $year['year']) ? 'selected' : '' ?>>
                        <?= $year['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="specific_year">ปีการศึกษาเฉพาะ:</label>
            <select name="specific_year" id="specific_year">
                <option value="">--เลือกปีการศึกษาเฉพาะ--</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year['year'] ?>" <?= ($selected_specific_year == $year['year']) ? 'selected' : '' ?>>
                        <?= $year['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="faculty">สาขา:</label>
            <select name="faculty" id="faculty">
                <option value="">--เลือกสาขา--</option>
                <?php foreach ($faculties as $faculty): ?>
                    <option value="<?= $faculty['faculty'] ?>" <?= ($selected_faculty == $faculty['faculty']) ? 'selected' : '' ?>>
                        <?= $faculty['faculty'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">ค้นหา</button>
        </form>
        
        <?php if ($selected_start_year || $selected_end_year || $selected_specific_year || $selected_faculty): ?>
            <h3>
                จำนวนปริญญานิพนธ์
                <?php if ($selected_faculty): ?>
                    ของสาขา <?= $selected_faculty ?>
                <?php endif; ?>
                <?php if ($selected_specific_year): ?>
                    ในปีการศึกษา <?= $selected_specific_year ?>
                <?php elseif ($selected_start_year && $selected_end_year): ?>
                    ในช่วงปีการศึกษา <?= $selected_start_year ?> ถึง <?= $selected_end_year ?>
                <?php endif; ?>
                ทั้งหมด: <?= $total_theses_filtered ?> เล่ม
            </h3>
        <?php endif; ?>

        <!-- Display thesis names with numbers -->
        <?php if ($theses): ?>
            <h3>รายชื่อปริญญานิพนธ์ที่ตรงกับเงื่อนไข:</h3>
            <ul>
                <?php 
                $counter = 1; // Initialize counter variable
                foreach ($theses as $thesis): ?>
                    <li><?= $counter++ ?>. <?= htmlspecialchars($thesis['thesis_name']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
