<?php
session_start();
include 'db_connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_POST['professor_id'];
    $course_id = $_POST['course_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];

    // Insert schedule into database
    $stmt = $conn->prepare("INSERT INTO class_schedule (professor_id, course_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $professor_id, $course_id, $day_of_week, $start_time, $end_time, $room);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Schedule added successfully!'); window.location.href='admin_manage_schedules.php';</script>";
    } else {
        echo "<script>alert('❌ Error adding schedule!'); window.location.href='admin_manage_schedules.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
