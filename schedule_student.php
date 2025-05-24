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

// Create connection to professors database
$conn_professors = new mysqli('localhost', 'root', '003421.!', 'lms_dashborad_professors');
if ($conn_professors->connect_error) {
    die("Connection to professors database failed: {$conn_professors->connect_error}");
}

// Create connection to students database
$conn_students = new mysqli('localhost', 'root', '003421.!', 'lms_database_student');
if ($conn_students->connect_error) {
    die("Connection to students database failed: {$conn_students->connect_error}");
}

// Get student's section first
$section_query = "SELECT section FROM students WHERE student_id = ?";
$stmt_section = $conn_students->prepare($section_query);
$stmt_section->bind_param("i", $student_id);
$stmt_section->execute();
$section_result = $stmt_section->get_result();
$student_section = $section_result->fetch_assoc()['section'] ?? '';
$stmt_section->close();

// Get student's enrolled courses schedule
$schedule_query = "
    SELECT 
        cs.day_of_week as day,
        cs.start_time,
        cs.end_time,
        cs.room,
        e.course_name,
        c.course_code,
        p.Full_Name as professor,
        e.enrolled_at
    FROM lms_dashborad_professors.enrollments e
    JOIN lms_dashborad_professors.class_schedule cs ON e.course_id = cs.course_id
    JOIN lms_dashborad_professors.courses c ON cs.course_id = c.course_id
    JOIN lms_dashborad_professors.professors p ON cs.professor_id = p.id
    WHERE e.student_id = ?
    ORDER BY 
        CASE 
            WHEN cs.day_of_week = 'Monday' THEN 1
            WHEN cs.day_of_week = 'Tuesday' THEN 2
            WHEN cs.day_of_week = 'Wednesday' THEN 3
            WHEN cs.day_of_week = 'Thursday' THEN 4
            WHEN cs.day_of_week = 'Friday' THEN 5
            WHEN cs.day_of_week = 'Saturday' THEN 6
            ELSE 7
        END,
        cs.start_time";

$stmt = $conn_professors->prepare($schedule_query);
if (!$stmt) {
    die("Prepare failed for schedule query: {$conn_professors->error}");
}
$stmt->bind_param("i", $student_id);
if (!$stmt->execute()) {
    die("Execute failed for schedule query: {$stmt->error}");
}
$schedule_result = $stmt->get_result();
if (!$schedule_result) {
    die("Getting result failed for schedule query: {$stmt->error}");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule</title>
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
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin: 0;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .schedule-table th,
        .schedule-table td {
            padding: 15px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        .schedule-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border: 1px solid #3367d6;
        }

        .schedule-table tr:hover {
            background-color: rgba(66, 133, 244, 0.05);
        }

        .time-cell {
            white-space: nowrap;
            color: var(--dark-color);
        }

        .course-cell {
            font-weight: 500;
        }

        .professor-cell {
            color: var(--primary-color);
        }

        .room-cell {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
            border-radius: 4px;
            padding: 4px 8px;
            display: inline-block;
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
            <h1>My Class Schedule</h1>
        </div>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Course</th>
                    <th>Professor</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($schedule_result->num_rows > 0): ?>
                    <?php while ($schedule = $schedule_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['day']) ?></td>
                            <td class="time-cell">
                                <?= date('h:i A', strtotime($schedule['start_time'])) ?> - 
                                <?= date('h:i A', strtotime($schedule['end_time'])) ?>
                            </td>
                            <td class="course-cell">
                                <?= htmlspecialchars($schedule['course_name']) ?>
                                <br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($schedule['course_code']) ?> 
                                    <?php if (!empty($student_section)): ?>
                                        (Section <?= htmlspecialchars($student_section) ?>)
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td class="professor-cell"><?= htmlspecialchars($schedule['professor']) ?></td>
                            <td><span class="room-cell"><?= htmlspecialchars($schedule['room']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No scheduled classes found. Please enroll in courses to view your schedule.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 