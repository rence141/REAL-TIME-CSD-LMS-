<?php
session_start();
include 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login.php';</script>";
    exit();
}

$student_id = $_SESSION['student_id'];

// Create connection to professors database
$conn_professors = new mysqli('localhost', 'root', '003421.!', 'lms_dashborad_professors');
if ($conn_professors->connect_error) {
    die("Connection to professors database failed: " . $conn_professors->connect_error);
}

// Get student information
$stmt = $conn_student->prepare("SELECT student_name, profile, section FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_name = $student['student_name'];
$profile_image = !empty($student['profile']) ? $student['profile'] : "Default_avatar.jpg.png";
$section = $student['section'];

// Fetch Course Statistics
$course_query = "
    SELECT 
        COUNT(DISTINCT e.course_id) as total_courses,
        COUNT(DISTINCT s.submission_id) as total_submissions,
        COUNT(DISTINCT CASE WHEN s.grade IS NOT NULL THEN s.submission_id END) as graded_submissions,
        COALESCE(AVG(s.grade), 0) as average_grade
    FROM enrollments e
    LEFT JOIN activities a ON e.course_id = a.course_id
    LEFT JOIN submissions s ON a.activity_id = s.activity_id AND s.student_id = ?
    WHERE e.student_id = ?";
$stmt_course = $conn_professors->prepare($course_query);
$stmt_course->bind_param("ii", $student_id, $student_id);
$stmt_course->execute();
$course_stats = $stmt_course->get_result()->fetch_assoc();

// Fetch Attendance Statistics
$attendance_query = "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused_count
    FROM attendance
    WHERE student_id = ?";
$stmt_attendance = $conn_professors->prepare($attendance_query);
$stmt_attendance->bind_param("i", $student_id);
$stmt_attendance->execute();
$attendance_stats = $stmt_attendance->get_result()->fetch_assoc();

// Calculate attendance percentages
$total_attendance = $attendance_stats['total_records'] ?: 1; // Avoid division by zero
$present_percentage = round(($attendance_stats['present_count'] / $total_attendance) * 100);
$absent_percentage = round(($attendance_stats['absent_count'] / $total_attendance) * 100);
$late_percentage = round(($attendance_stats['late_count'] / $total_attendance) * 100);
$excused_percentage = round(($attendance_stats['excused_count'] / $total_attendance) * 100);

// Fetch Recent Grades
$grades_query = "
    SELECT 
        s.submission_id,
        s.grade,
        s.submission_date,
        a.activity_name,
        a.activity_type,
        c.course_name,
        c.course_code,
        CASE 
            WHEN s.grade >= 97 THEN '1.00'
            WHEN s.grade >= 94 THEN '1.25'
            WHEN s.grade >= 91 THEN '1.50'
            WHEN s.grade >= 88 THEN '1.75'
            WHEN s.grade >= 85 THEN '2.00'
            WHEN s.grade >= 82 THEN '2.25'
            WHEN s.grade >= 79 THEN '2.50'
            WHEN s.grade >= 76 THEN '2.75'
            WHEN s.grade >= 75 THEN '3.00'
            ELSE '5.00'
        END as grade_equivalent
    FROM submissions s
    JOIN activities a ON s.activity_id = a.activity_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.student_id = ? AND s.grade IS NOT NULL
    ORDER BY s.submission_date DESC
    LIMIT 5";
$stmt_grades = $conn_professors->prepare($grades_query);
$stmt_grades->bind_param("i", $student_id);
$stmt_grades->execute();
$recent_grades = $stmt_grades->get_result();

// Function to format relative date
function getRelativeDate($date) {
    $now = new DateTime();
    $submissionDate = new DateTime($date);
    $diff = $now->diff($submissionDate);
    
    if ($diff->d == 0) {
        if ($diff->h == 0) {
            return $diff->i . " minutes ago";
        }
        return $diff->h . " hours ago";
    } elseif ($diff->d == 1) {
        return "Yesterday";
    } else {
        return date('M d', strtotime($date));
    }
}

// Calculate GPA
function calculateGPA($grade) {
    if ($grade >= 97) return '1.00';
    if ($grade >= 94) return '1.25';
    if ($grade >= 91) return '1.50';
    if ($grade >= 88) return '1.75';
    if ($grade >= 85) return '2.00';
    if ($grade >= 82) return '2.25';
    if ($grade >= 79) return '2.50';
    if ($grade >= 76) return '2.75';
    if ($grade >= 75) return '3.00';
    return '5.00';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Report - <?= htmlspecialchars($student_name) ?></title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
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
            --border-color: #e0e0e0;
            --border-radius: 8px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        body {
            font-family: 'Google Sans', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-details h1 {
            margin: 0;
            font-size: 24px;
            color: var(--dark-color);
        }

        .student-details p {
            margin: 5px 0 0;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .stat-card h3 {
            margin: 0 0 15px;
            color: var(--dark-color);
            font-size: 18px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .attendance-chart {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .attendance-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .attendance-label-text {
            min-width: 70px;
            font-size: 14px;
            color: #666;
        }

        .attendance-bar {
            flex: 1;
            height: 30px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.05);
        }

        .attendance-bar-fill {
            position: absolute;
            left: 0;
            height: 100%;
            border-radius: 10px;
            min-width: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .attendance-value {
            color: white;
            font-size: 14px;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            z-index: 1;
        }

        .recent-grades {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .grade-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .grade-item:last-child {
            border-bottom: none;
        }

        .grade-info h4 {
            margin: 0;
            font-size: 16px;
            color: var(--dark-color);
        }

        .grade-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        .grade-value {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #3367d6;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="student-info">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-image">
                <div class="student-details">
                    <h1><?= htmlspecialchars($student_name) ?></h1>
                    <p>Section: <?= htmlspecialchars($section) ?></p>
                </div>
            </div>
            <a href="dashboard_student.php" class="back-button">
                <i class="material-icons">arrow_back</i>
                Back to Dashboard
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Courses</h3>
                <div class="stat-value"><?= $course_stats['total_courses'] ?></div>
                <p>Enrolled Courses</p>
            </div>

            <div class="stat-card">
                <h3>Academic Performance</h3>
                <div class="stat-value"><?= number_format($course_stats['average_grade'], 2) ?></div>
                <p>Average Grade</p>
                <p>GPA Equivalent: <?= calculateGPA($course_stats['average_grade']) ?></p>
            </div>

            <div class="stat-card">
                <h3>Submissions</h3>
                <div class="stat-value"><?= $course_stats['graded_submissions'] ?>/<?= $course_stats['total_submissions'] ?></div>
                <p>Graded/Total Submissions</p>
            </div>

            <div class="stat-card">
                <h3>Attendance Statistics</h3>
                <div class="attendance-chart">
                    <div class="attendance-row">
                        <span class="attendance-label-text">Present</span>
                        <div class="attendance-bar">
                            <div class="attendance-bar-fill" style="width: <?= $present_percentage ?>%; background: #34A853;">
                                <span class="attendance-value"><?= $attendance_stats['present_count'] ?> (<?= $present_percentage ?>%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <span class="attendance-label-text">Absent</span>
                        <div class="attendance-bar">
                            <div class="attendance-bar-fill" style="width: <?= $absent_percentage ?>%; background: #EA4335;">
                                <span class="attendance-value"><?= $attendance_stats['absent_count'] ?> (<?= $absent_percentage ?>%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <span class="attendance-label-text">Late</span>
                        <div class="attendance-bar">
                            <div class="attendance-bar-fill" style="width: <?= $late_percentage ?>%; background: #FBBC05;">
                                <span class="attendance-value"><?= $attendance_stats['late_count'] ?> (<?= $late_percentage ?>%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <span class="attendance-label-text">Excused</span>
                        <div class="attendance-bar">
                            <div class="attendance-bar-fill" style="width: <?= $excused_percentage ?>%; background: #4285F4;">
                                <span class="attendance-value"><?= $attendance_stats['excused_count'] ?> (<?= $excused_percentage ?>%)</span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 5px; font-size: 12px; color: #666;">
                        Total Sessions: <?= $attendance_stats['total_records'] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-grades">
            <h3>Recent Grades</h3>
            <?php if ($recent_grades->num_rows > 0): ?>
                <?php while ($grade = $recent_grades->fetch_assoc()): ?>
                    <div class="grade-item">
                        <div class="grade-info">
                            <h4><?= htmlspecialchars($grade['activity_name']) ?></h4>
                            <p><?= htmlspecialchars($grade['course_code']) ?> - <?= htmlspecialchars($grade['course_name']) ?></p>
                            <p><?= getRelativeDate($grade['submission_date']) ?></p>
                        </div>
                        <div class="grade-value">
                            <?= number_format($grade['grade'], 2) ?>
                            <small>(<?= $grade['grade_equivalent'] ?>)</small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No grades available yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
