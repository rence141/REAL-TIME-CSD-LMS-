<?php
session_start();
include 'db_connect.php'; // Has $conn and $conn_student

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location.href='view_attendance_prof.php';</script>";
    exit();
}

$attendance_id = (int) $_GET['id'];

// Fetch attendance record
$query = "SELECT a.*, c.course_name FROM attendance a 
          JOIN courses c ON a.course_id = c.course_id 
          WHERE a.attendance_id = $attendance_id AND c.professor_id = $professor_id";

$result = $conn->query($query);
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Attendance record not found.'); window.location.href='view_attendance_prof.php';</script>";
    exit();
}

$attendance = $result->fetch_assoc();

// Get student info from student DB
$student_id = $attendance['student_id'];
$student_info = $conn_student->query("SELECT student_name FROM students WHERE student_id = $student_id")->fetch_assoc();
$student_name = $student_info['student_name'] ?? 'Unknown';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $conn->real_escape_string($_POST['status']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $date = $conn->real_escape_string($_POST['date']);

    $update = "UPDATE attendance SET status = '$status', notes = '$notes', date = '$date' WHERE attendance_id = $attendance_id";
    if ($conn->query($update)) {
        echo "<script>alert('Attendance updated successfully.'); window.location.href='view_attendance_prof.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating record: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Attendance</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="card p-4 shadow">
        <h2 class="mb-4">Edit Attendance</h2>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Student</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student_name) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Course</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($attendance['course_name']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($attendance['date']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="Present" <?= $attendance['status'] == 'Present' ? 'selected' : '' ?>>Present</option>
                    <option value="Absent" <?= $attendance['status'] == 'Absent' ? 'selected' : '' ?>>Absent</option>
                    <option value="Late" <?= $attendance['status'] == 'Late' ? 'selected' : '' ?>>Late</option>
                    <option value="Excused" <?= $attendance['status'] == 'Excused' ? 'selected' : '' ?>>Excused</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($attendance['notes']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Attendance</button>
            <a href="view_attendance_prof.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
