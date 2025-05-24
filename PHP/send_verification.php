<?php
require_once 'db_connect.php';
session_start();

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendVerificationEmail($email, $code) {
    $to = $email;
    $subject = "SADD LMS - Login Verification Code";
    $message = "Your verification code is: $code\n\n";
    $message .= "This code will expire in 10 minutes.\n";
    $message .= "If you did not request this code, please ignore this email.";
    $headers = "From: noreply@buppolangui.edu.ph";

    return mail($to, $subject, $message, $headers);
}

if (isset($_POST['professor_id']) && isset($_POST['email'])) {
    $professor_id = $_POST['professor_id'];
    $email = $_POST['email'];
    
    // Generate new verification code
    $code = generateVerificationCode();
    
    // Set expiration time (10 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Insert new verification code
    $stmt = $conn->prepare("INSERT INTO professor_verification_codes (professor_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $professor_id, $code, $expires_at);
    
    if ($stmt->execute()) {
        if (sendVerificationEmail($email, $code)) {
            echo json_encode(['success' => true, 'message' => 'Verification code sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send verification email']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate verification code']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?> 
