<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $total_questions = $_POST['total_questions'];
    $total_marks = $_POST['total_marks'];

    $stmt = $pdo->prepare("INSERT INTO exams (title, total_questions, total_marks) VALUES (?, ?, ?)");
    $stmt->execute([$title, $total_questions, $total_marks]);
    header('Location: dashboard.php');
}
?>

<?php include '../includes/header.php'; ?>
<h2 class="mb-4">Create Exam</h2>
<form method="POST" class="card p-4">
    <div class="mb-3">
        <label class="form-label">Exam Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Total Questions</label>
        <input type="number" name="total_questions" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Total Marks</label>
        <input type="number" name="total_marks" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Create Exam</button>
</form>
<?php include '../includes/footer.php'; ?>