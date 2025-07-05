<?php
require_once '../includes/student_header.php';

// Fetch all results for the logged-in student
$stmt = $pdo->prepare("
    SELECT 
        e.title, 
        r.score, 
        e.total_marks,
        r.submitted_at
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at DESC
");
$stmt->execute([$_SESSION['student_id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the passing percentage
$passing_percentage = 40;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">My Exam History</h2>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Exam Title</th>
                            <th>Your Score</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th class="text-center">Status</th>
                            <th>Date Attempted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="alert alert-info text-center my-3">You have not attempted any exams yet. Your history will appear here once you complete an exam.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $index => $result): 
                                $percentage = ($result['total_marks'] > 0) ? round(($result['score'] / $result['total_marks']) * 100, 2) : 0;
                                $status = ($percentage >= $passing_percentage) ? 'Pass' : 'Fail';
                                $status_class = ($status === 'Pass') ? 'bg-success' : 'bg-danger';
                                $progress_bar_class = ($status === 'Pass') ? 'bg-success' : 'bg-danger';
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($result['title']); ?></td>
                                    <td><?php echo $result['score']; ?></td>
                                    <td><?php echo $result['total_marks']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar <?php echo $progress_bar_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <strong><?php echo $percentage; ?>%</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $status_class; ?> fs-6"><?php echo $status; ?></span>
                                    </td>
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

<?php require_once '../includes/student_footer.php'; ?>
