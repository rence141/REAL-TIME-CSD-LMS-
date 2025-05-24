<?php
session_start();
require_once 'db_connect.php';

// Add at the beginning of the file, after session_start()
date_default_timezone_set('Asia/Manila');

// Check if professor is logged in
if (!isset($_SESSION['professor_id'])) {
    header('Location: login.php');
    exit();
}

// Get professor information
$professor_id = $_SESSION['professor_id'];

// Get professor details from database
$prof_query = $conn->prepare("SELECT Full_Name, email FROM professors WHERE id = ?");
$prof_query->bind_param("i", $professor_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$prof_data = $prof_result->fetch_assoc();

$professor_name = $prof_data['Full_Name'] ?? 'Unknown Professor';
$professor_email = $prof_data['email'] ?? 'No email available';

// Get profile image with default fallback
$profile_stmt = $conn->prepare("SELECT profile_image FROM professors WHERE id = ?");
$profile_stmt->bind_param("i", $professor_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile_data = $profile_result->fetch_assoc();
$profile_image = $profile_data['profile_image'] ?? '../IMAGES/default_profile.png';
$profile_stmt->close();

// Define assessment types and their weights
$ASSESSMENT_WEIGHTS = [
    'quiz' => 0.30,      // 30% of total grade
    'activity' => 0.30,  // 30% of total grade
    'assignment' => 0.40 // 40% of total grade
];

// Handle form submission for grades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    $course_id = $_POST['course_id'];
    $subject_id = $_POST['activity_id'];
    $student_grades = $_POST['grades'];
    $grades_updated = 0;
    
    foreach ($student_grades as $student_id => $grade) {
        if (is_numeric($grade) && $grade >= 0 && $grade <= 100) {
            // Update the submission with the grade
            $update_stmt = $conn->prepare("UPDATE submissions 
                                         SET grade = ?, 
                                             graded_by = ?, 
                                             graded_at = NOW() 
                                         WHERE student_id = ? 
                                         AND activity_id = ?");
            
            $update_stmt->bind_param("diis", 
                $grade,
                $professor_id,
                $student_id,
                $subject_id
            );
            
            if ($update_stmt->execute()) {
                $grades_updated++;
            }
        }
    }
    
    if ($grades_updated > 0) {
        // Get course and activity details for the notification
        $details_stmt = $conn->prepare("
            SELECT c.course_name, c.course_code, a.activity_name 
            FROM courses c 
            JOIN activities a ON a.course_id = c.course_id 
            WHERE c.course_id = ? AND a.activity_id = ?");
        $details_stmt->bind_param("ii", $course_id, $subject_id);
        $details_stmt->execute();
        $details = $details_stmt->get_result()->fetch_assoc();
        
        // Insert notification into professor_activities
        $activity_message = "Graded " . $grades_updated . " submission(s) for " . $details['activity_name'] . " in " . $details['course_code'];
        $notify_stmt = $conn->prepare("INSERT INTO professor_activities (professor_id, activity, timestamp) VALUES (?, ?, NOW())");
        $notify_stmt->bind_param("is", $professor_id, $activity_message);
        $notify_stmt->execute();
    }
    
    $_SESSION['success_message'] = "Grades have been successfully saved!";
    header("Location: grades.php?course_id=" . $course_id . "&activity_id=" . $subject_id);
    exit();
}

// Get courses taught by the professor
$stmt = $conn->prepare("SELECT course_id, course_code, course_name 
                       FROM courses 
                       WHERE professor_id = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

// Get selected course and activity
$selected_course = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$selected_activity = isset($_GET['activity_id']) ? $_GET['activity_id'] : null;

// Get activities/subjects for selected course grouped by type
$activities = [];
if ($selected_course) {
    $stmt = $conn->prepare("SELECT DISTINCT 
                               a.activity_id, 
                               a.activity_name,
                               a.activity_type as assessment_type
                           FROM activities a 
                           WHERE a.course_id = ?
                           ORDER BY a.activity_type, a.activity_name");
    $stmt->bind_param("i", $selected_course);
    $stmt->execute();
    $activities_result = $stmt->get_result();
    while ($row = $activities_result->fetch_assoc()) {
        $activities[$row['assessment_type']][] = $row;
    }
}

// Get statistics for selected activity
$stats = [
    'total_submissions' => 0,
    'graded_count' => 0,
    'average_score' => 0,
    'highest_score' => 0,
    'assessment_type' => ''
];

if ($selected_activity) {
    // Get submission and grade statistics
    $stmt = $conn->prepare("SELECT 
        COUNT(DISTINCT s.submission_id) as total_submissions,
        COUNT(DISTINCT CASE WHEN s.grade IS NOT NULL THEN s.submission_id END) as graded,
        COALESCE(AVG(NULLIF(s.grade, 0)), 0) as average,
        COALESCE(MAX(NULLIF(s.grade, 0)), 0) as highest,
        a.activity_type as assessment_type
    FROM activities a
    LEFT JOIN submissions s ON a.activity_id = s.activity_id
    WHERE a.activity_id = ?
    GROUP BY a.activity_type");
    
    $stmt->bind_param("i", $selected_activity);
    $stmt->execute();
    $stats_result = $stmt->get_result()->fetch_assoc();
    
    if ($stats_result) {
        $stats['total_submissions'] = $stats_result['total_submissions'];
        $stats['graded_count'] = $stats_result['graded'];
        $stats['average_score'] = round($stats_result['average'], 2);
        $stats['highest_score'] = $stats_result['highest'];
        $stats['assessment_type'] = $stats_result['assessment_type'];
    }
}

// Calculate student grades
function calculateStudentGrades($grades) {
    global $ASSESSMENT_WEIGHTS;
    $type_totals = [];
    $type_counts = [];
    
    // Calculate average for each assessment type
    foreach ($grades as $grade) {
        $type = $grade['assessment_type'];
        if (!isset($type_totals[$type])) {
            $type_totals[$type] = 0;
            $type_counts[$type] = 0;
        }
        $type_totals[$type] += $grade['grade'];
        $type_counts[$type]++;
    }
    
    // Calculate weighted average
    $final_grade = 0;
    foreach ($ASSESSMENT_WEIGHTS as $type => $weight) {
        if (isset($type_totals[$type]) && $type_counts[$type] > 0) {
            $average = $type_totals[$type] / $type_counts[$type];
            $final_grade += $average * $weight;
        }
    }
    
    return round($final_grade, 2);
}

// Get students and their submissions/grades
$students = [];
if ($selected_course && $selected_activity && isset($_GET['block'])) {
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    
    // Debug student data before query
    $debug_stmt = $conn_student->prepare("
        SELECT student_id, student_name, block 
        FROM students 
        WHERE block = ?
    ");
    $debug_stmt->bind_param("s", $_GET['block']);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();
    echo "<!-- Students in block " . $_GET['block'] . ": -->";
    while ($row = $debug_result->fetch_assoc()) {
        echo "<!-- Student: " . $row['student_name'] . " (ID: " . $row['student_id'] . ") Block: " . $row['block'] . " -->";
    }
    
    // Modified query to prevent duplicates and properly show submission status
    $query = "SELECT 
        st.student_id,
        st.student_name,
        st.block,
        st.section,
        s.submission_id,
        s.submission_date,
        s.file_path,
        s.grade as submission_grade,
        a.activity_type as assessment_type,
        e.course_id,
        CASE 
            WHEN s.submission_id IS NULL THEN 'Not Submitted'
            WHEN s.grade IS NOT NULL THEN 'Completed'
            WHEN s.submission_id IS NOT NULL THEN 'Pending'
            ELSE 'Not Submitted'
        END as submission_status
    FROM lms_dashborad_professors.submissions s
    LEFT JOIN " . $database_student . ".students st ON st.student_id = s.student_id
    LEFT JOIN " . $database_student . ".enrollments e ON st.student_id = e.student_id
    LEFT JOIN lms_dashborad_professors.activities a ON a.activity_id = s.activity_id
    WHERE s.activity_id = ?";

    $params = [$selected_activity];
    $types = "i";

    if (isset($_GET['block']) && $_GET['block']) {
        $query .= " AND st.block = ?";
        $params[] = $_GET['block'];
        $types .= "s";
    }

    if (isset($_GET['section']) && $_GET['section']) {
        $query .= " AND st.section = ?";
        $params[] = $_GET['section'];
        $types .= "s";
    }

    if (isset($_GET['search']) && $_GET['search']) {
        $query .= " AND st.student_id LIKE ?";
        $params[] = '%' . $_GET['search'] . '%';
        $types .= "s";
    }

    $query .= " ORDER BY 
        CASE 
            WHEN s.submission_id IS NULL THEN 3
            WHEN s.grade IS NOT NULL THEN 1
            WHEN s.submission_id IS NOT NULL THEN 2
            ELSE 3
        END,
        st.student_name";

    // Add debugging output
    echo "<!-- Debug Info:
    Selected Course: " . $selected_course . "
    Selected Activity: " . $selected_activity . "
    Selected Block: " . (isset($_GET['block']) ? $_GET['block'] : 'not set') . "
    Query: " . $query . "
    Parameters: " . implode(", ", $params) . "
    Parameter Types: " . $types . "
    -->";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "<!-- Prepare Error: " . $conn->error . " -->";
    } else {
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            echo "<!-- Execute Error: " . $stmt->error . " -->";
        } else {
            $students_result = $stmt->get_result();
            $students = $students_result->fetch_all(MYSQLI_ASSOC);
            
            // Debug each student found
            echo "<!-- Students found after query: -->";
            foreach ($students as $student) {
                echo "<!-- Found: " . $student['student_name'] . " (ID: " . $student['student_id'] . ") Block: " . $student['block'] . " Course: " . $student['course_id'] . " -->";
            }
            
            // Debug student count
            echo "<!-- Number of students found: " . count($students) . " -->";
        }
    }

    // Get all grades for each student to calculate final grade
    foreach ($students as &$student) {
        $stmt = $conn->prepare("SELECT s.grade, a.activity_type as assessment_type
                              FROM submissions s
                              JOIN activities a ON s.activity_id = a.activity_id
                              WHERE s.student_id = ? AND a.course_id = ?
                              AND s.grade IS NOT NULL");
        $stmt->bind_param("ii", $student['student_id'], $selected_course);
        $stmt->execute();
        $grades_result = $stmt->get_result();
        $all_grades = $grades_result->fetch_all(MYSQLI_ASSOC);
        
        $student['final_grade'] = calculateStudentGrades($all_grades);
        $student['final_grade_equivalent'] = calculateGradeEquivalent($student['final_grade']);
    }
}

function calculateGradeEquivalent($grade) {
    if ($grade >= 97) return '1.00';
    if ($grade >= 94) return '1.25';
    if ($grade >= 91) return '1.50';
    if ($grade >= 88) return '1.75';
    if ($grade >= 85) return '2.00';
    if ($grade >= 82) return '2.25';
    if ($grade >= 79) return '2.50';
    if ($grade >= 76) return '2.75';
    if ($grade >= 75) return '3.00';
    return '1.0';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Entry - Computer Science Department</title>
   <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34A853;
            --warning-color: #FBBC04;
            --error-color: #EA4335;
            --dark-color: #202124;
            --medium-color: #5F6368;
            --light-color: #F8F9FA;
            --border-radius: 8px;
            --box-shadow: 0 1px 2px rgba(60,64,67,0.3), 0 2px 6px rgba(60,64,67,0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: white;
            box-shadow: var(--box-shadow);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.12);
        }

        .sidebar-logo {
            height: 40px;
            margin-right: 12px;
        }

        .app-name {
            font-size: 18px;
            font-weight: 500;
            color: var(--medium-color);
        }

        .nav-menu {
            padding: 8px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: var(--medium-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .nav-item:hover {
            background-color: #F1F3F4;
        }

        .nav-item.active {
            background-color: #E8F0FE;
            color: var(--primary-color);
        }

        .nav-item i {
            margin-right: 16px;
            font-size: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 24px;
        }

        /* Header Section */
        .header {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-start;
        }

        .select-container {
            min-width: 200px;
        }

        .search-container {
            flex-grow: 1;
            min-width: 200px;
        }

        select, input[type="text"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #DFE1E5;
            border-radius: 4px;
            font-size: 14px;
            color: var(--dark-color);
            background: white;
        }

        select:focus, input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 16px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--medium-color);
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 500;
            color: var(--primary-color);
        }

        /* Students Table */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #DFE1E5;
        }

        th {
            background-color: #F8F9FA;
            font-weight: 500;
            color: var(--medium-color);
        }

        tr:hover {
            background-color: #F8F9FA;
        }

        /* Status Badges */
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.not-submitted {
            background-color: #FEEFE3;
            color: #D93025;
        }

        .status.pending {
            background-color: #FEF7E0;
            color: #EA8600;
        }

        .status.graded {
            background-color: #E6F4EA;
            color: #137333;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        /* Form Elements */
        .grade-input, .grade-equivalent-input, .grade-percentage-input {
            width: 80px;
            padding: 4px 8px;
            border: 1px solid #DFE1E5;
            border-radius: 4px;
            text-align: center;
        }

        .grade-display {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary-color);
            background-color: #e8f0fe;
            padding: 4px 12px;
            border-radius: 4px;
            display: inline-block;
        }

        .grade-equivalent-input, .grade-percentage-input {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #1557B0;
        }

        /* Success Message */
        .success-message {
            background-color: #E6F4EA;
            color: #137333;
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 16px;
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

        .notification-item.pending {
            border-left-color: var(--warning-color);
        }

        .notification-item.graded {
            border-left-color: var(--secondary-color);
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin: 0;
            font-size: 14px;
            color: var(--dark-color);
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

        .notification-item.pending .notification-icon {
            color: var(--warning-color);
        }

        .notification-item.graded .notification-icon {
            color: var(--secondary-color);
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

        /* Confirmation Dialog */
        .dialog-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 400px;
            width: 90%;
        }

        .dialog-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        /* Professor Profile Styles */
        .professor-profile {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.12);
            margin-bottom: 8px;
            background-color: #f8f9fa;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid #4285F4;
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
            color: var(--medium-color);
            margin-top: 2px;
        }

        .assessment-type {
            font-weight: 500;
            color: var(--primary-color);
            padding: 8px 0;
            margin-top: 16px;
            border-bottom: 2px solid var(--primary-color);
        }

        .final-grade {
            font-weight: bold;
            color: var(--primary-color);
        }

        .grade-info {
            margin-top: 24px;
            padding: 16px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
        }

        .grade-info h3 {
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .grade-info p {
            margin: 4px 0;
            color: var(--medium-color);
        }

        .weight-badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
        }

        .select-info {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Additional Styles */
        .download-link, .grade-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            margin-top: 8px;
        }

        .download-link {
            color: var(--primary-color);
            background-color: #e8f0fe;
        }

        .grade-btn {
            color: white;
            background-color: var(--primary-color);
        }

        .download-link:hover, .grade-btn:hover {
            opacity: 0.9;
        }

        .submission-info {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background-color: #e6f4ea;
            color: #137333;
        }

        .status-pending {
            background-color: #fef7e0;
            color: #ea8600;
        }

        .status-not-submitted {
            background-color: #fce8e6;
            color: #c5221f;
        }
    </style>
</head>
<body>
    <!-- Add this right after <body> tag -->
    <div class="notifications-container" id="notificationsContainer"></div>

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
            <a href="grades.php" class="nav-item active">
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
            <a href="reports.php" class="nav-item">
                <i class="material-icons">bar_chart</i>
                <span>Reports</span>
            </a>
            
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Section -->
        <div class="header">
            <h1>Grade Entry</h1>
            
            <!-- Grade Calculation Info -->
            <div class="grade-info">
                <h3>Grade Calculation</h3>
                <p>Quizzes: 30% of total grade</p>
                <p>Activities: 30% of total grade</p>
                <p>Assignments: 40% of total grade</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="" id="filterForm">
                <div class="filters">
                    <div class="select-container">
                        <select id="course_select" name="course_id" onchange="this.form.submit()">
                            <option value="">Select Subject</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>" <?= $selected_course == $course['course_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($selected_course): ?>
                        <div class="select-container">
                            <select id="block_select" name="block" onchange="this.form.submit()">
                                <option value="">Select Block</option>
                                <?php
                                // Get unique blocks directly from students table
                                $block_stmt = $conn_student->prepare("
                                    SELECT DISTINCT block 
                                    FROM students 
                                    ORDER BY block
                                ");
                                $block_stmt->execute();
                                $blocks = $block_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                foreach ($blocks as $block):
                                    if (!empty($block['block'])): // Only show non-empty blocks
                                ?>
                                    <option value="<?= htmlspecialchars($block['block']) ?>" <?= isset($_GET['block']) && $_GET['block'] == $block['block'] ? 'selected' : '' ?>>
                                        Block <?= htmlspecialchars($block['block']) ?>
                                    </option>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </select>
                        </div>

                        <?php 
                        // Modified block selection check
                        $selected_block = isset($_GET['block']) && !empty(trim($_GET['block'])) ? $_GET['block'] : null;
                        if ($selected_block): 
                        ?>
                            <div class="select-container">
                                <select id="section_select" name="section" onchange="this.form.submit()">
                                    <option value="">All Courses</option>
                                    <?php
                                    // Get unique sections for the selected block
                                    $section_stmt = $conn_student->prepare("
                                        SELECT DISTINCT section 
                                        FROM students 
                                        WHERE block = ?
                                        ORDER BY section
                                    ");
                                    $section_stmt->bind_param("s", $selected_block);
                                    $section_stmt->execute();
                                    $sections = $section_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    foreach ($sections as $section):
                                    ?>
                                        <option value="<?= htmlspecialchars($section['section']) ?>" <?= isset($_GET['section']) && $_GET['section'] == $section['section'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($section['section']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="select-container">
                                <select id="activity_select" name="activity_id" onchange="this.form.submit()">
                                    <option value="">Select Activity</option>
                                    <?php foreach ($activities as $type => $type_activities): ?>
                                        <optgroup label="<?= ucfirst($type) ?>s">
                                            <?php foreach ($type_activities as $activity): ?>
                                                <option value="<?= $activity['activity_id'] ?>" <?= $selected_activity == $activity['activity_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($activity['activity_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($selected_activity): ?>
                                <div class="search-container">
                                    <input type="text" id="search" name="search" placeholder="Search students..." 
                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="select-info">
                                <span class="text-muted">Please select a block to view activities and students</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if ($selected_activity && isset($_GET['block']) && $_GET['block']): ?>
            <!-- Statistics Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Assessment Type</h3>
                    <div class="value">
                        <?php if (isset($stats['assessment_type']) && $stats['assessment_type']): ?>
                            <?= ucfirst($stats['assessment_type']) ?>
                            <span class="weight-badge"><?= ($ASSESSMENT_WEIGHTS[$stats['assessment_type']] * 100) ?>%</span>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Submissions</h3>
                    <div class="value"><?= $stats['total_submissions'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <h3>Graded Submissions</h3>
                    <div class="value"><?= $stats['graded_count'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Score</h3>
                    <div class="value"><?= isset($stats['average_score']) && $stats['average_score'] ? number_format($stats['average_score'], 2) . '%' : '-' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Highest Score</h3>
                    <div class="value"><?= isset($stats['highest_score']) && $stats['highest_score'] ? number_format($stats['highest_score'], 2) . '%' : '-' ?></div>
                </div>
            </div>

            <!-- Notifications Section -->
            <?php if (isset($students) && !empty($students)): ?>
                <?php
                $completed = 0;
                $pending = 0;
                $not_submitted = 0;
                foreach ($students as $student) {
                    if ($student['submission_status'] === 'Completed') {
                        $completed++;
                    } elseif ($student['submission_status'] === 'Pending') {
                        $pending++;
                    } else {
                        $not_submitted++;
                    }
                }
                ?>
                <?php if ($completed > 0): ?>
                    <div class="notification success">
                        <i class="material-icons">check_circle</i>
                        <div>
                            <strong><?= $completed ?> student<?= $completed > 1 ? 's have' : ' has' ?> completed submissions</strong>
                            <?php if ($completed === count($students)): ?>
                                <p>All students have submitted and been graded!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($pending > 0): ?>
                    <div class="notification warning">
                        <i class="material-icons">pending</i>
                        <div>
                            <strong><?= $pending ?> submission<?= $pending > 1 ? 's need' : ' needs' ?> grading</strong>
                            <p>There are pending submissions that require your attention.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($not_submitted > 0): ?>
                    <div class="notification info">
                        <i class="material-icons">info</i>
                        <div>
                            <strong><?= $not_submitted ?> student<?= $not_submitted > 1 ? 's have' : ' has' ?> not submitted yet</strong>
                            <p>Some students still need to submit their work.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Students Table -->
            <form id="gradeForm" method="POST" action="grades.php">
                <input type="hidden" name="course_id" value="<?= $selected_course ?>">
                <input type="hidden" name="activity_id" value="<?= $selected_activity ?>">
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                                <th>Grade (%)</th>
                                <th>Grade Equivalent</th>
                                <th>Final Grade</th>
                                <th>Final Grade Equivalent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                    <td>
                                        <span class="status <?= strtolower(str_replace(' ', '-', $student['submission_status'])) ?>">
                                            <?= $student['submission_status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $student['submission_date'] ? date('M d, Y h:i A', strtotime($student['submission_date'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($student['submission_status'] === 'Not Submitted'): ?>
                                            <span class="text-muted">No submission yet</span>
                                        <?php elseif ($student['submission_status'] === 'Pending'): ?>
                                            <div class="submission-info">
                                                <span class="text-muted">Pending grading</span>
                                                <?php if ($student['file_path']): ?>
                                                    <a href="<?= htmlspecialchars($student['file_path']) ?>" class="download-link" download>
                                                        <i class="material-icons">download</i>
                                                        Download
                                                    </a>
                                                    <a href="grade_submission.php?submission_id=<?= htmlspecialchars($student['submission_id']) ?>" class="grade-btn">
                                                        <i class="material-icons">grade</i>
                                                        Grade
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="submission-info">
                                                <?php if ($student['submission_grade']): ?>
                                                    <span class="grade-display"><?= number_format($student['submission_grade'], 2) ?>%</span>
                                                <?php else: ?>
                                                    <input type="number" class="grade-input" name="grades[<?= $student['student_id'] ?>]"
                                                           value="<?= $student['grade'] ?>" min="0" max="100"
                                                           onchange="calculateGrades(this)">
                                                <?php endif; ?>
                                                <?php if ($student['file_path']): ?>
                                                    <a href="<?= htmlspecialchars($student['file_path']) ?>" class="download-link" download>
                                                        <i class="material-icons">download</i>
                                                        Download
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['submission_status'] === 'Not Submitted' || $student['submission_status'] === 'Pending'): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <input type="text" class="grade-equivalent-input" readonly
                                                   value="<?= $student['submission_grade'] ? calculateGradeEquivalent($student['submission_grade']) : $student['grade_equivalent'] ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td class="final-grade"><?= $student['final_grade'] ? $student['final_grade'] . '%' : '-' ?></td>
                                    <td class="final-grade"><?= $student['final_grade'] ? $student['final_grade_equivalent'] : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($students)): ?>
                    <div style="text-align: right; margin-top: 16px;">
                        <button type="button" class="btn btn-primary" onclick="confirmSubmit()">Save Grades</button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </main>

    <!-- Confirmation Dialog -->
    <div class="dialog-overlay" id="confirmDialog">
        <div class="dialog">
            <h2>Confirm Grade Submission</h2>
            <p>Are you sure you want to save these grades? This action cannot be undone.</p>
            <div class="dialog-buttons">
                <button class="btn" onclick="hideDialog()">Cancel</button>
                <button class="btn btn-primary" onclick="submitGrades()">Save Grades</button>
            </div>
        </div>
    </div>

    <script>
        // Handle course and activity selection
        document.getElementById('course_select').addEventListener('change', function() {
            const courseId = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('course_id', courseId);
            currentUrl.searchParams.delete('activity_id');
            currentUrl.searchParams.delete('search');
            window.location.href = currentUrl.toString();
        });

        document.getElementById('activity_select')?.addEventListener('change', function() {
            const activityId = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('activity_id', activityId);
            currentUrl.searchParams.delete('search');
            window.location.href = currentUrl.toString();
        });

        // Handle search
        let searchTimeout;
        document.getElementById('search')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('search', searchTerm);
                window.location.href = currentUrl.toString();
            }, 500);
        });

        // Confirmation dialog
        function showDialog() {
            document.getElementById('confirmDialog').style.display = 'block';
        }

        function hideDialog() {
            document.getElementById('confirmDialog').style.display = 'none';
        }

        function confirmSubmit() {
            showDialog();
        }

        function submitGrades() {
            document.getElementById('gradeForm').submit();
        }

        // Close dialog when clicking outside
        document.getElementById('confirmDialog').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDialog();
            }
        });

        function calculateGrades(input) {
            const grade = parseFloat(input.value);
            const row = input.closest('tr');
            const gradeEquivalentInput = row.querySelector('.grade-equivalent-input');
            
            // Calculate grade equivalent
            let gradeEquivalent = '';
            if (grade >= 97) gradeEquivalent = '1.00';
            else if (grade >= 94) gradeEquivalent = '1.25';
            else if (grade >= 91) gradeEquivalent = '1.50';
            else if (grade >= 88) gradeEquivalent = '1.75';
            else if (grade >= 85) gradeEquivalent = '2.00';
            else if (grade >= 82) gradeEquivalent = '2.25';
            else if (grade >= 79) gradeEquivalent = '2.50';
            else if (grade >= 76) gradeEquivalent = '2.75';
            else if (grade >= 75) gradeEquivalent = '3.00';
            else gradeEquivalent = '1.0';

            // Update grade equivalent
            gradeEquivalentInput.value = gradeEquivalent;
        }

        let lastNotificationTime = localStorage.getItem('lastProfNotificationTime') || '1970-01-01 00:00:00';

        function fetchNotifications() {
            fetch(`fetch_notifications.php?last_seen=${encodeURIComponent(lastNotificationTime)}`)
                .then(response => response.json())
                .then(notifications => {
                    const container = document.getElementById('notificationsContainer');
                    
                    // Only process if we have new notifications
                    if (notifications.length > 0) {
                        notifications.forEach(notification => {
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

                        // Update the last notification time
                        if (notifications[0].timestamp > lastNotificationTime) {
                            lastNotificationTime = notifications[0].timestamp;
                            localStorage.setItem('lastProfNotificationTime', lastNotificationTime);
                        }
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        function createNotificationElement(notification) {
            const div = document.createElement('div');
            div.className = `notification-item ${notification.status}`;
            
            const icon = notification.status === 'graded' ? 'check_circle' : 'pending';
            
            div.innerHTML = `
                <i class="material-icons notification-icon">${icon}</i>
                <div class="notification-content">
                    <p class="notification-message">${notification.message}</p>
                    <p class="notification-time">${notification.time}</p>
                </div>
            `;
            
            return div;
        }

        // Initial fetch on page load
        fetchNotifications();

        // Check for new notifications every 30 seconds
        setInterval(fetchNotifications, 30000);
    </script>
</body>
</html> 