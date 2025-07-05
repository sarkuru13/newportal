<?php
require_once '../includes/student_header.php';

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    header('Location: dashboard.php');
    exit;
}
$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['student_id'];

// Check if student has already submitted this exam
$result_stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND exam_id = ?");
$result_stmt->execute([$student_id, $exam_id]);
if ($result_stmt->fetch()) {
    echo "<div class='alert alert-warning text-center'>You have already completed this exam. <a href='dashboard.php'>Go to Dashboard</a></div>";
    require_once '../includes/student_footer.php';
    exit;
}

// Fetch exam details
$exam_stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam_title = $exam_stmt->fetchColumn();

// Fetch exam questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($questions)) {
    echo "<div class='alert alert-warning'>This exam has no questions yet. Please try again later.</div>";
    require_once '../includes/student_footer.php';
    exit;
}

// Fetch any saved progress
$progress_stmt = $pdo->prepare("SELECT answers FROM exam_progress WHERE student_id = ? AND exam_id = ?");
$progress_stmt->execute([$student_id, $exam_id]);
$saved_answers = $progress_stmt->fetchColumn();
$saved_answers_decoded = $saved_answers ? json_decode($saved_answers, true) : [];
?>
<link rel="stylesheet" href="assets/css/exam.css">

<div class="container-fluid exam-container">
    <!-- Initial Instructions Screen -->
    <div id="instructionScreen" class="card shadow-lg">
        <div class="card-header text-center bg-primary text-white">
            <h2 class="my-2">Instructions for <?php echo htmlspecialchars($exam_title); ?></h2>
        </div>
        <div class="card-body p-4">
            <h4 class="card-title">Please read the following instructions carefully:</h4>
            <ul>
                <li>This exam consists of <strong><?php echo count($questions); ?> questions</strong>.</li>
                <li>Each question has only one correct answer.</li>
                <li>Your progress will be saved automatically every 3 seconds.</li>
                <li>Do not switch tabs, open new windows, or try to copy/paste text. These actions will be reported.</li>
            </ul>
            <hr>
            <h5>Palette Legend:</h5>
            <div class="d-flex justify-content-around my-3">
                <div><span class="palette-legend answered"></span> Answered</div>
                <div><span class="palette-legend visited"></span> Visited (Not Answered)</div>
                <div><span class="palette-legend not-visited"></span> Not Visited</div>
            </div>
            <hr>
            <div class="text-center">
                <p class="fs-5">All the best for your exam!</p>
                <button class="btn btn-lg btn-success" id="startExamBtn">Start Exam</button>
            </div>
        </div>
    </div>

    <!-- Main Exam Interface (Initially hidden) -->
    <div class="row d-none" id="examInterface">
        <!-- Questions Column -->
        <div class="col-md-8">
            <form id="examForm">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="card question-card mb-4 d-none" id="q-card-<?php echo $index; ?>">
                        <div class="card-header">
                            <strong>Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></strong>
                            <span class="float-end">Marks: <?php echo $question['marks']; ?></span>
                        </div>
                        <div class="card-body">
                            <p class="card-text fs-5"><?php echo htmlspecialchars($question['question']); ?></p>
                            <?php $options = json_decode($question['options'], true); ?>
                            <?php foreach ($options as $option_key => $option): ?>
                                <div class="form-check fs-5 mb-2">
                                    <input class="form-check-input" type="radio" 
                                           name="answers[<?php echo $question['id']; ?>]" 
                                           id="q-<?php echo $question['id']; ?>-opt-<?php echo $option_key; ?>" 
                                           value="<?php echo htmlspecialchars($option); ?>"
                                           <?php echo (isset($saved_answers_decoded[$question['id']]) && $saved_answers_decoded[$question['id']] === $option) ? 'checked' : ''; ?>
                                           onchange="updatePalette(<?php echo $index; ?>, true)">
                                    <label class="form-check-label" for="q-<?php echo $question['id']; ?>-opt-<?php echo $option_key; ?>">
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="navigateQuestion(-1)" disabled>
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <div>
                        <span id="saveStatus" class="text-muted fst-italic"></span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="navigateQuestion(1)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="btn btn-success" id="submitBtn" onclick="submitExam()" style="display: none;">
                            <i class="fas fa-check-circle"></i> Submit Exam
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Palette and Proctoring Column -->
        <div class="col-md-4">
            <div class="position-sticky" style="top: 2rem;">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Question Palette</h5>
                    </div>
                    <div class="card-body" id="question-palette">
                        <?php foreach ($questions as $index => $question): ?>
                            <button type="button" class="btn btn-outline-secondary palette-btn m-1 not-visited" id="palette-btn-<?php echo $index; ?>" onclick="showQuestion(<?php echo $index; ?>)">
                                <?php echo $index + 1; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Proctoring Violation Modal -->
<div class="modal fade" id="violationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="violationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger border-3">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="violationModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Warning: Exam Violation Detected</h5>
      </div>
      <div class="modal-body fs-5">
        <p id="violationMessage">You have switched tabs or windows. This action is not permitted during the exam and has been reported.</p>
        <p class="text-danger fw-bold">Further violations may result in disqualification.</p>
      </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">I Understand</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const examForm = document.getElementById('examForm');
        const questions = document.querySelectorAll('.question-card');
        const totalQuestions = questions.length;
        let currentQuestionIndex = 0;
        const violationModal = new bootstrap.Modal(document.getElementById('violationModal'));
        const violationMessageEl = document.getElementById('violationMessage');
        const startExamBtn = document.getElementById('startExamBtn');
        const saveStatusEl = document.getElementById('saveStatus');

        window.showQuestion = function(index) {
            if (index < 0 || index >= totalQuestions) return;
            questions.forEach(q => q.classList.add('d-none'));
            questions[index].classList.remove('d-none');
            currentQuestionIndex = index;
            updatePalette(index, false); // Mark as visited
            updateNavButtons();
        }

        window.navigateQuestion = function(direction) {
            showQuestion(currentQuestionIndex + direction);
        }

        function updateNavButtons() {
            document.getElementById('prevBtn').disabled = (currentQuestionIndex === 0);
            document.getElementById('nextBtn').style.display = (currentQuestionIndex === totalQuestions - 1) ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = (currentQuestionIndex === totalQuestions - 1) ? 'inline-block' : 'none';
        }

        window.updatePalette = function(index, isAnswered) {
            const btn = document.getElementById(`palette-btn-${index}`);
            if (isAnswered) {
                btn.className = 'btn palette-btn m-1 answered';
            } else if (!btn.classList.contains('answered')) {
                btn.className = 'btn palette-btn m-1 visited';
            }
        }

        function saveProgress() {
            const formData = new FormData(examForm);
            saveStatusEl.textContent = 'Saving...';
            saveStatusEl.classList.remove('text-success', 'text-danger');
            
            fetch('save_progress.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    const now = new Date();
                    saveStatusEl.textContent = `Progress saved at ${now.toLocaleTimeString()}`;
                    saveStatusEl.classList.add('text-success');
                } else {
                    saveStatusEl.textContent = 'Failed to save progress.';
                    saveStatusEl.classList.add('text-danger');
                }
            })
            .catch(err => {
                console.error('Failed to save progress:', err);
                saveStatusEl.textContent = 'Save error. Check connection.';
                saveStatusEl.classList.add('text-danger');
            });
        }

        window.submitExam = function() {
            if (confirm('Are you sure you want to submit the exam? You cannot make any changes after this.')) {
                saveProgress();
                const formData = new FormData(examForm);
                fetch('submit_exam.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(`Exam submitted successfully! Your score is: ${data.score}`);
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('An error occurred: ' + (data.message || 'Unknown error.'));
                    }
                });
            }
        }
        
        function reportViolation(type) {
            violationMessageEl.textContent = `Violation Detected: ${type}. This action is not permitted and has been reported.`;
            violationModal.show();
            const examId = document.querySelector('input[name="exam_id"]').value;
            fetch('../report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ exam_id: examId, violation_type: type })
            });
        }

        function startExam() {
            document.getElementById('instructionScreen').classList.add('d-none');
            document.getElementById('examInterface').classList.remove('d-none');
            
            // Activate security features
            document.body.classList.add('exam-in-progress');
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') reportViolation('Tab/Window Switched');
            });
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('copy', e => { e.preventDefault(); reportViolation('Copy Attempted'); });
            document.addEventListener('paste', e => { e.preventDefault(); reportViolation('Paste Attempted'); });
            document.addEventListener('cut', e => { e.preventDefault(); reportViolation('Cut Attempted'); });

            // Initial setup
            showQuestion(0);
            
            // This loop now correctly initializes the palette based on saved answers
            <?php foreach ($questions as $index => $question): ?>
                <?php if (isset($saved_answers_decoded[$question['id']])): ?>
                    updatePalette(<?php echo $index; ?>, true);
                <?php endif; ?>
            <?php endforeach; ?>
            
            setInterval(saveProgress, 3000);
        }

        if (startExamBtn) {
            startExamBtn.addEventListener('click', startExam);
        }
    });
</script>

<?php require_once '../includes/student_footer.php'; ?>
