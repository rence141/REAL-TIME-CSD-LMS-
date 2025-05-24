<?php
require 'db.connect.php';
session_start();

// Verify student is logged in
$student_id = $_SESSION['professor_id'] ?? null;
if (!$student_id || $_SESSION['role'] !== 'student') {
    header("HTTP/1.1 403 Forbidden");
    die("Access denied");
}

// Get input data
$activity_id = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

if (!$activity_id || !$course_id) {
    header("HTTP/1.1 400 Bad Request");
    die("Invalid activity or course ID");
}

// Check enrollment
$stmt = $conn->prepare("
    SELECT e.enrollment_id 
    FROM enrollments e
    WHERE e.student_id = ? AND e.course_id = ?
");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.1 403 Forbidden");
    die("You are not enrolled in this course");
}

$enrollment = $result->fetch_assoc();
$enrollment_id = $enrollment['enrollment_id'];

// Handle file upload
$media_path = null;
if (!empty($_FILES['submission_file']['name'])) {
    $file = $_FILES['submission_file'];
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $upload_dir = 'uploads/student_activity/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = "submission_{$student_id}_{$activity_id}_" . time() . ".$file_ext";
        $media_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($file['tmp_name'], $media_path)) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Failed to upload file");
        }
    } else {
        header("HTTP/1.1 400 Bad Request");
        die("Invalid file type or size (max 10MB, PDF/JPEG/PNG only)");
    }
}

// Check if already submitted
$stmt = $conn->prepare("
    SELECT 1 FROM student_activity 
    WHERE enrollment_id = ? AND activity_id = ?
");
$stmt->bind_param("ii", $enrollment_id, $activity_id);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    header("HTTP/1.1 400 Bad Request");
    die("You have already submitted this activity");
}

// Insert submission
$stmt = $conn->prepare("
    INSERT INTO student_activity
    (enrollment_id, activity_id, submission_date, media, status)
    VALUES (?, ?, NOW(), ?, 'Submitted')
");
$stmt->bind_param("iis", $enrollment_id, $activity_id, $media_path);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Activity submitted successfully']);
} else {
    header("HTTP/1.1 500 Internal Server Error");
    die("Error submitting activity");
}
?>
