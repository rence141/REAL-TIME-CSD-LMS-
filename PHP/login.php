<?php
session_start();
include 'db_connect.php'; // Ensure the correct path to your database connection file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendVerificationEmail($toEmail, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Lorenzezz0987@gmail.com';
        $mail->Password = 'ceqd fmip fgld mlgb';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Rence@gmail.com', 'CSD LMS');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SADD LMS - Login Verification Code';
        $mail->Body = "
            <h2>Your Verification Code</h2>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this code, please ignore this email.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize user input
    $identifier = trim(filter_input(INPUT_POST, 'email_or_employeeid', FILTER_SANITIZE_STRING));
    $password = $_POST['password']; // Password sanitization handled separately as it's hashed

    // Validate input
    if (!empty($identifier) && !empty($password)) {
        // Query to check for case-sensitive credentials
        $stmt = $conn->prepare("SELECT * FROM professors WHERE BINARY email = ? OR BINARY employeeid_number = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password securely
            if (password_verify($password, $user['password'])) {
                // Generate and send verification code
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store verification code
                $verify_stmt = $conn->prepare("INSERT INTO student_verification_codes (student_id, code, expires_at) VALUES (?, ?, ?)");
                $verify_stmt->bind_param("iss", $user['student_id'], $code, $expires_at);
                
                if ($verify_stmt->execute()) {
                    if (sendVerificationEmail($user['email'], $code)) {
                        // Store necessary info in session
                        $_SESSION['verification_pending'] = true;
                        $_SESSION['student_id'] = $user['_id'];
                        $_SESSION['student_name'] = $user['student_name'];
                        $_SESSION['email'] = $user['email'];
                        
                        // Redirect to verification page
                        header('Location: verify_login.php');
                        exit();
                    } else {
                        echo "<script>alert('Failed to send verification code. Please try again.'); window.location.href='login_professors.php';</script>";
                    }
                } else {
                    echo "<script>alert('Error generating verification code. Please try again.'); window.location.href='login_professors.php';</script>";
                }
                $verify_stmt->close();
            } else {
                // Incorrect password
                echo "<script>alert('Incorrect password! Please try again.'); window.location.href='login_professors.php';</script>";
            }
        } else {
            // User not found
            echo "<script>alert('User not found! Please check your email or Employee ID.'); window.location.href='login_professors.php';</script>";
        }
        $stmt->close();
    } else {
        // Missing input fields
        echo "<script>alert('Please fill in both fields.'); window.location.href='login_professors.php';</script>";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
<link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover; height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center;">

    <div class="signup-container" style="background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 10px; width: 900px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); display: flex; flex-direction: row;">
        <!-- Left Side (Image) -->
        <div class="left-box" style="width: 50%; background: url('../IMAGES/Screenshot 2025-02-27 182517.png') no-repeat center center/cover; border-radius: 10px 0 0 10px; background-size: contain;"></div>

        <!-- Right Side (Form) -->
        <div class="right-box" style="width: 50%; padding: 40px; display: flex; flex-direction: column; justify-content: center;">
            <h2 style="color: #2c3e50; font-weight: bold; text-align: center;">Student Login</h2>
            <p class="text-muted text-center">Sign in to your account</p>

            <form action="login_student_process.php" method="POST">
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember Me</label>
                </div>

                <!-- Sign In Button -->
                <button type="submit" class="btn btn-primary" style="background-color: #2c3e50; border: none; width: 100%; padding: 10px; font-weight: bold;">Login</button>

                <!-- Forgot Password & Signup Link -->
                <div class="text-center mt-3">
                    <a href="forgot_password_student.php" class="forgot-password" style="color:rgb(3, 55, 114);">Forgot Password?</a>
                    <p>Don't have an account? <a href="signup.php" class="forgot-password">Sign Up</a></p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
