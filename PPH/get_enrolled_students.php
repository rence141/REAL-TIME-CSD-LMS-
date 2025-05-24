<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    echo json_encode(['error' => 'Please log in first']);
    exit();
}

$professor_id = $_SESSION['professor_id'];

if (!isset($_GET['course_id'])) {
    echo json_encode(['error' => 'Course ID is required']);
    exit();
}

$course_id = (int)$_GET['course_id'];

// Verify the professor owns this course
$verify_query = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND professor_id = ?");
$verify_query->bind_param("ii", $course_id, $professor_id);
$verify_query->execute();
if ($verify_query->get_result()->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized access to course']);
    exit();
}

// Get enrolled students directly from enrollments table
$query = "SELECT 
            e.student_id,
            COALESCE(e.student_name, 'Unknown Student') as student_name,
            e.course_name,
            SUBSTRING_INDEX(e.course_name, ' - ', -1) as block
          FROM enrollments e
          WHERE e.course_id = ?
          ORDER BY e.student_name ASC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $course_id);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Execution error: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();
$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'block' => $row['block']
    ];
}

// If no students found, return empty array with message
if (empty($students)) {
    echo json_encode(['message' => 'No students enrolled in this course', 'students' => []]);
    exit();
}

echo json_encode($students);
?> 
