<?php
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $message = '<div class="alert alert-danger">Email already exists. Please choose a different one.</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
        $stmt->execute([$email, $password]);
        $user_id = $pdo->lastInsertId();

        $code = rand(100000, 999999);
        // Generates a timestamp 10 minutes in the future in UTC
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $code, $expires]);

        $mail = new PHPMailer(true);
        try {
            // SMTP configuration...
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
            $mail->Body = "Dear User,<br><br>Thank you for registering. Your verification code is: <b>$code</b><br><br>This code will expire in 10 minutes.<br><br>Best regards,<br>The Exam Portal Team";
            $mail->send();
            header('Location: verify.php?email=' . urlencode($email));
            exit;
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Mail error: ' . $mail->ErrorInfo . '</div>';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Create Your Account</h2>
                    <?php if(!empty($message)) echo $message; ?>
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
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
