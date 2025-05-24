<?php
// Start session (if needed)
session_start();
require 'db.connect_professors.php';

// Database connection (replace with your actual connection)
$conn = new mysqli(hostname: "localhost", username: "root", password: "003421.!", database: "lms_dashborad_professors");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate email input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }
            
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM professors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Delete any existing tokens for this email
        $conn->query("DELETE FROM forgot_password WHERE email = '$email'");
        
        // Store new token
        $stmt = $conn->prepare("INSERT INTO forgot_password (email, reset_token, expired_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expiration);
        
        if ($stmt->execute()) {
            // Build reset link (use your actual domain)
            $resetLink = "https://yourdomain.com/reset-password.php?token=" . urlencode($token);
            
            // Email content
            $subject = "Password Reset Request";
            $message = "
                <html>
                <body>
                    <p>You requested a password reset. Click the link below:</p>
                    <p><a href='$resetLink'>Reset Password</a></p>
                    <p><small>This link expires in 1 hour.</small></p>
                    <p>If you didn't request this, please ignore this email.</p>
                </body>
                </html>
            ";
            
            // Email headers
            $headers = "From: no-reply@yourdomain.com\r\n";
            $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // Send email
            if (mail($email, $subject, $message, $headers)) {
                $_SESSION['message'] = "Password reset link sent to your email";
            } else {
                $_SESSION['error'] = "Failed to send email. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Database error. Please try again.";
        }
    } else {
        $_SESSION['error'] = "No account found with that email.";
    }
    
    // Redirect back to the form page
    header("Location: reset_password_prof.php");
    exit();
}
?>
