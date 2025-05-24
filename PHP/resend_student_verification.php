<?php
session_start();
require_once 'db_connect.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$student_id = $_SESSION['student_id'];

try {
    // Get student email
    $stmt = $conn_student->prepare("SELECT email FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception("Student not found");
    }
    
    $student = $result->fetch_assoc();
    $student_email = $student['email'];
    
    // Generate new verification code
    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $current_time = date('Y-m-d H:i:s');
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Begin transaction
    $conn_student->begin_transaction();
    
    // Delete any existing unused codes
    $delete_stmt = $conn_student->prepare("
        DELETE FROM student_verification_codes 
        WHERE student_id = ? AND is_used = 0
    ");
    $delete_stmt->bind_param("s", $student_id);
    $delete_stmt->execute();
    
    // Insert new code
    $insert = $conn_student->prepare("
        INSERT INTO student_verification_codes (student_id, code, created_at, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $insert->bind_param("ssss", $student_id, $code, $current_time, $expires_at);
    
    if (!$insert->execute()) {
        throw new Exception("Failed to store verification code");
    }
    
    // Send email
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'Lorenzezz0987@gmail.com';
    $mail->Password = 'ceqd fmip fgld mlgb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Recipients
    $mail->setFrom('Lorenzezz0987@gmail.com', 'BUP LMS');
    $mail->addAddress($student_email);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your New Login Verification Code';
    $mail->Body = "
        <h2>Your New Verification Code</h2>
        <p>Your verification code is: <strong>{$code}</strong></p>
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request this code, please ignore this email.</p>
        <p>Current server time: {$current_time}</p>
    ";
    
    $mail->send();
    
    // Commit transaction
    $conn_student->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'New verification code sent'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if ($conn_student->inTransaction()) {
        $conn_student->rollback();
    }
    
    error_log("Resend verification error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send new verification code'
    ]);
}

// Close connections
if (isset($stmt)) $stmt->close();
if (isset($delete_stmt)) $delete_stmt->close();
if (isset($insert)) $insert->close();
$conn_student->close();
?> 
