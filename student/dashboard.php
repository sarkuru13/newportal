<?php
require_once '../includes/student_header.php';

// Fetch student's name
$student_name = htmlspecialchars(explode('@', $student_email)[0]);

// Fetch exams the student has already taken
$completed_exams_stmt = $pdo->prepare("SELECT exam_id FROM results WHERE student_id = ?");
$completed_exams_stmt->execute([$_SESSION['student_id']]);
$completed_exam_ids = $completed_exams_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Fetch available exams and count their questions
$stmt = $pdo->query("
    SELECT e.id, e.title, e.total_questions, e.total_marks, COUNT(q.id) as question_count
    FROM exams e
    LEFT JOIN questions q ON e.id = q.exam_id
    GROUP BY e.id
");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student's results for the chart
$results_stmt = $pdo->prepare("
    SELECT e.title, r.score, e.total_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at
");
$results_stmt->execute([$_SESSION['student_id']]);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
$results_json = json_encode($results);
?>

<div class="container mt-4">
    <div class="p-5 mb-4 bg-light rounded-3 shadow-sm">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold">Welcome, <?php echo ucfirst($student_name); ?>!</h1>
            <p class="col-md-8 fs-4">Ready to test your knowledge? Choose an exam below to get started.</p>
        </div>
    </div>

    <h2 class="mb-4">Available Exams</h2>
    <div class="row g-4">
        <?php if (empty($exams)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No exams are available at the moment. Please check back later.</div>
            </div>
        <?php else: ?>
            <?php foreach ($exams as $exam): 
                $is_ready = $exam['question_count'] > 0;
                $has_completed = in_array($exam['id'], $completed_exam_ids);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card exam-card h-100 text-center shadow-sm <?php echo !$is_ready || $has_completed ? 'exam-disabled' : ''; ?>">
                        <div class="card-body d-flex flex-column">
                             <div class="mb-3">
                                <i class="fas fa-file-alt card-icon"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo $exam['total_questions']; ?> Questions | <?php echo $exam['total_marks']; ?> Marks
                            </p>
                            <div class="mt-auto">
                                <?php if ($has_completed): ?>
                                    <button class="btn btn-success" disabled><i class="fas fa-check-circle me-2"></i>Attempted</button>
                                <?php elseif ($is_ready): ?>
                                    <a href="exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary">Start Exam</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Not Yet Started</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr class="my-5">

    <h2 class="mb-4">Your Performance History</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($results)): ?>
                <p class="text-center text-muted">You have not completed any exams yet. Your results will appear here.</p>
            <?php else: ?>
                <canvas id="resultsChart" style="height: 300px;"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsData = <?php echo $results_json; ?>;
    if (resultsData.length > 0) {
        const labels = resultsData.map(r => r.title);
        const scores = resultsData.map(r => (r.score / r.total_marks * 100).toFixed(2));
        const ctx = document.getElementById('resultsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score (%)',
                    data: scores,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } },
            }
        });
    }
});
</script>

<?php require_once '../includes/student_footer.php'; ?>
