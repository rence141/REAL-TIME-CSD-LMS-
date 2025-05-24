<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Access denied!'); window.location.href='admin_login.php';</script>";
    exit();
}

// Fetch schedules
$query = "SELECT cs.schedule_id, u.full_name AS professor_name, c.course_name, cs.day_of_week, cs.start_time, cs.end_time, cs.room
          FROM class_schedule cs
          JOIN users u ON cs.professor_id = u.professor_id
          JOIN courses c ON cs.course_id = c.course_id
          ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), cs.start_time";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Schedules</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center"> All Professor Schedules</h2>

    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>Professor</th>
                <th>Course</th>
                <th>Day</th>
                <th>Time</th>
                <th>Room</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['professor_name'] ?></td>
                    <td><?= $row['course_name'] ?></td>
                    <td><?= $row['day_of_week'] ?></td>
                    <td><?= date("h:i A", strtotime($row['start_time'])) ?> - <?= date("h:i A", strtotime($row['end_time'])) ?></td>
                    <td><?= $row['room'] ?></td>
                    <td>
                        <a href="edit_schedule.php?id=<?= $row['schedule_id'] ?>" class="btn btn-warning">✏ Edit</a>
                        <a href="delete_schedule.php?id=<?= $row['schedule_id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this schedule?')">❌ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="text-center mt-4">
        <a href="admin_dashboard.php" class="btn btn-secondary">🔙 Back to Dashboard</a>
    </div>

</body>
</html>
