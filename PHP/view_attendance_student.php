<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Create connection to professors database with error handling
$conn_professors = new mysqli('localhost', 'root', '003421.!', 'lms_dashborad_professors');
if ($conn_professors->connect_error) {
    die("Connection to professors database failed: {$conn_professors->connect_error}");
}

// Check if student database connection exists
if (!isset($conn_student) || $conn_student->connect_error) {
    die("Connection to student database failed: " . ($conn_student->connect_error ?? "Connection not established"));
}

// Get student information with error handling
$stmt = $conn_student->prepare("SELECT student_name, profile FROM students WHERE student_id = ?");
if (!$stmt) {
    die("Prepare failed: {$conn_student->error}");
}
$stmt->bind_param("i", $student_id);
if (!$stmt->execute()) {
    die("Execute failed: {$stmt->error}");
}
$result = $stmt->get_result();
if (!$result) {
    die("Getting result failed: {$stmt->error}");
}
$student = $result->fetch_assoc();
if (!$student) {
    die("Student not found in database");
}
$student_name = $student['student_name'];
$profile_image = !empty($student['profile']) ? $student['profile'] : "Default_avatar.jpg.png";

// Get attendance statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused_count
    FROM attendance a
    JOIN enrollments e ON a.course_id = e.course_id
    WHERE e.student_id = ?";

$stmt = $conn_professors->prepare($stats_query);
if (!$stmt) {
    die("Prepare failed for stats query: {$conn_professors->error}");
}
$stmt->bind_param("i", $student_id);
if (!$stmt->execute()) {
    die("Execute failed for stats query: {$stmt->error}");
}
$result = $stmt->get_result();
if (!$result) {
    die("Getting result failed for stats query: {$stmt->error}");
}
$stats = $result->fetch_assoc();
if (!$stats) {
    // Initialize stats with zeros if no records found
    $stats = [
        'total_classes' => 0,
        'present_count' => 0,
        'absent_count' => 0,
        'late_count' => 0,
        'excused_count' => 0
    ];
}

$total_classes = $stats['total_classes'] ?: 1; // Avoid division by zero
$attendance_percentage = round(($stats['present_count'] / $total_classes) * 100);

// Get attendance records with error handling
$attendance_query = "
    SELECT DISTINCT
        a.attendance_id,
        a.date,
        a.status,
        a.notes,
        c.course_name,
        c.course_code,
        COALESCE(ap.status, 'none') as appeal_status,
        ap.reason as appeal_reason
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrollments e ON a.course_id = e.course_id AND e.student_id = ?
    LEFT JOIN attendance_appeals ap ON a.attendance_id = ap.attendance_id
    WHERE a.student_id = ?
    GROUP BY a.date, a.course_id
    ORDER BY a.date DESC";

$stmt = $conn_professors->prepare($attendance_query);
if (!$stmt) {
    die("Prepare failed for attendance query: " . $conn_professors->error);
}
$stmt->bind_param("ii", $student_id, $student_id);
if (!$stmt->execute()) {
    die("Execute failed for attendance query: " . $stmt->error);
}
$attendance_result = $stmt->get_result();
if (!$attendance_result) {
    die("Getting result failed for attendance query: " . $stmt->error);
}

// Handle appeal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appeal'])) {
    $attendance_id = $_POST['attendance_id'];
    $reason = $_POST['appeal_reason'];
    
    // Start transaction
    $conn_professors->begin_transaction();
    
    try {
        // Check if appeal already exists
        $check_appeal = $conn_professors->prepare("SELECT id FROM attendance_appeals WHERE attendance_id = ?");
        $check_appeal->bind_param("i", $attendance_id);
        $check_appeal->execute();
        
        if ($check_appeal->get_result()->num_rows === 0) {
            // Insert new appeal
            $insert_appeal = $conn_professors->prepare("
                INSERT INTO attendance_appeals (attendance_id, student_id, reason, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $insert_appeal->bind_param("iis", $attendance_id, $student_id, $reason);
            
            if ($insert_appeal->execute()) {
                // Update attendance status to 'Appealed'
                $update_status = $conn_professors->prepare("
                    UPDATE attendance 
                    SET status = 'Appealed'
                    WHERE attendance_id = ?
                ");
                $update_status->bind_param("i", $attendance_id);
                $update_status->execute();
                
                $conn_professors->commit();
                echo "<script>
                    alert('Appeal submitted successfully!');
                    window.location.href = window.location.href;
                </script>";
                exit();
            }
        } else {
            echo "<script>
                var modal = bootstrap.Modal.getInstance(document.getElementById('appealModal'));
                if (modal) modal.hide();
                alert('An appeal for this attendance record already exists.');
                window.location.href = window.location.href;
            </script>";
            exit();
        }
    } catch (Exception $e) {
        $conn_professors->rollback();
        echo "<script>
            var modal = bootstrap.Modal.getInstance(document.getElementById('appealModal'));
            if (modal) modal.hide();
            alert('Error submitting appeal: " . $e->getMessage() . "');
            window.location.href = window.location.href;
        </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #34A853;
            --danger-color: #EA4335;
            --warning-color: #FBBC05;
            --dark-color: #202124;
            --light-color: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        body {
            font-family: 'Google Sans', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: var(--border-radius);
        }

        .stat-item.present { background-color: rgba(52, 168, 83, 0.1); }
        .stat-item.absent { background-color: rgba(234, 67, 53, 0.1); }
        .stat-item.late { background-color: rgba(251, 188, 5, 0.1); }
        .stat-item.excused { background-color: rgba(66, 133, 244, 0.1); }

        .stat-value {
            font-size: 24px;
            font-weight: 500;
            margin: 10px 0;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .attendance-table th, 
        .attendance-table td {
            padding: 15px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        .attendance-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border: 1px solid #3367d6;
        }

        .attendance-table tr:hover {
            background-color: rgba(66, 133, 244, 0.05);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.present { background-color: rgba(52, 168, 83, 0.1); color: #34A853; }
        .status-badge.absent { background-color: rgba(234, 67, 53, 0.1); color: #EA4335; }
        .status-badge.late { background-color: rgba(251, 188, 5, 0.1); color: #FBBC05; }
        .status-badge.excused { background-color: rgba(66, 133, 244, 0.1); color: #4285F4; }

        .appeal-button {
            padding: 5px 10px;
            border: none;
            border-radius: 15px;
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .appeal-button:hover {
            background-color: #3367d6;
        }

        .appeal-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .modal-content {
            border-radius: var(--border-radius);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #3367d6;
            color: white;
            text-decoration: none;
        }

        .back-button i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <a href="dashboard_student.php" class="back-button">
            <i class="material-icons">arrow_back</i>
            Back to Dashboard
        </a>

        <div class="header">
            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile">
            <h1><?= htmlspecialchars($student_name) ?>'s Attendance</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-card">
            <div class="stats-grid">
                <div class="stat-item present">
                    <div class="stat-label">Present</div>
                    <div class="stat-value"><?= $stats['present_count'] ?></div>
                    <div class="stat-percent"><?= round(($stats['present_count'] / $total_classes) * 100) ?>%</div>
                </div>
                <div class="stat-item absent">
                    <div class="stat-label">Absent</div>
                    <div class="stat-value"><?= $stats['absent_count'] ?></div>
                    <div class="stat-percent"><?= round(($stats['absent_count'] / $total_classes) * 100) ?>%</div>
                </div>
                <div class="stat-item late">
                    <div class="stat-label">Late</div>
                    <div class="stat-value"><?= $stats['late_count'] ?></div>
                    <div class="stat-percent"><?= round(($stats['late_count'] / $total_classes) * 100) ?>%</div>
                </div>
                <div class="stat-item excused">
                    <div class="stat-label">Excused</div>
                    <div class="stat-value"><?= $stats['excused_count'] ?></div>
                    <div class="stat-percent"><?= round(($stats['excused_count'] / $total_classes) * 100) ?>%</div>
                </div>
            </div>
        </div>

        <!-- Attendance Records -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Appeal Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($record = $attendance_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                        <td><?= htmlspecialchars($record['course_name']) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($record['status']) ?>">
                                <?= htmlspecialchars($record['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($record['notes']) ?></td>
                        <td>
                            <?php if ($record['appeal_status'] !== 'none'): ?>
                                <span class="status-badge <?= $record['appeal_status'] ?>">
                                    <?= ucfirst($record['appeal_status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($record['status'] !== 'Present' && $record['appeal_status'] === 'none'): ?>
                                <button class="appeal-button" 
                                        onclick="showAppealModal(<?= $record['attendance_id'] ?>)">
                                    Appeal
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Appeal Modal -->
    <div class="modal fade" id="appealModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Attendance Appeal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="attendance_id" id="appealAttendanceId">
                        <div class="mb-3">
                            <label for="appealReason" class="form-label">Reason for Appeal</label>
                            <textarea class="form-control" id="appealReason" name="appeal_reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_appeal" class="btn btn-primary">Submit Appeal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAppealModal(attendanceId) {
            document.getElementById('appealAttendanceId').value = attendanceId;
            new bootstrap.Modal(document.getElementById('appealModal')).show();
        }
    </script>
</body>
</html>
