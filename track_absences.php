<?php
require_once 'db_connect.php';

class AbsenceTracker {
    private $conn;
    private $conn_student;
    
    public function __construct($conn, $conn_student) {
        $this->conn = $conn;
        $this->conn_student = $conn_student;
    }
    
    public function trackAbsence($student_id, $course_id, $date) {
        // Get course name
        $course_query = $this->conn->prepare("SELECT course_name, course_code FROM courses WHERE course_id = ?");
        $course_query->bind_param("i", $course_id);
        $course_query->execute();
        $course_result = $course_query->get_result();
        $course_data = $course_result->fetch_assoc();
        $course_name = $course_data['course_name'];
        $course_code = $course_data['course_code'];

        // Count recent absences (within the last 30 days)
        $count_query = $this->conn->prepare("
            SELECT COUNT(*) as absence_count 
            FROM attendance 
            WHERE student_id = ? 
            AND course_id = ? 
            AND status = 'Absent'
            AND date >= DATE_SUB(?, INTERVAL 30 DAY)
        ");
        $count_query->bind_param("iis", $student_id, $course_id, $date);
        $count_query->execute();
        $count_result = $count_query->get_result();
        $absence_data = $count_result->fetch_assoc();
        $absence_count = $absence_data['absence_count'];

        // Create notification based on absence count
        if ($absence_count >= 1) {
            $title = "Absence Alert - {$course_code}";
            $message = "You have been marked absent in {$course_name}. ";
            
            if ($absence_count > 1) {
                $message .= "This is your {$absence_count}th absence in the last 30 days. ";
                if ($absence_count >= 3) {
                    $title = "⚠️ Critical Absence Warning - {$course_code}";
                    $message .= "WARNING: You have reached the maximum allowed absences! Please contact your professor immediately.";
                }
            } else {
                $message .= "This is your first absence in the last 30 days.";
            }

            // Insert notification
            $insert_notif = $this->conn->prepare("
                INSERT INTO notifications (
                    type,
                    user_id,
                    user_type,
                    title,
                    message,
                    reference_id,
                    reference_type,
                    created_at,
                    is_read
                ) VALUES (
                    'absence_warning',
                    ?,
                    'student',
                    ?,
                    ?,
                    ?,
                    'attendance',
                    NOW(),
                    0
                )
            ");
            $insert_notif->bind_param("issi", $student_id, $title, $message, $course_id);
            $insert_notif->execute();
        }
    }

    public function resetAbsenceTracking($student_id, $course_id) {
        // Optional: Add any reset logic here if needed
    }

    public function getStudentAbsenceCount($student_id, $course_id, $days = 30) {
        $query = $this->conn->prepare("
            SELECT COUNT(*) as absence_count 
            FROM attendance 
            WHERE student_id = ? 
            AND course_id = ? 
            AND status = 'Absent'
            AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $query->bind_param("iii", $student_id, $course_id, $days);
        $query->execute();
        $result = $query->get_result();
        $data = $result->fetch_assoc();
        return $data['absence_count'];
    }
}
?> 