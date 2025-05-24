<?php
session_start();
include 'db_connect.php';

// Check if professor is logged in
if (!isset($_SESSION['professor_id'])) {
    header("Location: signin_professor.php");
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor's courses to populate dropdown
$courses_query = "SELECT course_id, course_name FROM courses WHERE professor_id = ?";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $professor_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];
    
    // Validate time conflict
    $conflict_query = "SELECT * FROM class_schedule 
                      WHERE professor_id = ? AND day_of_week = ? 
                      AND ((start_time < ? AND end_time > ?) OR 
                          (start_time >= ? AND start_time < ?) OR 
                          (end_time > ? AND end_time <= ?))";
    $conflict_stmt = $conn->prepare($conflict_query);
    $conflict_stmt->bind_param("isssssss", $professor_id, $day_of_week, 
                              $end_time, $start_time, 
                              $start_time, $end_time,
                              $start_time, $end_time);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();
    
    if ($conflict_result->num_rows > 0) {
        $error = "Schedule conflict detected! You already have a class at this time.";
    } else {
        // Insert new schedule
        $insert_query = "INSERT INTO class_schedule 
                        (professor_id, course_id, day_of_week, start_time, end_time, room)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iissss", $professor_id, $course_id, $day_of_week, 
                               $start_time, $end_time, $room);
        
        if ($insert_stmt->execute()) {
            $success = "Schedule added successfully!";
        } else {
            $error = "Error adding schedule: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2 class="text-center mb-4">Add New Class