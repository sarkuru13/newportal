<?php
// This check should be at the top of every admin page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';

// Fetch admin's email for display in the navbar
$admin_email_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$admin_email_stmt->execute([$_SESSION['admin_id']]);
$admin_email = $admin_email_stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Exam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-dark" id="sidebar-wrapper">
        <div class="sidebar-heading text-center py-4 text-light fs-4 fw-bold text-uppercase border-bottom">
            <i class="fas fa-user-shield me-2"></i>Exam Portal
        </div>
        <div class="list-group list-group-flush my-3">
            <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent text-light">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a href="manage_exams.php" class="list-group-item list-group-item-action bg-transparent text-light">
                <i class="fas fa-book-open me-2"></i>Manage Exams
            </a>
            <a href="view_results.php" class="list-group-item list-group-item-action bg-transparent text-light">
                <i class="fas fa-poll me-2"></i>View Results
            </a>
            <a href="../logout.php" class="list-group-item list-group-item-action bg-transparent text-danger fw-bold mt-auto">
                <i class="fas fa-power-off me-2"></i>Logout
            </a>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom py-3 px-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-align-left text-dark fs-4 me-3" id="menu-toggle"></i>
                <h2 class="fs-2 m-0" id="page-title">Dashboard</h2>
            </div>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" id="navbarDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($admin_email); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <main class="container-fluid p-4">
