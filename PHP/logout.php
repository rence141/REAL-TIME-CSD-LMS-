<?php
// logout.php - Enhanced secure logout script

// Use consistent session name
session_name('BUPSESSID');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear database session if exists
if (!empty($_SESSION['professor_id']) && !empty($_SESSION['session_token'])) {
    @include 'db_connect.php';
    if (isset($conn)) {
        $stmt = $conn->prepare("DELETE FROM professor_sessions WHERE professor_id = ? AND session_token = ?");
        $stmt->bind_param("is", $_SESSION['professor_id'], $_SESSION['session_token']);
        $stmt->execute();
    }
}

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any existing output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Security headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Redirect to login page
header("Location: login_professors.php");
exit();
?>
