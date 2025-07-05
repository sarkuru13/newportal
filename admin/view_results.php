<?php
require_once '../includes/admin_header.php';

// Fetch all exams for the filter dropdown
$exams_stmt = $pdo->query("SELECT id, title FROM exams ORDER BY title");
$exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle filtering by exam
$filter_exam_id = $_GET['exam_id'] ?? 'all';
$query_conditions = "";
$params = [];

if ($filter_exam_id !== 'all' && is_numeric($filter_exam_id)) {
    $query_conditions = "WHERE r.exam_id = ?";
    $params[] = $filter_exam_id;
}

// Fetch results based on the current filter
$results_stmt = $pdo->prepare("
    SELECT r.id, u.email, e.title, r.score, e.total_marks, r.submitted_at
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN exams e ON r.exam_id = e.id
    $query_conditions
    ORDER BY r.submitted_at DESC
");
$results_stmt->execute($params);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="m-0">Student Results</h3>
        <form action="export_results.php" method="GET">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($filter_exam_id); ?>">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
        </form>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="exam_id" class="form-label">Filter by Exam</label>
                    <select name="exam_id" id="exam_id" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Exams</option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?php echo $exam['id']; ?>" <?php echo ($filter_exam_id == $exam['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-4 d-flex align-items-end">
                    <a href="view_results.php" class="btn btn-outline-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Student Email</th>
                            <th>Exam Title</th>
                            <th>Score</th>
                            <th>Total Marks</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr><td colspan="5" class="text-center py-4">No results found for the selected filter.</td></tr>
                        <?php else: ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['email']); ?></td>
                                    <td><?php echo htmlspecialchars($result['title']); ?></td>
                                    <td><?php echo $result['score']; ?></td>
                                    <td><?php echo $result['total_marks']; ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($result['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
