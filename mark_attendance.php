<?php
session_start();
require_once 'db_connect.php';
require_once 'track_absences.php';

header('Content-Type: application/json');

if (!isset($_SESSION['professor_id'])) {
    echo json_encode(['error' => 'Please log in first!']);
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Initialize absence tracker
$absenceTracker = new AbsenceTracker($conn, $conn_student);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON data']);
        exit();
    }
    
    if (!isset($data['course_id']) || !isset($data['date']) || !isset($data['attendance'])) {
        echo json_encode(['error' => 'Missing required data']);
        exit();
    }
    
    $course_id = $data['course_id'];
    $date = $data['date'];
    $attendance_data = $data['attendance'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, verify the professor owns this course
        $verify_query = "SELECT course_id FROM courses WHERE course_id = ? AND professor_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $course_id, $professor_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Unauthorized access to course");
        }
        
        // Prepare attendance insert statement
        $insert_query = "INSERT INTO attendance (student_id, course_id, date, status, notes, professor_id) 
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        notes = VALUES(notes)";
        $stmt = $conn->prepare($insert_query);
        
        foreach ($attendance_data as $record) {
            $student_id = $record['student_id'];
            $status = $record['status'];
            $notes = $record['notes'] ?? null;
            
            $stmt->bind_param("iisssi", $student_id, $course_id, $date, $status, $notes, $professor_id);
            $stmt->execute();
            
            // Track absences if status is 'Absent'
            if ($status === 'Absent') {
                $absenceTracker->trackAbsence($student_id, $course_id, $date);
            } else {
                // Reset absence tracking if student is present
                $absenceTracker->resetAbsenceTracking($student_id, $course_id);
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error marking attendance: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to mark attendance. Please try again.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?> 