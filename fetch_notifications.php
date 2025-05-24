<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$professor_id = $_SESSION['professor_id'];

// Get the last seen timestamp from the request
$last_seen = isset($_GET['last_seen']) ? $_GET['last_seen'] : '1970-01-01 00:00:00';

// Get the latest submissions and updates
$query = "SELECT 
    s.submission_id,
    s.student_id,
    s.activity_id,
    s.submission_date,
    st.student_name,
    a.activity_name,
    c.course_code,
    CASE 
        WHEN s.grade IS NOT NULL THEN 'graded'
        ELSE 'pending'
    END as status
FROM submissions s
JOIN " . $database_student . ".students st ON s.student_id = st.student_id
JOIN activities a ON s.activity_id = a.activity_id
JOIN courses c ON a.course_id = c.course_id
WHERE c.professor_id = ? 
AND s.submission_date > ?
ORDER BY s.submission_date DESC
LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $professor_id, $last_seen);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['submission_date']);
    $notifications[] = [
        'id' => $row['submission_id'],
        'message' => $row['student_name'] . " submitted " . $row['activity_name'] . " for " . $row['course_code'],
        'status' => $row['status'],
        'time' => $time_ago,
        'timestamp' => $row['submission_date']
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