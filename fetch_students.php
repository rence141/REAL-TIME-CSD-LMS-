<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    die("Unauthorized");
}

$course_id = $_GET['course_id'] ?? 0;
$block = $_GET['block'] ?? '';

if (!$course_id || !$block) {
    die('<option value="">Invalid request</option>');
}

$query = $student_conn->prepare("
    SELECT s.student_id, s.full_name 
    FROM students s
    JOIN enrollments e ON s.student_id = e.student_id
    WHERE e.course_id = ? AND s.course_block = ?
    ORDER BY s.full_name ASC
");
$query->bind_param("is", $course_id, $block);
$query->execute();
$result = $query->get_result();

$options = '<option value="">Select student</option>';
while ($row = $result->fetch_assoc()) {
    $options .= sprintf(
        '<option value="%d">%s</option>',
        $row['student_id'],
        htmlspecialchars($row['full_name'])
    );
}

echo $options;
?>