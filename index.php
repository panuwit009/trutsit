<?php
require_once 'config.php';
session_start();

// User session details
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$name = $is_logged_in ? $_SESSION['name'] : '';
$surname = $is_logged_in ? $_SESSION['surname'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : 'non_login';

// Sorting and pagination variables
$sort_column = 'year';
$sort_order = 'DESC';
$records_per_page = 10;

// Handle sorting
if (isset($_GET['sort-by'])) {
    switch ($_GET['sort-by']) {
        case 'newest_year':
            $sort_column = 'year';
            $sort_order = 'DESC';
            break;
        case 'oldest_year':
            $sort_column = 'year';
            $sort_order = 'ASC';
            break;
        case 'most_download':
            $sort_column = 'most_downloaded';
            $sort_order = 'DESC';
            break;
        case 'most_search':
            $sort_column = 'most_searched';
            $sort_order = 'DESC';
            break;
    }
}

// Filters
$thesis_name = isset($_GET['thesis_name']) ? $_GET['thesis_name'] : '';
$author_name = isset($_GET['author_name']) ? $_GET['author_name'] : '';
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$advisor = isset($_GET['advisor']) ? $_GET['advisor'] : '';
$selected_tags = isset($_GET['tags']) ? explode(',', $_GET['tags']) : [];

// Count total records for pagination
$count_query = "
    SELECT COUNT(DISTINCT t.id) AS total
    FROM theses t
    LEFT JOIN thesis_tags tt ON t.id = tt.thesis_id
    LEFT JOIN tags tag ON tt.tag_id = tag.id
    WHERE 1=1
";

if ($thesis_name) {
    $count_query .= " AND t.thesis_name LIKE :thesis_name";
}
if ($author_name) {
    $count_query .= " AND t.author_name LIKE :author_name";
}
if ($faculty) {
    $count_query .= " AND t.faculty = :faculty";
}
if ($advisor) {
    $count_query .= " AND t.advisor = :advisor";
}
if (!empty($selected_tags)) {
    $count_query .= " AND (";
    foreach ($selected_tags as $index => $tag_id) {
        $count_query .= "tt.tag_id = :tag_id_$index";
        if ($index < count($selected_tags) - 1) {
            $count_query .= " OR ";
        }
    }
    $count_query .= ")";
}

$count_stmt = $pdo->prepare($count_query);

if ($thesis_name) {
    $count_stmt->bindValue(':thesis_name', "%$thesis_name%");
}
if ($author_name) {
    $count_stmt->bindValue(':author_name', "%$author_name%");
}
if ($faculty) {
    $count_stmt->bindValue(':faculty', $faculty);
}
if ($advisor) {
    $count_stmt->bindValue(':advisor', $advisor);
}
if (!empty($selected_tags)) {
    foreach ($selected_tags as $index => $tag_id) {
        $count_stmt->bindValue(":tag_id_$index", $tag_id);
    }
}

$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, min($total_pages, $current_page));
$offset = ($current_page - 1) * $records_per_page;

// Fetch records for the current page
$query = "
    SELECT t.*, GROUP_CONCAT(tag.tag SEPARATOR ', ') AS tags 
    FROM theses t
    LEFT JOIN thesis_tags tt ON t.id = tt.thesis_id
    LEFT JOIN tags tag ON tt.tag_id = tag.id
    WHERE 1=1
";

if ($thesis_name) {
    $query .= " AND t.thesis_name LIKE :thesis_name";
}
if ($author_name) {
    $query .= " AND t.author_name LIKE :author_name";
}
if ($faculty) {
    $query .= " AND t.faculty = :faculty";
}
if ($advisor) {
    $query .= " AND t.advisor = :advisor";
}
if (!empty($selected_tags)) {
    $query .= " AND (";
    foreach ($selected_tags as $index => $tag_id) {
        $query .= "tt.tag_id = :tag_id_$index";
        if ($index < count($selected_tags) - 1) {
            $query .= " OR ";
        }
    }
    $query .= ")";
}

$query .= " GROUP BY t.id ORDER BY $sort_column $sort_order LIMIT :offset, :records_per_page";

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
if ($advisor) {
    $stmt->bindValue(':advisor', $advisor);
}
if (!empty($selected_tags)) {
    foreach ($selected_tags as $index => $tag_id) {
        $stmt->bindValue(":tag_id_$index", $tag_id);
    }
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);

$stmt->execute();
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$faculties_query = "SELECT DISTINCT faculty FROM theses";
$faculties_stmt = $pdo->query($faculties_query);
$faculties = $faculties_stmt->fetchAll(PDO::FETCH_COLUMN);

$advisors_query = "SELECT DISTINCT advisor FROM theses";
$advisors_stmt = $pdo->query($advisors_query);
$advisors = $advisors_stmt->fetchAll(PDO::FETCH_COLUMN);

$tags_query = "SELECT id, tag FROM tags";
$tags_stmt = $pdo->query($tags_query);
$tags = $tags_stmt->fetchAll(PDO::FETCH_ASSOC);

$user_type = $is_logged_in ? $_SESSION['user_type'] : 'non_login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRU T-Sit</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="home.png" type="image/png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
.search-form, .popup-form {
display: flex;
flex-wrap: wrap;
gap: 10px;
}
.search-form input[type="text"], 
.search-form select, 
.popup-form input[type="text"], 
.popup-form input[type="password"], 
.popup-form input[type="email"], 
.popup-form select {
    width: 200px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.search-form button, 
.popup-form button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background-color: #007BFF;
    color: #fff;
    cursor: pointer;
}
.search-form button:hover, 
.popup-form button:hover {
    background-color: #0056b3;
}
.submit-container {
    margin-top: 0px;
}
.submit-container button {
    font-weight: bold;
    font-size: 20px;
    padding: 5px 10px;
    border: none;
    background-color: #007BFF;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}
.submit-container button:hover {
    background-color: #0056b3;
}
.sort-container {
    margin-top: 20px;
}
.popup {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}
.popup-content {
    background-color: #fefefe;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    max-width: 500px; /* Maximum width for smaller screens */
    text-align: center; /* Center text inside the popup */
    border-radius: 8px; /* Rounded corners for popup */
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
.logout-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background-color: #dc3545;
    color: #fff;
    cursor: pointer;
}
.logout-button:hover {
    background-color: #c82333;
}
.welcome-message {
    margin: 10px 0;
    font-size: 18px;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px; /* Adds spacing between the buttons */
}
.admin-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background-color: #28a745;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}
.admin-button:hover {
    background-color: #218838;
}
.info-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background-color: #007bff;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}
.info-button:hover {
    background-color: #0056b3;
}
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px; /* Space between buttons */
    margin: 20px 0;
    font-family: Arial, sans-serif;
}

.pagination a {
    text-decoration: none;
    color: #007bff;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease-in-out;
}

.pagination a:hover {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

.pagination a[style="font-weight: bold;"] {
    font-weight: bold;
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

.pagination span {
    padding: 8px 12px;
    font-size: 14px;
    color: #6c757d;
}

#showMoreContainer {
    text-align: center;
    margin: 20px 0;
}
#showMoreButton {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    background-color: #007BFF;
    color: #fff;
    cursor: pointer;
}
#showMoreButton:hover {
    background-color: #0056b3;
}
.pdf-modal {
    display: none; 
    position: fixed; 
    z-index: 9999; 
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.8); /* Semi-transparent background */
}
.pdf-modal-content {
    position: relative;
    width: 100%;
    height: 100%;
    padding: 0;
    border: none;
    overflow: hidden;
}
.pdf-iframe {
    width: 100%;
    height: 100%;
    border: none;
}
.close-button {
    position: absolute;
    top: 45px;
    right: 30px;
    color: black;
    font-size: 50px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
    background-color: transparent;
    border: none;
    transition: color 0.3s ease;
}
.close-button:hover {
    color: #ff0000;
}
.view-button{
    display: inline-flex; 
    padding: 10px 20px;   /* Consistent padding for both */
    font-size: 16px;      /* Same font size */
    width: 35px;         /* Set fixed width */
    height: 35px;         /* Set fixed height */
    color: white;
    background-color: white;
    border: none;
    border-radius: 10px; 
    cursor: pointer;
    align-items: center;
    justify-content: center; 
    text-decoration: none;
    box-sizing: border-box; /* Ensure padding is included in width/height */
} 
.download-button {
    display: inline-flex; 
    padding: 10px 20px;   /* Consistent padding for both */
    font-size: 36px;      /* Same font size */
    width: 40px;         /* Set fixed width */
    height: 40px;         /* Set fixed height */
    color: white;
    background-color: white;
    border: none;
    border-radius: 10px; 
    cursor: pointer;
    align-items: center;
    justify-content: center; 
    text-decoration: none;
    box-sizing: border-box; /* Ensure padding is included in width/height */
}
.content {
    flex: 1; /* This allows the content to take up all available space, pushing the footer to the bottom */
}
.footer {
    background-color: #333;
    color: white;
    text-align: center;
    padding: 1px 0;
    font-size: 14px;
}
.modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.5); /* Black w/ opacity */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 600px; /* Maximum width */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        .thesis-table {
        width: 100%; /* Ensure the table takes the full width */
        border-collapse: collapse; /* Collapse borders for a cleaner look */
    }
    .thesis-table td {
        text-align: left; /* Center text in th and td */
        padding: 10px; /* Add some padding */
        border: 1px solid #ddd; /* Optional: Add borders */
    }
    .thesis-table th {
        text-align: center; /* Center text in th and td */
        padding: 10px; /* Add some padding */
        border: 1px solid #ddd; /* Optional: Add borders */
    }
            
    /* Style for the disabled-looking button */
.download-button.disabled {
    color: #ccc; /* Light grey color for text */
    cursor: not-allowed; /* Shows a 'not-allowed' cursor */
    text-decoration: none; /* No underline */
    pointer-events: all; /* Keeps it clickable */
    opacity: 0.5; /* Make it appear faded */
}

.download-button.disabled:hover {
    /* No hover effect to make it look fully inactive */
    background-color: transparent;
    color: #ccc;
}

    </style>
</head>
<body>
<div class="header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="message-box" style="text-align: center; padding: 10px;">
    <a href="https://trutsit.atwebpages.com/" style="text-decoration: none; color: inherit;">
        <div>
            <span style="font-size: 40px; font-weight: bold;">TRU T-Sit</span>
        </div>
    </a>
</div>
        <?php if ($is_logged_in): ?>
    <div class="welcome-message">
        ยินดีต้อนรับ <?php echo htmlspecialchars($name . ' ' . $surname); ?>
        <form action="logout.php" method="POST" style="display:inline;">
            <button type="submit" class="logout-button">ออกจากระบบ</button>
        </form>
        <a href="user_info.php" class="info-button">ข้อมูลผู้ใช้</a>
        <?php if ($user_type == 'admin'): ?>
            <a href="admin.php" class="admin-button">ผู้ดูแล</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="login-container">
        <form action="login.php" method="POST">
            <div class="login-elements">
                <input type="text" name="username" placeholder="ชื่อผู้ใช้ (Username)" required>
                <input type="password" name="password" placeholder="รหัสผ่าน" required>
                <button type="submit" class="login-button">เข้าสู่ระบบ</button>
                <button type="button" class="register-button" onclick="openRegisterPopup()">ลงทะเบียน</button>
                <button type="button" onclick="openResetPasswordRequestPopup()">รีเซ็ตรหัสผ่าน</button>
            </div>
        </form>
    </div>
<?php endif; ?>
</div>
    <div class="main">
    <form class="search-form" action="index.php" method="GET" id="searchForm"> 
    <input type="text" name="thesis_name" placeholder="ชื่อปริญญานิพนธ์" value="<?php echo htmlspecialchars($thesis_name); ?>" onchange="submitForm()">
    <input type="text" name="author_name" placeholder="ชื่อผู้จัดทำ" value="<?php echo htmlspecialchars($author_name); ?>" onchange="submitForm()">
    <select name="faculty" onchange="submitForm()">
        <option value="">เลือกสาขา</option>
        <?php foreach ($faculties as $fac): ?>
            <option value="<?php echo htmlspecialchars($fac); ?>" <?php if ($faculty == $fac) echo 'selected'; ?>>
                <?php echo htmlspecialchars($fac); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="advisor" onchange="submitForm()">
        <option value="">เลือกที่ปรึกษา</option>
        <?php foreach ($advisors as $adv): ?>
            <option value="<?php echo htmlspecialchars($adv); ?>" <?php if ($advisor == $adv) echo 'selected'; ?>>
                <?php echo htmlspecialchars($adv); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="button" id="tagFilterButton">รูปแบบ</button>

    <input type="hidden" name="sort-by" value="<?php echo isset($_GET['sort-by']) ? htmlspecialchars($_GET['sort-by']) : 'newest_year'; ?>">
    
    <div class="submit-container">
        <button type="button" onclick="clearFilters()">↺</button>
    </div>
</form>
<div class="sort-container">
    <label for="sort-by">จัดเรียงตาม:</label>
    <select id="sort-by" name="sort-by" onchange="updateSort()">
        <option value="most_download" <?php if (isset($_GET['sort-by']) && $_GET['sort-by'] == 'most_download') echo 'selected'; ?>>การดาวน์โหลด</option>
        <option value="most_search" <?php if (isset($_GET['sort-by']) && $_GET['sort-by'] == 'most_search') echo 'selected'; ?>>การค้นหา</option>
        <option value="newest_year" <?php if (!isset($_GET['sort-by']) || $_GET['sort-by'] == 'newest_year') echo 'selected'; ?>>ปีล่าสุด</option>
        <option value="oldest_year" <?php if (isset($_GET['sort-by']) && $_GET['sort-by'] == 'oldest_year') echo 'selected'; ?>>ปีเก่าสุด</option>
    </select>
</div>
        <table class="thesis-table">
            <thead>
                <tr>
                    <th>ชื่อปริญญานิพนธ์</th>
                    <th>ชื่อผู้จัดทำ</th>
                    <th>ปี</th>
                    <th>สาขา</th>
                    <th>ที่ปรึกษา</th>
                    <th> 🔍 / ⬇️</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($theses as $thesis): ?>
    <tr>
        <td>
            <?php echo htmlspecialchars($thesis['thesis_name']); ?>
            <br>
            <small style="color: #007BFF;">
    <strong>รูปแบบ: </strong>
    <?php echo htmlspecialchars($thesis['tags'] ?: '-'); ?>
</small>
        </td>
        <td><?php echo htmlspecialchars($thesis['author_name']); ?></td>
        <td><?php echo htmlspecialchars($thesis['year']); ?></td>
        <td><?php echo htmlspecialchars($thesis['faculty']); ?></td>
        <td><?php echo htmlspecialchars($thesis['advisor']); ?></td>
        <td>
            <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if (!empty($thesis['file_path'])): ?>
                		<button type="button" class="view-button" onclick="openPdfModal(<?php echo $thesis['id']; ?>)">🔍</button>
                    <?php endif; ?>
				
    			<?php if ($thesis['download_permission'] == 'yes' && !empty($thesis['file_path'])): ?>
                    <?php if ($user_type == 'non_login' || $user_type == 'banned'): ?>
                    <a href="javascript:void(0)" onclick="showLoginPrompt()" class="download-button disabled">⬇️</a>
                    <?php else: ?>
        			<a href="javascript:void(0)" onclick="checkFileAndDownload(<?php echo $thesis['id']; ?>)" class="download-button">⬇️</a>
    			<?php endif; ?>
			<?php endif; ?>

            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
        </table>

<div class="pagination">
    <?php
    // Define range for pagination display
    $range = 2; // Number of pages to show around the current page
    $middle_page = ceil($total_pages / 2);

    // Get the current filter values from the URL
    $filters = [
        'thesis_name' => isset($_GET['thesis_name']) ? $_GET['thesis_name'] : '',
        'author_name' => isset($_GET['author_name']) ? $_GET['author_name'] : '',
        'faculty' => isset($_GET['faculty']) ? $_GET['faculty'] : '',
        'advisor' => isset($_GET['advisor']) ? $_GET['advisor'] : '',
        'tags' => isset($_GET['tags']) ? $_GET['tags'] : ''
    ];

    // Function to build query string with filters
    function buildUrlWithFilters($page, $filters) {
        $url = "?page=" . $page;
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $url .= "&$key=" . urlencode($value);
            }
        }
        return $url;
    }

    // Always show the first two pages
    if ($current_page > 3) {
        for ($i = 1; $i <= 2; $i++) {
            echo '<a href="' . buildUrlWithFilters($i, $filters) . '">' . $i . '</a>';
        }
        if ($current_page > 4) {
            echo '<span>...</span>'; // Ellipsis after the first two pages
        }
    }

    // Show the current page and its neighbors
    $start = max(1, $current_page - $range);
    $end = min($total_pages, $current_page + $range);

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            echo '<a href="' . buildUrlWithFilters($i, $filters) . '" style="font-weight: bold;">' . $i . '</a>';
        } else {
            echo '<a href="' . buildUrlWithFilters($i, $filters) . '">' . $i . '</a>';
        }
    }

    // Show ellipsis before the last two pages if there's a gap
    if ($current_page < $total_pages - 3) {
        if ($current_page < $total_pages - 4) {
            echo '<span>...</span>';
        }
        for ($i = $total_pages - 1; $i <= $total_pages; $i++) {
            echo '<a href="' . buildUrlWithFilters($i, $filters) . '">' . $i . '</a>';
        }
    }
    ?>
</div>




</div>
<div id="tagFilterModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>เลือกรูปแบบ</h2>
        <form id="tagFilterForm">
            <?php
            $tags_query = "SELECT * FROM tags";
            $tags_stmt = $pdo->query($tags_query);
            $tags = $tags_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tags as $tag):
            ?>
                <label>
                    <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                    <?php echo htmlspecialchars($tag['tag']); ?>
                </label><br>
            <?php endforeach; ?>
            <button type="submit">ตกลง</button>
        </form>
    </div>
</div>
<div id="pdfModal" class="pdf-modal">
    <span class="close-button" onclick="closePdfModal()">&times;</span>
    <div class="pdf-modal-content">
        <iframe id="pdfIframe" class="pdf-iframe"></iframe>
    </div>
</div>
        </div>
    </div>
    
<div class="footer">
    <p>© 2024 TRU T-Sit (Thepsatri Rajabhat University Senior Thesis Searching System for the Faculty of Information Technology) . All rights reserved.</p>
</div>



<script>
document.getElementById("tagFilterButton").onclick = function() {
        const selectedTags = getSelectedTagsFromUrl();
        const checkboxes = document.querySelectorAll('#tagFilterForm input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectedTags.includes(checkbox.value);
        });
        document.getElementById("tagFilterModal").style.display = "block";
    }
    
    document.getElementsByClassName("close")[0].onclick = function() {
        document.getElementById("tagFilterModal").style.display = "none";
    }
    
    document.getElementById("tagFilterForm").onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const selectedTags = [];
        for (const entry of formData.entries()) {
            selectedTags.push(entry[1]);
        }
        const params = new URLSearchParams(window.location.search);
        params.set('tags', selectedTags.join(','));
        window.location.search = params.toString();
    }
    
    function getSelectedTagsFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const tags = params.get('tags');
        return tags ? tags.split(',') : [];
    }
    
    window.onload = function() {
        const rows = document.querySelectorAll('tbody .thesis-row');
        console.log("Initializing rows visibility. Total rows:", rows.length);
    }

    function closePopup() {
        document.getElementById('registerPopup').style.display = 'none';
    }

    function openLoginPopup() {
        document.getElementById('loginPopup').style.display = 'block';
    }

    function closeLoginPopup() {
        document.getElementById('loginPopup').style.display = 'none';
    }

    function openRegisterPopup() {
        document.getElementById('registerPopup').style.display = 'block';
    }

    function closeRegisterPopup() {
        document.getElementById('registerPopup').style.display = 'none';
    }

    function openResetPasswordRequestPopup() {
        document.getElementById('resetPasswordRequestPopup').style.display = 'block';
    }

    function closeResetPasswordRequestPopup() {
        document.getElementById('resetPasswordRequestPopup').style.display = 'none';
    }

    function submitForm() {
        document.getElementById('searchForm').submit();
    }

    function updateSort() {
        const sortBy = document.getElementById('sort-by').value;
        const url = new URL(window.location.href);
        url.searchParams.set('sort-by', sortBy);
        window.location.href = url.toString();
    }

    function clearFilters() {
        const url = new URL(window.location.href);
        url.searchParams.delete('thesis_name');
        url.searchParams.delete('author_name');
        url.searchParams.delete('faculty');
        url.searchParams.delete('advisor');
        url.searchParams.delete('tags');
        window.location.href = url.toString();
    }

    function showLoginPrompt() {
        alert("กรุณาเข้าสู่ระบบเพื่อดาวน์โหลดไฟล์");
    }

    function downloadFile(id) {
        var link = document.createElement('a');
        link.href = 'download-thesis.php?id=' + id;
        link.download = '';
        link.click();
        alert('Your download will start shortly.');
    }

    function openPdfModal(thesisId) {
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    
    // If not logged in, show an alert
    if (!isLoggedIn) {
        alert("กรุณาเข้าสู่ระบบเพื่อดูปริญญานิพนธ์ฉบับเต็ม");
        var url = "view-thesis-preview.php?id=" + thesisId; // Redirect to preview page
    } else {
        var url = "view-thesis.php?id=" + thesisId; // Redirect to the actual thesis page
    }

    // Check if file exists using AJAX
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "check-file.php?id=" + thesisId, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText === "exists") {
                document.getElementById("pdfIframe").src = url;
                document.getElementById("pdfModal").style.display = "block";
                document.documentElement.style.overflow = 'hidden';
            } else {
                alert("ไม่พบข้อมูลนี้ในระบบ");
            }
        }
    };
    xhr.send();
}

function closePdfModal() {
    document.getElementById("pdfIframe").src = "";
    document.getElementById("pdfModal").style.display = "none";
    document.documentElement.style.overflow = '';

}
function checkFileAndDownload(thesisId) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "check-file.php?id=" + thesisId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = xhr.responseText.trim();
            if (response === "exists") {
                window.location.href = "download-thesis.php?id=" + thesisId;
            } else if (response === "not_exists") {
                showNoFilePopup();
            } else if (response === "no_permission") {
                showNoPermissionPopup();
            }
        }
    };
    xhr.send();
}
function showNoFilePopup() {
    alert('ไม่พบไฟล์นี้ในระบบ');
}
function showNoPermissionPopup() {
    alert('ระบบไม่อนุญาตให้ดาวน์โหลดไฟล์นี้');
}
function showNoFilePopup() {
    var popup = document.createElement("div");
    popup.style.position = "fixed";
    popup.style.top = "50%";
    popup.style.left = "50%";
    popup.style.transform = "translate(-50%, -50%)";
    popup.style.backgroundColor = "#f44336";
    popup.style.color = "white";
    popup.style.padding = "20px";
    popup.style.borderRadius = "5px";
    popup.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.2)";
    popup.style.zIndex = "10000";
    popup.innerHTML = "<strong>ไม่พบไฟล์นี้ในระบบ</strong><br><button onclick='this.parentElement.remove()' style='margin-top: 10px; background-color: #fff; color: #f44336; border: none; padding: 5px 10px; cursor: pointer;'>ปิด</button>";

    document.body.appendChild(popup);
}
window.onclick = function(event) {
    if (event.target === document.getElementById('loginPopup')) {
        closeLoginPopup();
    } 
    else if (event.target === document.getElementById('resetPasswordRequestPopup')) {
        closeResetPasswordRequestPopup();
    } 
    else if (event.target === document.getElementById('pdfModal')) {
        closePdfModal();
    }
}

    </script>
<div id="loginPopup" class="popup">
    <div class="popup-content">
        <span class="close" onclick="closeLoginPopup()">&times;</span>
        <h2>เข้าสู่ระบบ</h2>
        <form id="loginForm" class="popup-form" action="login.php" method="POST">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>
</div>
    <div id="registerPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closeRegisterPopup()">&times;</span>
            <h2>ลงทะเบียน</h2>
            <form id="registerForm" class="popup-form" action="register.php" method="POST">
                <input type="text" name="name" placeholder="ชื่อ" required>
                <input type="text" name="surname" placeholder="นามสกุล" required>
                <input type="text" name="student_id" placeholder="รหัสนักศึกษา (ถ้าไม่มีให้เว้นว่าง)">
                <input type="text" name="id_number" placeholder="เลขประจำตัวประชาชน" required>
                <input type="text" name="username" placeholder="ชื่อผู้ใช้ (Username)" required>
                <input type="password" name="password" placeholder="รหัสผ่าน" required>
                <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
                <input type="email" name="email" placeholder="อีเมล (Email)" required>
                <button type="submit">ลงทะเบียน</button>
            </form>
        </div>
    </div>
    <div id="resetPasswordRequestPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closeResetPasswordRequestPopup()">&times;</span>
            <h2>รีเซ็ทรหัสผ่าน</h2>
            <form id="resetPasswordRequestForm" class="popup-form" action="reset-password-request.php" method="POST">
                <input type="text" name="email" placeholder="ชื่อผู้ใช้ / อีเมล (Username / Email)" required>
                <input type="text" name="id_number" placeholder="เลขประจำตัวประชาชน" required>
                <input type="text" name="student_id" placeholder="รหัสนักศึกษา (ถ้าไม่มีให้เว้นว่าง)">
                <button type="submit">รับลิงค์รีเซ็ตรหัสผ่าน</button>
            </form>
        </div>
    </div>
<?php if (isset($_SESSION['error_message'])): ?>
    <div id="loginErrorPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('loginErrorPopup')">&times;</span>
            <p><?php echo $_SESSION['error_message']; ?></p>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['register_success'])): ?>
    <div id="registerSuccessPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('registerSuccessPopup')">&times;</span>
            <p><?php echo htmlspecialchars($_SESSION['register_success']); ?></p>
        </div>
    </div>
    <?php unset($_SESSION['register_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['register_error'])): ?>
    <div id="registerErrorPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('registerErrorPopup')">&times;</span>
            <p><?php echo htmlspecialchars($_SESSION['register_error']); ?></p>
        </div>
    </div>
    <?php unset($_SESSION['register_error']); ?>
<?php endif; ?>
<script>
    window.onload = function() {
        var loginErrorPopup = document.getElementById('loginErrorPopup');
        if (loginErrorPopup) {
            console.log('Opening login error popup');
            loginErrorPopup.style.display = 'block';
        }
        var registerSuccessPopup = document.getElementById('registerSuccessPopup');
        if (registerSuccessPopup) {
            console.log('Opening register success popup');
            registerSuccessPopup.style.display = 'block';
        }
        var registerErrorPopup = document.getElementById('registerErrorPopup');
        if (registerErrorPopup) {
            console.log('Opening register error popup');
            registerErrorPopup.style.display = 'block';
        }
    };
    function closePopup(popupId) {
        console.log('Closing popup with ID: ' + popupId);
        document.getElementById(popupId).style.display = 'none';
    }
</script>
<?php if (isset($_SESSION['reset_success'])): ?>
    <div id="resetSuccessPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('resetSuccessPopup')">&times;</span>
            <p><?php echo htmlspecialchars($_SESSION['reset_success']); ?></p>
        </div>
    </div>
    <script>
        window.onload = function() {
            document.getElementById('resetSuccessPopup').style.display = 'block';
        };
    </script>
    <?php unset($_SESSION['reset_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['reset_error'])): ?>
    <div id="resetErrorPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('resetErrorPopup')">&times;</span>
            <p><?php echo htmlspecialchars($_SESSION['reset_error']); ?></p>
        </div>
    </div>
    <script>
        window.onload = function() {
            document.getElementById('resetErrorPopup').style.display = 'block';
        };
    </script>
    <?php unset($_SESSION['reset_error']); ?>
<?php endif; ?>
<script>
    function closePopup(popupId) {
        document.getElementById(popupId).style.display = 'none';
    }
</script>
<?php if (isset($_SESSION['message'])): ?>
    <div id="verificationEmailSentPopup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup('verificationEmailSentPopup')">&times;</span>
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        </div>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>
<script>
    window.onload = function() {
        var loginErrorPopup = document.getElementById('loginErrorPopup');
        if (loginErrorPopup) {
            console.log('Opening login error popup');
            loginErrorPopup.style.display = 'block';
        }
        var registerSuccessPopup = document.getElementById('registerSuccessPopup');
        if (registerSuccessPopup) {
            console.log('Opening register success popup');
            registerSuccessPopup.style.display = 'block';
        }
        var registerErrorPopup = document.getElementById('registerErrorPopup');
        if (registerErrorPopup) {
            console.log('Opening register error popup');
            registerErrorPopup.style.display = 'block';
        }
        var verificationEmailSentPopup = document.getElementById('verificationEmailSentPopup');
        if (verificationEmailSentPopup) {
            console.log('Opening verification email sent popup');
            verificationEmailSentPopup.style.display = 'block';
        }
        var resetSuccessPopup = document.getElementById('resetSuccessPopup');
        if (resetSuccessPopup) {
            resetSuccessPopup.style.display = 'block';
        }
        var resetErrorPopup = document.getElementById('resetErrorPopup');
        if (resetErrorPopup) {
            resetErrorPopup.style.display = 'block';
        }
    };
    function closePopup(popupId) {
        console.log('Closing popup with ID: ' + popupId);
        document.getElementById(popupId).style.display = 'none';
    }
</script>
</body>
</html>
