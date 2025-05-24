<?php
session_start();
include 'db_connect.php';

//  Security Check: Ensure professor is authenticated
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit;
}

//  Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate inputs
    $activity_id = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
    $new_status = trim($_POST['status']);

    // Optional: Restrict status values
    $allowed_statuses = ['pending', 'completed', 'in_progress'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo "<script>alert('Invalid status value!'); window.location.href='view_activity.php';</script>";
        exit;
    }

    if ($activity_id === false || !$new_status) {
        echo "<script>alert('Invalid input data!'); window.location.href='view_activity.php';</script>";
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE student_activity SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $activity_id);

        if ($stmt->execute()) {
            echo "<script>alert(' Activity updated successfully!'); window.location.href='view_activity.php';</script>";
        } else {
            throw new Exception("Failed to execute update.");
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Log error if needed: error_log($e->getMessage());
        echo "<script>alert(' Error updating activity. Please try again.'); window.location.href='edit_activity.php?id=$activity_id';</script>";
        exit;
    }
}
?>
