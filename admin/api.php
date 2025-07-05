<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create_exam':
    case 'update_exam':
        handle_save_exam();
        break;
    case 'get_exam':
        handle_get_exam();
        break;
    case 'delete_exam':
        handle_delete_exam();
        break;
    case 'add_question':
    case 'update_question':
        handle_save_question();
        break;
    case 'get_question':
        handle_get_question();
        break;
    case 'delete_question':
        handle_delete_question();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

function handle_save_exam() {
    global $pdo;
    $action = $_POST['action'];
    $title = $_POST['title'];
    $total_questions = $_POST['total_questions'];
    $total_marks = $_POST['total_marks'];

    if ($action === 'create_exam') {
        $stmt = $pdo->prepare("INSERT INTO exams (title, total_questions, total_marks) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $total_questions, $total_marks])) {
            echo json_encode(['status' => 'success', 'message' => 'Exam created successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create exam.']);
        }
    } elseif ($action === 'update_exam') {
        $exam_id = $_POST['exam_id'];
        $stmt = $pdo->prepare("UPDATE exams SET title = ?, total_questions = ?, total_marks = ? WHERE id = ?");
        if ($stmt->execute([$title, $total_questions, $total_marks, $exam_id])) {
            echo json_encode(['status' => 'success', 'message' => 'Exam updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update exam.']);
        }
    }
}

function handle_get_exam() {
    global $pdo;
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exam) {
        echo json_encode(['status' => 'success', 'exam' => $exam]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Exam not found.']);
    }
}

function handle_delete_exam() {
    global $pdo;
    $exam_id = $_POST['exam_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM results WHERE exam_id = ?")->execute([$exam_id]);
        $pdo->prepare("DELETE FROM exam_progress WHERE exam_id = ?")->execute([$exam_id]);
        $pdo->prepare("DELETE FROM questions WHERE exam_id = ?")->execute([$exam_id]);
        $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$exam_id]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Exam and all related data deleted.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete exam: ' . $e->getMessage()]);
    }
}

function handle_save_question() {
    global $pdo;
    $action = $_POST['action'];
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question'];
    $options = json_encode([$_POST['option1'], $_POST['option2'], $_POST['option3'], $_POST['option4']]);
    $correct_answer = $_POST['correct_answer'];
    $marks = $_POST['marks'];

    if ($action === 'add_question') {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$exam_id, $question_text, $options, $correct_answer, $marks])) {
            echo json_encode(['status' => 'success', 'message' => 'Question added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add question.']);
        }
    } elseif ($action === 'update_question') {
        $question_id = $_POST['question_id'];
        $stmt = $pdo->prepare("UPDATE questions SET question = ?, options = ?, correct_answer = ?, marks = ? WHERE id = ? AND exam_id = ?");
        if ($stmt->execute([$question_text, $options, $correct_answer, $marks, $question_id, $exam_id])) {
            echo json_encode(['status' => 'success', 'message' => 'Question updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update question.']);
        }
    }
}

function handle_get_question() {
    global $pdo;
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($question) {
        echo json_encode(['status' => 'success', 'question' => $question]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Question not found.']);
    }
}

function handle_delete_question() {
    global $pdo;
    $question_id = $_POST['question_id'];
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    if ($stmt->execute([$question_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Question deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete question.']);
    }
}
?>
