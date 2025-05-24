<?php
require_once 'db_connect.php';
session_start();

class NotificationSystem {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getRecentSubmissions($professorId, $limit = null) {
        $query = "SELECT 
                    s.submission_id,
                    s.student_id,
                    s.activity_id,
                    s.submission_date,
                    s.file_path,
                    a.activity_name,
                    a.activity_type,
                    c.course_name,
                    st.first_name,
                    st.last_name
                FROM submissions s
                JOIN activities a ON s.activity_id = a.activity_id
                JOIN courses c ON a.course_id = c.course_id
                JOIN students st ON s.student_id = st.student_id
                WHERE a.professor_id = ?
                AND s.grade IS NULL
                ORDER BY s.submission_date DESC";
        
        if ($limit) {
            $query .= " LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $professorId, $limit);
        } else {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $professorId);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getGradeAppeals($professorId, $limit = 5) {
        $query = "SELECT ga.appeal_id, ga.student_id, ga.grade_id, ga.reason, 
                         ga.submission_date, st.first_name, st.last_name,
                         c.course_name
                  FROM grade_appeals ga
                  JOIN students st ON ga.student_id = st.student_id
                  JOIN grades g ON ga.grade_id = g.grade_id
                  JOIN courses c ON g.course_id = c.course_id
                  WHERE c.professor_id = ? AND ga.status = 'pending'
                  ORDER BY ga.submission_date DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $professorId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getAttendanceNotifications($professorId, $limit = 5) {
        // Modified to work with existing attendance table
        $query = "SELECT a.attendance_id, a.student_id, a.course_id, a.status, 
                         a.date, a.notes, st.first_name, st.last_name,
                         c.course_name
                  FROM attendance a
                  JOIN students st ON a.student_id = st.student_id
                  JOIN courses c ON a.course_id = c.course_id
                  WHERE a.professor_id = ? 
                  AND (a.status = 'Late' OR a.status = 'Absent')
                  AND a.date = CURRENT_DATE
                  ORDER BY a.date DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $professorId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAttendanceAppeals($professorId, $limit = null) {
        $query = "SELECT 
                    a.attendance_id,
                    a.student_id,
                    a.course_id,
                    a.status,
                    a.date,
                    a.notes,
                    st.first_name,
                    st.last_name,
                    c.course_name,
                    CASE 
                        WHEN a.notes LIKE '%[Appeal Submitted]%' AND a.notes NOT LIKE '%[Appeal Approved%' AND a.notes NOT LIKE '%[Appeal Rejected%' THEN 'pending'
                        WHEN a.notes LIKE '%[Appeal Approved%' THEN 'approved'
                        WHEN a.notes LIKE '%[Appeal Rejected%' THEN 'rejected'
                        ELSE 'none'
                    END as appeal_status,
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(a.notes, '[Appeal Submitted] ', -1),
                        '[Appeal',
                        1
                    ) as appeal_reason
                FROM attendance a
                JOIN students st ON a.student_id = st.student_id
                JOIN courses c ON a.course_id = c.course_id
                WHERE a.professor_id = ?
                AND (
                    (a.status IN ('Absent', 'Late') AND a.notes NOT LIKE '%[Appeal%')
                    OR 
                    (a.notes LIKE '%[Appeal Submitted]%' AND a.notes NOT LIKE '%[Appeal Approved%' AND a.notes NOT LIKE '%[Appeal Rejected%')
                )
                ORDER BY 
                    CASE WHEN a.notes LIKE '%[Appeal Submitted]%' THEN 0 ELSE 1 END,
                    a.date DESC";
        
        if ($limit) {
            $query .= " LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $professorId, $limit);
        } else {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $professorId);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUnreadNotificationsCount($professorId) {
        $query = "SELECT 
                    (SELECT COUNT(*) 
                     FROM submissions s
                     JOIN activities a ON s.activity_id = a.activity_id
                     WHERE a.professor_id = ? 
                     AND s.grade IS NULL) +
                    (SELECT COUNT(*) 
                     FROM attendance a
                     WHERE a.professor_id = ?
                     AND (
                         (a.status IN ('Absent', 'Late') AND a.notes NOT LIKE '%[Appeal%')
                         OR 
                         (a.notes LIKE '%[Appeal Submitted]%' AND a.notes NOT LIKE '%[Appeal Approved%' AND a.notes NOT LIKE '%[Appeal Rejected%')
                     )
                     AND a.date >= CURDATE() - INTERVAL 7 DAY
                    ) as total";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $professorId, $professorId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    
    public function markNotificationAsRead($notificationType, $notificationId) {
        switch($notificationType) {
            case 'submission':
                $query = "UPDATE submissions SET grade = 0 WHERE submission_id = ?";
                break;
            case 'attendance':
                // Mark as viewed without changing the appeal status
                $query = "UPDATE attendance 
                         SET notes = CONCAT(IFNULL(notes, ''), ' [Viewed]') 
                         WHERE attendance_id = ? 
                         AND notes NOT LIKE '%[Viewed]%'";
                break;
        }
        
        if (!empty($query)) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $notificationId);
            return $stmt->execute();
        }
        return false;
    }

    public function handleAppeal($attendanceId, $decision, $remarks) {
        $this->conn->begin_transaction();
        
        try {
            // Get current attendance record
            $get_attendance = $this->conn->prepare("SELECT status, notes FROM attendance WHERE attendance_id = ?");
            $get_attendance->bind_param("i", $attendanceId);
            $get_attendance->execute();
            $attendance = $get_attendance->get_result()->fetch_assoc();
            
            // Prepare new notes
            $new_notes = $attendance['notes'] ?? '';
            $decision_text = $decision === 'approved' ? '[Appeal Approved: ' : '[Appeal Rejected: ';
            $new_notes .= $decision_text . $remarks . ']';
            
            // Update attendance
            $update = $this->conn->prepare("
                UPDATE attendance 
                SET status = ?,
                    notes = ?
                WHERE attendance_id = ?
            ");
            
            $new_status = $decision === 'approved' ? 'Excused' : $attendance['status'];
            $update->bind_param("ssi", $new_status, $new_notes, $attendanceId);
            $update->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}

// API endpoint to get notifications
if (isset($_GET['action'])) {
    $notification = new NotificationSystem($conn);
    $professorId = $_SESSION['professor_id'] ?? null;
    
    if (!$professorId) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    switch($_GET['action']) {
        case 'get_all':
            $response = [
                'submissions' => $notification->getRecentSubmissions($professorId),
                'appeals' => $notification->getAttendanceAppeals($professorId),
                'unread_count' => $notification->getUnreadNotificationsCount($professorId)
            ];
            echo json_encode($response);
            break;
            
        case 'mark_read':
            if (isset($_POST['type']) && isset($_POST['id'])) {
                $success = $notification->markNotificationAsRead($_POST['type'], $_POST['id']);
                echo json_encode(['success' => $success]);
            }
            break;

        case 'handle_appeal':
            if (isset($_POST['attendance_id']) && isset($_POST['decision']) && isset($_POST['remarks'])) {
                $success = $notification->handleAppeal(
                    $_POST['attendance_id'],
                    $_POST['decision'],
                    $_POST['remarks']
                );
                echo json_encode(['success' => $success]);
            }
            break;
    }
}
?> 
