<?php
// Include database connection
include 'config.php';

// Get the report type from the URL
$type = $_GET['type'];

// Set the content type to plain text
header('Content-Type: text/plain');

// Function to format and output text report
function outputReport($title, $content) {
    echo "=== $title ===\n\n";
    echo $content;
}

switch ($type) {
    case 'user_activity':
        // Query and generate report for user activity
        $query = "SELECT * FROM user_activity"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = "Date\tUser ID\tLogin Count\tDownload Count\tView Count\n";
        foreach ($data as $row) {
            $content .= implode("\t", $row) . "\n";
        }
        outputReport('User Activity Report', $content);
        break;

    case 'user_login_count':
        // Query and generate report for user login count
        $query = "SELECT DATE(date) as date, COUNT(*) as login_count FROM user_logins GROUP BY DATE(date)"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = "Date\tLogin Count\n";
        foreach ($data as $row) {
            $content .= implode("\t", $row) . "\n";
        }
        outputReport('User Login Count Report', $content);
        break;

    case 'thesis_activity':
        // Query and generate report for thesis activity
        $query = "SELECT thesis_id, COUNT(*) as download_count, COUNT(DISTINCT view_id) as view_count FROM thesis_activity GROUP BY thesis_id"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = "Thesis ID\tDownload Count\tView Count\n";
        foreach ($data as $row) {
            $content .= implode("\t", $row) . "\n";
        }
        outputReport('Thesis Activity Report', $content);
        break;

    case 'thesis_yearly':
        // Query and generate report for number of theses per year
        $query = "SELECT YEAR(date_added) as year, COUNT(*) as thesis_count FROM theses GROUP BY YEAR(date_added)"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = "Year\tThesis Count\n";
        foreach ($data as $row) {
            $content .= implode("\t", $row) . "\n";
        }
        outputReport('Thesis per Year Report', $content);
        break;

    case 'thesis_faculty':
        // Query and generate report for number of theses per faculty
        $query = "SELECT faculty, COUNT(*) as thesis_count FROM theses GROUP BY faculty"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = "Faculty\tThesis Count\n";
        foreach ($data as $row) {
            $content .= implode("\t", $row) . "\n";
        }
        outputReport('Thesis per Faculty Report', $content);
        break;

    case 'total_theses':
        // Query and generate report for total number of theses
        $query = "SELECT COUNT(*) as total_theses FROM theses"; // Adjust according to your schema
        $stmt = $pdo->query($query);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $content = "Total Theses: " . $data['total_theses'];
        outputReport('Total Number of Theses Report', $content);
        break;

    default:
        echo "Invalid report type.";
        break;
}
?>
