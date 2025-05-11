<?php
session_start(); // Start the session

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config.php'; // Make sure this includes the correct database connection

use setasign\Fpdi\Fpdi;

$id = $_GET['id'];
$sql = "SELECT * FROM theses WHERE id = :id"; // Use a prepared statement
$stmt = $pdo->prepare($sql); // Use $pdo instead of $conn
$stmt->bindParam(':id', $id, PDO::PARAM_INT); // Bind the ID parameter
$stmt->execute(); // Execute the query
$thesis = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result

if ($thesis) {
    $filePath = $thesis['file_path'];
    $updateStmt = $pdo->prepare("UPDATE theses SET most_searched = most_searched + 1 WHERE id = ?");
    $updateStmt->execute([$id]);

    // Record view history
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $viewStmt = $pdo->prepare("INSERT INTO view_history (user_id, thesis_id, view_time) VALUES (?, ?, NOW())");
        $viewStmt->execute([$user_id, $id]);
    }

    if (file_exists($filePath)) {
        $pdf = new Fpdi();

        // Get the page count of the source PDF
        $pageCount = $pdf->setSourceFile($filePath);

        // Loop through the first three pages
        for ($i = 1; $i <= min(3, $pageCount); $i++) {
            $tplIdx = $pdf->importPage($i);
            $pdf->addPage();
            $pdf->useTemplate($tplIdx);
                
            //$pdf->Image('trulogo.jpg', 10, 10, 190, 277, 'JPG');
        	//$pdf->SetAlpha(0.2); // Uncomment this to make the image semi-transparent

            //$pdf->SetFont('Arial', 'B', 30); // Font type, style, and size (smaller for positioning at the top)
			//$pdf->SetTextColor(169, 169, 169); // Soft grey color (RGB values)
			//$pdf->SetXY(50, 50); // Position of the text more towards the top
			//$pdf->Text(50, 50, 'กรุณาเข้าสู่ระบบเพื่อดูปริญญานิพนธ์เล่มนี้ฉบับเต็ม'); ต้องติดตั้ง font Thai ก่อนถึงจะใช้ได้

        }

        // Add headers to suggest that the PDF is for preview only
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="preview.pdf"');

        // Output the first three pages
        $pdf->Output();
    } else {
        echo "ไม่พบไฟล์";
    }
} else {
    echo "ไม่พบปริญญานิพนธ์";
}
?>