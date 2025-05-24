<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

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

if (!isset($_SESSION['verification_pending']) || !isset($_SESSION['professor_id'])) {
    $_SESSION['verification_error'] = 'Session verification data missing.';
    echo "Error: Session verification data missing";
    header('Location: verify_login.php');
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Get the verification code from POST data
if (isset($_POST['verification_code']) && !empty($_POST['verification_code'])) {
    $code = $_POST['verification_code'];
    echo "Received code: " . htmlspecialchars($code);
    error_log("Received verification code: " . $code);
} else {
    $_SESSION['verification_error'] = 'No verification code submitted.';
    error_log("No verification code in POST data");
    echo "Error: No verification code submitted";
    header('Location: verify_login.php');
    exit();
}

// Debug: Log the query parameters
error_log("Checking code for professor_id: " . $professor_id . " and code: " . $code);

try {
    // Set timezone to match the database
    date_default_timezone_set('UTC');
    
    // Debug timestamp information
    echo "\nCurrent server time: " . date('Y-m-d H:i:s');
    error_log("Current server time: " . date('Y-m-d H:i:s'));
    
    // Check if code is valid and not expired
    $stmt = $conn->prepare("
        SELECT v.*, 
               TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), v.expires_at) as minutes_until_expiry
        FROM professor_verification_codes v
        WHERE v.professor_id = ? 
        AND v.code = ? 
        AND v.is_used = 0 
        AND v.expires_at > UTC_TIMESTAMP()
        ORDER BY v.created_at DESC 
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("is", $professor_id, $code);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    echo "\nQuery result rows: " . $result->num_rows;

    if ($result->num_rows === 1) {
        // Mark code as used
        $row = $result->fetch_assoc();
        echo "\nFound matching code record: ";
        print_r($row);
        echo "\nMinutes until expiry: " . $row['minutes_until_expiry'];
        
        $code_id = $row['id'];
        $update_stmt = $conn->prepare("UPDATE professor_verification_codes SET is_used = 1 WHERE id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("i", $code_id);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // Close the main statement
        $stmt->close();
        
        // Complete login process
        unset($_SESSION['verification_pending']);
        $_SESSION['verified'] = true;
        
        echo "\nVerification successful, redirecting to dashboard";
        // Redirect to dashboard
        header('Location: dashboard_professor.php');
        exit();
    } else {
        // Close the statement before redirect
        $stmt->close();
        
        echo "\nNo valid verification code found";
        // Invalid or expired code
        $_SESSION['verification_error'] = 'Invalid or expired verification code. Please try again.';
        header('Location: verify_login.php');
        exit();
    }
} catch (Exception $e) {
    // Close the statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }
    
    echo "\nError: " . $e->getMessage();
    $_SESSION['verification_error'] = 'An error occurred during verification: ' . $e->getMessage();
    header('Location: verify_login.php');
    exit();
}

?>