<?php
require_once '../includes/admin_header.php';

// Check for a valid exam ID in the URL
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    echo "<div class='alert alert-danger'>Invalid exam ID. <a href='manage_exams.php' class='alert-link'>Go back to exams</a>.</div>";
    require_once '../includes/admin_footer.php';
    exit;
}
$exam_id = $_GET['exam_id'];

// Fetch exam details to ensure it exists and to display its title
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam = $exam_stmt->fetch();
if (!$exam) {
    echo "<div class='alert alert-danger'>Exam not found. <a href='manage_exams.php' class='alert-link'>Go back to exams</a>.</div>";
    require_once '../includes/admin_footer.php';
    exit;
}

// This block will handle all form submissions (Create, Update, Delete, Bulk Add)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_question' || $action === 'update_question') {
        $question_text = $_POST['question'] ?? '';
        $options = isset($_POST['option1']) ? json_encode([$_POST['option1'], $_POST['option2'], $_POST['option3'], $_POST['option4']]) : '[]';
        $correct_answer = $_POST['correct_answer'] ?? '';
        $marks = $_POST['marks'] ?? 0;
        $question_id = $_POST['question_id'] ?? 0;

        if ($action === 'add_question') {
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$exam_id, $question_text, $options, $correct_answer, $marks])) {
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Question added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to add question. Please try again. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        } elseif ($action === 'update_question') {
            $stmt = $pdo->prepare("UPDATE questions SET question = ?, options = ?, correct_answer = ?, marks = ? WHERE id = ?");
            if ($stmt->execute([$question_text, $options, $correct_answer, $marks, $question_id])) {
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Question updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to update question. Please try again. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    } elseif ($action === 'bulk_add_questions') {
        $json_data = $_POST['questions_json'] ?? '';
        $questions_to_add = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid JSON format. Please check your syntax. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } elseif (!is_array($questions_to_add)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">JSON must be an array of question objects. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $pdo->beginTransaction();
            $added_count = 0;
            $error_count = 0;
            try {
                $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?)");
                foreach ($questions_to_add as $q) {
                    // Basic validation for each question object
                    if (isset($q['question'], $q['options'], $q['correct_answer'], $q['marks']) && is_array($q['options']) && count($q['options']) === 4) {
                        $stmt->execute([
                            $exam_id,
                            $q['question'],
                            json_encode($q['options']),
                            $q['correct_answer'],
                            $q['marks']
                        ]);
                        $added_count++;
                    } else {
                        $error_count++;
                    }
                }
                if ($error_count > 0) {
                     // Rollback if any question is invalid to ensure atomicity
                    throw new Exception("$error_count questions had an invalid format and were skipped. The entire batch was rolled back.");
                }
                $pdo->commit();
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Successfully added ' . $added_count . ' questions! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">An error occurred during bulk import: ' . $e->getMessage() . ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    }
}

// Handle GET requests for deleting questions
if (isset($_GET['action']) && $_GET['action'] === 'delete_question' && isset($_GET['id'])) {
    $question_id_to_delete = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    if ($stmt->execute([$question_id_to_delete])) {
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Question deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Failed to delete question. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Re-fetch all questions for the exam after any potential CRUD operation
$questions_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
$questions_stmt->execute([$exam_id]);
$questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="manage_exams.php" class="btn btn-outline-secondary mb-2"><i class="fas fa-arrow-left"></i> Back to Exams</a>
            <h3 class="m-0">Questions for "<?php echo htmlspecialchars($exam['title']); ?>"</h3>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkQuestionModal">
                <i class="fas fa-file-import me-2"></i>Bulk Add via JSON
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" onclick="prepareCreateQuestionForm()">
                <i class="fas fa-plus me-2"></i>Add New Question
            </button>
        </div>
    </div>

    <?php if (!empty($message)) echo $message; // Display success or error messages ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <p class="text-center text-muted py-4">No questions have been added for this exam yet. Click "Add New Question" to get started!</p>
            <?php else: ?>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong class="pe-3">Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($q['question']); ?></strong>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick='prepareEditQuestionForm(<?php echo json_encode($q, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="manage_questions.php?exam_id=<?php echo $exam_id; ?>&action=delete_question&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this question?');"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </div>
                        <ul class="list-unstyled mt-2 ms-4">
                            <?php 
                            $options_array = json_decode($q['options'], true);
                            if (is_array($options_array)):
                                foreach ($options_array as $option): ?>
                                    <li>
                                        <?php echo htmlspecialchars($option); ?>
                                        <?php if ($option == $q['correct_answer']): ?>
                                            <i class="fas fa-check-circle text-success ms-2" title="Correct Answer"></i>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; 
                            endif;
                            ?>
                        </ul>
                        <small class="text-muted ms-4">Marks: <?php echo $q['marks']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Single Question Modal for Create/Edit -->
<div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="questionModalLabel">Add New Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="questionForm" method="POST" action="manage_questions.php?exam_id=<?php echo $exam_id; ?>">
          <div class="modal-body">
                <input type="hidden" name="action" id="question_action" value="add_question">
                <input type="hidden" name="question_id" id="question_id" value="">
                <div class="mb-3">
                    <label for="question_text" class="form-label">Question Text</label>
                    <textarea name="question" id="question_text" class="form-control" rows="3" required></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Option 1</label><input type="text" name="option1" id="option1" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Option 2</label><input type="text" name="option2" id="option2" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Option 3</label><input type="text" name="option3" id="option3" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Option 4</label><input type="text" name="option4" id="option4" class="form-control" required></div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-8">
                         <label for="correct_answer" class="form-label">Correct Answer</label>
                         <input type="text" name="correct_answer" id="correct_answer" class="form-control" required placeholder="Enter the exact text of the correct option">
                    </div>
                     <div class="col-md-4">
                        <label for="marks" class="form-label">Marks</label>
                        <input type="number" name="marks" id="marks" class="form-control" required>
                    </div>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Question</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Bulk Question Modal -->
<div class="modal fade" id="bulkQuestionModal" tabindex="-1" aria-labelledby="bulkQuestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkQuestionModalLabel">Bulk Add Questions via JSON</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="manage_questions.php?exam_id=<?php echo $exam_id; ?>">
          <div class="modal-body">
                <input type="hidden" name="action" value="bulk_add_questions">
                <div class="mb-3">
                    <label for="questions_json" class="form-label">Paste JSON here</label>
                    <textarea name="questions_json" id="questions_json" class="form-control" rows="15" required placeholder="Paste an array of question objects here..."></textarea>
                </div>
                <div class="alert alert-info">
                    <strong>JSON Format Example:</strong>
                    <pre class="mb-0">[
    {
        "question": "What is 2 + 2?",
        "options": ["3", "4", "5", "6"],
        "correct_answer": "4",
        "marks": 5
    },
    {
        "question": "What is the capital of France?",
        "options": ["London", "Berlin", "Paris", "Madrid"],
        "correct_answer": "Paris",
        "marks": 10
    }
]</pre>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Import Questions</button>
          </div>
      </form>
    </div>
  </div>
</div>


<script>
const questionModalElement = document.getElementById('questionModal');
const questionModal = new bootstrap.Modal(questionModalElement);
const questionForm = document.getElementById('questionForm');

// Function to prepare the modal for creating a new question
function prepareCreateQuestionForm() {
    questionForm.reset();
    document.getElementById('questionModalLabel').textContent = 'Add New Question';
    document.getElementById('question_action').value = 'add_question';
    document.getElementById('question_id').value = '';
}

// Function to populate the modal with data for editing an existing question
function prepareEditQuestionForm(questionData) {
    questionForm.reset();
    document.getElementById('questionModalLabel').textContent = 'Edit Question';
    document.getElementById('question_action').value = 'update_question';
    
    document.getElementById('question_id').value = questionData.id;
    document.getElementById('question_text').value = questionData.question;
    
    try {
        const options = JSON.parse(questionData.options);
        document.getElementById('option1').value = options[0] || '';
        document.getElementById('option2').value = options[1] || '';
        document.getElementById('option3').value = options[2] || '';
        document.getElementById('option4').value = options[3] || '';
    } catch (e) {
        console.error("Could not parse options JSON: ", questionData.options);
    }
    
    document.getElementById('correct_answer').value = questionData.correct_answer;
    document.getElementById('marks').value = questionData.marks;
    
    questionModal.show();
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
