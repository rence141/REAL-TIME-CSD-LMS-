<?php
session_start();
include 'db_connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_SESSION['professor_id'];
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $status = $_POST['status'];
    $date = $_POST['date'];

    // Insert attendance record
    $stmt = $conn->prepare("INSERT INTO attendance (professor_id, student_id, course_id, date, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("iiiss", $professor_id, $student_id, $course_id, $date, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Attendance marked successfully!'); window.location.href='attendance_prof.php';</script>";
    } else {
        echo " Error inserting record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
