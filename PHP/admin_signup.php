<?php
session_start();
include 'db_connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password hashing

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert(' Email already exists!'); window.location.href='admin_signup.php';</script>";
        exit();
    }

    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO admin (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $password);

    if ($stmt->execute()) {
        echo "<script>alert(' Admin account created!'); window.location.href='admin_login.php';</script>";
    } else {
        echo "<script>alert(' Error creating admin account!'); window.location.href='admin_signup.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Sign Up</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2 class="text-center"> Admin Sign Up</h2>
    
    <form action="admin_signup.php" method="POST" class="card p-4">
        <div class="mb-3">
            <label>Full Name:</label>
            <input type="text" class="form-control" name="full_name" required>
        </div>
        <div class="mb-3">
            <label>Email:</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Password:</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
    </form>

    <div class="text-center mt-3">
        <a href="admin_login.php"> Already have an account? Log in</a>
    </div>
</body>
</html>
