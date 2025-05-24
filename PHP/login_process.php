<?php
session_start();
include 'db_connect.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize user input
    $identifier = trim(filter_input(INPUT_POST, 'email_or_employeeid', FILTER_SANITIZE_STRING));
    $password = $_POST['password']; // Password sanitization handled separately since it's hashed

    // Validate input
    if (!empty($identifier) && !empty($password)) {
        // Check if the user exists with case sensitivity using BINARY
        $stmt = $conn->prepare("SELECT * FROM professors WHERE BINARY email = ? OR BINARY employeeid_number = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['professor_id'] = $user['id'];
            $_SESSION['Full_Name'] = $user['Full_Name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'];

            echo "<script>alert('Login successful! Redirecting...'); window.location.href='../PHP/dashboard_professor.php';</script>";
            exit();
        } else {
            echo "<script>alert('Incorrect password! Please try again.'); window.location.href='login_professors.php';</script>";
        }
    } else {
        echo "<script>alert('User not found! Please check your email or Employee ID.'); window.location.href='login_professors.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
}

?>
