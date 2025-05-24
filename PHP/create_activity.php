<?php
include 'db_connect.php';
session_start();

$professor_id = $_SESSION['professor_id'] ?? 1; // Default to 1 for testing, but use session value if set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Activity</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $activity_name = $_POST['activity_name'] ?? '';
    $activity_type = $_POST['activity_type'] ?? 'activity';
    $description = $_POST['activity_description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $section = $_POST['section'] ?? '';
    $block_id = $_POST['block_id'] ?? '';
    $media = $_FILES['media']['name'] ?? '';

    $course_code = '';
    if ($course_id) {
        $stmt = $conn->prepare("SELECT course_code FROM courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $stmt->bind_result($course_code);
        $stmt->fetch();
        $stmt->close();
    }

    $target_dir = "uploads/";
    $target_file = $target_dir . uniqid('activities_', true) . "." . pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
    
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 25 * 1024 * 1024; // 25 MB
    
    if (!in_array($_FILES['media']['type'], $allowed_types)) {
        echo "<div class='alert alert-danger'>Invalid file type. Only JPEG, PNG, and PDF are allowed.</div>";
    } elseif ($_FILES['media']['size'] > $max_file_size) {
        echo "<div class='alert alert-danger'>File size exceeds the 25MB limit.</div>";
    } elseif (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
    
        $media = $target_file; // ✅ this line is crucial
    
        $stmt = $conn->prepare("INSERT INTO activities (course_id, activity_name, description, media, due_date, professor_id, created_at, activity_type) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("issssss", $course_id, $activity_name, $description, $media, $due_date, $professor_id, $activity_type);
    
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Activity created successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
        }
    
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Media upload failed.</div>";
    }
    
}
?>
    <h2 class="text-center">Create Activity</h2>
    <div class="container">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="activity_name">Activity Title</label>
                <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
                <input type="text" name="activity_name" id="activity_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="activity_type">Activity Type</label>
                <select name="activity_type" id="activity_type" class="form-control" required>
                    <option value="quiz">Quiz (30%)</option>
                    <option value="activity">Activity (30%)</option>
                    <option value="assignment">Assignment (40%)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="activity_description">Activity Description</label>
                <textarea name="activity_description" id="activity_description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="media">Upload Media</label>
                <input type="file" name="media" id="media" class="form-control" required>
            </div>
            <div class="form-group">
            <label for="course_id">Select Your Course</label>
            <select name="course_id" id="course_id" class="form-control" required>
                <?php
                // Ensure the professor is logged in
                $professor_id = $_SESSION['professor_id'] ?? null;
                if ($professor_id) {
                    $stmt = $conn->prepare("SELECT course_id, course_name FROM courses WHERE professor_id = ?");
                    $stmt->bind_param("i", $professor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['course_id'] . "'>" . htmlspecialchars($row['course_name']) . "</option>";
                    }

                    $stmt->close();
                } else {
                    echo "<option disabled>No courses available.</option>";
                }
                ?>
            </select>
        </div>
        </div>
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <input type="text" name="academic_year" id="academic_year" class="form-control" required>
            </div>
            <div class="form-group">
            <label for="semester">Semester</label>
            <select name="semester" id="semester" class="form-control" required>
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
            </select>
        </div>
            <div class="form-group">
                <label for="section">Section</label>
                <input type="text" name="section" id="section" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="block_id">Block ID</label>
                <input type="text" name="block_id" id="block_id" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Create Activity</button>
        </form>
    </div>
</div>
</body>
</html>
