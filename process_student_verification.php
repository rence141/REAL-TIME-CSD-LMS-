<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

// Set timezone to match the database
date_default_timezone_set('Asia/Manila');

// Debug: Print all data
echo "<pre>";
echo "Session Data:\n";
print_r($_SESSION);
echo "\nPOST Data:\n";
print_r($_POST);
echo "</pre>";

// Debug: Log session and POST data
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

if (!isset($_SESSION['verification_pending']) || !isset($_SESSION['student_id'])) {
    $_SESSION['verification_error'] = 'Session verification data missing.';
    echo "Error: Session verification data missing";
    header('Location: verify_login_student.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Get the verification code from POST data
if (isset($_POST['verification_code']) && !empty($_POST['verification_code'])) {
    $code = $_POST['verification_code'];
    echo "Received code: " . htmlspecialchars($code);
    error_log("Received verification code: " . $code);
} else {
    $_SESSION['verification_error'] = 'No verification code submitted.';
    error_log("No verification code in POST data");
    echo "Error: No verification code submitted";
    header('Location: verify_login_student.php');
    exit();
}

// Debug: Log the query parameters and current time
$current_time = date('Y-m-d H:i:s');
error_log("Checking code for student_id: " . $student_id . " and code: " . $code);
error_log("Current server time: " . $current_time);

try {
    // First, get the verification code record regardless of expiration
    $stmt = $conn_student->prepare("
        SELECT v.*, 
               TIMESTAMPDIFF(MINUTE, v.created_at, NOW()) as minutes_since_created,
               TIMESTAMPDIFF(MINUTE, NOW(), v.expires_at) as minutes_until_expiry
        FROM student_verification_codes v
        WHERE v.student_id = ? 
        AND v.code = ? 
        AND v.is_used = 0
        ORDER BY v.created_at DESC 
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn_student->error);
    }

    $stmt->bind_param("ss", $student_id, $code);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    echo "\nQuery result rows: " . $result->num_rows;

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        echo "\nFound matching code record: ";
        print_r($row);
        echo "\nMinutes since created: " . $row['minutes_since_created'];
        echo "\nMinutes until expiry: " . $row['minutes_until_expiry'];
        
        // Check if code is expired
        if ($row['minutes_until_expiry'] < 0) {
            $_SESSION['verification_error'] = 'Verification code has expired. Please request a new code.';
            error_log("Code expired. Created at: " . $row['created_at'] . ", Expires at: " . $row['expires_at'] . ", Current time: " . $current_time);
            header('Location: verify_login_student.php');
            exit();
        }
        
        // Code is valid and not expired, mark it as used
        $update_stmt = $conn_student->prepare("UPDATE student_verification_codes SET is_used = 1 WHERE id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // Close the main statement
        $stmt->close();
        
        // Complete login process
        unset($_SESSION['verification_pending']);
        unset($_SESSION['verification_time']);
        $_SESSION['verified'] = true;
        
        echo "\nVerification successful, redirecting to dashboard";
        // Redirect to dashboard
        header('Location: dashboard_student.php');
        exit();
    } else {
        // Close the statement before redirect
        $stmt->close();
        
        $_SESSION['verification_error'] = 'Invalid verification code. Please try again.';
        error_log("No matching verification code found for student_id: " . $student_id . " and code: " . $code);
        header('Location: verify_login_student.php');
        exit();
    }
} catch (Exception $e) {
    // Close the statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }
    
    echo "\nError: " . $e->getMessage();
    error_log("Verification error: " . $e->getMessage());
    $_SESSION['verification_error'] = 'An error occurred during verification: ' . $e->getMessage();
    header('Location: verify_login_student.php');
    exit();
}
?>