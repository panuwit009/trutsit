<?php
session_start();
require 'config.php'; // Include your database configuration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize the form data
    $thesis_name = $_POST['thesis_name'];
    $author_name = $_POST['author_name'];
    $year = $_POST['year'];
    $faculty = $_POST['faculty'];
    $advisor = $_POST['advisor'];
    $most_searched = $_POST['most_searched'];
    $most_downloaded = $_POST['most_downloaded'];

    // Initialize file_path as null
    $file_path = null;

    // Handle file upload if a file was provided
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['file_upload']['tmp_name'];
        $file_name = basename($_FILES['file_upload']['name']);
        $upload_dir = 'uploads/';
        
        // Create the upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate a unique file name to avoid overwriting
        $new_file_path = $upload_dir . uniqid() . '-' . $file_name;
        
        // Move the uploaded file to the desired directory
        if (move_uploaded_file($file_tmp_path, $new_file_path)) {
            $file_path = $new_file_path; // Save the new file path for database insertion
        } else {
            $_SESSION['error_message'] = 'File upload failed!';
        }
    }

    // Prepare and execute the SQL statement for inserting thesis data
    try {
        $stmt = $pdo->prepare("INSERT INTO theses (thesis_name, author_name, year, faculty, advisor, file_path, most_searched, most_downloaded) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$thesis_name, $author_name, $year, $faculty, $advisor, $file_path, $most_searched, $most_downloaded]);

        $_SESSION['success_message'] = 'เพิ่มปริญญานิพนธ์สำเร็จ';

        // Get the query string from $_GET and append it to the redirect URL
        $query_params = $_GET;
        $query_string = http_build_query($query_params);

        header("Location: thesesmanage.php?page=" . $_POST['page'] . "&" . $query_string . "&success_message=" . urlencode("เพิ่มปริญญานิพนธ์สำเร็จ"));
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        $query_params = $_GET;
        $query_string = http_build_query($query_params);
        header("Location: thesesmanage.php?page=" . $_POST['page'] . "&" . $query_string . "&error_message=" . urlencode($_SESSION['error_message']));
        exit();
    }

} else {
    echo "Invalid request method.";
}
?>
