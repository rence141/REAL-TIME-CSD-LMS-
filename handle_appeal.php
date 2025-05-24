<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['professor_id']) || !isset($_POST['appeal_id']) || !isset($_POST['decision']) || !isset($_POST['remarks'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$appeal_id = (int)$_POST['appeal_id'];
$decision = $_POST['decision'];
$remarks = $_POST['remarks'];
$professor_id = $_SESSION['professor_id'];

// Verify this appeal is for a course taught by this professor
$verify_query = $conn->prepare("
    SELECT a.attendance_id, c.professor_id, aa.student_id, c.course_id, c.course_name
    FROM attendance_appeals aa
    JOIN attendance a ON aa.attendance_id = a.attendance_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE aa.id = ? AND aa.status = 'pending'
");

$verify_query->bind_param("i", $appeal_id);
$verify_query->execute();
$result = $verify_query->get_result();
$appeal_data = $result->fetch_assoc();

if (!$appeal_data || $appeal_data['professor_id'] !== $professor_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or appeal not found']);
    exit;
}

$conn->begin_transaction();

try {
    // Update appeal status and add remarks
    $update_appeal = $conn->prepare("
        UPDATE attendance_appeals 
        SET status = ?,
            admin_remarks = ?,
            updated_at = CURRENT_TIMESTAMP,
            processed_at = CURRENT_TIMESTAMP,
            processed_by = ?
        WHERE id = ?
    ");
    $update_appeal->bind_param("ssii", $decision, $remarks, $professor_id, $appeal_id);
    $update_appeal->execute();

    // Update attendance record based on decision
    if ($decision === 'approved') {
        $update_attendance = $conn->prepare("
            UPDATE attendance 
            SET status = 'Excused',
                notes = CONCAT(IFNULL(notes, ''), ' [Appeal Approved: ', ?, ']')
            WHERE attendance_id = ?
        ");
        $update_attendance->bind_param("si", $remarks, $appeal_data['attendance_id']);
        $update_attendance->execute();
    } else {
        $update_attendance = $conn->prepare("
            UPDATE attendance 
            SET notes = CONCAT(IFNULL(notes, ''), ' [Appeal Rejected: ', ?, ']')
            WHERE attendance_id = ?
        ");
        $update_attendance->bind_param("si", $remarks, $appeal_data['attendance_id']);
        $update_attendance->execute();
    }

    // Create notification for student
    $notification_message = "Your attendance appeal for " . $appeal_data['course_name'] . " has been " . 
                          $decision . ". " . ($decision === 'approved' ? "Your absence has been marked as excused." : "Reason: " . $remarks);
    
    $insert_notification = $conn->prepare("
        INSERT INTO notifications (type, reference_id, user_id, user_type, message)
        VALUES ('appeal', ?, ?, 'student', ?)
    ");
    $insert_notification->bind_param("iis", $appeal_id, $appeal_data['student_id'], $notification_message);
    $insert_notification->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Appeal processed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error processing appeal: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to process appeal. Please try again.']);
}
?> 