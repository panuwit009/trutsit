<?php
session_start();
require_once 'config.php';

// Check if user is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$thesis_id = $input['thesis_id'];
$tag_id = $input['tag_id'];

// Validate inputs
if (empty($thesis_id) || empty($tag_id)) {
    die(json_encode(['success' => false, 'message' => 'Invalid input']));
}

try {
    // Insert into thesis_tags table if not already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM thesis_tags WHERE thesis_id = :thesis_id AND tag_id = :tag_id");
    $stmt->bindValue(':thesis_id', $thesis_id);
    $stmt->bindValue(':tag_id', $tag_id);
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if ($exists) {
        die(json_encode(['success' => false, 'message' => 'รูปแบบซ้ำ']));
    }

    $stmt = $pdo->prepare("INSERT INTO thesis_tags (thesis_id, tag_id) VALUES (:thesis_id, :tag_id)");
    $stmt->bindValue(':thesis_id', $thesis_id);
    $stmt->bindValue(':tag_id', $tag_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
