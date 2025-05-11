<?php
require_once 'config.php';

// Get search parameters
$thesis_name = isset($_GET['thesis_name']) ? $_GET['thesis_name'] : '';
$author_name = isset($_GET['author_name']) ? $_GET['author_name'] : '';
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

// Set default sorting column and order
$sort_column = 'year';
$sort_order = 'DESC';

if (isset($_GET['sort-by'])) {
    switch ($_GET['sort-by']) {
        case 'advisor_name':
            $sort_column = 'advisor';
            $sort_order = 'ASC';
            break;
        case 'year':
            $sort_column = 'year';
            $sort_order = 'DESC';
            break;
    }
}

// Build query with search filters
$query = "SELECT * FROM theses WHERE 1=1";

if ($thesis_name) {
    $query .= " AND thesis_name LIKE :thesis_name";
}
if ($author_name) {
    $query .= " AND author_name LIKE :author_name";
}
if ($faculty) {
    $query .= " AND faculty = :faculty";
}

$query .= " ORDER BY $sort_column $sort_order";

$stmt = $pdo->prepare($query);

if ($thesis_name) {
    $stmt->bindValue(':thesis_name', "%$thesis_name%");
}
if ($author_name) {
    $stmt->bindValue(':author_name', "%$author_name%");
}
if ($faculty) {
    $stmt->bindValue(':faculty', $faculty);
}

$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query to fetch all unique faculties for the dropdown menu
$faculties_query = "SELECT DISTINCT faculty FROM theses";
$faculties_stmt = $pdo->query($faculties_query);
$faculties = $faculties_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Search System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .search-form input[type="text"], 
        .search-form select {
            width: 200px; /* Same width for input boxes and dropdown */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-form button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background-color: #007BFF;
            color: #fff;
            cursor: pointer;
        }
        .search-form button:hover {
            background-color: #0056b3;
        }
        .submit-container {
            margin-top: 10px; /* Space before submit button */
        }
        .sort-container {
            margin-top: 20px; /* Space above sort dropdown */
        }
    </style>
</head>
<body>
    <div class="app-name">
        T-Sit (Thesis Searching System for IT Faculty) - Search Results
    </div>
    <div class="main">
        <form class="search-form" action="search.php" method="GET">
            <input type="text" name="thesis_name" placeholder="ชื่อวิทยานิพนธ์" value="<?php echo htmlspecialchars($thesis_name); ?>">
            <input type="text" name="author_name" placeholder="ชื่อผู้จัดทำ" value="<?php echo htmlspecialchars($author_name); ?>">
            <select name="faculty">
                <option value="">เลือกสาขา</option>
                <?php foreach ($faculties as $fac): ?>
                    <option value="<?php echo htmlspecialchars($fac); ?>" <?php if ($faculty == $fac) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($fac); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="sort-by" value="<?php echo isset($_GET['sort-by']) ? htmlspecialchars($_GET['sort-by']) : 'year'; ?>">
            <div class="submit-container">
                <button type="submit">ค้นหา</button>
            </div>
        </form>

        <table class="thesis-table">
            <thead>
                <tr>
                    <th>ชื่อวิทยานิพนธ์</th>
                    <th>ชื่อผู้จัดทำ</th>
                    <th>ปีการศึกษา</th>
                    <th>สาขา</th>
                    <th>อาจารย์ที่ปรึกษา</th>
                    <th>ไฟล์</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($theses as $thesis): ?>
                <tr>
                    <td><?php echo htmlspecialchars($thesis['thesis_name']); ?></td>
                    <td><?php echo htmlspecialchars($thesis['author_name']); ?></td>
                    <td><?php echo htmlspecialchars($thesis['year']); ?></td>
                    <td><?php echo htmlspecialchars($thesis['faculty']); ?></td>
                    <td><?php echo htmlspecialchars($thesis['advisor']); ?></td>
                    <td>
                        <a href="view-thesis.php?id=<?php echo $thesis['id']; ?>" target="_blank">ดูไฟล์</a>
                        <a href="download-thesis.php?id=<?php echo $thesis['id']; ?>">ดาวน์โหลด</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
