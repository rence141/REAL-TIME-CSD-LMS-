<?php
session_start();
include 'db_connect.php';

// Ensure professor is logged in
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

// Check if grade_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location.href='view_grades.php';</script>";
    exit();
}

$grade_id = $_GET['id'];

// Delete the grade
$delete_query = "DELETE FROM grades WHERE grade_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $grade_id);

if ($stmt->execute()) {
    echo "<script>alert('Grade deleted successfully!'); window.location.href='view_grades.php';</script>";
} else {
    echo "<script>alert('Error deleting grade.'); window.location.href='view_grades.php';</script>";
}
?>
