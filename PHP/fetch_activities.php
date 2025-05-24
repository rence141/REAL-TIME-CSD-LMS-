<?php
session_start();
include 'db_connect.php';

$professor_id = $_SESSION['professor_id'];

$activity_query = $conn->prepare("
    SELECT activity, DATE_FORMAT(timestamp, '%M %d, %Y %h:%i %p') AS formatted_time 
    FROM activities 
    WHERE professor_id = ? 
    ORDER BY timestamp DESC LIMIT 5");
$activity_query->bind_param("i", $professor_id);
$activity_query->execute();
$activity_result = $activity_query->get_result();

$activities = [];
while ($row = $activity_result->fetch_assoc()) {
    $activities[] = $row;
}

echo json_encode($activities);
?>
