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

// Fetch distinct user types from the users table
$user_types = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT user_type FROM users");
    $user_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}

$records_per_page = 20; // Number of records to display per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Handle search query
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_sql = '';
$search_params = [];

// If a search query exists, adjust the SQL query to filter across multiple columns
if (!empty($search_query)) {
    $search_sql = "WHERE (username LIKE :search_query OR name LIKE :search_query OR surname LIKE :search_query OR email LIKE :search_query OR id_number LIKE :search_query OR student_id LIKE :search_query)";
    $search_params[':search_query'] = "%$search_query%";
}

// Fetch users from the database with pagination and search
$users = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users $search_sql LIMIT :offset, :records_per_page");
    foreach ($search_params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}

// Get the total number of users to calculate total pages
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $search_sql");
    foreach ($search_params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $records_per_page);
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (isset($_POST['update'])) {
        // Update user information
        $username = $_POST['username'];
        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $email = $_POST['email'];
        $is_verified = $_POST['is_verified'];
        $id_number = $_POST['id_number'];
        $student_id = $_POST['student_id'];
        $user_type = $_POST['user_type'];

        // Update query
        $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, surname = ?, email = ?, is_verified = ?, id_number = ?, student_id = ?, user_type = ? WHERE id = ?");
        try {
            if ($stmt->execute([$username, $name, $surname, $email, $is_verified, $id_number, $student_id, $user_type, $id])) {
                $success_message = "แก้ไขผู้ใช้สำเร็จ";
            } else {
                $success_message = "แก้ไขผู้ใช้ล้มเหลว";
            }
        } catch (PDOException $e) {
            $success_message = "Database error: " . $e->getMessage();
        }

        // Store the success message in the session
        $_SESSION['success_message'] = $success_message;

    } elseif (isset($_POST['delete'])) {
        // Delete related records in login_history
        $stmt = $pdo->prepare("DELETE FROM login_history WHERE user_id = ?");
        try {
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "ลบล้มเหลวเนื่องจากมีความเชื่อมโยงกับตารางประวัติการเข้าสู่ระบบ: " . $e->getMessage();
        }
    
        // Delete related records in download_history
        $stmt = $pdo->prepare("DELETE FROM download_history WHERE user_id = ?");
        try {
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "ลบล้มเหลวเนื่องจากมีความเชื่อมโยงกับตารางประวัติการดาวน์โหลด: " . $e->getMessage();
        }

        // Delete related records in view_history
        $stmt = $pdo->prepare("DELETE FROM view_history WHERE user_id = ?");
        try {
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            echo "ลบล้มเหลวเนื่องจากมีความเชื่อมโยงกับตารางการดาวน์โหลดหรือค้นหา: " . $e->getMessage();
        }
    
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        try {
            if ($stmt->execute([$id])) {
                $success_message = "ลบผู้ใช้สำเร็จ";
            } else {
                $success_message = "ลบผู้ใช้ล้มเหลว";
            }
        } catch (PDOException $e) {
            $success_message = "Database error: " . $e->getMessage();
        }
    
        // Store the success message in the session
        $_SESSION['success_message'] = $success_message;
    }
    

    // Redirect back to avoid form resubmission and display the success message
    header('Location: usermanage.php');
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
            <a href="admin.php?table=tags">จัดการรูปแบบปริญญานิพนธ์</a>
            <a href="admin.php?table=faculty">จัดการรายชื่อสาขา</a>
            <a href="#" id="reportBtn">รายงาน</a>
        </div>


        <div class="admin-actions">
    <button id="addUserBtn">เพิ่มผู้ใช้</button>  
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
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>เพิ่มผู้ใช้</h2>
            <form id="addUserForm" action="add_user.php" method="post">
                <label for="username">ชื่อบัญชีผู้ใช้ (Username):</label>
                <input type="text" id="username" name="username" required><br>

                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required><br>

                <label for="name">ชื่อ:</label>
                <input type="text" id="name" name="name" required><br>

                <label for="surname">นามสกุล:</label>
                <input type="text" id="surname" name="surname" required><br>

                <label for="email">อีเมล (Email):</label>
                <input type="email" id="email" name="email" required><br>

                <label for="id_number">เลขประจำตัวประชาชน:</label>
                <input type="text" id="id_number" name="id_number" required><br>

                <label for="student_id">รหัสนักศึกษา:</label>
                <input type="text" id="student_id" name="student_id" required><br>

                <label for="user_type">ประเภทผู้ใช้:</label>
                <select id="user_type" name="user_type" required>
    <?php foreach ($user_types as $type): ?>
        <option value="<?php echo htmlspecialchars($type['user_type']); ?>" 
            <?php echo ($type['user_type'] === 'Normal User') ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($type['user_type']); ?>
        </option>
    <?php endforeach; ?>
</select>



                <input type="radio" id="notverified" name="is_verified" value="1">
                <label for="verified">ยืนยันบัญชี</label>
                <input type="radio" id="verified" name="is_verified" value="0">
                <label for="notverified">ไม่ยืนยันบัญชี</label><br>

                <input type="hidden" name="verification_token" value="">
                <input type="hidden" name="token_expires_at" value="">
                <input type="hidden" name="reset_token" value="">
                <input type="hidden" name="reset_token_expires_at" value="">
                <input type="submit" value="เพิ่มผู้ใช้">
            </form>
        </div>
    </div>
        
        <div class="main-panel">
                <h2>ข้อมูลจากตารางผู้ใช้</h2>
    <!-- User table -->

    <form method="GET" action="">
    <input type="text" name="search_query" placeholder="ค้นหาจาก username, ชื่อ, นามสกุล, อีเมล, เลขประจำตัวประชาชน, รหัสนักศึกษา" 
           style="width: 32%; padding: 1px; font-size: 16px;" 
           value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
    <button type="submit" style="padding: 5px 10px; font-size: 16px;">ค้นหา</button>
</form>
<table>
    <thead>
        <tr>
            <th>ชื่อบัญชีผู้ใช้ (Username)</th>
            <th>ชื่อ</th>
            <th>นามสกุล</th>
            <th>อีเมล</th>
            <th>การยืนยันตัวตน</th>
            <th>เลขประจำตัวประชาชน</th>
            <th>รหัสนักศึกษา</th>
            <th>ประเภทผู้ใช้</th>
            <th>แก้ไข / ลบ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <form method="POST" action="">
                <td><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"></td>
                <td><input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"></td>
                <td><input type="text" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>"></td>
                <td><input type="text" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"></td>
                <td>
                    <input type="radio" name="is_verified" value="1" <?php echo $user['is_verified'] == 1 ? 'checked' : ''; ?>> ใช่
                    <input type="radio" name="is_verified" value="0" <?php echo $user['is_verified'] == 0 ? 'checked' : ''; ?>> ไม่
                </td>
                <td><input type="text" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>"></td>
                <td><input type="text" name="student_id" value="<?php echo htmlspecialchars($user['student_id']); ?>"></td>
                <td>
                <select name="user_type">
                    <?php foreach ($user_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['user_type']); ?>" <?php echo $user['user_type'] == $type['user_type'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['user_type']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                </td>
                <td>
                    <input type="hidden" name="table" value="users">
                    <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                    <!-- Update button with confirmation -->
                    <button type="submit" name="update" onclick="return confirm('คุณแน่ใจใช่ไหมว่าจะแก้ไขข้อมูลนี้')">แก้ไข</button>
                    
                    <!-- Delete button with confirmation -->
                    <button type="submit" name="delete" onclick="return confirm('คุณแน่ใจใช่ไหมว่าจะลบข้อมูลนี้')">ลบ</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php
    $range = 2; // Adjust the range of pages displayed around the current page

    // Show the first page and a "..." if the current page is far from the first page
    if ($page > $range + 1) {
        echo '<a href="?table=users&page=1">1</a>';
        if ($page > $range + 2) {
            echo '<span>...</span>';
        }
    }

    // Display pages around the current page
    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
        echo '<a href="?table=users&page=' . $i . '" class="' . ($page == $i ? 'active' : '') . '">' . $i . '</a>';
    }

    // Show the last page and a "..." if the current page is far from the last page
    if ($page < $total_pages - $range) {
        if ($page < $total_pages - $range - 1) {
            echo '<span>...</span>';
        }
        echo '<a href="?table=users&page=' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
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
        var addUserBtn = document.getElementById('addUserBtn');
        var reportBtn = document.getElementById('reportBtn');

        // Get the modals
        
        var addUserModal = document.getElementById('addUserModal');
        var reportModal = document.getElementById('reportModal');

        // Event listener for buttons to open the respective modals
        if (addUserBtn) addUserBtn.onclick = function() { openModal('addUserModal'); }
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
