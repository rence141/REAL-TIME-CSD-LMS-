<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['Full_Name']);
    $email = trim($_POST['email']);
    $employeeid_number = trim($_POST['employeeid_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

    // Check if professor already exists
    $check = $conn->prepare("SELECT * FROM professors WHERE email = ? OR employeeid_number = ?");
    $check->bind_param("ss", $email, $employeeid_number);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('❌ Email or Employee ID already exists!'); window.location.href='admin_add_professor.php';</script>";
        exit();
    }

    // Insert professor into database
    $stmt = $conn->prepare("INSERT INTO professors (Full_Name, email, employeeid_number, password, role, profile_image) VALUES (?, ?, ?, ?, 'professor', 'default_profile.jpg')");
    $stmt->bind_param("ssss", $full_name, $email, $employeeid_number, $password);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Professor added successfully!'); window.location.href='manage_professors.php';</script>";
    } else {
        echo "<script>alert('❌ Error adding professor!'); window.location.href='admin_add_professor.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Professor</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center">➕ Add New Professor</h2>
    
    <form action="admin_add_professor.php" method="POST" class="card p-4">
        <div class="mb-3">
            <label>Full Name:</label>
            <input type="text" class="form-control" name="Full_Name" required>
        </div>
        <div class="mb-3">
            <label>Email:</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Employee ID:</label>
            <input type="text" class="form-control" name="employeeid_number" required>
        </div>
        <div class="mb-3">
            <label>Password:</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Add Professor</button>
    </form>

    <div class="text-center mt-3">
        <a href="manage_professors.php">🔙 Back to Professors</a>
    </div>
</body>
</html>
