<?php
require 'db_connect.php';
session_start();

if (!isset($_POST['activity_id'], $_POST['new_status'], $_POST['course_id'])) {
    die("Invalid request.");
}

$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    die("Unauthorized access.");
}

$activity_id = (int)$_POST['activity_id'];
$new_status = (int)$_POST['new_status'];
$course_id = (int)$_POST['course_id'];

// Update the activity status
$stmt = $conn->prepare("UPDATE activities SET status = ? WHERE activity_id = ? AND professor_id = ?");
$stmt->bind_param("iii", $new_status, $activity_id, $professor_id);

if ($stmt->execute()) {
    header("Location: course_details.php?course_id=$course_id");
    exit;
} else {
    die("Failed to update status.");
}
