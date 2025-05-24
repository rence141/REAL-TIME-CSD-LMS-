<?php
require 'db_connect.php'; 
session_start();

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    header("Location: login_professors.php");
    exit();
}

// Validate `activity_id`
if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id'])) {
    die("Invalid Activity ID.");
}
$activity_id = intval($_GET['activity_id']);

// Fetch activity details
$stmt = $conn->prepare("
    SELECT a.*, c.course_name 
    FROM activities a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.activity_id = ?
");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    die("Activity not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity['activity_name']); ?> - Activity Details</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <style>
       
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

/* Sidebar - Material Design Inspired */
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

/* Main Content - Material UI Flavor */
.main-content {
    margin-left: 280px;
    flex: 1;
    padding: 40px;
    background-color: #f5f5f5;
    display: flex;
    justify-content: center;
}

.activity-card {
    background: #fff;
    width: 100%;
    max-width: 900px;
    padding: 32px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.activity-card h1 {
    font-size: 26px;
    font-weight: 600;
    color: var(--dark-color);
}

.course-name {
    font-size: 16px;
    font-weight: 500;
    color: var(--primary-color);
}

.due-date {
    font-size: 14px;
    color: #5f6368;
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-description {
    font-size: 15px;
    line-height: 1.6;
    color: var(--dark-color);
}

.activity-media img {
    width: 100%;
    max-height: 400px;
    border-radius: var(--border-radius);
    object-fit: cover;
    box-shadow: var(--box-shadow);
}

/* File preview cards */
.file-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background-color: #e8f0fe;
    border-radius: var(--border-radius);
    align-items: center;
}

.file-icon {
    font-size: 40px;
    color: var(--primary-color);
}

.file-info {
    flex-grow: 1;
}

.file-name {
    font-weight: 500;
    color: var(--dark-color);
    font-size: 16px;
}

/* Stats and Buttons */
.submission-stats {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #5f6368;
}

.activity-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Button Styles (Google feel) */
.btn-primary {
    background-color: #4285F4;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    padding: 10px 20px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-transform: uppercase;
    box-shadow: 0 2px 4px rgba(26, 115, 232, 0.4);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background-color: #1a73e8;
    color: white;
    text-decoration: none;
}

.btn-primary i {
    font-size: 18px;
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 6px;
    font-size: 14px;
    padding: 8px 16px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.btn-outline:hover {
    background-color: #e8f0fe;
}

/* Delete Icon */
.delete-icon {
    cursor: pointer;
    color: #e74c3c;
    font-size: 18px;
    transition: 0.3s ease;
}

.delete-icon:hover {
    color: #c0392b;
    transform: scale(1.2);
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
            <a href="dashboard_professor.php" class="nav-item"><i class="material-icons">dashboard</i> <span>Dashboard</span></a>
            <a href="attendance_prof.php" class="nav-item"><i class="material-icons">check_circle</i> <span>Attendance</span></a>
            <a href="add_grades.php" class="nav-item"><i class="material-icons">grade</i> <span>Grading</span></a>
            <a href="schedule.php" class="nav-item"><i class="material-icons">calendar_today</i> <span>Schedules</span></a>
            <a href="manage_courses.php" class="nav-item active"><i class="material-icons">class</i> <span>Course Management</span></a>
            <a href="reports.php" class="nav-item"><i class="material-icons">bar_chart</i> <span>Reports</span></a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
    <div class="activity-card">
        <h1><?php echo htmlspecialchars($activity['activity_name']); ?></h1>
        <div class="course-name"><?php echo htmlspecialchars($activity['course_name']); ?></div>
        <div class="due-date"><i class="material-icons">event</i>Due: <?php echo date('M j, Y', strtotime($activity['due_date'])); ?></div>

        <div class="activity-description">
            <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
        </div>

        <?php if (!empty($activity['media'])): ?>
            
            <div class="activity-media">
    <?php
    $media = htmlspecialchars($activity['media']);
    $filename = basename($media);
    $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
    $fileIcon = 'insert_drive_file'; // default icon

    // Adjust icon based on file type
    if (in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif'])) {
        echo '<img src="' . $media . '" alt="Activity Media">';
    } else {
        if (strtolower($fileExt) === 'pdf') {
            $fileIcon = 'picture_as_pdf';
        } elseif (in_array(strtolower($fileExt), ['doc', 'docx'])) {
            $fileIcon = 'description';
        }
        ?>

        <div class="file-card">
            <i class="material-icons file-icon"><?php echo $fileIcon; ?></i>
            <div class="file-info">
                <span class="file-name"><?php echo $filename; ?></span>
                <a class="btn btn-outline download-btn" href="<?php echo $media; ?>" download>Download</a>
            </div>
        </div>
        <?php
    }
    ?>
</div>

        <?php endif; ?>

        <div class="submission-stats">
            <span><i class="material-icons">how_to_reg</i> Submissions: 10</span>
            <span><i class="material-icons">trending_up</i> Average Score: 85%</span>
        </div>

        <div class="activity-actions">
            <a href="view_submissions.php?activity_id=<?= $activity_id ?>" class="btn-primary">
                <i class="material-icons">people</i>
                View Submissions
            </a>
        </div>

    </div>
</div>

</body>
</html>
