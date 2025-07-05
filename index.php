<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
} elseif (isset($_SESSION['student_id'])) {
    header('Location: student/dashboard.php');
} else {
    header('Location: student/login.php');
}
exit;
?>