<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Access denied!'); window.location.href='admin_login.php';</script>";
    exit();
}

if (isset($_GET['id'])) {
    $professor_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM professors WHERE id = ?");
    $stmt->bind_param("i", $professor_id);

    if ($stmt->execute()) {
        echo "<script>alert(' Professor deleted successfully!'); window.location.href='manage_professors.php';</script>";
    } else {
        echo "<script>alert(' Error deleting professor!'); window.location.href='manage_professors.php';</script>";
    }
}
?>
