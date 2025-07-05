<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or not logged in.']);
    exit;
}

try {
    $student_id = $_SESSION['student_id'];
    $exam_id = $_POST['exam_id'] ?? null;
    $answers = $_POST['answers'] ?? [];

    if (!$exam_id) {
        throw new Exception("Exam ID is missing.");
    }

    // Fetch all questions for the exam to get correct answers and marks
    $stmt = $pdo->prepare("SELECT id, correct_answer, marks FROM questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $questions_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map of question_id => {correct_answer, marks} for easy lookup
    $questions_map = [];
    foreach ($questions_data as $q) {
        $questions_map[$q['id']] = $q;
    }

    // Calculate the score
    $score = 0;
    if (!empty($answers)) {
        foreach ($answers as $question_id => $student_answer) {
            if (isset($questions_map[$question_id]) && $student_answer === $questions_map[$question_id]['correct_answer']) {
                $score += (int)$questions_map[$question_id]['marks'];
            }
        }
    }

    // Insert the final result into the database
    $stmt = $pdo->prepare("INSERT INTO results (student_id, exam_id, score) VALUES (?, ?, ?)");
    $stmt->execute([$student_id, $exam_id, $score]);

    // Clean up the saved progress for this exam
    $stmt = $pdo->prepare("DELETE FROM exam_progress WHERE student_id = ? AND exam_id = ?");
    $stmt->execute([$student_id, $exam_id]);

    echo json_encode(['status' => 'success', 'score' => $score]);

} catch (Exception $e) {
    // Return a specific error message if something goes wrong
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
