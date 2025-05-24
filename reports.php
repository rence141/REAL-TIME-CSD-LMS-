<?php
session_start();
include 'db_connect.php'; // Database connection

// Check if professor is logged in
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor information
$stmt_prof = $conn->prepare("SELECT Full_Name, email, profile_image FROM professors WHERE id = ?");
$stmt_prof->bind_param("i", $professor_id);
$stmt_prof->execute();
$prof_result = $stmt_prof->get_result();
$prof_data = $prof_result->fetch_assoc();

$professor_name = $prof_data['Full_Name'];
$professor_email = $prof_data['email'];
$profile_image = $prof_data['profile_image'];

// Fetch Course Statistics
$course_query = "
    SELECT COUNT(DISTINCT c.course_id) as total_courses,
           COUNT(DISTINCT e.student_id) as total_students
    FROM courses c
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    WHERE c.professor_id = ?";
$stmt_course = $conn->prepare($course_query);
$stmt_course->bind_param("i", $professor_id);
$stmt_course->execute();
$course_stats = $stmt_course->get_result()->fetch_assoc();

// Fetch Attendance Statistics
$attendance_query = "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    WHERE c.professor_id = ?";
$stmt_attendance = $conn->prepare($attendance_query);
$stmt_attendance->bind_param("i", $professor_id);
$stmt_attendance->execute();
$attendance_stats = $stmt_attendance->get_result()->fetch_assoc();

// Calculate attendance percentages
$total_attendance = $attendance_stats['total_records'] ?: 1; // Avoid division by zero
$present_percentage = round(($attendance_stats['present_count'] / $total_attendance) * 100);
$absent_percentage = round(($attendance_stats['absent_count'] / $total_attendance) * 100);
$late_percentage = round(($attendance_stats['late_count'] / $total_attendance) * 100);

// Fetch Recent Submissions
$submissions_query = "
    SELECT 
        s.*,
        a.activity_name,
        a.activity_type,
        c.course_name
    FROM submissions s
    LEFT JOIN activities a ON s.activity_id = a.activity_id
    LEFT JOIN courses c ON a.course_id = c.course_id
    WHERE s.professor_id = ?
    ORDER BY s.submission_date DESC
    LIMIT 5";
$stmt_submissions = $conn->prepare($submissions_query);
$stmt_submissions->bind_param("i", $professor_id);
$stmt_submissions->execute();
$recent_submissions = $stmt_submissions->get_result();

// Get submission statistics
$submission_stats_query = "
    SELECT 
        COUNT(*) as total_submissions,
        COUNT(DISTINCT student_id) as unique_submitters,
        COUNT(DISTINCT activity_id) as activities_with_submissions,
        SUM(CASE WHEN DATE(submission_date) = CURDATE() THEN 1 ELSE 0 END) as today_submissions
    FROM submissions
    WHERE professor_id = ?";
$stmt_submission_stats = $conn->prepare($submission_stats_query);
$stmt_submission_stats->bind_param("i", $professor_id);
$stmt_submission_stats->execute();
$submission_stats = $stmt_submission_stats->get_result()->fetch_assoc();

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Bootstrap -->
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
            --box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #3c4043;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            box-shadow: var(--box-shadow);
            z-index: 100;
        }

        .sidebar-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            height: 40px;
            margin-right: 12px;
        }

        .app-name {
            font-size: 18px;
            font-weight: 500;
            color: #5f6368;
        }

        /* Professor Profile */
        .professor-profile {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.12);
            background-color: #f8f9fa;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .professor-info {
            display: flex;
            flex-direction: column;
        }

        .professor-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .professor-email {
            font-size: 12px;
            color: #5f6368;
        }

        /* Navigation Menu */
        .nav-menu {
            padding: 8px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: var(--transition);
        }

        .nav-item:hover {
            background-color: #f1f3f4;
        }

        .nav-item.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }

        .nav-item i {
            margin-right: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 24px;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .stats-card-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stats-card-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--dark-color);
        }

        .stats-card-body {
            padding: 16px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .stat-icon.primary { background-color: #e8f0fe; color: var(--primary-color); }
        .stat-icon.success { background-color: #e6f4ea; color: var(--secondary-color); }
        .stat-icon.warning { background-color: #fef7e0; color: var(--warning-color); }
        .stat-icon.danger { background-color: #fce8e6; color: var(--danger-color); }

        .stat-info h4 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .stat-info p {
            margin: 4px 0 0;
            color: #5f6368;
            font-size: 14px;
        }

        /* Progress Bars */
        .progress {
            height: 8px;
            margin-top: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <span class="app-name">Computer Science Department</span>
        </div>
        
        <nav class="nav-menu">
            <div class="professor-profile">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-image">
                <div class="professor-info">
                    <span class="professor-name"><?= htmlspecialchars($professor_name) ?></span>
                    <span class="professor-email"><?= htmlspecialchars($professor_email) ?></span>
                </div>
            </div>
            
            <a href="dashboard_professor.php" class="nav-item">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="attendance_prof.php" class="nav-item">
                <i class="material-icons">check_circle</i>
                <span>Attendance</span>
            </a>
            <a href="grades.php" class="nav-item">
                <i class="material-icons">grade</i>
                <span>Grade Entry</span>
            </a>
            <a href="schedule.php" class="nav-item">
                <i class="material-icons">calendar_today</i>
                <span>Schedules</span>
            </a>
            <a href="manage_courses.php" class="nav-item">
                <i class="material-icons">class</i>
                <span>Course Management</span>
            </a>
            <a href="reports.php" class="nav-item active">
                <i class="material-icons">bar_chart</i>
                <span>Reports</span>
            </a>
           
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Reports & Analytics</h2>

            <div class="row">
                <!-- Course Statistics -->
                <div class="col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <h3>Course Statistics</h3>
                            <i class="material-icons">school</i>
                        </div>
                        <div class="stats-card-body">
                            <div class="stat-item">
                                <div class="stat-icon primary">
                                    <i class="material-icons">book</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $course_stats['total_courses'] ?></h4>
                                    <p>Total Courses</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon success">
                                    <i class="material-icons">people</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $course_stats['total_students'] ?></h4>
                                    <p>Total Students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <h3>Attendance Overview</h3>
                            <i class="material-icons">event_available</i>
                        </div>
                        <div class="stats-card-body">
                            <div class="stat-item">
                                <div class="stat-icon success">
                                    <i class="material-icons">check_circle</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $present_percentage ?>%</h4>
                                    <p>Present</p>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?= $present_percentage ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon warning">
                                    <i class="material-icons">schedule</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $late_percentage ?>%</h4>
                                    <p>Late</p>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: <?= $late_percentage ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon danger">
                                    <i class="material-icons">cancel</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $absent_percentage ?>%</h4>
                                    <p>Absent</p>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: <?= $absent_percentage ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission Statistics -->
                <div class="col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <h3>Submission Analytics</h3>
                            <i class="material-icons">assignment_turned_in</i>
                        </div>
                        <div class="stats-card-body">
                            <div class="stat-item">
                                <div class="stat-icon primary">
                                    <i class="material-icons">today</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $submission_stats['total_submissions'] ?></h4>
                                    <p>Total Submissions</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon success">
                                    <i class="material-icons">people</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $submission_stats['unique_submitters'] ?></h4>
                                    <p>Unique Submitters</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon warning">
                                    <i class="material-icons">class</i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $submission_stats['activities_with_submissions'] ?></h4>
                                    <p>Activities with Submissions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Submissions -->
                <div class="col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <h3>Recent Submissions</h3>
                            <i class="material-icons">history</i>
                        </div>
                        <div class="stats-card-body">
                            <?php if ($recent_submissions->num_rows > 0): ?>
                                <?php while ($submission = $recent_submissions->fetch_assoc()): ?>
                                    <div class="submission-item d-flex align-items-center mb-3 p-2 border rounded">
                                        <div class="submission-icon me-3">
                                            <i class="material-icons text-primary">description</i>
                                        </div>
                                        <div class="submission-info flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-truncate">
                                                    Student ID: <?= htmlspecialchars($submission['student_id']) ?>
                                                </strong>
                                                <small class="text-muted">
                                                    <?= getRelativeDate($submission['submission_date']) ?>
                                                </small>
                                            </div>
                                            <small class="text-muted d-block">
                                                <?= $submission['course_name'] ? htmlspecialchars($submission['course_name']) . ' - ' : '' ?>
                                                <?= $submission['activity_name'] ? htmlspecialchars($submission['activity_name']) : 'Activity #' . $submission['activity_id'] ?>
                                                <?php if ($submission['grade']): ?>
                                                    <span class="badge bg-success">Grade: <?= htmlspecialchars($submission['grade']) ?></span>
                                                <?php endif; ?>
                                            </small>
                                            <?php if ($submission['comments']): ?>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="material-icons" style="font-size: 14px;">comment</i>
                                                    <?= htmlspecialchars($submission['comments']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="material-icons d-block mb-2">inbox</i>
                                    No submissions found
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>