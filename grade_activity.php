<?php
require 'db_connect.php';
session_start();

// Verify professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id || $_SESSION['role'] !== 'professor') {
    header("HTTP/1.1 403 Forbidden");
    die("Access denied");
}

// Get input data
$student_activity_id = filter_input(INPUT_POST, 'student_activity_id', FILTER_VALIDATE_INT);
$score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_FLOAT);
$remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING);

if (!$student_activity_id || $score === false) {
    header("HTTP/1.1 400 Bad Request");
    die("Invalid input data");
}

// Verify professor has permission to grade this activity
$stmt = $conn->prepare("
    SELECT 1 
    FROM student_activities sa
    JOIN activities a ON sa.activity_id = a.activity_id
    WHERE sa.student_activity_id = ? AND a.professor_id = ?
");
$stmt->bind_param("ii", $student_activity_id, $professor_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    header("HTTP/1.1 403 Forbidden");
    die("You are not authorized to grade this activity");
}

// Update grade
$stmt = $conn->prepare("
    UPDATE student_activities 
    SET score = ?, remarks = ?, status = 'Graded', 
        graded_by = ?, graded_at = NOW()
    WHERE student_activity_id = ?
");
$stmt->bind_param("dsii", $score, $remarks, $professor_id, $student_activity_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Activity graded successfully']);
} else {
    header("HTTP/1.1 500 Internal Server Error");
    die("Error grading activity");
}
?>