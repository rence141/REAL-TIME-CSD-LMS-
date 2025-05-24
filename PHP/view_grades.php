<?php
session_start();
include 'db_connect.php';

// Ensure professor is logged in
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

// Fetch filters from GET request
$semester = $_GET['semester'] ?? 'Sem 1';
$search = $_GET['search'] ?? '';
$course_block = $_GET['course_block'] ?? '';
$subject = $_GET['subject'] ?? '';
$min_grade = $_GET['min_grade'] ?? '';
$max_grade = $_GET['max_grade'] ?? '';

// Fetch unique course blocks and subjects for filters
$courseBlocks = $conn->query("SELECT DISTINCT course_block FROM courses ORDER BY course_block ASC")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch grades with filters applied
$query = "
    SELECT grades.grade_id, students.id AS student_id, students.name AS student_name, 
           IFNULL(courses.course_block, 'N/A') AS course_block, 
           IFNULL(subjects.subject_name, 'N/A') AS subject_name, 
           IFNULL(grades.grade, 0) AS grade, 
           IFNULL(grades.semester, 'N/A') AS semester
    FROM students
    LEFT JOIN grades ON grades.student_id = students.id AND grades.semester = ? 
    LEFT JOIN courses ON students.course_id = courses.course_id
    LEFT JOIN subjects ON grades.subject_id = subjects.subject_id
    WHERE grades.semester = ? 
    AND (students.name LIKE ? OR ? = '') 
    AND (courses.course_block = ? OR ? = '') 
    AND (subjects.subject_name = ? OR ? = '') 
    AND (grades.grade >= ? OR ? = '')
    AND (grades.grade <= ? OR ? = '')
    ORDER BY students.name ASC
";

$stmt = $conn->prepare($query);
$searchWildcard = "%$search%";
$stmt->bind_param("ssssssssssss", $semester, $semester, $searchWildcard, $search, $course_block, $course_block, $subject, $subject, $min_grade, $min_grade, $max_grade, $max_grade);
$stmt->execute();
$result = $stmt->get_result();

// Function to convert elementary grades to college grades
function convertToCollegeGrade($grade) {
    if ($grade >= 98) return 1.0;
    if ($grade >= 95) return 1.25;
    if ($grade >= 92) return 1.5;
    if ($grade >= 89) return 1.75;
    if ($grade >= 86) return 2.0;
    if ($grade >= 83) return 2.25;
    if ($grade >= 80) return 2.5;
    if ($grade >= 77) return 2.75;
    if ($grade >= 75) return 3.0;
    return 5.0; // Failing grade
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Grades</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <!-- Material Icons -->
     <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
<style>
        /* General Page Styling */
        <style>
        /* Global Styling */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* Sidebar */
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
            transition: transform 0.3s ease;
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
        /* Header Styling */
        h2  {
            font-size: 28px;
            color:rgb(243, 246, 255);
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
        }
        h1 {
            font-size: 28px;
            color:rgb(24, 25, 27);
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Card Container */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(60, 64, 67, 0.15), 0 1px 2px rgba(60, 64, 67, 0.3);
            background-color: #ffffff;
            padding: 30px;
        }

        /* Form Styling */
        .form-label {
            font-weight: 500;
            color:rgb(195, 201, 210);
        }

        .form-control, select {
            height: 40px;
            border-radius: 8px;
            border: 1px solidrgb(48, 50, 53);
        }

        .form-control:focus, select:focus {
            box-shadow: none;
            border-color: #4285f4; /* Google's blue */
        }

        .btn-primary {
            background-color: #4285f4;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #357ae8;
        }

        .btn-sm {
            font-size: 13px;
            padding: 5px 10px;
        }
          /* Main Content */
           /* Main Content */
        .main-content {
            margin-left: 80px;
            flex: 1;
            padding: 24px;
            background-color: #f5f5f5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background:rgb(255, 254, 254);
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: black 0px 0px 10px;
            margin-bottom: 20px;
        }
        .btn-add {
            background:rgb(71, 137, 229);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-add:hover {
            background:rgba(71, 77, 137, 229);
        }

        /* Table Styling */
        .table-container {
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .table thead {
            background:rgb(241, 247, 253);
            color: white;
        }
        .table th, .table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        /* Action Buttons */
        .btn-view, .btn-delete {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view {
            background: #007bff;
        }
        .btn-delete {
            background: #dc3545;
        }
        .btn-view:hover {
            background: #0056b3;
        }
        .btn-delete:hover {
            background: #c82333;
        }


        /* Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #e8eaed;
            color: #202124;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 2px solid #dadce0;
        }

        .table tbody td {
            font-size: 14px;
            color: #5f6368;
            padding: 12px;
        }

        .table tbody tr {
            border-bottom: 1px solid #dadce0;
        }

        .table tbody tr:hover {
            background-color: #f1f3f4;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .card {
                padding: 20px;
            }

            .form-control, select {
                height: 35px;
                font-size: 14px;
            }

            .btn-primary {
                font-size: 13px;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar Navigation -->
<aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <span class="app-name">Computer Science Department</span>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard_professor.php" class="nav-item ">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="attendance_prof.php" class="nav-item">
                <i class="material-icons">check_circle</i>
                <span>Attendance</span>
            </a>
            <a href="add_grades.php" class="nav-item active">
                <i class="material-icons">grade</i>
                <span>Grading</span>
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

    <h1> View Student Grades</h1>
<div class="main-content">
    <div class="card">
        <!-- Filters Form -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Select Semester:</label>
                <select class="form-control" name="semester" onchange="this.form.submit()">
                    <option value="Sem 1" <?= $semester == "Sem 1" ? "selected" : "" ?>>Semester 1</option>
                    <option value="Sem 2" <?= $semester == "Sem 2" ? "selected" : "" ?>>Semester 2</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Search Student:</label>
                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter student name">
            </div>

            <div class="col-md-3">
                <label class="form-label">Course Block:</label>
                <select class="form-control" name="course_block">
                    <option value="">All</option>
                    <?php foreach ($courseBlocks as $cb): ?>
                        <option value="<?= $cb['course_block'] ?>" <?= $course_block == $cb['course_block'] ? "selected" : "" ?>>
                            <?= $cb['course_block'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Subject:</label>
                <select class="form-control" name="subject">
                    <option value="">All</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['subject_name'] ?>" <?= $subject == $sub['subject_name'] ? "selected" : "" ?>>
                            <?= $sub['subject_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Min Grade:</label>
                <input type="number" step="0.01" class="form-control" name="min_grade" value="<?= htmlspecialchars($min_grade) ?>" placeholder="e.g. 1.5">
            </div>

            <div class="col-md-3">
                <label class="form-label">Max Grade:</label>
                <input type="number" step="0.01" class="form-control" name="max_grade" value="<?= htmlspecialchars($max_grade) ?>" placeholder="e.g. 3.0">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"> Apply Filters</button>
            </div>
        </form>

        <!-- Table -->
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Course Block</th>
                    <th>Subject</th>
                    <th>Semester</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['course_block']) ?></td>
                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                            <td><?= htmlspecialchars($row['semester']) ?></td>
                            <td><?= convertToCollegeGrade($row['grade']) ?></td>
                            <td>
                                <a href="edit_grade.php?id=<?= $row['grade_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_grade.php?id=<?= $row['grade_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No grades found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
