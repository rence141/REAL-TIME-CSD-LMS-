<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$student_id = $_SESSION['student_id'];

// Get all notifications (grades and absence warnings)
$query = "SELECT 
    n.id,
    n.type,
    n.message,
    n.created_at,
    CASE 
        WHEN n.type = 'absence_warning' THEN 'warning'
        ELSE 'grade'
    END as notification_type
FROM notifications n
WHERE n.user_id = ? 
AND n.user_type = 'student'
AND n.is_read = 0
ORDER BY n.created_at DESC
LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['created_at']);
    $notifications[] = [
        'id' => $row['id'],
        'type' => $row['notification_type'],
        'message' => $row['message'],
        'time' => $time_ago,
        'timestamp' => $row['created_at']
    ];
}

echo json_encode($notifications);

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return "Just now";
            }
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        }
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    }
    if ($diff->d < 7) {
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    }
    return date("M j", strtotime($datetime));
}
?> 