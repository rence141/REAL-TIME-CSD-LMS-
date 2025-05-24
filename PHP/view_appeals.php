<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Handle specific appeal viewing
$highlight_appeal_id = isset($_GET['appeal_id']) ? (int)$_GET['appeal_id'] : null;

// Build the ORDER BY clause based on highlight_appeal_id
$order_by = $highlight_appeal_id ? "ORDER BY aa.id = $highlight_appeal_id DESC, aa.created_at DESC" : "ORDER BY aa.created_at DESC";

// Modify the appeals query to sort highlighted appeal first if specified
$appeals_query = "
    SELECT 
        aa.id as appeal_id,
        aa.reason,
        aa.status as appeal_status,
        aa.created_at,
        aa.admin_remarks,
        a.date as attendance_date,
        a.status as attendance_status,
        c.course_name,
        s.student_name
    FROM attendance_appeals aa
    JOIN attendance a ON aa.attendance_id = a.attendance_id
    JOIN courses c ON a.course_id = c.course_id
    JOIN lms_database_student.students s ON aa.student_id = s.student_id
    WHERE c.professor_id = ?
    $order_by";

$stmt = $conn->prepare($appeals_query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$appeals_result = $stmt->get_result();

// Handle appeal status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appeal_id'])) {
    $appeal_id = $_POST['appeal_id'];
    $new_status = $_POST['status'];
    $admin_remarks = $_POST['admin_remarks'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update appeal status
        $update_appeal = $conn->prepare("
            UPDATE attendance_appeals 
            SET status = ?, admin_remarks = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND attendance_id IN (
                SELECT attendance_id FROM attendance a
                JOIN courses c ON a.course_id = c.course_id
                WHERE c.professor_id = ?
            )
        ");
        $update_appeal->bind_param("ssii", $new_status, $admin_remarks, $appeal_id, $professor_id);
        $update_appeal->execute();

        // Get the attendance_id for this appeal
        $get_attendance = $conn->prepare("
            SELECT attendance_id FROM attendance_appeals WHERE id = ?
        ");
        $get_attendance->bind_param("i", $appeal_id);
        $get_attendance->execute();
        $attendance_result = $get_attendance->get_result();
        $attendance_row = $attendance_result->fetch_assoc();
        $attendance_id = $attendance_row['attendance_id'];

        // Update attendance status based on appeal decision
        $new_attendance_status = $new_status === 'approved' ? 'Excused' : 'Absent';
        $status_note = $new_status === 'approved' ? 
            ' [Excused: ' . $admin_remarks . ']' : 
            ' [Absence Upheld: ' . $admin_remarks . ']';

        $update_attendance = $conn->prepare("
            UPDATE attendance 
            SET status = ?,
                notes = ?
            WHERE attendance_id = ?
        ");
        $update_attendance->bind_param("ssi", $new_attendance_status, $admin_remarks, $attendance_id);
        $update_attendance->execute();

        // Commit transaction
        $conn->commit();
        echo "<script>
            alert('Appeal status updated successfully!');
            window.location.href = 'view_appeals.php';
        </script>";
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "<script>alert('Error updating appeal status: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance Appeals</title>
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

        .appeal-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
        }

        .appeal-card.highlighted {
            border: 2px solid var(--primary-color);
            animation: highlight-pulse 2s infinite;
        }

        @keyframes highlight-pulse {
            0% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(66, 133, 244, 0); }
            100% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0); }
        }

        .appeal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.pending { 
            background-color: rgba(251, 188, 5, 0.1);
            color: #FBBC05;
        }
        .status-badge.approved { 
            background-color: rgba(52, 168, 83, 0.1);
            color: #34A853;
        }
        .status-badge.rejected { 
            background-color: rgba(234, 67, 53, 0.1);
            color: #EA4335;
        }

        .appeal-details {
            margin-bottom: 15px;
        }

        .appeal-actions {
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
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
        <a href="attendance_prof.php" class="back-button">
            <i class="material-icons">arrow_back</i>
            Back to Attendance
        </a>

        <h1 class="mb-4">Attendance Appeals</h1>

        <?php while ($appeal = $appeals_result->fetch_assoc()): ?>
            <div class="appeal-card <?= ($highlight_appeal_id && $appeal['appeal_id'] == $highlight_appeal_id) ? 'highlighted' : '' ?>">
                <div class="appeal-header">
                    <h5 class="mb-0"><?= htmlspecialchars($appeal['student_name']) ?></h5>
                    <span class="status-badge <?= strtolower($appeal['appeal_status']) ?>">
                        <?= ucfirst(htmlspecialchars($appeal['appeal_status'])) ?>
                    </span>
                </div>
                <div class="appeal-details">
                    <p><strong>Course:</strong> <?= htmlspecialchars($appeal['course_name']) ?></p>
                    <p><strong>Date:</strong> <?= date('M d, Y', strtotime($appeal['attendance_date'])) ?></p>
                    <p><strong>Original Status:</strong> <?= htmlspecialchars($appeal['attendance_status']) ?></p>
                    <p><strong>Appeal Reason:</strong> <?= htmlspecialchars($appeal['reason']) ?></p>
                    <p><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($appeal['created_at'])) ?></p>
                    <?php if ($appeal['admin_remarks']): ?>
                        <p><strong>Professor's Remarks:</strong> <?= htmlspecialchars($appeal['admin_remarks']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($appeal['appeal_status'] === 'pending'): ?>
                    <div class="appeal-actions">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="appeal_id" value="<?= $appeal['appeal_id'] ?>">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Update Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="admin_remarks" class="form-label">Remarks</label>
                                <textarea name="admin_remarks" id="admin_remarks" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Appeal</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($highlight_appeal_id): ?>
    <script>
        // Scroll to highlighted appeal
        document.querySelector('.highlighted')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    </script>
    <?php endif; ?>
</body>
</html> 
