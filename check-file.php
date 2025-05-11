<?php
require_once 'config.php';

// Get the thesis ID from the request
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Prepare the SQL query to get the file path and download permission
$sql = "SELECT file_path, download_permission FROM theses WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$thesis = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the thesis record exists
if ($thesis) {
    // Check download permission
    if ($thesis['download_permission'] === 'yes') {
        // Check if the file exists
        if (!empty($thesis['file_path']) && file_exists($thesis['file_path'])) {
            echo "exists";
        } else {
            echo "not_exists";
        }
    } else {
        // Download permission is 'no'
        echo "no_permission";
    }
} else {
    // Thesis record not found
    echo "not_exists";
}
?>
