<?php
session_start();
require '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute the statement to find an admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Verify admin existence and password
    if ($admin && password_verify($password, $admin['password'])) {
        // Set session for admin
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Set error message for invalid credentials
        $message = '<div class="alert alert-danger">Invalid email or password. Please try again.</div>';
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
    body {
        background-color: #f0f2f5;
    }
    .login-container {
        max-width: 450px;
        margin: 5rem auto;
    }
    .card-header {
        background-color: #0d6efd;
        color: white;
    }
</style>

<div class="container">
    <div class="login-container">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <h3>Admin Login</h3>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($message)) echo $message; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required autofocus>
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
                    <a href="../student/login.php">Switch to Student Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
