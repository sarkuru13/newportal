<?php
require_once '../includes/admin_header.php';

// Handle form submission for creating a new exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {
    $title = $_POST['title'];
    $total_questions = $_POST['total_questions'];
    $total_marks = $_POST['total_marks'];

    $stmt = $pdo->prepare("INSERT INTO exams (title, total_questions, total_marks) VALUES (?, ?, ?)");
    if ($stmt->execute([$title, $total_questions, $total_marks])) {
        echo "<script>alert('Exam created successfully!'); window.location.href='manage_exams.php';</script>";
    } else {
        echo "<script>alert('Failed to create exam.');</script>";
    }
}

// Fetch all exams to display in the table
$exams_stmt = $pdo->query("SELECT e.*, COUNT(q.id) as question_count FROM exams e LEFT JOIN questions q ON e.id = q.exam_id GROUP BY e.id ORDER BY e.created_at DESC");
$exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="m-0">Exam List</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
            <i class="fas fa-plus me-2"></i>Create New Exam
        </button>
    </div>

    <!-- Exams List Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Questions Added</th>
                            <th>Total Marks</th>
                            <th>Created On</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr><td colspan="6" class="text-center py-4">No exams found. Create one to get started!</td></tr>
                        <?php else: ?>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td>#<?php echo $exam['id']; ?></td>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo $exam['question_count'] . ' / ' . $exam['total_questions']; ?></td>
                                    <td><?php echo $exam['total_marks']; ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($exam['created_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="manage_questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info" title="Manage Questions"><i class="fas fa-list-ul"></i> Questions</a>
                                        <button onclick="deleteExam(<?php echo $exam['id']; ?>)" class="btn btn-sm btn-danger" title="Delete Exam"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="createExamModal" tabindex="-1" aria-labelledby="createExamModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createExamModalLabel">Create New Exam</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Exam Title</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="total_questions" class="form-label">Total Questions</label>
                    <input type="number" name="total_questions" id="total_questions" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="total_marks" class="form-label">Total Marks</label>
                    <input type="number" name="total_marks" id="total_marks" class="form-control" required>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="create_exam" class="btn btn-primary">Create Exam</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function deleteExam(examId) {
    if (confirm('Are you sure you want to delete this exam? This will also delete all associated questions and results. This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete_exam');
        formData.append('exam_id', examId);

        fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => alert('An error occurred.'));
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
