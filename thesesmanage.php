<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied");
}
$records_per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$success_message = '';
$table = 'theses';
$faculty_options = [];
try {
    $stmt = $pdo->query("SELECT id, faculty FROM faculty");
    $faculty_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
$available_tags = [];
try {
    $stmt = $pdo->query("SELECT id, tag FROM tags");
    $available_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
$addthsfaculty_options = [];
try {
    $stmt = $pdo->query("SELECT faculty FROM faculty");
    $addthsfaculty_options = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
$excluded_columns = ['most_searched', 'most_downloaded', 'file_path'];
try {
    $columns_query = "DESCRIBE $table";
    $columns = $pdo->query($columns_query)->fetchAll(PDO::FETCH_ASSOC);
    $select_columns = array_filter($columns, function ($column) use ($excluded_columns) {
        return !in_array($column['Field'], $excluded_columns);
    });
    $select_columns_list = implode(', ', array_column($select_columns, 'Field'));
    $total_records = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $rows_query = "SELECT $select_columns_list, file_path FROM $table LIMIT $offset, $records_per_page";
    $rows = $pdo->query($rows_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $original_id = $_POST['original_id'];
    $update_values = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['table', 'update', 'original_id', 'page', 'id_to_delete', 'file_upload', 'tag_dropdown'])) {
            $update_values[] = "$key = :$key";
        }
    }
    if (empty($update_values)) {
        die('No fields to update');
    }
    $update_query = "UPDATE $table SET " . implode(', ', $update_values) . " WHERE id = :original_id";
    $stmt = $pdo->prepare($update_query);
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['table', 'update', 'original_id', 'page', 'id_to_delete', 'file_upload', 'tag_dropdown'])) {
            $stmt->bindValue(":$key", $value);
        }
    }
    $stmt->bindValue(":original_id", $original_id);
    try {
        if ($stmt->execute()) {
            $success_message = "แก้ไขสำเร็จ";
        } else {
            $success_message = "แก้ไขล้มเหลว";
        }
    } catch (PDOException $e) {
        $success_message = "Database error: " . $e->getMessage();
    }
    $_SESSION['success_message'] = 'แก้ไขสำเร็จ';
    $query_params = $_GET;
    $query_string = http_build_query($query_params);
    header("Location: thesesmanage.php?page=$page&" . $query_string . "&success_message=" . urlencode($success_message));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id_to_delete = $_POST['id_to_delete'];
    $id_to_delete = filter_var($id_to_delete, FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("DELETE FROM download_history WHERE thesis_id = ?");
        $stmt->execute([$id_to_delete]);
        $stmt = $pdo->prepare("DELETE FROM view_history WHERE thesis_id = ?");
        $stmt->execute([$id_to_delete]);
        $delete_query = "DELETE FROM $table WHERE id = :id_to_delete";
        $stmt = $pdo->prepare($delete_query);
        $stmt->bindValue(":id_to_delete", $id_to_delete, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $success_message = "ลบสำเร็จ";
        } else {
            $success_message = "ลบล้มเหลว";
        }
    } catch (PDOException $e) {
        $success_message = "Database error: " . $e->getMessage();
    }
    $_SESSION['success_message'] = 'ลบสำเร็จ';
    $query_params = $_GET;
    $query_string = http_build_query($query_params);
    header("Location: thesesmanage.php?page=$page&" . $query_string . "&success_message=" . urlencode($success_message));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
    $original_id = $_POST['original_id'];
    $file_tmp_path = $_FILES['file_upload']['tmp_name'];
    $file_name = basename($_FILES['file_upload']['name']);
    $file_size = $_FILES['file_upload']['size'];
    $file_type = $_FILES['file_upload']['type']; // MIME type of the uploaded file

    // Check file extension
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    
    // Allowed file types (PDF)
    $allowed_extensions = ['pdf'];
    $allowed_mime_types = ['application/pdf'];

    // Validate file extension
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        $_SESSION['error_message'] = 'Error: Only PDF files are allowed!';
        header("Location: thesesmanage.php?page=$page&" . http_build_query($_GET));
        exit();
    }

    // Validate MIME type
    if (!in_array($file_type, $allowed_mime_types)) {
        $_SESSION['error_message'] = 'Error: The file must be a valid PDF (MIME type check).';
        header("Location: thesesmanage.php?page=$page&" . http_build_query($_GET));
        exit();
    }

    // Proceed with the file upload if validation is successful
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique file name
    $new_file_path = $upload_dir . uniqid() . '-' . $file_name;

    // Move the file to the server
    if (move_uploaded_file($file_tmp_path, $new_file_path)) {
        // Update the database with the file path
        $stmt = $pdo->prepare("UPDATE $table SET file_path = :file_path WHERE id = :original_id");
        $stmt->bindValue(':file_path', $new_file_path);
        $stmt->bindValue(':original_id', $original_id);
        $stmt->execute();

        $_SESSION['success_message'] = 'File uploaded successfully!';
    } else {
        $_SESSION['error_message'] = 'File upload failed!';
    }

    // Redirect back to the page with query parameters
    $query_params = $_GET;
    $query_string = http_build_query($query_params);
    header("Location: thesesmanage.php?page=$page&" . $query_string);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $original_id = $_POST['original_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM $table WHERE id = :original_id");
    $stmt->bindValue(':original_id', $original_id);
    $stmt->execute();
    $file_path = $stmt->fetchColumn();
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }
    $stmt = $pdo->prepare("UPDATE $table SET file_path = NULL WHERE id = :original_id");
    $stmt->bindValue(':original_id', $original_id);
    $stmt->execute();
    $_SESSION['success_message'] = 'File deleted successfully!';
    $query_params = $_GET;
    $query_string = http_build_query($query_params);
    header("Location: thesesmanage.php?page=$page&" . $query_string);
    exit();
}
try {
    $tag_query = "SELECT t.tag 
                  FROM thesis_tags tt 
                  JOIN tags t ON tt.tag_id = t.id 
                  WHERE tt.thesis_id = :thesis_id";
    
    $tag_stmt = $pdo->prepare($tag_query);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
$search_conditions = [];
$search_params = [];
if (!empty($_GET['thesis_name'])) {
    $search_conditions[] = "thesis_name LIKE :thesis_name";
    $search_params[':thesis_name'] = '%' . $_GET['thesis_name'] . '%';
}
if (!empty($_GET['author_name'])) {
    $search_conditions[] = "author_name LIKE :author_name";
    $search_params[':author_name'] = '%' . $_GET['author_name'] . '%';
}
if (!empty($_GET['advisor'])) {
    $search_conditions[] = "advisor LIKE :advisor";
    $search_params[':advisor'] = '%' . $_GET['advisor'] . '%';
}
if (!empty($_GET['year'])) {
    $search_conditions[] = "year = :year";
    $search_params[':year'] = $_GET['year'];
}
if (!empty($_GET['faculty'])) {
    $search_conditions[] = "faculty = :faculty";
    $search_params[':faculty'] = $_GET['faculty'];
}
$search_query = "SELECT $select_columns_list, file_path FROM $table";
if (!empty($search_conditions)) {
    $search_query .= " WHERE " . implode(' AND ', $search_conditions);
}
$search_query .= " LIMIT :offset, :records_per_page";
$stmt = $pdo->prepare($search_query);
foreach ($search_params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
try {
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
$query_params = $_GET;
unset($query_params['page']); // Optional: Remove page if you don't want it in the query string
$query_string = http_build_query($query_params);
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการฐานข้อมูล</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f9;
    }

    .navbar {
        position: fixed; /* Make the navbar fixed at the top */
        top: 0; /* Align it at the top of the page */
        width: 100%; /* Make it full-width */
        display: flex;
        justify-content: center;
        background-color: #333; /* Set the background color */
        padding: 10px 0;
        z-index: 1000; /* Ensure it stays on top of other content */
    }

    .navbar a {
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        margin: 0 10px;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .navbar a:hover {
        background-color: #575757;
    }

    .container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 60px; /* Add padding to account for the fixed navbar */
        width: 100%; /* Ensure the container takes up full width */
        box-sizing: border-box; /* Include padding in the width calculation */
    }

    .main-panel {
        width: 100%;
        max-width: 1800px;
        background: white;
        padding: 20px;
        margin-top: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        overflow-x: auto; /* Enable horizontal scrolling */
        overflow-y: hidden; /* Prevent vertical scrolling */
        box-sizing: border-box;
    }

    h2 {
        font-size: 1.5em;
        margin-bottom: 20px;
    }

    table {
    width: 100%;
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 10px;
    text-align: center; /* Center align text */
    word-wrap: break-word; /* Allow breaking of long words */
    overflow-wrap: break-word; /* Fallback for older browsers */
    max-width: 150px; /* Set a maximum width for the columns */
    white-space: pre-wrap; /* Allows text to wrap and respects whitespace */
    overflow: hidden; /* Prevents overflow of text */
}

th {
    background-color: #f2f2f2;
}


    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination a {
        margin: 0 5px;
        padding: 5px 10px;
        text-decoration: none;
        border: 1px solid #ddd;
        color: #333;
        border-radius: 5px;
    }

    .pagination a:hover {
        background-color: #ddd;
    }

    .pagination a.active {
        background-color: #333;
        color: white;
    }

    /* Modal styles */
    .modal {
        display: none; 
        position: fixed;
        z-index: 1;
        padding-top: 100px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0,0,0);
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .admin-actions button {
        padding: 10px 20px;
        margin: 10px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
    }

    .admin-actions button:hover {
        background-color: #45a049;
    }
.thesis-name {
    overflow: auto; /* Adds a scrollbar if necessary */
    max-width: 100%; /* Ensures it does not exceed its container */
    min-height: 100px; /* Taller for thesis names */
    max-height: 200px; /* Optionally sets a maximum height */
    border: 1px solid #ddd; /* Matches table styling */
    border-radius: 3px; /* Rounded corners */
    padding: 5px; /* Inner padding */
    font-size: 14px; /* Font size */
}

.author-name {
    overflow: auto; /* Adds a scrollbar if necessary */
    max-width: 100%; /* Ensures it does not exceed its container */
    min-height: 20px; /* Shorter for author names */
    max-height: 150px; /* Optionally sets a maximum height */
    border: 1px solid #ddd; /* Matches table styling */
    border-radius: 3px; /* Rounded corners */
    padding: 5px; /* Inner padding */
    font-size: 14px; /* Font size */
}
.advisor {
    overflow: auto; /* Adds a scrollbar if necessary */
    max-width: 100%; /* Ensures it does not exceed its container */
    min-height: 20px; /* Shorter for author names */
    max-height: 150px; /* Optionally sets a maximum height */
    border: 1px solid #ddd; /* Matches table styling */
    border-radius: 3px; /* Rounded corners */
    padding: 5px; /* Inner padding */
    font-size: 14px; /* Font size */
}
</style>

</head>
<body>
    <div class="container">
        <div class="navbar">
            <a href="index.php">หน้าหลัก</a>
            <a href="thesesmanage.php">จัดการข้อมูลปริญญานิพนธ์</a>
            <a href="usermanage.php">จัดการข้อมูลผู้ใช้</a>
            <a href="admin.php?table=tags">จัดการรูปแบบปริญญานิพนธ์</a>
            <a href="admin.php?table=faculty">จัดการรายชื่อสาขา</a>
            <a href="#" id="reportBtn">รายงาน</a>
        </div>


        <div class="admin-actions">
    <button id="addThesisBtn">เพิ่มปริญญานิพนธ์</button>
        </div>

<!-- Report Modal -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>กรุณาเลือกรายงาน</h2>
        <button onclick="window.location.href='report1.php'">รายงานการเข้าสู่ระบบ</button>
        <button onclick="window.location.href='report2.php'">รายงานการค้นหาและดาวน์โหลด</button>
        <button onclick="window.location.href='report3.php'">รายงานสรุปจำนวนปริญญานิพนธ์</button>
    </div>
</div>

<div id="addThesisModal" class="modal"> 
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>เพิ่มปริญญานิพนธ์</h2>
        <form id="addThesisForm">
            <label for="thesis_name">ชื่อปริญญานิพนธ์:</label>
            <input type="text" id="thesis_name" name="thesis_name" required><br>

            <label for="author_name">ชื่อผู้จัดทำ:</label>
            <input type="text" id="author_name" name="author_name" required><br>

            <label for="year">ปี:</label>
            <input type="number" id="year" name="year" required><br>

            <label for="faculty">ชื่อสาขา:</label>
            <select id="faculty" name="faculty" required>
                <?php foreach ($addthsfaculty_options as $faculty): ?>
                    <option value="<?php echo htmlspecialchars($faculty); ?>"><?php echo htmlspecialchars($faculty); ?></option>
                <?php endforeach; ?>
            </select><br>

            <label for="advisor">ที่ปรึกษา:</label>
            <input type="text" id="advisor" name="advisor" required><br>

            <!-- These hidden inputs will pass data such as pagination -->
            <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
            <input type="hidden" name="most_searched" value="0">
            <input type="hidden" name="most_downloaded" value="0">

            <input type="submit" value="เพิ่มปริญญานิพนธ์">
        </form>
    </div>
</div>


        
<div class="main-panel"> 
    <?php if (isset($rows)): ?>
        <h2>ข้อมูลจากตารางปริญญานิพนธ์</h2>
        <form method="GET" action="">
    <input type="text" name="thesis_name" placeholder="ค้นหาด้วยชื่อปริญญานิพนธ์" value="<?php echo isset($_GET['thesis_name']) ? htmlspecialchars($_GET['thesis_name']) : ''; ?>">
    <input type="text" name="author_name" placeholder="ค้นหาด้วยชื่อผู้จัดทำ" value="<?php echo isset($_GET['author_name']) ? htmlspecialchars($_GET['author_name']) : ''; ?>">
    <input type="text" name="advisor" placeholder="ค้นหาด้วยชื่อที่ปรึกษา" value="<?php echo isset($_GET['advisor']) ? htmlspecialchars($_GET['advisor']) : ''; ?>">
    <input type="text" name="year" placeholder="ค้นหาด้วยปี" value="<?php echo isset($_GET['year']) ? htmlspecialchars($_GET['year']) : ''; ?>">
    <select name="faculty">
        <option value="">เลือกสาขา</option>
        <?php foreach ($faculty_options as $faculty): ?>
            <option value="<?php echo htmlspecialchars($faculty['faculty']); ?>" <?php echo (isset($_GET['faculty']) && $_GET['faculty'] == $faculty['faculty']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($faculty['faculty']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">ค้นหา</button>
</form>

        <table>
    <thead>
        <tr>
            <th>ชื่อปริญญานิพนธ์</th>
            <th>ชื่อผู้จัดทำ</th>
            <th>ที่ปรึกษา</th>
            <th>ปี</th>
            <th>สาขา</th>
            <th>ดาวน์โหลด</th>
            <th>ไฟล์</th>
            <th>รูปแบบ</th>
            <th>แก้ไข / ลบ</th>
        </tr>
    </thead>
    <tbody>
        
        <?php foreach ($rows as $row): ?>
            <tr>
                <form method="POST" action="" enctype="multipart/form-data">
                <td>
    <textarea class="thesis-name" name="thesis_name" rows="3" style="width: 100%; box-sizing: border-box;"><?php echo htmlspecialchars($row['thesis_name']); ?></textarea>
</td>
<td>
    <textarea class="author-name" name="author_name" rows="2" style="width: 100%; box-sizing: border-box;"><?php echo htmlspecialchars($row['author_name']); ?></textarea>
</td>
<td>
    <textarea class="advisor" name="advisor" rows="2" style="width: 100%; box-sizing: border-box;"><?php echo htmlspecialchars($row['advisor']); ?></textarea>
</td>
                    <td>
                        <input type="text" name="year" value="<?php echo htmlspecialchars($row['year']); ?>" style="width: 100%; box-sizing: border-box;">
                    </td>
                    <td>
                        <select name="faculty" style="width: 100%; box-sizing: border-box;">
                            <?php foreach ($faculty_options as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty['faculty']); ?>"
                                    <?php echo $faculty['faculty'] === $row['faculty'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['faculty']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="download_permission" style="width: 100%; box-sizing: border-box;">
                            <option value="Yes" <?php echo trim(strtolower($row['download_permission'])) === 'yes' ? 'selected' : ''; ?>>ใช่</option>
                            <option value="No" <?php echo trim(strtolower($row['download_permission'])) === 'no' ? 'selected' : ''; ?>>ไม่</option>
                        </select>
                    </td>
                    <td>
                        <?php if (!empty($row['file_path'])): ?>
                            <?php 
                            $file_name = basename($row['file_path']);
                            ?>
                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($file_name); ?></a>
                            <br>
                            <button type="submit" name="delete_file" onclick="return confirm('Are you sure you want to delete this file?');">ลบไฟล์</button>
                        <?php else: ?>
                            <input type="file" name="file_upload" accept=".pdf,application/pdf">
                            <button type="submit" name="upload_file">อัพโหลดไฟล์</button>
                            <p style="font-size: 12px; color: #666;">***รองรับไฟล์ PDF เท่านั้น***</p>
                        <?php endif; ?>
                    </td>
                    <td>
    <?php
    // Fetch the currently associated tags for this thesis
    $tag_stmt = $pdo->prepare("SELECT t.id, t.tag FROM tags t INNER JOIN thesis_tags tt ON t.id = tt.tag_id WHERE tt.thesis_id = :thesis_id");
    $tag_stmt->bindValue(':thesis_id', $row['id']);
    $tag_stmt->execute();
    $current_tags = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="tags-list">
        <?php foreach ($current_tags as $tag): ?>
            <span class="tag-item">
                <?php echo htmlspecialchars($tag['tag']); ?>
                <button type="button" class="remove-tag" data-thesis-id="<?php echo $row['id']; ?>" data-tag-id="<?php echo $tag['id']; ?>">ลบ</button>
            </span>
        <?php endforeach; ?>
    </div>

    <!-- Dropdown to select new tags -->
    <select name="tag_dropdown" class="tag-dropdown">
        <option value="">เลือกรูปแบบ</option>
        <?php foreach ($available_tags as $available_tag): ?>
            <option value="<?php echo $available_tag['id']; ?>">
                <?php echo htmlspecialchars($available_tag['tag']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="add-tag" data-thesis-id="<?php echo $row['id']; ?>">เพิ่ม</button>
</td>

                    
                    <td>
                        <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                        <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <input type="hidden" name="id_to_delete" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <input type="submit" name="update" value="แก้ไข" onclick="return confirm('คุณแน่ใจใช่ไหมที่จะแก้ไขข้อมูลนี้');">
                        <input type="submit" name="delete" value="ลบ" onclick="return confirm('คุณแน่ใจใช่ไหมที่จะลบข้อมูลนี้');">
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<div class="pagination">
    <?php
    $total_pages = ceil($total_records / $records_per_page);
    $range = 3; // Number of pages to show before and after the current page

    // Collect filter parameters to append them to pagination links
    $query_params = $_GET;
    unset($query_params['page']); // Remove page from the query parameters
    $query_string = http_build_query($query_params); // Rebuild the query string without 'page'

    // Show the first page and a "..." if the current page is far from the first page
    if ($page > $range + 1) {
        echo '<a href="?page=1&' . $query_string . '">1</a>';
        if ($page > $range + 2) {
            echo '<span>...</span>';
        }
    }

    // Display pages around the current page
    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
        echo '<a href="?page=' . $i . '&' . $query_string . '" class="' . ($page == $i ? 'active' : '') . '">' . $i . '</a>';
    }

    // Show the last page and a "..." if the current page is far from the last page
    if ($page < $total_pages - $range) {
        if ($page < $total_pages - $range - 1) {
            echo '<span>...</span>';
        }
        echo '<a href="?page=' . $total_pages . '&' . $query_string . '">' . $total_pages . '</a>';
    }
    ?>
</div>


    <?php else: ?>
        <p>เลือกเมนูที่ต้องการจากด้านบน</p>
    <?php endif; ?>
</div>

    <!-- Success Message Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="successMessage">แก้ไขสำเร็จ</p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.add-tag').forEach(button => {
        button.addEventListener('click', function() {
            const thesisId = this.getAttribute('data-thesis-id');
            const tagDropdown = this.closest('tr').querySelector('select[name="tag_dropdown"]');
            const selectedTagId = tagDropdown.value; // Get the selected tag's ID
            const tagTextarea = this.closest('tr').querySelector('textarea[name="tags"]');

            if (selectedTagId) {
                fetch('add_tagR.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        thesis_id: thesisId,
                        tag_id: selectedTagId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('เพิ่มรูปแบบสำเร็จ');
                        // Append the new tag to the textarea
                        const newTag = tagDropdown.options[tagDropdown.selectedIndex].text;
                        tagTextarea.value += tagTextarea.value ? ', ' + newTag : newTag;
                    } else {
                        alert('เพิ่มรูปแบบล้มเหลว: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                alert('กรุณาเลือกรูปแบบ');
            }
        });
    });
    document.querySelectorAll('.remove-tag').forEach(button => {
        button.addEventListener('click', function() {
            const thesisId = this.getAttribute('data-thesis-id');
            const tagId = this.getAttribute('data-tag-id');

            if (confirm('คุณแน่ใจใช่ไหมที่จะลบรูปแบบนี้')) {
                fetch('remove_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        thesis_id: thesisId,
                        tag_id: tagId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ลบรูปแบบสำเร็จ');
                        // Remove the tag from the UI
                        this.parentElement.remove();
                    } else {
                        alert('ลบรูปแบบล้มเหลว: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    document.getElementById('addThesisForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the default form submission

    // Collect form data
    const formData = {
        thesis_name: document.getElementById('thesis_name').value,
        author_name: document.getElementById('author_name').value,
        year: document.getElementById('year').value,
        faculty: document.getElementById('faculty').value,
        advisor: document.getElementById('advisor').value,
        page: document.querySelector('[name="page"]').value,
        most_searched: document.querySelector('[name="most_searched"]').value,
        most_downloaded: document.querySelector('[name="most_downloaded"]').value
    };

    // Send the data as JSON via fetch API
        fetch('add_thesis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message); // Show success message
                window.location.href = data.redirect_url; // Redirect to the success page
            } else {
                alert('Error: ' + data.message); // Show error message
            }
        })
        .catch(error => {
            alert('Error: ' + error.message); // Handle any errors
        });
    });
        // Function to display the modal with a message
        function showModal(message) {
            const modal = document.getElementById('successModal');
            if (modal) {
                const modalMessage = document.getElementById('successMessage');
                if (modalMessage) {
                    modalMessage.textContent = message;
                }
                modal.style.display = 'block';
            }
        }

        // Function to open the modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        }

        // Function to close the modal
        function closeModal(modal) {
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Get buttons that open the modals
        var addThesisBtn = document.getElementById('addThesisBtn');
        var addUserBtn = document.getElementById('addUserBtn');
        var addFacultyBtn = document.getElementById('addFacultyBtn');
        var addTagBtn = document.getElementById('addTagBtn');
        var reportBtn = document.getElementById('reportBtn');

        // Get the modals
        var addThesisModal = document.getElementById('addThesisModal');
        var addUserModal = document.getElementById('addUserModal');
        var addFacultyModal = document.getElementById('addFacultyModal');
        var addTagModal = document.getElementById('addTagModal');
        var reportModal = document.getElementById('reportModal');

        // Event listener for buttons to open the respective modals
        if (addThesisBtn) addThesisBtn.onclick = function() { openModal('addThesisModal'); }
        if (addUserBtn) addUserBtn.onclick = function() { openModal('addUserModal'); }
        if (addFacultyBtn) addFacultyBtn.onclick = function() { openModal('addFacultyModal'); }
        if (addTagBtn) addTagBtn.onclick = function() { openModal('addTagModal'); }
        if (reportBtn) reportBtn.onclick = function() { openModal('reportModal'); }

        // Event listener for closing modals
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            var closeBtn = modal.querySelector('.close');
            if (closeBtn) {
                closeBtn.onclick = function() { closeModal(modal); }
            }
        });

        // Event listener for clicking outside of modal to close it
        window.onclick = function(event) {
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a success message in the session
            <?php if (isset($_SESSION['success_message'])): ?>
                showModal('<?php echo $_SESSION['success_message']; ?>');
                <?php unset($_SESSION['success_message']); // Clear the message after showing ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
