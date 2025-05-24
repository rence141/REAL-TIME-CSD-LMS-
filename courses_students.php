<?php
session_start();
require 'db_connect.php'; // Professors DB

// Ensure student is logged in
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    die("Student not logged in.");
}

// Get student name for enrollment records
$student_stmt = $conn_student->prepare("SELECT student_name FROM students WHERE student_id = ?");
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();
$student_name = $student_data['student_name'] ?? '';
$student_stmt->close();

// Enroll logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);

    // Get course name for enrollment record
    $course_stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course_data = $course_result->fetch_assoc();
    $course_name = $course_data['course_name'] ?? '';
    $course_stmt->close();

    // Check if already enrolled
    $check_stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $check_stmt->bind_param("si", $student_id, $course_id);
    $check_stmt->execute();
    $already_enrolled = $check_stmt->get_result()->num_rows > 0;
    $check_stmt->close();

    if (!$already_enrolled) {
        $enroll_stmt = $conn->prepare("
            INSERT INTO enrollments (
                student_id, 
                student_name,
                course_id, 
                course_name,
                enrolled_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        $enroll_stmt->bind_param("ssis", $student_id, $student_name, $course_id, $course_name);
        $enroll_success = $enroll_stmt->execute();
        $enroll_stmt->close();

        if ($enroll_success) {
            $success = "Successfully enrolled in $course_name!";
        } else {
            $error = "Failed to enroll in course.";
        }
    } else {
        $error = "You're already enrolled in this course.";
    }
}

// Fetch all courses with professor info
$stmt_courses = $conn->prepare("
    SELECT 
        c.course_id,
        c.course_name,
        c.description,
        c.academic_year,
        c.semester,
        c.section,
        c.course_code,
        c.professor_id,
        c.block_id,
        CONCAT(p.Full_Name, ' ') AS professor_name
    FROM courses c
    LEFT JOIN professors p ON c.professor_id = p.id
");

$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();
$courses = $result_courses->fetch_all(MYSQLI_ASSOC);
$stmt_courses->close();

// Fetch all enrolled courses for the current student
$enrolled_courses = [];
$enrollment_stmt = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enrollment_stmt->bind_param("s", $student_id);
$enrollment_stmt->execute();
$enrollment_result = $enrollment_stmt->get_result();
while ($row = $enrollment_result->fetch_assoc()) {
    $enrolled_courses[] = $row['course_id'];
}
$enrollment_stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Course Management</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
      <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
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
            input[type="text"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            table, th, td {
                border: 1px solid #ccc;
            }

            th, td {
                padding: 10px;
                text-align: center;
                vertical-align: middle;
            }

            th {
                background-color: #f8f9fa;
                font-weight: 500;
            }

            tr {
                height: 60px;
            }

            .btn {
                padding: 10px 18px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-width: 120px;
                justify-content: center;
            }

            .btn-primary {
                background-color: var(--primary-color);
                color: white;
            }

            .btn[disabled] {
                background-color: #ccc;
                cursor: not-allowed;
                min-width: 120px;
                opacity: 0.7;
            }

            .toast {
                visibility: hidden;
                min-width: 250px;
                background-color: #333;
                color: #fff;
                text-align: center;
                border-radius: 8px;
                padding: 16px;
                position: fixed;
                z-index: 999;
                left: 50%;
                bottom: 30px;
                transform: translateX(-50%);
                font-size: 16px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
                opacity: 0;
                transition: opacity 0.4s ease-in-out, bottom 0.4s ease-in-out;
        }

            .toast.show {
                visibility: visible;
                bottom: 50px;
                opacity: 1;
            }
    </style>
    <script>
        function filterTable() {
            // Get input value and convert to lowercase for case-insensitive search
            const searchText = document.getElementById('searchBar').value.toLowerCase();
            const table = document.querySelector('table tbody');
            const rows = table.getElementsByTagName('tr');

            // Loop through all table rows
            for (let row of rows) {
                let matchFound = false;
                // Get all cells in the row except the last one (which contains the enroll button)
                const cells = Array.from(row.getElementsByTagName('td')).slice(0, -1);
                
                // Search through each cell
                for (let cell of cells) {
                    const text = cell.textContent || cell.innerText;
                    if (text.toLowerCase().indexOf(searchText) > -1) {
                        matchFound = true;
                        break;
                    }
                }
                
                // Show/hide row based on whether it matches the search
                row.style.display = matchFound ? '' : 'none';
            }
        }
    </script>
</head>
<body>
     <!-- Sidebar -->
        <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <p class="app-name">Computer Science Department</p>
        </div>

        <nav class="nav-menu">
            <a href="dashboard_student.php" class="nav-item ">
                <i class="material-icons" aria-label="Dashboard Icon">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="view_attendance_student.php" class="nav-item">
                <i class="material-icons" aria-label="Attendance Icon">check_circle</i>
                <span>Attendance</span>
            </a>
            <a href="grading_student.php" class="nav-item">
                <i class="material-icons" aria-label="Grading Icon">grade</i>
                <span>Grading</span>
            </a>

            <a href="schedule_student.php" class="nav-item">
                <i class="material-icons" aria-label="Schedule Icon">calendar_today</i>
                <span>Schedules</span>
            </a>
            <a href="courses_students.php" class="nav-item active">
                <i class="material-icons" aria-label="Course Management Icon">class</i>
                <span>Course Management</span>
            </a>
            <a href="view_report_students.php" class="nav-item">
                <i class="material-icons" aria-label="Reports Icon">bar_chart</i>
                <span>Reports</span>
            </a>
            
        </nav>
    </aside>

    <div class="main-content">
        <h2>Available Courses</h2>


        <?php if (!empty($success)): ?>
            <div class="alert success">
                <i class="material-icons">check_circle</i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert error">
                <i class="material-icons">error</i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <input type="text" id="searchBar" placeholder="Search courses..." onkeyup="filterTable()">
 <!-- Enrollment Records Section -->
        <div class="enrollment-section" style="margin-top: 40px;">
            <a href="Student_enrollment_view.php" class="btn btn-primary">
                <i class="material-icons">list_alt</i>
                My Enrollments
            </a>
        </div>
        <!-- Course List -->
        <table>
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Description</th>
                    <th>Professor</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Enroll</th>
                </tr>
            </thead>
           <tbody>
    <?php if (empty($courses)): ?>
        <tr>
            <td colspan="6" style="text-align: center; color: #888;">
                No courses available.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
            <?php 
                // Check if student is already enrolled in the current course
                $check_enrollment_stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
                $check_enrollment_stmt->bind_param("si", $student_id, $course['course_id']);
                $check_enrollment_stmt->execute();
                $is_enrolled = $check_enrollment_stmt->get_result()->num_rows > 0;
                $check_enrollment_stmt->close();
            ?>
            <tr>
                <td><?= htmlspecialchars($course['course_name']) ?></td>
                <td><?= htmlspecialchars(substr($course['description'], 0, 50) . (strlen($course['description']) > 50 ? '...' : '')) ?></td>
                <td><?= htmlspecialchars($course['professor_name']) ?></td>
                <td><?= htmlspecialchars($course['academic_year']) ?></td>
                <td><?= htmlspecialchars($course['semester']) ?></td>
                <td>
                    <?php if ($is_enrolled): ?>
                        <button class="btn" disabled>
                            <i class="material-icons">check_circle</i>
                            Enrolled
                        </button>
                    <?php else: ?>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="course_id" value="<?= htmlspecialchars($course['course_id']) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons">how_to_reg</i>
                                Enroll
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>

        </table>