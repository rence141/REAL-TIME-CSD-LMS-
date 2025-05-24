<?php
session_start();
include 'db_connect.php';

// Check if the professor is logged in
if (!isset($_SESSION['professor_id'])) {
    header("Location: login_professors.php");
    exit();
}

// Check if the activity_id is provided in the URL
if (isset($_GET['activity_id'])) {
    $activity_id = intval($_GET['activity_id']); // Sanitize input

    // Prepare the SQL statement to delete the activity
    $stmt = $conn->prepare("DELETE FROM activities WHERE activity_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $activity_id);

        // Execute the statement and check for success
        if ($stmt->execute()) {
            echo "<script>alert('Activity deleted successfully!'); window.location.href='course_details.php?course_id=<?';</script>";
        } else {
            echo "<script>alert('Error deleting activity!'); window.location.href='activity_edit.php';</script>";
        }

        $stmt->close(); // Close the statement
    } else {
        echo "<script>alert('Error preparing the statement!'); window.location.href='activity_details.php';</script>";
    }
} else {
    echo "<script>alert('No activity ID provided!'); window.location.href='activity_details.php';</script>";
}

$conn->close(); // Close the database connection
?>
