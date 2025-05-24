<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_POST['professor_id'];
    $student_id = $_POST['student_id'];
    $course_block = $_POST['course_block'];
    $subject_id = $_POST['subject_id'];
    $grade = $_POST['grade'];
    $semester = $_POST['semester'];

    $stmt = $conn->prepare("INSERT INTO grades (professor_id, student_id, course_block, subject_id, grade, semester) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisiis", $professor_id, $student_id, $course_block, $subject_id, $grade, $semester);

    if ($stmt->execute()) {
        echo "<script>alert('Grade added successfully!'); window.location.href='add_grade.php';</script>";
    } else {
        echo "<script>alert('Error adding grade.'); window.location.href='add_grade.php';</script>";
    }
}
?>
