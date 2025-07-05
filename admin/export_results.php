<?php
session_start();
require '../config/db.php';

// Ensure only authorized admins can access this script
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized Access');
}

// Handle filtering
$filter_exam_id = $_GET['exam_id'] ?? 'all';
$query_conditions = "";
$params = [];

if ($filter_exam_id !== 'all' && is_numeric($filter_exam_id)) {
    $query_conditions = "WHERE r.exam_id = ?";
    $params[] = $filter_exam_id;
}

// Fetch results based on the filter
$results_stmt = $pdo->prepare("
    SELECT u.email, e.title, r.score, e.total_marks, r.submitted_at
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN exams e ON r.exam_id = e.id
    $query_conditions
    ORDER BY r.submitted_at DESC
");
$results_stmt->execute($params);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
$filename = "exam_results_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '";');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the CSV header row
fputcsv($output, ['Student Email', 'Exam Title', 'Score', 'Total Marks', 'Submitted At']);

// Write the data rows to the CSV file
if (!empty($results)) {
    foreach ($results as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>
