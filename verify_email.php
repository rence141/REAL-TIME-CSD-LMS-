<?php
// filepath: php-email-verification-project/PHP/verify_email.php
session_start();
include 'db_connect.php'; // Ensure the correct path to your database connection file
include 'utils/email_helper.php'; // Include email helper functions
include 'utils/token_generator.php'; // Include token generator functions

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate the token
    $stmt = $conn->prepare("SELECT * FROM professors WHERE verification_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Update the user's email verification status
        $stmt = $conn->prepare("UPDATE professors SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        if ($stmt->execute()) {
            echo "<script>alert('Email verified successfully! You can now log in.'); window.location.href='login_professors.php';</script>";
        } else {
            echo "<script>alert('Error verifying email. Please try again later.'); window.location.href='login_professors.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid verification token.'); window.location.href='login_professors.php';</script>";
    }
    $stmt->close();
} else {
    echo "<script>alert('No token provided.'); window.location.href='login_professors.php';</script>";
}

$conn->close();
?>