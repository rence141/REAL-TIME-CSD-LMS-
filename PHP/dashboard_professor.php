<?php
    session_start();

// Database connection
include 'db_connect.php';

// Ensure professor is logged in
if (!isset($_SESSION['professor_id'])) {
    header('Location: login_professor.php');
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor information
$query = $conn->prepare("SELECT Full_Name, profile_image, email FROM professors WHERE id = ?");
$query->bind_param("i", $professor_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();
if ($user) {
    $user_name = $user['Full_Name'];
    $user_email = $user['email'];
    $profile_image = !empty($user['profile_image']) ? $user['profile_image'] : "../Default_avatar.jpg.png";
} else {
    $user_name = "Unknown";
    $user_email = "Unknown";
    $profile_image = "../Default_avatar.jpg.png";
}

// Debug database connection
echo "<!-- Debug: Database connection status = " . ($conn ? "Connected" : "Not connected") . " -->";
if (!$conn) {
    echo "<!-- Debug: Connection error = " . mysqli_connect_error() . " -->";
}

// Fetch today's schedule (optimized with prepared statement)
$current_day = date('l');

// Debug output
echo "<!-- Debug: Using professor_id = " . $professor_id . " -->";
echo "<!-- Debug: Current day = " . $current_day . " -->";

$schedule_query = $conn->prepare("
    SELECT 
        c.course_name, 
        c.course_code, 
        cs.start_time, 
        cs.end_time, 
        cs.room,
        cs.day_of_week
    FROM class_schedule cs
    JOIN courses c ON cs.course_id = c.course_id 
    WHERE cs.professor_id = ? 
    AND cs.day_of_week = ? 
    ORDER BY cs.start_time ASC
");

if (!$schedule_query) {
    echo "<!-- Debug: Prepare failed: " . $conn->error . " -->";
} else {
    $schedule_query->bind_param("is", $professor_id, $current_day);
    if (!$schedule_query->execute()) {
        echo "<!-- Debug: Execute failed: " . $schedule_query->error . " -->";
    }
    $schedule_result = $schedule_query->get_result();
    if (!$schedule_result) {
        echo "<!-- Debug: Get result failed: " . $schedule_query->error . " -->";
    } else {
        echo "<!-- Debug: Found " . $schedule_result->num_rows . " schedules for today -->";
    }
}

// Fetch Activity log (optimized with prepared statement)
$activity_query = $conn->prepare("
    SELECT log_id, timestamp 
    FROM professor_activities 
    WHERE professor_id = ? 
    ORDER BY timestamp DESC LIMIT 5");
$activity_query->bind_param("i", $professor_id);
$activity_query->execute();
$activity_result = $activity_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <!-- Theme Support -->
    <?php 
    require_once 'includes/theme-includes.php';
    addThemeHeaders();
    ?>
    
    <style>
        /* Remove hardcoded colors and use theme variables */
        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar - Google-style */
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            height: 100vh;
            position: fixed;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            z-index: 100;
            display: flex;
            flex-direction: column;
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
            color: var(--text-secondary);
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
            color: var(--text-secondary);
            text-decoration: none;
        }
        
        .nav-item:hover {
            background-color: var(--border-color);
        }
        
        .nav-item.active {
            background-color: rgba(var(--primary-color-rgb), 0.1);
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
            background-color: var(--bg-primary);
        }
        
        /* Top App Bar */
        .app-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
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
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .activity-icon {
            margin-right: 12px;
            color: var(--secondary-color);
        }
        
        .activity-time {
            font-size: 12px;
            color: #5f6368;
            margin-top: 4px;
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

        .schedule-card {
            margin: 20px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .card-header i {
            margin-right: 8px;
            color: #1a73e8;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #202124;
        }

        .schedule-item {
            display: flex;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .schedule-item:last-child {
            border-bottom: none;
        }

        .schedule-time {
            min-width: 120px;
            color: #1a73e8;
            font-weight: 500;
        }

        .schedule-details {
            flex-grow: 1;
        }

        .course-name {
            font-weight: 500;
            color: #202124;
        }

        .course-info {
            color: #5f6368;
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .no-schedule {
            text-align: center;
            color: #5f6368;
            padding: 16px;
        }

        /* Theme Toggles Container */
        .theme-toggles {
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <p class="app-name">Computer Science Department</p>
        </div>
        
        <nav class="nav-menu">
            <!-- Theme Toggles -->
            <?php addThemeToggles(); ?>
            
            <!-- Navigation Items -->
            <a href="dashboard_professor.php" class="nav-item active"><i class="material-icons">dashboard</i> <span>Dashboard</span></a>
            <a href="attendance_prof.php" class="nav-item"><i class="material-icons">check_circle</i> <span>Attendance</span></a>
            <a href="grades.php" class="nav-item"><i class="material-icons" aria-label="Grading Icon">grade</i> <span>Grading</span> </a>
            <a href="schedule.php" class="nav-item"><i class="material-icons">calendar_today</i> <span>Schedules</span></a>
            <a href="manage_courses.php" class="nav-item"><i class="material-icons">class</i> <span>Course Management</span></a>
            <a href="reports.php" class="nav-item"><i class="material-icons">bar_chart</i> <span>Reports</span></a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top App Bar -->
        <header class="app-bar">
            <button class="material-icons menu-toggle" style="display: none;">menu</button>    
            <div class="user-profile">
                <div class="mdc-menu-surface--anchor">
                    <button class="mdc-icon-button" id="user-menu-button">
                        <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="avatar">
                    </button>
                    <div class="mdc-menu-surface--anchor" style="position: absolute; top: 0; left: 0; z-index: 100;">
                    <div class="mdc-menu mdc-menu-surface" tabindex="-1">
                    <div class=" mdc-list">
                    <span class="mdc-list-item__text" style="display: flex; align-items: center;">
                        <a href="profile.php" style="display: flex; align-items: center; text-decoration: none; color: inherit; transition: background-color 0.3s;" 
                            onmouseover="this.style.backgroundColor='#f1f3f4';" 
                            onmouseout="this.style.backgroundColor='transparent';">
                             <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px;">
                             <div style="display: flex; flex-direction: column;">
                                  <span class="mdc-list-item__primary-text"><?= htmlspecialchars($user_name) ?></span>
                                  <span class="mdc-list-item__secondary-text"><?= htmlspecialchars($user_email) ?></span>
                             </div>
                        </a>
                        </span>  </div>
                            <hr class="mdc-list-divider">
                            <hr class="mdc-list-divider">
                            <a class="mdc-list-item" href="logout.php" tabindex="0">
                                <i class="material-icons mdc-list-item__graphic" aria-hidden="true">exit_to_app</i>
                                <span class="mdc-list-item__text">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="top-main"> 
                <span class="greeting">Hello Professor, <?= htmlspecialchars($user_name) ?>!</span>
            </div>
            <div class="top-right">
                <div style="flex-grow: 1;"></div>
                <div class="menu-toggle" id="menu-toggle" style="display: none;">
                    <i class="material-icons">menu</i>
                </div>
                <div class="far-right-realtime-date">
                    <span id="current-date-time"></span>
                </div>
            </div>
        </header>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- First Row: Schedule and Notifications -->
            <div class="dashboard-row">
                <!-- Schedule Card -->
                <div class="mdc-card">
                    <div class="card-header">
                        <i class="material-icons" aria-label="Schedule Icon">schedule</i>
                        <h3>Today's Schedule (<?= htmlspecialchars($current_day) ?>)</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($schedule_result && $schedule_result->num_rows > 0): ?>
                            <?php while ($schedule = $schedule_result->fetch_assoc()): ?>
                                <div class="schedule-item">
                                    <div class="schedule-time">
                                        <?= date("g:i A", strtotime($schedule['start_time'])) ?> - 
                                        <?= date("g:i A", strtotime($schedule['end_time'])) ?>
                                    </div>
                                    <div class="schedule-details">
                                        <div class="course-name">||<?= htmlspecialchars($schedule['course_name']) ?></div>
                                        <div class="course-code">
                                            <?= htmlspecialchars($schedule['course_code']) ?> • 
                                            <?= htmlspecialchars($schedule['room']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-schedule">No classes scheduled for today.</p>
                        <?php endif; ?>
                    </div>
                </div>

              
                
            </div>

            <!-- Second Row: Quick Actions and Help -->
        <!-- Schedule Card -->
       
        <!-- Activity log Card -->
        <div class="mdc-card">
            <div class="card-header">
                <i class="material-icons">notifications</i>
                <h3>Notifications</h3>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <?php if ($activity_result && $activity_result->num_rows > 0): ?>
                        <?php while ($activity = $activity_result->fetch_assoc()): ?>
                            <div class="activity-item">
                                <i class="material-icons activity-icon">fiber_manual_record</i>
                                <div class="activity-content">
                                    <div class="activity-text"><?= htmlspecialchars($activity['activities']) ?></div>
                                    <div class="activity-time">
                                        <?= date("M j, g:i A", strtotime($activity['timestamp'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-data-message">
                            <i class="material-icons" style="font-size: 48px; color: #dadce0; margin-bottom: 8px;">notifications_none</i>
                            <p>No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="mdc-card">
            <div class="card-header">
                <i class="material-icons">bolt</i>
                <h3>Quick Actions</h3>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="attendance_prof.php" class="action-button">
                        <i class="material-icons">check_circle</i>
                        <span>Take Attendance</span>
                    </a>
                    <a href="grades.php" class="action-button">
                        <i class="material-icons">grade</i>
                        <span>Enter Grades</span>
                    </a>
                    <a href="create_activity.php" class="action-button">
                        <i class="material-icons">assignment</i>
                        <span>New Assignment</span>
                    </a>

                </div>
            </div>
        </div>

        <!-- Resources Card -->
        <div class="mdc-card">
            <div class="card-header">
                <i class="material-icons">help_outline</i>
                <h3>Help & Resources</h3>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="support.php" class="action-button">
                        <i class="material-icons">support_agent</i>
                        <span>Contact Support</span>
                    </a>
                    <a href="faq.php" class="action-button">
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
    </main>

    <!-- Material Components JS -->
    <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
    <script>
        // Initialize Material Components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all MDC components
            mdc.autoInit();
            
            // Menu toggle for mobile
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }
            
            // User menu
            const menuButton = document.getElementById('user-menu-button');
            const menu = document.querySelector('.mdc-menu');
            
            if (menuButton && menu) {
                const menuInstance = new mdc.menu.MDCMenu(menu);
                menuButton.addEventListener('click', function() {
                    menuInstance.open = !menuInstance.open;
                });
            }
            
            // Auto-update activities every 30 seconds
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
    </script>

    <!-- Theme Scripts -->
    <?php addThemeScripts(); ?>
</body>
</html>
