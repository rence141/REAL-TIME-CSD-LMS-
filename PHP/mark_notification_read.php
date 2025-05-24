<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$student_id = $_SESSION['student_id'];

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    exit();
}

$notification_id = (int)$data['notification_id'];

// Update notification as read
$query = $conn->prepare("
    UPDATE notifications 
    SET is_read = 1 
    WHERE id = ? 
    AND user_id = ? 
    AND user_type = 'student'
");

$query->bind_param("ii", $notification_id, $student_id);

if ($query->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update notification']);
}
?> 
