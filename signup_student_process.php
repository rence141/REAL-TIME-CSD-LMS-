<?php
session_start();
include 'db_connect.php'; // Ensure this file includes $conn_student

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch & sanitize inputs
    $fullName = trim($_POST['student_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $block = trim($_POST['block']);
    $section = trim($_POST['section']);
    $studentId = 'AUTO'; // Can be changed if auto-generated logic is needed

    // Validate empty fields
    if (
        empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) ||
        empty($block) || empty($section)
    ) {
        echo "<script>alert('All fields are required!'); window.location.href='signup.php';</script>";
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.location.href='signup.php';</script>";
        exit();
    }

    // Validate password match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.location.href='signup.php';</script>";
        exit();
    }

    // Check for existing email
    $stmt = $conn_student->prepare("SELECT email FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already registered!'); window.location.href='signup.php';</script>";
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user data
    $stmt = $conn_student->prepare("
        INSERT INTO students (student_name, email, block, section, student_id, password) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $fullName, $email, $block, $section, $studentId, $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.location.href='signup.php';</script>";
    }

    // Close connections
    $stmt->close();
    $conn_student->close();
}
?>
