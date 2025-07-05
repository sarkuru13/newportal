<?php
require '../config/db.php';

$message = '';
$email = $_GET['email'] ?? '';

if (isset($_GET['resent']) && $_GET['resent'] === 'true') {
    $message = '<div class="alert alert-success">A new verification code has been sent to your email.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $code = $_POST['code'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // This query now works correctly because of the timezone fix
        $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
        $stmt->execute([$user['id'], $code]);
        $verification_code = $stmt->fetch();

        if ($verification_code) {
            $stmt = $pdo->prepare("UPDATE users SET verified = TRUE WHERE id = ?");
            $stmt->execute([$user['id']]);
            $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $message = '<div class="alert alert-success">Your email has been verified successfully! You can now <a href="login.php" class="alert-link">login</a>.</div>';
        } else {
            $message = '<div class="alert alert-danger">Invalid or expired verification code. Please try again.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">User not found.</div>';
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Verify Your Email</h2>
                    <p class="text-center text-muted">A verification code has been sent to your email address. Please enter the code below to verify your account.</p>
                    <?php if(!empty($message)) echo $message; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" name="code" id="code" class="form-control" required autofocus>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Verify</button>
                        </div>
                    </form>
                     <div class="text-center mt-3">
                        <p>Didn't receive a code? <a href="login.php">Try logging in again</a> to resend it.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
