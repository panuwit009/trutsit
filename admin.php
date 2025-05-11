<?php
session_start();
require_once 'config.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied");
}

$records_per_page = 20; // Number of records to display per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$success_message = '';

// Fetch user types
//$user_types = [];
//try {
//  $stmt = $pdo->query("SELECT * FROM user_types");
//    $user_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
//} catch (PDOException $e) {
//    echo 'Database error: ' . $e->getMessage();
//}

$faculty_options = [];
try {
    $stmt = $pdo->query("SELECT id, faculty FROM faculty");
    $faculty_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}

// Handle table actions
$table = isset($_GET['table']) ? $_GET['table'] : null; // Initialize table variable
$select_columns_list = '*'; // Default to select all columns

if ($table) {
    // Handle exclusions based on the table
    $excluded_columns = [];
    if ($table == 'users') {
        $excluded_columns = ['password', 'verification_token', 'token_expires_at', 'reset_token', 'reset_token_expires_at'];
    } elseif ($table == 'theses') {
        $excluded_columns = ['most_searched', 'most_downloaded', 'file_path'];
    }

    // Fetch columns and data from the selected table
    try {
        $columns_query = "DESCRIBE $table";
        $columns = $pdo->query($columns_query)->fetchAll(PDO::FETCH_ASSOC);

        // Filter out excluded columns
        $select_columns = array_filter($columns, function ($column) use ($excluded_columns) {
            return !in_array($column['Field'], $excluded_columns);
        });

        $select_columns_list = implode(', ', array_column($select_columns, 'Field'));
        $total_records = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();

        // Initialize query parts for search conditions
        $search_conditions = [];
        $search_params = [];

        // Handle search for tags
        if ($table == 'tags' && !empty($_GET['search_tag'])) {
            $search_conditions[] = "tag LIKE :tag";
            $search_params[':tag'] = '%' . $_GET['search_tag'] . '%';
        }

        // Handle search for faculty
        if ($table == 'faculty' && !empty($_GET['search_faculty'])) {
            $search_conditions[] = "faculty LIKE :faculty";
            $search_params[':faculty'] = '%' . $_GET['search_faculty'] . '%';
        }

// Construct the final SQL query
$rows_query = "SELECT $select_columns_list FROM $table";
if (!empty($search_conditions)) {
    $rows_query .= " WHERE " . implode(' AND ', $search_conditions);
}

// Add ORDER BY to sort by id
$rows_query .= " ORDER BY id LIMIT :offset, :records_per_page";

        // Prepare and execute the query
        $stmt = $pdo->prepare($rows_query);
        
        // Bind search parameters
        foreach ($search_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination values
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);

        // Execute the query
        try {
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Database error: ' . $e->getMessage();
        }
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    }
}

// Handle record updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $table = $_POST['table'];
    $original_id = $_POST['original_id'];
    $update_values = [];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['table', 'update', 'original_id', 'page', 'id_to_delete'])) {
            $update_values[] = "$key = :$key";
        }
    }

    if (empty($update_values)) {
        die('No fields to update');
    }

    $update_query = "UPDATE $table SET " . implode(', ', $update_values) . " WHERE id = :original_id";
    $stmt = $pdo->prepare($update_query);

    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['table', 'update', 'original_id', 'page', 'id_to_delete'])) {
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

    $_SESSION['success_message'] = 'แก้ไขข้อมูลสำเร็จ';
    header("Location: admin.php?table=$table&page=$page&success_message=" . urlencode($success_message));
    exit();
}

// Handle record deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $table = $_POST['table'];
    $id_to_delete = $_POST['id_to_delete'];

    // Sanitize the id_to_delete
    $id_to_delete = filter_var($id_to_delete, FILTER_SANITIZE_NUMBER_INT);

    try {
        // Prepare and execute the deletion query
        $delete_query = "DELETE FROM $table WHERE id = :id_to_delete";
        $stmt = $pdo->prepare($delete_query);
        $stmt->bindValue(":id_to_delete", $id_to_delete, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $success_message = "การลบสำเร็จ";
        } else {
            $success_message = "การลบล้มเหลว";
        }
    } catch (PDOException $e) {
        $success_message = "Database error: " . $e->getMessage();
    }

    $_SESSION['success_message'] = 'การลบสำเร็จ';
    header("Location: admin.php?table=$table&page=$page&success_message=" . urlencode($success_message));
    exit();
}


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
    text-align: center;
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



    </style>
</head>
<body>
    <div class="container">
        <div class="navbar">
            <a href="index.php">หน้าหลัก</a>
            <a href="thesesmanage.php">จัดการข้อมูลปริญญานิพนธ์</a>
            <a href="usermanage.php">จัดการข้อมูลผู้ใช้</a>
            <a href="?table=tags">จัดการรูปแบบปริญญานิพนธ์</a>
            <a href="?table=faculty">จัดการรายชื่อสาขา</a>
            <a href="#" id="reportBtn">รายงาน</a>
        </div>


        <div class="admin-actions">
    <button id="addTagBtn">เพิ่มรูปแบบปริญญานิพนธ์</button>
    <button id="addFacultyBtn">เพิ่มรายชื่อสาขา</button>
    
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


    <!-- Add Tag Modal -->
    <div id="addTagModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>เพิ่มรูปแบบปริญญานิพนธ์</h2>
            <form id="addTagForm" action="add_tag.php" method="post">
                <label for="tag_name">ชื่อรูปแบบปริญญานิพนธ์:</label>
                <input type="text" id="tag_name" name="tag_name" required><br>

                <input type="submit" value="เพิ่มรูปแบบปริญญานิพนธ์">
            </form>
        </div>
    </div>

        <!-- Add Faculty Modal -->
    <div id="addFacultyModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>เพิ่มรายชื่อสาขา</h2>
            <form id="addFacultyForm" action="add_faculty.php" method="post">
                <label for="faculty_name">ชื่อสาขา:</label>
                <input type="text" id="faculty_name" name="faculty_name" required><br>

                <input type="submit" value="เพิ่มรายชื่อสาขา">
            </form>
        </div>
    </div>   

        <div class="main-panel">
            <?php if (isset($rows)): ?>
                <h2>ข้อมูลจากตาราง <?php echo htmlspecialchars($table); ?></h2>
                <form method="GET" action="admin.php">
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
    <?php if ($table == 'tags') : ?>
        <input type="text" name="search_tag" placeholder="ค้นหารูปแบบ" value="<?php echo isset($_GET['search_tag']) ? htmlspecialchars($_GET['search_tag']) : ''; ?>">
    <?php elseif ($table == 'faculty') : ?>
        <input type="text" name="search_faculty" placeholder="ค้นหารายชื่อสาขา" value="<?php echo isset($_GET['search_faculty']) ? htmlspecialchars($_GET['search_faculty']) : ''; ?>">
    <?php endif; ?>
    <input type="submit" value="ค้นหา">
</form>
                <table>
                <thead>
    <tr>
        <?php foreach ($columns as $column): ?>
            <?php if (!in_array($column['Field'], $excluded_columns)): ?>
                <th><?php echo htmlspecialchars($column['Field']); ?></th>
            <?php endif; ?>
        <?php endforeach; ?>
        <th>การแก้ไข / ลบ</th>
    </tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
    <tr>
    <form method="POST" action="">
    <?php foreach ($row as $key => $cell): ?>
        <?php if (!in_array($key, $excluded_columns)): ?>
            <td>
                <input type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($cell); ?>">
            </td>
        <?php endif; ?>
    <?php endforeach; ?>
    <td>
        <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
        <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($row['id']); ?>">
        <input type="hidden" name="id_to_delete" value="<?php echo htmlspecialchars($row['id']); ?>">
        <input type="submit" name="update" value="แก้ไข" onclick="return confirm('คุณแน่ใจใช้ไหมที่จะแก้ไขข้อมูลนี้');">
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

    // Show the first page and a "..." if the current page is far from the first page
    if ($page > $range + 1) {
        echo '<a href="?table=' . htmlspecialchars($table) . '&page=1">1</a>';
        if ($page > $range + 2) {
            echo '<span>...</span>';
        }
    }

    // Display pages around the current page
    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
        echo '<a href="?table=' . htmlspecialchars($table) . '&page=' . $i . '" class="' . ($page == $i ? 'active' : '') . '">' . $i . '</a>';
    }

    // Show the last page and a "..." if the current page is far from the last page
    if ($page < $total_pages - $range) {
        if ($page < $total_pages - $range - 1) {
            echo '<span>...</span>';
        }
        echo '<a href="?table=' . htmlspecialchars($table) . '&page=' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
</div>
            <?php else: ?>
                <p>เลือกเมนูที่ต้องการใช้งานได้ที่ด้านบน</p>
            <?php endif; ?>
        </div>
    </div>
    <!-- Success Message Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="successMessage">แก้ไขสำเร็จ</p>
        </div>
    </div>

    <script>
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
