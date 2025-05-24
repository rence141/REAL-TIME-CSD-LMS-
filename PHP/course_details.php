<?php
require 'db_connect.php';
session_start();

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    header("Location: login_professors.php");
    exit();
}

// Validate `course_id`
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    die("Invalid Course ID.");
}
$course_id = intval($_GET['course_id']);

// Fetch course details with additional information
$stmt = $conn->prepare("
    SELECT c.*, COUNT(a.activity_id) as activity_count 
    FROM courses c
    LEFT JOIN activities a ON c.course_id = a.course_id
    WHERE c.course_id = ?
    GROUP BY c.course_id
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    die("Course not found.");
}

// Handle assignment/activity creation
$success = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_name = trim($_POST['activity_name']);
    $activity_desc = trim($_POST['activity_description']);
    $due_date = $_POST['due_date'];
    $max_points = isset($_POST['max_points']) ? intval($_POST['max_points']) : null;

    // Validate inputs
    if (empty($activity_name)) {
        $error = "Activity name is required.";
    } elseif (empty($due_date)) {
        $error = "Due date is required.";
    } else {
        // File upload handling
        $media_path = null;
        if (!empty($_FILES['media']['name'])) {
            $media = $_FILES['media'];
            $allowed = ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4'];
            $max_size = 104857600; // 100MB
            
            if (in_array($media['type'], $allowed) && $media['size'] <= $max_size) {
                $upload_dir = 'uploads/activities/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($media['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('activity_', true) . '.' . $file_ext;
                $media_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($media['tmp_name'], $media_path)) {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "Invalid file type or size (max 100MB). Allowed types: JPEG, PNG, PDF, MP4";
            }
        }

        // Insert assignment into DB
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO activities 
                (course_id, title, description, media, due_date, max_points, professor_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issssii", $course_id, $activity_name, $activity_desc, $media_path, $due_date, $max_points, $professor_id);
            
            if ($stmt->execute()) {
                $success = "Activity created successfully!";
                // Refresh course data
                $course['activity_count']++;
            } else {
                $error = "Error creating activity: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch posted activities with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT a.*, 
           COUNT(s.submission_id) as submission_count,
           AVG(s.score) as avg_score
    FROM activities a
    LEFT JOIN submissions s ON a.activity_id = a.activity_id
    WHERE a.course_id = ?
    GROUP BY a.activity_id
    ORDER BY a.due_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $course_id, $limit, $offset);
$stmt->execute();
$activities = $stmt->get_result();
$stmt->close();

// Count total activities for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM activities WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$total_activities = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_pages = ceil($total_activities / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> - Course Management</title>
   <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
    :root {
        --primary-color: #4285F4;
        --primary-light: #E8F0FE;
        --secondary-color: #34A853;
        --danger-color: #EA4335;
        --warning-color: #FBBC05;
        --dark-color: #202124;
        --medium-color: #5F6368;
        --light-color: #F8F9FA;
        --border-color: #DADCE0;
        --border-radius: 8px;
        --box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Google Sans', 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        background-color: var(--light-color);
        color: var(--dark-color);
        display: flex;
        min-height: 100vh;
        line-height: 1.5;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 280px;
        background: white;
        height: 100vh;
        position: fixed;
        box-shadow: var(--box-shadow);
        display: flex;
        flex-direction: column;
        z-index: 100;
    }

    .sidebar-header {
        padding: 16px 24px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
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
        flex: 1;
        overflow-y: auto;
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
        background-color: var(--primary-light);
        color: var(--primary-color);
    }

    .nav-item i {
        margin-right: 16px;
        font-size: 20px;
    }

    /* Main Content Styles */
    .main-content {
        margin-left: 280px;
        flex: 1;
        padding: 32px;
        background-color: var(--light-color);
        width: calc(100% - 280px);
    }

    /* Course Header */
    .course-header {
        background: white;
        padding: 24px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 24px;
    }

    .course-title {
        font-size: 24px;
        margin: 0 0 8px 0;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .course-code {
        font-size: 16px;
        color: var(--medium-color);
        font-weight: normal;
    }

    .course-meta {
        display: flex;
        gap: 24px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .course-meta-item {
        display: flex;
        align-items: center;
        color: var(--medium-color);
        font-size: 14px;
    }

    .course-meta-item i {
        margin-right: 8px;
        color: var(--medium-color);
    }

    .course-stats {
        display: flex;
        gap: 16px;
        margin-top: 16px;
    }

    .stat-card {
        background: white;
        padding: 16px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        min-width: 160px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 500;
        color: var(--dark-color);
    }

    .stat-label {
        font-size: 14px;
        color: var(--medium-color);
    }

    /* Activity Section */
    .activity-section {
        background: white;
        padding: 24px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 24px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .section-title {
        font-size: 20px;
        margin: 0;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Activity Grid */
    .activity-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    /* Activity Card */
    .activity-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        transition: var(--transition);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .activity-card:hover {
        border-color: var(--primary-color);
        box-shadow: 0 1px 2px 0 rgba(66,133,244,0.3);
        transform: translateY(-2px);
    }

    .activity-card-header {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        background-color: var(--primary-light);
    }

    .activity-title {
        font-size: 18px;
        margin: 0 0 4px 0;
        color: var(--primary-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .activity-due {
        font-size: 14px;
        color: var(--medium-color);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .activity-card-body {
        padding: 16px;
        flex: 1;
    }

    .activity-desc {
        margin: 0 0 12px 0;
        color: var(--medium-color);
        line-height: 1.5;
        font-size: 14px;
    }

    .activity-stats {
        display: flex;
        gap: 12px;
        margin-top: 12px;
        font-size: 13px;
        color: var(--medium-color);
    }

    .stat {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .activity-media-container {
        margin-top: 12px;
        border-top: 1px solid var(--border-color);
        padding-top: 12px;
    }

    .activity-media {
        max-width: 100%;
        border-radius: var(--border-radius);
        display: block;
        margin-top: 8px;
    }

    .media-placeholder {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--medium-color);
        padding: 8px;
        background: var(--light-color);
        border-radius: var(--border-radius);
    }

    .activity-card-footer {
        padding: 12px 16px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .activity-actions {
        display: flex;
        gap: 8px;
    }

    /* Buttons */
    .btn {
        padding: 10px 16px;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-family: 'Google Sans', sans-serif;
        font-weight: 500;
        font-size: 14px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        border: none;
    }

    .btn i {
        font-size: 18px;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: #3367d6;
        box-shadow: 0 1px 2px 0 rgba(66,133,244,0.3), 0 1px 3px 1px rgba(66,133,244,0.15);
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--medium-color);
    }

    .btn-outline:hover {
        background: #f1f3f4;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
    }

    /* Notifications */
    .notification {
        padding: 12px 16px;
        border-radius: var(--border-radius);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .notification i {
        font-size: 20px;
    }

    .success {
        background-color: #E6F4EA;
        color: var(--secondary-color);
        border-left: 4px solid var(--secondary-color);
    }

    .error {
        background-color: #FCE8E6;
        color: var(--danger-color);
        border-left: 4px solid var(--danger-color);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--medium-color);
        grid-column: 1 / -1;
    }

    .empty-state i {
        font-size: 48px;
        color: #dadce0;
        margin-bottom: 16px;
    }

    .empty-state h3 {
        margin: 0 0 8px 0;
        font-weight: 500;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
    }

    .page-item {
        list-style: none;
    }

    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: var(--medium-color);
        text-decoration: none;
        transition: var(--transition);
    }

    .page-link:hover {
        background-color: #f1f3f4;
    }

    .page-link.active {
        background-color: var(--primary-color);
        color: white;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .activity-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    @media (max-width: 768px) {
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

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 16px;
        }

        .course-header, .activity-section {
            padding: 16px;
        }

        .course-meta {
            gap: 12px;
        }

        .stat-card {
            min-width: 120px;
        }
    }

    /* Floating Action Button */
    .fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        cursor: pointer;
        transition: var(--transition);
        z-index: 90;
        border: none;
    }

    .fab:hover {
        background-color: #3367d6;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .fab i {
        font-size: 24px;
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 16px;
        left: 16px;
        z-index: 110;
        background: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="material-icons">menu</i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
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
        <!-- Course Header -->
        <div class="course-header">
            <h1 class="course-title">
                <?php echo htmlspecialchars($course['course_name']); ?>
                <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
            </h1>
            
            <div class="course-meta">
                <span class="course-meta-item">
                    <i class="material-icons">calendar_today</i>
                    <?php echo htmlspecialchars($course['semester']); ?> <?php echo htmlspecialchars($course['academic_year']); ?>
                </span>
                <span class="course-meta-item">
                    <i class="material-icons">people</i>
                    Section <?php echo htmlspecialchars($course['section']); ?>
                </span>
                <span class="course-meta-item">
                    <i class="material-icons">view_agenda</i>
                    Block <?php echo htmlspecialchars($course['block_id']); ?>
                </span>
            </div>
            
            <div class="course-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo htmlspecialchars($course['activity_count']); ?></div>
                    <div class="stat-label">Activities</div>
                </div>
                <!-- Add more stats as needed -->
            </div>
            
            <div style="margin-top: 16px; display: flex; gap: 12px;">
                
                <a href="view_students.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline">
                    <i class="material-icons">people</i> View Students
                </a>
            </div>
        </div>

        <!-- Activities Section -->
        <div class="activity-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="material-icons">assignment</i>
                    Course Activities
                </h2>
                
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-primary" onclick="window.location.href='create_activity.php?course_id=<?php echo $course_id; ?>'">
                        <i class="material-icons">add</i> New Activity
                    </button>
                    <button class="btn btn-outline">
                        <i class="material-icons">filter_list</i> Filter
                    </button>
                </div>
            </div>

            <!-- Notifications -->
            <?php if ($success): ?>
                <div class="notification success">
                    <i class="material-icons">check_circle</i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="notification error">
                    <i class="material-icons">error</i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Activities Grid -->
            <div class="activity-grid">
                <?php if ($activities->num_rows > 0): ?>
                    <?php while ($activity = $activities->fetch_assoc()): ?>
                        <div class="activity-card" onclick="window.location.href='activity_view.php?activity_id=<?php echo $activity['activity_id']; ?>'">
                            <div class="activity-card-header">
                                <h3 class="activity-title">
                                    <?php echo htmlspecialchars($activity['activity_name']); ?>
                                    <span class="activity-due">
                                        <i class="material-icons" style="font-size: 16px;">event</i>
                                        <?php echo date('M j', strtotime($activity['due_date'])); ?>
                                    </span>
                                </h3>
                            </div>
                            
                            <div class="activity-card-body">
                                <p class="activity-desc">
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                </p>
                                
                                <?php if (!empty($activity['media'])): ?>
                                    <div class="activity-media-container">
                                        <?php
                                            $media = $activity['media'];
                                            $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                                            $image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                        ?>
                                        
                                        <?php if (in_array($ext, $image_types)): ?>
                                            <img src="<?php echo htmlspecialchars($media); ?>" alt="Activity Media" class="activity-media">
                                        <?php else: ?>
                                            <div class="media-placeholder">
                                                <i class="material-icons">attach_file</i>
                                                <?php echo htmlspecialchars(basename($media)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($submissions) && isset($submissions['score'])): ?>
                                <div class="activity-stats">
                                    <div class="stat">
                                        <i class="material-icons" style="font-size: 16px;">assessment</i>
                                        <?php echo htmlspecialchars($submissions['max_points'] ?? "N/A"); ?> pts
                                    </div>
                                    <?php if (!empty($submissions['submission_count']) && $submissions['submission_count'] > 0): ?>
                                        <div class="stat">
                                            <i class="material-icons" style="font-size: 16px;">how_to_reg</i>
                                            <?php echo htmlspecialchars($submissions['submission_count']); ?> submissions
                                        </div>
                                        <?php if (!empty($submissions['avg_score'])): ?>
                                            <div class="stat">
                                                <i class="material-icons" style="font-size: 16px;">trending_up</i>
                                                Avg: <?php echo number_format($submissions['avg_score'], 1); ?>/<?php echo htmlspecialchars($submissions['max_points'] ?? "N/A"); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            </div>
                            
                            <div class="activity-card-footer">
                                <div class="activity-meta">
                                    <span>
                                        <i class="material-icons" style="font-size: 16px;">schedule</i>
                                        <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <div class="activity-actions">
                                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); window.location.href='activity_edit.php?activity_id=<?php echo $activity['activity_id']; ?>'">
                                        <i class="material-icons" style="font-size: 16px;">edit</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="material-icons">assignment</i>
                        <h3>No Activities Posted Yet</h3>
                        <p>Create your first activity to get started</p>
                        <button class="btn btn-primary" onclick="window.location.href='create_activity.php?course_id=<?php echo $course_id; ?>'" style="margin-top: 16px;">
                            <i class="material-icons">add</i> Create Activity
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item">
                            <a href="?course_id=<?php echo $course_id; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
</body>
</html>
