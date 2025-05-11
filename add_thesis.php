<?php
session_start();
require 'config.php'; // Include your database configuration

// Set header to indicate JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input_data = json_decode(file_get_contents('php://input'), true);

    // Check if the necessary fields are provided
    if (isset($input_data['thesis_name'], $input_data['author_name'], $input_data['year'], $input_data['faculty'], $input_data['advisor'])) {
        // Sanitize and assign form data
        $thesis_name = $input_data['thesis_name'];
        $author_name = $input_data['author_name'];
        $year = $input_data['year'];
        $faculty = $input_data['faculty'];
        $advisor = $input_data['advisor'];
        $most_searched = $input_data['most_searched'];
        $most_downloaded = $input_data['most_downloaded'];

        try {
            // Prepare and execute the SQL statement
            $stmt = $pdo->prepare("INSERT INTO theses (thesis_name, author_name, year, faculty, advisor, most_searched, most_downloaded) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$thesis_name, $author_name, $year, $faculty, $advisor, $most_searched, $most_downloaded]);

            // Prepare the response
            $response = [
                'success' => true,
                'message' => 'เพิ่มปริญญานิพนธ์สำเร็จ',
                'redirect_url' => 'thesesmanage.php?page=' . $input_data['page'] . '&success_message=' . urlencode('เพิ่มปริญญานิพนธ์สำเร็จ')
            ];
        } catch (PDOException $e) {
            // Handle error and prepare the response
            $response = [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()
            ];
        }
    } else {
        // Missing required fields
        $response = [
            'success' => false,
            'message' => 'ข้อมูลไม่ครบถ้วน'
        ];
    }

    // Return the JSON response
    echo json_encode($response);
} else {
    // Invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
