<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $professor_id = $_SESSION['professor_id'];
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $course_id = $conn->real_escape_string($_POST['course_id']);
    $course_block = $conn->real_escape_string($_POST['course_block']);
    $status = $conn->real_escape_string($_POST['status']);
    $date = $conn->real_escape_string($_POST['date']);
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';

    // Check if attendance record already exists for this student, course, and date
    $check_sql = "SELECT * FROM attendance 
                 WHERE student_id = $student_id 
                 AND course_id = $course_id 
                 AND date = '$date'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo "<script>alert('Attendance for this student on this date already exists!'); window.history.back();</script>";
        exit();
    }

    // Insert new attendance record
    $sql = "INSERT INTO attendance (student_id, course_id, professor_id, status, date, notes, course_block)
            VALUES ($student_id, $course_id, $professor_id, '$status', '$date', '$notes', '$course_block')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Attendance recorded successfully!'); window.location.href='view_attendance_prof.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "'); window.history.back();</script>";
    }
} else {
    header("Location: dashboard_professor.php");
}
?>