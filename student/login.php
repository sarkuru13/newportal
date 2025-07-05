<?php
session_start();
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!$user['verified']) {
            $code = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Delete any existing codes for this user
            $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Insert the new code
            $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $code, $expires]);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'engtisarkuru13@gmail.com';
                $mail->Password = 'cpqs qqei cvve uobm';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('engtisarkuru13@gmail.com', 'Exam Portal');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email Address';
                $mail->Body = "Dear User,<br><br>Your new verification code is: <b>$code</b><br><br>This code will expire in 10 minutes.<br><br>Best regards,<br>The Exam Portal Team";
                $mail->send();
                header('Location: verify.php?email=' . urlencode($email) . '&resent=true');
                exit;
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Mail error: ' . $mail->ErrorInfo . '</div>';
            }
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['student_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $message = '<div class="alert alert-danger">Invalid email or password.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid email or password.</div>';
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Login to Your Account</h2>
                    <?php echo $message; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>