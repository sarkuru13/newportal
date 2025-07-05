<?php
require_once '../includes/admin_header.php';

// Fetch stats for the dashboard cards
$total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_results = $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();

// Fetch data for the student performance chart
$results_stmt = $pdo->query("
    SELECT e.title, AVG(r.score / e.total_marks * 100) as avg_percentage
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    GROUP BY r.exam_id
    ORDER BY e.title
");
$chart_data = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
$chart_json = json_encode($chart_data);

// Fetch recent exam results
$recent_results_stmt = $pdo->query("
    SELECT r.id, u.email, e.title, r.score, e.total_marks, r.submitted_at
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN exams e ON r.exam_id = e.id
    ORDER BY r.submitted_at DESC
    LIMIT 5
");
$recent_results = $recent_results_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card border-primary shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-muted">Total Exams</h5>
                        <h2 class="mb-0 display-5 fw-bold"><?php echo $total_exams; ?></h2>
                    </div>
                    <i class="fas fa-book-open fa-3x text-primary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card border-success shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-muted">Total Questions</h5>
                        <h2 class="mb-0 display-5 fw-bold"><?php echo $total_questions; ?></h2>
                    </div>
                    <i class="fas fa-question-circle fa-3x text-success opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card border-info shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-muted">Total Students</h5>
                        <h2 class="mb-0 display-5 fw-bold"><?php echo $total_students; ?></h2>
                    </div>
                    <i class="fas fa-user-graduate fa-3x text-info opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card border-warning shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-muted">Submissions</h5>
                        <h2 class="mb-0 display-5 fw-bold"><?php echo $total_results; ?></h2>
                    </div>
                    <i class="fas fa-poll fa-3x text-warning opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Average Exam Performance (%)</h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($recent_results)): ?>
                            <p class="text-center text-muted p-3">No recent submissions.</p>
                        <?php else: ?>
                            <?php foreach ($recent_results as $result): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($result['email']); ?></strong>
                                        <small class="d-block text-muted"><?php echo htmlspecialchars($result['title']); ?></small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $result['score'] . ' / ' . $result['total_marks']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = <?php echo $chart_json; ?>;
    if (chartData.length > 0) {
        const labels = chartData.map(d => d.title);
        const data = chartData.map(d => d.avg_percentage);

        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Score (%)',
                    data: data,
                    backgroundColor: 'rgba(13, 110, 253, 0.6)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
