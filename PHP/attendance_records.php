<?php
session_start();
include 'db_connect.php'; // Database connection

// Ensure the user is an admin (assuming you have a 'user_type' column)
if ($_SESSION['user_type'] != 'admin') {
    echo "<script>alert('You are not authorized to view this page.'); window.location.href='login_professor.php';</script>";
    exit();
}

// Fetch attendance records
$query = "
    SELECT a.id, s.name AS student_name, c.course_name, a.course_block, a.status, a.date, p.name AS professor_name
    FROM attendance_records a
    JOIN students s ON a.student_id = s.id
    JOIN courses c ON a.course_id = c.course_id
    JOIN professors p ON a.professor_id = p.id
    ORDER BY a.date DESC, c.course_name, s.name
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center mb-4"> Attendance Records</h2>

    <!-- Attendance Table -->
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Professor</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Course Block</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['professor_name']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['course_block']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Go Back Button -->
    <div class="mt-4 text-center">
        <a href="admin_dashboard.php" class="btn btn-secondary"> Back to Dashboard</a>
    </div>

</body>
</html>
