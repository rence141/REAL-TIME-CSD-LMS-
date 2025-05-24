<?php
// **Dashboard for Students**
session_start();
include 'db_connect.php'; // Ensure this file initializes $conn_student

// Add at the beginning of the file, after session_start()
date_default_timezone_set('Asia/Manila');

// Ensure user is logged in as student
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['student_id'];
$today = date('l'); // Get current day name

// Create connection to professors database
$conn_professors = new mysqli('localhost', 'root', '003421.!', 'lms_dashborad_professors');

// Add connection error checking
if ($conn_professors->connect_error) {
    echo "<!-- Connection failed: " . $conn_professors->connect_error . " -->";
}

// **Use `$conn_student` instead of `$conn`**
$query = $conn_student->prepare("SELECT student_name, profile, email FROM students WHERE student_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = $user['student_name'];
    $user_email = $user['email'];
    $profile_image = !empty($user['profile']) ? $user['profile'] : "Default_avatar.jpg.png";
} else {
    $user_name = "Unknown";
    $user_email = "N/A";
    $profile_image = "Default_avatar.jpg.png";
}

// First, let's check if the student has any submissions at all
$check_submissions = "SELECT COUNT(*) as submission_count FROM submissions WHERE student_id = ? AND grade IS NOT NULL";
$check_stmt = $conn_professors->prepare($check_submissions);
if ($check_stmt) {
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $count_result = $check_stmt->get_result();
    $submission_count = $count_result->fetch_assoc()['submission_count'];
    echo "<!-- Total graded submissions found for student " . $user_id . ": " . $submission_count . " -->";
    $check_stmt->close();
}

// Get all notifications (both grades and absences)
$query = "SELECT 
    'grade' as source,
    s.submission_id as id,
    'submission' as type,
    s.submission_date as created_at,
    CONCAT('Grade posted for ', a.activity_name, ' in ', c.course_code) as display_title,
    CONCAT('Grade: ', s.grade, '%') as display_message,
    s.grade,
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
WHERE s.student_id = ? 
AND s.grade IS NOT NULL

UNION ALL

SELECT 
    'notification' as source,
    n.id,
    n.type,
    n.created_at,
    n.title as display_title,
    n.message as display_message,
    NULL as grade,
    NULL as grade_equivalent
FROM notifications n
WHERE n.user_id = ?
AND n.user_type = 'student'
AND n.type = 'absence_warning'
AND n.is_read = 0

ORDER BY created_at DESC
LIMIT 10";

$stmt = $conn_professors->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$notifications_result = $stmt->get_result();

// Get enrolled courses
$enrolled_courses_query = "
    SELECT course_id 
    FROM enrollments 
    WHERE student_id = ?";

echo "<!-- Debug: Student ID = " . $user_id . " -->";

$stmt = $conn_student->prepare($enrolled_courses_query);
if (!$stmt) {
    die("Prepare failed (enrollments): " . $conn_student->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$enrolled_course_ids = [];
while ($row = $result->fetch_assoc()) {
    $enrolled_course_ids[] = $row['course_id'];
}

echo "<!-- Debug: Enrolled courses = " . implode(", ", $enrolled_course_ids) . " -->";

// First check enrollments and get schedule from professor's database
$schedule_query = "
    SELECT 
        s.schedule_id,
        s.course_id,
        s.day_of_week,
        s.start_time,
        s.end_time,
        s.room,
        c.course_name,
        c.course_code
    FROM enrollments e
    JOIN class_schedule s ON e.course_id = s.course_id
    JOIN courses c ON s.course_id = c.course_id
    WHERE e.student_id = ?
    AND s.day_of_week = ?
    ORDER BY s.start_time ASC";

echo "<!-- Debug: Query: " . str_replace('?', '%s', $schedule_query) . " -->";
echo "<!-- Debug: Student ID: " . $user_id . ", Day: " . $today . " -->";

$stmt = $conn_professors->prepare($schedule_query);
if (!$stmt) {
    die("Prepare failed (schedule): " . $conn_professors->error);
}

$stmt->bind_param("is", $user_id, $today);
if (!$stmt->execute()) {
    die("Execute failed (schedule): " . $stmt->error);
}
$schedules = $stmt->get_result();

echo "<!-- Debug: Found " . $schedules->num_rows . " schedules for today -->";
if ($schedules && $schedules->num_rows > 0) {
    $first_schedule = $schedules->fetch_assoc();
    echo "<!-- Debug: First schedule: " . json_encode($first_schedule) . " -->";
    $schedules->data_seek(0);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    
    <!-- Theme Support -->
    <?php 
    require_once 'includes/theme-includes.php';
    addThemeHeaders();
    ?>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <!-- Theme Styles -->
    <link href="css/themes.css" rel="stylesheet">
    
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
        
        /* Sidebar - Google-style */
         .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            box-shadow: var(--shadow-md);  /* This is the key shadow */
            transition: transform 0.3s ease;
            z-index: 100;
            display: flex;
            flex-direction: column;
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
        
        .nav-menu {
            padding: 8px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #5f6368;
            text-decoration: none;
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
            font-size: 20px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 24px;
            background-color: #f5f5f5;
        }
        
        /* Top App Bar */
        .app-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        /* Cards */
        .mdc-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 24px;
        }
        
        .mdc-card:hover {
            box-shadow: 0 3px 5px -1px rgba(0,0,0,0.2), 0 5px 8px 0 rgba(0,0,0,0.14), 0 1px 14px 0 rgba(0,0,0,0.12);
        }
        
        .card-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 12px;
            color: var(--primary-color);
        }
        
        .card-content {
            padding: 16px;
        }
        
        /* Schedule List */
        .schedule-item {
            display: flex;
            padding: 12px 0;
            row-gap: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            justify-content: space-between; /* Add space between schedule time and course details */
        }
        
        .schedule-time {
            min-width: 120px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .schedule-details {
            flex: 1;
        }
        
        .course-code {
            font-size: 12px;
            color: #5f6368;
        }
        
        /* Activity List */
        .activity-item {
            padding: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: background-color 0.3s ease;
        }
        
        .activity-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .activity-item.warning {
            background-color: rgba(251, 188, 5, 0.1);
        }
        
        .activity-item.warning:hover {
            background-color: rgba(251, 188, 5, 0.15);
        }
        
        .activity-item.warning .activity-icon {
            color: #FBBC05;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .action-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: white;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            color: inherit;
        }
        
        .action-button:hover {
            background: #f1f3f4;
        }
        
        .action-button i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }

        .far-right-realtime-date {
            font-size: 14px;
            color: #5f6368;
        }
        .top-main {
            flex: 1;
        }
        .top-right {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .greeting {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-color);
        }
        .mdc-menu-surface--anchor {
            position: relative;
        }   
        .mdc-menu {
            width: 350px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .mdc-menu-surface {
            background-color: white;
            padding: 0;
            margin: 0;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .mdc-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }   
        .mdc-list-item {
            padding: 12px 16px;
            cursor: pointer;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        .mdc-list-item:hover {
            background-color: #f1f3f4;
        }
        .mdc-menu-surface--anchor .mdc-list-item__graphic {
            margin-right: 12px;
            color: var(--primary-color);
            width: 100px;
            height: 38px;
            max-width: 100%;
            max-height: 100%;
        }

        /* Notifications */
        .notifications-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }

        .notification-item {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 12px;
            margin-bottom: 8px;
            animation: slideIn 0.3s ease;
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin: 0;
            font-size: 14px;
            color: var(--dark-color);
        }

        .notification-details {
            margin-top: 4px;
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .notification-time {
            font-size: 12px;
            color: var(--medium-color);
            margin-top: 4px;
        }

        .notification-icon {
            color: var(--primary-color);
            font-size: 20px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        .notification-text {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .grade-info {
            color: var(--primary-color);
            font-size: 13px;
            margin-top: 2px;
            font-weight: 500;
        }
        
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            color: var(--primary-color);
        }

        .notification-item.warning {
            background-color: rgba(251, 188, 5, 0.1);
        }
        
        .notification-item.warning .notification-icon {
            color: #FBBC05;
        }
        
        .notification-item.warning .notification-message {
            color: #EA4335;
            font-weight: 500;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .notification-item.warning {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Add notification container -->
    <div class="notifications-container" id="notificationsContainer"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/BUPC_Logo.png" alt="BU Logo" class="sidebar-logo">
            <span class="app-name">BU LMS</span>
        </div>

        <nav class="nav-menu">
            <!-- Theme Toggles -->
            <?php addThemeToggles(); ?>
            
            <!-- Navigation Items -->
            <a href="dashboard_student.php" class="nav-item active">
                <i class="material-icons">dashboard</i>
                Dashboard
            </a>
            <a href="view_attendance_student.php" class="nav-item">
                <i class="material-icons">check_circle</i>
                Attendance
            </a>
            <a href="grading_student.php" class="nav-item">
                <i class="material-icons">grade</i>
                Grading
            </a>

            <a href="schedule_student.php" class="nav-item">
                <i class="material-icons">calendar_today</i>
                Schedules
            </a>
            <a href="courses_students.php" class="nav-item">
                <i class="material-icons">class</i>
                Course Management
            </a>
            <a href="view_report_students.php" class="nav-item">
                <i class="material-icons">bar_chart</i>
                Reports
            </a>
            
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top App Bar -->
        <header class="app-bar">
            <button class="material-icons menu-toggle" style="display: none;" aria-label="Toggle Menu">menu</button>
            <div class="user-profile">
                <div class="mdc-menu-surface--anchor">
                    <button class="mdc-icon-button" id="user-menu-button" aria-label="User Menu">
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="avatar">
                    </button>

                    <!-- ✅ FIXED nested anchor issue -->
                    <div class="mdc-menu mdc-menu-surface" tabindex="-1">
                        <ul class="mdc-list" role="menu" aria-hidden="true" aria-orientation="vertical">
                            <li class="mdc-list-item" role="menuitem">
                                <a href="profile_student.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;" 
                                    onmouseover="this.style.backgroundColor='#f1f3f4';" 
                                    onmouseout="this.style.backgroundColor='transparent';">
                                    <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px;">
                                    <div>
                                        <span class="mdc-list-item__primary-text"><?= htmlspecialchars($user_name) ?></span>
                                        <span class="mdc-list-item__secondary-text"><?= htmlspecialchars($user_email) ?></span>
                                    </div>
                                </a>
                            </li>
                            <hr class="mdc-list-divider">
                            <li class="mdc-list-item" role="menuitem">
                                <a class="mdc-list-item" href="logout_student.php">
                                    <i class="material-icons mdc-list-item__graphic" aria-hidden="true">exit_to_app</i>
                                    <span class="mdc-list-item__text">Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="top-main">
                <span class="greeting">Hello, <?= htmlspecialchars($user_name) ?>!</span>
            </div>

            <div class="top-right">
                <div style="flex-grow: 1;"></div>
                <div class="menu-toggle" id="menu-toggle" style="display: none;">
                    <i class="material-icons" aria-label="Toggle Menu">menu</i>
                </div>
                <div class="far-right-realtime-date">
                    <span id="current-date-time"></span>
                </div>
                <script>
                    function updateDateTime() {
                        const now = new Date();
                        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                        document.getElementById('current-date-time').textContent = now.toLocaleDateString('en-US', options);
                    }
                    setInterval(updateDateTime, 1000);
                    updateDateTime();
                </script>
            </div>
        </header>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Schedule Card -->
            <div class="mdc-card">
                <div class="card-header">
                    <i class="material-icons" aria-label="Schedule Icon">schedule</i>
                    <h3>Today's Schedule (<?= htmlspecialchars($today) ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if ($schedules && $schedules->num_rows > 0): ?>
                        <?php while ($schedule = $schedules->fetch_assoc()): ?>
                            <div class="schedule-item">
                                <div class="schedule-time">
                                    <?= date("g:i A", strtotime($schedule['start_time'])) ?> - 
                                    <?= date("g:i A", strtotime($schedule['end_time'])) ?>
                                </div>
                                <div class="schedule-details">
                                    <div><?= htmlspecialchars($schedule['course_name']) ?></div>
                                    <div class="course-code">
                                        <?= htmlspecialchars($schedule['course_code']) ?> • 
                                        <?= htmlspecialchars($schedule['room']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="padding: 16px; text-align: center; color: #5f6368;">
                            No classes scheduled for today (<?= htmlspecialchars($today) ?>).
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notification Card -->
            <div class="mdc-card">
                <div class="card-header">
                    <i class="material-icons" aria-label="Notification Icon">notifications</i>
                    <h3> Notifications</h3>
                </div>
                <div class="card-content">
                    <div class="activity-list">
                        <?php if ($notifications_result && $notifications_result->num_rows > 0): 
                            while ($notification = $notifications_result->fetch_assoc()): 
                                $icon_class = $notification['type'] === 'absence_warning' ? 'warning' : 'grade';
                                $icon = $notification['type'] === 'absence_warning' ? 'warning' : 'assignment';
                                $notification_class = $notification['type'] === 'absence_warning' ? 'warning' : '';
                        ?>
                            <div class="activity-item <?= $notification_class ?>">
                                <i class="material-icons activity-icon"><?= $icon ?></i>
                                <div>
                                    <div class="notification-text">
                                        <?= htmlspecialchars($notification['display_title']) ?>
                                    </div>
                                    <div class="notification-message">
                                        <?= htmlspecialchars($notification['display_message']) ?>
                                        <?php if ($notification['source'] === 'grade' && $notification['grade_equivalent']): ?>
                                            (<?= htmlspecialchars($notification['grade_equivalent']) ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php 
                                        $date = new DateTime($notification['created_at']);
                                        echo $date->format('M j, g:i A'); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <p style="padding: 16px; text-align: center; color: #5f6368;">No notifications yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="mdc-card">
                <div class="card-header">
                    <i class="material-icons" aria-label="Actions Icon">bolt</i>
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="view_attendance_student.php" class="action-button">
                            <i class="material-icons">check_circle</i>
                            <span>View Attendance</span>
                        </a>
                        <a href="grading_student.php" class="action-button">
                            <i class="material-icons">grade</i>
                            <span>View Grades</span>
                        </a>
                        <a href="Student_enrollment_view.php" class="action-button">
                            <i class="material-icons">assignment</i>
                            <span>View Enrolled Courses</span>
                        </a>
                        
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="mdc-card">
                <div class="card-header">
                    <i class="material-icons" aria-label="Help Icon">help_outline</i>
                    <h3>Help & Resources</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="support.php" class="action-button">
                            <i class="material-icons">support_agent</i>
                            <span>Contact Support</span>
                        </a>
                        <a href="faq_student.php" class="action-button">
                            <i class="material-icons">help_center</i>
                            <span>FAQs</span>
                        </a>
                        
                        <a href="policy.php" class="action-button">
                            <i class="material-icons">policy</i>
                            <span>Policies</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Scripts -->
    <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            mdc.autoInit();

            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                });
            }

            const menuButton = document.getElementById('user-menu-button');
            const menu = document.querySelector('.mdc-menu');
            if (menuButton && menu) {
                const menuInstance = new mdc.menu.MDCMenu(menu);
                menuButton.addEventListener('click', function () {
                    menuInstance.open = !menuInstance.open;
                });
            }

            setInterval(fetchRecentActivities, 30000);
        });

        function fetchRecentActivities() {
            fetch('api/fetch_activities.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.querySelector('.activity-list');
                    if (container) {
                        container.innerHTML = '';
                        data.forEach(activity => {
                            const item = document.createElement('div');
                            item.className = 'activity-item';
                            item.innerHTML = `
                                <i class="material-icons activity-icon">fiber_manual_record</i>
                                <div>
                                    <div>${activity.activity}</div>
                                    <div class="activity-time">${activity.formatted_time}</div>
                                </div>
                            `;
                            container.appendChild(item);
                        });
                    }
                })
                .catch(error => console.error('Error fetching activities:', error));
        }

        let lastNotificationTime = localStorage.getItem('lastNotificationTime') || '';

        function fetchNotifications() {
            fetch('fetch_student_notifications.php')
                .then(response => response.json())
                .then(notifications => {
                    const container = document.getElementById('notificationsContainer');
                    
                    // Filter to only show notifications newer than the last seen
                    const newNotifications = notifications.filter(notification => 
                        notification.timestamp > lastNotificationTime
                    );

                    // Show new notifications
                    newNotifications.forEach(notification => {
                        const notificationElement = createNotificationElement(notification);
                        container.insertBefore(notificationElement, container.firstChild);
                        
                        // Remove notification after 5 seconds
                        setTimeout(() => {
                            notificationElement.style.animation = 'fadeOut 0.3s ease forwards';
                            setTimeout(() => {
                                if (notificationElement.parentNode === container) {
                                    container.removeChild(notificationElement);
                                }
                            }, 300);
                        }, 5000);
                    });

                    // Update the last notification time if we have new notifications
                    if (notifications.length > 0) {
                        lastNotificationTime = notifications[0].timestamp;
                        localStorage.setItem('lastNotificationTime', lastNotificationTime);
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        function createNotificationElement(notification) {
            const div = document.createElement('div');
            div.className = `notification-item ${notification.type}`;
            
            let icon = 'grade';
            if (notification.type === 'warning') {
                icon = 'warning';
                div.style.borderLeft = '4px solid #FBBC05';
            }
            
            div.innerHTML = `
                <i class="material-icons notification-icon">${icon}</i>
                <div class="notification-content">
                    <p class="notification-message">${notification.message}</p>
                    ${notification.grade ? 
                        `<p class="notification-details">Grade: ${notification.grade}% (${notification.grade_equivalent})</p>` 
                        : ''}
                    <p class="notification-time">${notification.time}</p>
                </div>
            `;
            
            return div;
        }

        // Initial fetch - only on page load
        fetchNotifications();

        // Periodic check every 30 seconds
        setInterval(fetchNotifications, 30000);
    </script>

    <!-- Theme Scripts -->
    <?php addThemeScripts(); ?>
</body>
</html>
