<?php
session_start();
require 'db_connect.php'; // Professors DB

// Ensure student is logged in
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    die("Student not logged in.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Enrollment</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined">
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
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
            overflow-x: auto; /* Add horizontal scroll if needed */
            max-width: calc(100vw - 280px); /* Prevent exceeding viewport */
        }
        
        .main-content h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #4285F4;
        }
        
        h2 {
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #4285F4;
            color: white;
        }
        
        .empty {
            color: red;
            text-align: center;
        }
        
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <p class="app-name">Computer Science Department</p>
        </div>

        <nav class="nav-menu">
            <a href="dashboard_student.php" class="nav-item">
                <i class="material-icons" aria-label="Dashboard Icon">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="view_attendance_student.php" class="nav-item">
                <i class="material-icons" aria-label="Attendance Icon">check_circle</i>
                <span>Attendance</span>
            </a>
            <a href="" class="nav-item">
                <i class="material-icons" aria-label="Grading Icon">grade</i>
                <span>Grading</span>
            </a>
      
            <a href="" class="nav-item">
                <i class="material-icons" aria-label="Schedule Icon">calendar_today</i>
                <span>Schedules</span>
            </a>
            <a href="courses_students.php" class="nav-item active">
                <i class="material-icons" aria-label="Course Management Icon">class</i>
                <span>Course Management</span>
            </a>
            <a href="" class="nav-item">
                <i class="material-icons" aria-label="Reports Icon">bar_chart</i>
                <span>Reports</span>
            </a>
            
        </nav>
    </aside>

    <div class="main-content">
        <?php
        // Fetch student's enrollments
        $enrollments_stmt = $conn->prepare("
            SELECT 
                enrollment_id,
                course_id,
                course_name,
                enrolled_at
            FROM enrollments
            WHERE student_id = ?
            ORDER BY enrolled_at DESC
        ");
        $enrollments_stmt->bind_param("s", $student_id);
        $enrollments_stmt->execute();
        $enrollments = $enrollments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $enrollments_stmt->close();
        ?>
        
        <h1>My Enrollments</h1>
        
        <?php if (empty($enrollments)): ?>
            <p class="empty">You are not enrolled in any courses yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">Courses</th>
                        <th style="text-align: center;">Enrollment ID</th>
                        <th style="text-align: center;">Enrollment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td>
                                <a href="course_details_student.php?course_id=<?= htmlspecialchars($enrollment['course_id']) ?>">
                                    <?= htmlspecialchars($enrollment['course_name']) ?>
                                </a>
                            <td><?= htmlspecialchars($enrollment['enrollment_id']) ?></td>
                            
                            <td><?= date('M j, Y g:i a', strtotime($enrollment['enrolled_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
    function filterTable() {
        let input = document.getElementById("searchBar").value.toLowerCase();
        let rows = document.querySelectorAll("tbody tr");

        rows.forEach(row => {
            let courseTitle = row.querySelector("td:nth-child(1)").textContent.toLowerCase();
            let professorName = row.querySelector("td:nth-child(3)").textContent.toLowerCase();

            if (courseTitle.includes(input) || professorName.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }
    </script>
</body>
</html>
