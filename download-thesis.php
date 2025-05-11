<?php
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM theses WHERE id = ?");
    $stmt->execute([$id]);
    $thesis = $stmt->fetch();

    if ($thesis) {
        $file_path = $thesis['file_path'];
        // Increment the download count
        $updateStmt = $pdo->prepare("UPDATE theses SET most_downloaded = most_downloaded + 1 WHERE id = ?");
        $updateStmt->execute([$id]);

        // Record download history
        $user_id = $_SESSION['user_id'];
        $downloadStmt = $pdo->prepare("INSERT INTO download_history (user_id, thesis_id, download_time) VALUES (?, ?, NOW())");
        $downloadStmt->execute([$user_id, $id]);

        // Check if file exists
        if (file_exists($file_path)) {
            // Serve the PDF file for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($file_path);

            // Exit after file is served
            exit();
        } else {
            echo "ไม่พบไฟล์";
        }
    } else {
        echo "ไม่พบไฟล์.";
    }
} else {
    echo "ไม่พบรหัสปริญญานิพนธ์";
}
?>
