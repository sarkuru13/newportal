<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $exam_id = $_POST['exam_id'];
    $answers = json_encode($_POST['answers'] ?? []);

    // Use INSERT ... ON DUPLICATE KEY UPDATE to save or update progress
    $stmt = $pdo->prepare("
        INSERT INTO exam_progress (student_id, exam_id, answers, last_updated)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE answers = ?, last_updated = NOW()
    ");
    $stmt->execute([$student_id, $exam_id, $answers, $answers]);
    
    echo json_encode(['status' => 'success']);
}
?>
