<?php
session_start();
require_once 'db_connect.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone to match the database
date_default_timezone_set('Asia/Manila');

// 🔐 Function to generate 6-digit code
function generateVerificationCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// 📧 Function to send verification code via email
function sendVerificationEmail($student_email, $code) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Lorenzezz0987@gmail.com'; // Your Gmail address
        $mail->Password = 'ceqd fmip fgld mlgb'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Lorenzezz0987@gmail.com', 'BUP LMS');
        $mail->addAddress($student_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Login Verification Code';
        $mail->Body = "
            <h2>Your Verification Code</h2>
            <p>Your verification code is: <strong>{$code}</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this code, please ignore this email.</p>
            <p>Current server time: " . date('Y-m-d H:i:s') . "</p>
        ";

        $mail->send();
        error_log("Verification email sent successfully to: " . $student_email . " at " . date('Y-m-d H:i:s'));
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// 🎯 Main Login Handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "Email and password are required.";
        exit();
    }

    $stmt = $conn_student->prepare("SELECT student_id, password, email FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $student_id = $row['student_id'];
            $student_email = $row['email'];

            // ✅ Set session
            $_SESSION['student_id'] = $student_id;
            $_SESSION['verification_pending'] = true;

            try {
                // Begin transaction
                $conn_student->begin_transaction();

                // Delete any existing unused codes for this student
                $delete_stmt = $conn_student->prepare("
                    DELETE FROM student_verification_codes 
                    WHERE student_id = ? AND is_used = 0
                ");
                $delete_stmt->bind_param("s", $student_id);
                $delete_stmt->execute();

                // 🔐 Generate and store code with proper timezone
                $code = generateVerificationCode();
                $current_time = date('Y-m-d H:i:s');
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Insert new verification code using proper timezone
                $insert = $conn_student->prepare("
                    INSERT INTO student_verification_codes (student_id, code, created_at, expires_at)
                    VALUES (?, ?, ?, ?)
                ");
                $insert->bind_param("ssss", $student_id, $code, $current_time, $expires_at);

                if ($insert->execute()) {
                    // Try to send the verification email
                    if (sendVerificationEmail($student_email, $code)) {
                        $conn_student->commit();
                        $_SESSION['verification_time'] = $current_time;
                        header("Location: verify_login_student.php");
                        exit();
                    } else {
                        throw new Exception("Failed to send verification email");
                    }
                } else {
                    throw new Exception("Failed to store verification code");
                }
            } catch (Exception $e) {
                $conn_student->rollback();
                error_log("Verification process failed: " . $e->getMessage());
                echo "Failed to send verification code. Please contact admin. Error: " . $e->getMessage();
                exit();
            }
        } else {
            echo "Invalid password.";
            exit();
        }
    } else {
        echo "No account found for this email.";
        exit();
    }
}

// Close any open statements and connections
if (isset($stmt)) {
    $stmt->close();
}
if (isset($delete_stmt)) {
    $delete_stmt->close();
}
if (isset($insert)) {
    $insert->close();
}
$conn_student->close();
