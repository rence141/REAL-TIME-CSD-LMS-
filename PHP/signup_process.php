<?php
session_start();
include 'db_connect.php'; // Ensure this file exists in PHP folder

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $employeeId = trim($_POST['employeeid_number']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate empty fields
    if (empty($fullName) || empty($email) || empty($employeeId) || empty($password) || empty($confirmPassword)) {
        echo "<script>alert('All fields are required!'); window.location.href='signup_prof.php';</script>";
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.location.href='signup_prof.php';</script>";
        exit();
    }

    // Validate password match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.location.href='signup_prof.php';</script>";
        exit();
    }

    // Check if email or employee ID already exists
    $stmt = $conn->prepare("SELECT * FROM professors WHERE email = ? OR employeeid_number = ?");
    $stmt->bind_param("ss", $email, $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email or Employee ID already registered!'); window.location.href='signup_prof.php';</script>";
        exit();
    }

    // Hash the password before storing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO professors (full_name, email, employeeid_number, password, role, profile_image) VALUES (?, ?, ?, ?, 'professor', 'default_profile.jpg')");
    $stmt->bind_param("ssss", $fullName, $email, $employeeId, $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='login_professors.php';</script>";
    } else {
        echo "<script>alert('Error during registration! Please try again.'); window.location.href='signup_prof.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
