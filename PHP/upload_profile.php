<?php
session_start();
include 'db_connect.php'; // Database connection

// Ensure professor is logged in
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professors.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $target_dir = "uploads/"; // Folder to store images

    // Ensure the uploads folder exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate a unique file name
    $image_name = $professor_id . "_" . time() . "_" . basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $image_name;

    // Move file to uploads folder (NO RESTRICTIONS)
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Update profile picture in database
        $stmt = $conn->prepare("UPDATE professors SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $professor_id);

        if ($stmt->execute()) {
            $_SESSION['profile_image'] = $target_file;
            echo "<script>alert('✅ Profile picture updated successfully!'); window.location.href='dashboard_professor.php';</script>";
        } else {
            echo "<script>alert('❌ Error updating profile picture in database!'); window.location.href='dashboard_professor.php';</script>";
        }
    } else {
        echo "<script>alert('❌ Upload failed! Check folder permissions.'); window.location.href='dashboard_professor.php';</script>";
    }
}
?>
