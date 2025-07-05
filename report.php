<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    $exam_id = $input['exam_id'] ?? null;
    $violation_type = $input['violation_type'] ?? 'unknown';

    if ($exam_id) {
        try {
            // Create the violations table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS exam_violations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    exam_id INT NOT NULL,
                    violation_type VARCHAR(255) NOT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
                )
            ");

            $stmt = $pdo->prepare("INSERT INTO exam_violations (student_id, exam_id, violation_type) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $exam_id, $violation_type]);
            echo json_encode(['status' => 'success', 'message' => 'Violation reported.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Exam ID not provided.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or invalid request.']);
}
?>
