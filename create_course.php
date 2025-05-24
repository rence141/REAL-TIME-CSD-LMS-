<?php
require 'db_connect.php';
session_start();

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    die("Professor not logged in.");
}

$success = "";
$error = "";

// Auto-generate course_id starting from C00009
$query = "SELECT course_id FROM courses ORDER BY course_id DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row) {
    $last_code = $row['course_id']; // Ensure you're referencing 'course_code'
    preg_match('/C(\d+)/', $last_code, $matches); // Extract numeric part

    $numeric_part = isset($matches[1]) ? intval($matches[1]) : 15; // Default to 13 if no previous code
    $new_code = "C" . str_pad($numeric_part + 1, 5, "0", STR_PAD_LEFT); // Ensures "C00013", "C00014", etc.
} else {
    $new_code = "C00013"; // Default start value
}

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $section = $_POST['section'];
    $block_id = $_POST['block_id'];
  
    if (empty($course_name) || empty($description) || empty($academic_year) || empty($semester) || empty($section) || empty($block_id) ) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("
    INSERT INTO courses (course_name, description, academic_year, semester, section, course_code, professor_id, block_id, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("ssssssis", $course_name, $description, $academic_year, $semester, $section, $new_code, $professor_id, $block_id);
        if ($stmt->execute()) {
            $success = "Course created successfully!";
        } else {
            $error = "Error creating course.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Course</title>
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
            background-color: var(--light-color);
            display: flex;
        }
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
            width: 100%;
            background-color: white;
            box-shadow: var(--box-shadow);
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            font-weight: bold;
        }
        input, select, textarea {
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
        .notification {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .success {
            background: var(--secondary-color);
            color: white;
        }
        .error {
            background: var(--danger-color);
            color: white;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/BUPC_Logo.png" alt="LMS Logo" class="sidebar-logo">
            <span class="app-name">Learning Management System</span>
        </div>
        <nav class="nav-menu">
            <a href="dashboard_professor.php" class="nav-item"><i class="material-icons">dashboard</i> Dashboard</a>
            <a href="attendance_prof.php" class="nav-item"><i class="material-icons">check_circle</i> Attendance</a>
            <a href="add_grades.php" class="nav-item"><i class="material-icons">grade</i> Grading</a>
            <a href="manage_courses.php" class="nav-item active"><i class="material-icons">class</i> Course Management</a>
            <a href="reports.php" class="nav-item"><i class="material-icons">bar_chart</i> Reports</a>
        </nav>
    </aside>
        
    <!-- Main Content -->
    <div class="main-content">
        <h2>Create New Course</h2>
        <?php if (!empty($success)) echo "<div class='notification success'>$success</div>"; ?>
        <?php if (!empty($error)) echo "<div class='notification error'>$error</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Course Name</label>
                <input type="text" name="course_name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Academic Year (e.g 2024-2025)</label>
                <input type="text" name="academic_year" required>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" required>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section" required>
            </div>
            <div class="form-group">
                <label>Block ID</label>
                <input type="text" name="block_id" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Course</button>
            <a href="manage_courses.php" class="">Courses</a>
        </form>
    </div>

</body>
</html>