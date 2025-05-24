<?php
require 'db_connect.php';
session_start();

$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    die("Professor not logged in.");
}

// Fetch professor's courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE professor_id = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Handle new course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_title = $_POST['title'];
    $course_description = $_POST['description'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $section = $_POST['section'];
    $block = $_POST['block'];

    $stmt = $conn->prepare("INSERT INTO courses (course_name, description, academic_year, semester, section, block, professor_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssi", $course_title, $course_description, $academic_year, $semester, $section, $block, $professor_id);
    $stmt->execute();
    $stmt->close();

    $success = "Course created successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #34A853;
            --danger-color: #EA4335;
            --dark-color: #202124;
            --light-color: #f8f9fa;
            --box-shadow: 0 1px 2px rgba(60,64,67,0.3), 0 2px 6px rgba(60,64,67,0.15);
        }

        body {
            font-family: 'Google Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: var(--dark-color);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            box-shadow: var(--box-shadow);
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
        }

        .nav-menu {
            padding: 8px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            text-decoration: none;
            color: #5f6368;
            transition: background 0.2s;
        }

        .nav-item:hover {
            background: #f1f3f4;
        }

        .nav-item.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }

        .nav-item i {
            margin-right: 16px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px;
            background-color: #f5f5f5;
        }

        .form-group {
            margin-bottom: 16px;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
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
    <div class="main-content">
        <h2>Manage Courses</h2>
        <?php if (!empty($success)) echo "<p style='color: green;'>$success</p>"; ?>

        <table>
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Description</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Section</th>
                    <th>Block</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($course = $courses->fetch_assoc()) { ?>
                    <tr>
                        <td><a href="course_details.php?course_id=<?= $course['course_id']; ?>"><?= htmlspecialchars($course['course_name']); ?></a></td>
                        <td><?= htmlspecialchars(strlen($course['description']) > 50 ? substr($course['description'], 0, 50) . '...' : $course['description']); ?></td>
                        <td><?= htmlspecialchars($course['academic_year']); ?></td>
                        <td><?= htmlspecialchars($course['semester']); ?></td>
                        <td><?= htmlspecialchars($course['section']); ?></td>
                        <td><?= htmlspecialchars($course['block_id']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <br><a href="create_course.php" class="btn btn-success">Create New Course +</a>
    </div>
</body>
</html>
