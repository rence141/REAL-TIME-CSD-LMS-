<?php
session_start();
include 'db_connect.php'; // Database connection

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Access denied! Admins only.'); window.location.href='admin_login.php';</script>";
    exit();
}

// Fetch professors
$professors = $conn->query("SELECT professor_id, full_name FROM users WHERE role='professor'");

// Fetch courses
$courses = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Schedules</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center"> Manage Professor Schedules</h2>

    <div class="card p-4 shadow">
        <form action="admin_add_schedule.php" method="POST">
            <!-- Select Professor -->
            <div class="mb-3">
                <label class="form-label fw-bold">Professor:</label>
                <select class="form-control" name="professor_id" required>
                    <option value="">-- Select Professor --</option>
                    <?php while ($row = $professors->fetch_assoc()): ?>
                        <option value="<?= $row['professor_id'] ?>"><?= $row['full_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Select Course -->
            <div class="mb-3">
                <label class="form-label fw-bold">Course:</label>
                <select class="form-control" name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php while ($row = $courses->fetch_assoc()): ?>
                        <option value="<?= $row['course_id'] ?>"><?= $row['course_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Select Day -->
            <div class="mb-3">
                <label class="form-label fw-bold">Day of the Week:</label>
                <select class="form-control" name="day_of_week" required>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
            </div>

            <!-- Time & Room -->
            <div class="mb-3">
                <label class="form-label fw-bold">Start Time:</label>
                <input type="time" class="form-control" name="start_time" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">End Time:</label>
                <input type="time" class="form-control" name="end_time" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Room:</label>
                <input type="text" class="form-control" name="room" required>
            </div>

            <button type="submit" class="btn btn-success w-100"> Add Schedule</button>
        </form>
    </div>

    <div class="text-center mt-4">
        <a href="admin_dashboard.php" class="btn btn-secondary"> Back to Dashboard</a>
    </div>

</body>
</html>
