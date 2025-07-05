<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $question = $json['question'];
    $options = json_encode($json['options']);
    $correct_answer = $json['correct_answer'];
    $marks = $json['marks'];
    $exam_id = $json['exam_id'];

    $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$exam_id, $question, $options, $correct_answer, $marks]);
    echo json_encode(['status' => 'success']);
}
?>

<?php include '../includes/header.php'; ?>
<h2 class="mb-4">Upload Questions</h2>
<form id="questionForm" class="card p-4">
    <div class="mb-3">
        <label class="form-label">Exam ID</label>
        <input type="number" id="exam_id" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Question</label>
        <input type="text" id="question" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Options (JSON format)</label>
        <textarea id="options" class="form-control"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Correct Answer</label>
        <input type="text" id="correct_answer" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Marks</label>
        <input type="number" id="marks" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Upload</button>
</form>
<script>
document.getElementById('questionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = {
        exam_id: document.getElementById('exam_id').value,
        question: document.getElementById('question').value,
        options: JSON.parse(document.getElementById('options').value),
        correct_answer: document.getElementById('correct_answer').value,
        marks: document.getElementById('marks').value
    };
    fetch('upload_questions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => res.json()).then(data => alert(data.status));
});
</script>
<?php include '../includes/footer.php'; ?>