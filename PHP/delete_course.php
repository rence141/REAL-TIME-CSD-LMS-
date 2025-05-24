<?php
session_start();
include 'db.connect_professors.php'; // Database connection file

// Ensure professor is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_professors.php");
    exit();
}

if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']); // Ensure valid course ID
    $professor_id = $_SESSION['user_id']; // Get the logged-in professor's ID

    // Check if the course belongs to the logged-in professor
    $check_query = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND professor_id = ?");
    $check_query->bind_param("ii", $course_id, $professor_id);
    $check_query->execute();
    $check_result = $check_query->get_result();

    if ($check_result->num_rows > 0) {
        // Proceed to delete the course
        $delete_query = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $delete_query->bind_param("i", $course_id);

        if ($delete_query->execute()) {
            $_SESSION['message'] = " Course deleted successfully!";
            header("Location: manage_courses.php");
            exit();
        } else {
            $_SESSION['message'] = " Error deleting course.";
            header("Location: manage_courses.php");
            exit();
        }
    } else {
        $_SESSION['message'] = " Invalid course or insufficient permissions.";
        header("Location: manage_courses.php");
        exit();
    }
} else {
    $_SESSION['message'] = " No course ID provided.";
    header("Location: manage_courses.php");
    exit();
}
?>
