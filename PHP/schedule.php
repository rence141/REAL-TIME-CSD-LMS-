<?php
session_start();
include 'db.connect_professors.php';

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='logiin_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor information
$stmt_prof = $conn->prepare("SELECT Full_Name, email, profile_image FROM professors WHERE id = ?");
$stmt_prof->bind_param("i", $professor_id);
$stmt_prof->execute();
$prof_result = $stmt_prof->get_result();
$prof_data = $prof_result->fetch_assoc();

$professor_name = $prof_data['Full_Name'];
$professor_email = $prof_data['email'];
$profile_image = $prof_data['profile_image'];

$current_day = date('l');

// Handle Schedule Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_schedule'])) {
    $course_id = $_POST['course_id'];
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $room = $_POST['room'];

    $insert_query = "INSERT INTO class_schedule (professor_id, course_id, day_of_week, start_time, end_time, room) 
                     VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($insert_query);
    $stmt_insert->bind_param("iissss", $professor_id, $course_id, $day, $start, $end, $room);
    $stmt_insert->execute();
}

$query = "SELECT courses.course_name, class_schedule.start_time, class_schedule.end_time, class_schedule.room 
          FROM class_schedule
          JOIN courses ON class_schedule.course_id = courses.course_id
          WHERE class_schedule.professor_id = ? AND class_schedule.day_of_week = ?
          ORDER BY class_schedule.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $professor_id, $current_day);
$stmt->execute();
$result = $stmt->get_result();

// Fetch courses for dropdown
$stmt_courses = $conn->prepare("SELECT * FROM courses WHERE professor_id = ?");
$stmt_courses->bind_param("i", $professor_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Today's Schedule</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            background-color: #f5f5f5;
            color: var(--dark-color);
            margin: 0;
        }
        /* Sidebar - Google-style */
        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
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
            color: #5f6368;
            margin-top: 2px;
        }

        .main-content {
            margin-left: 280px;
            padding: 24px;
        }
        .schedule-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            margin-top: 30px;
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
        <a href="grades.php" class="nav-item">
            <i class="material-icons">grade</i>
            <span>Grade Entry</span>
        </a>
        <a href="schedule.php" class="nav-item active">
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

<main class="main-content">
    <h2 class="text-center mb-4">Class Schedule for <?= $current_day ?></h2>

    <table class="table table-bordered bg-white shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Course</th>
                <th>Time</th>
                <th>Room</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['course_name'] ?></td>
                        <td><?= date("h:i A", strtotime($row['start_time'])) ?> - <?= date("h:i A", strtotime($row['end_time'])) ?></td>
                        <td><?= $row['room'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center">No classes scheduled for today.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add Schedule Form -->
    <div class="schedule-form mt-5">
        <h4>Add New Class Schedule</h4>
        <form method="POST" action="">
            <input type="hidden" name="add_schedule" value="1">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-select" required>
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?= $course['course_id'] ?>"><?= $course['course_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="day_of_week" class="form-label">Day</label>
                    <select name="day_of_week" class="form-select" required>
                        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label for="room" class="form-label">Room</label>
                    <input type="text" name="room" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Schedule</button>
        </form>
    </div>
    <div class="text-center">
        <a href="dashboard_professor.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>
</main>
</body>
</html>
