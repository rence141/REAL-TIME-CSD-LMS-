<?php
session_start();
include 'db_connect.php';

// Ensure professor is logged in
if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

// Fetch grades
$query = "
    SELECT grades.id AS grade_id, students.id AS student_id, students.name AS student_name, 
           courses.course_name, subjects.subject_name, 
           grades.grade, grades.semester
    FROM grades
    JOIN students ON grades.student_id = students.id
    JOIN courses ON students.course_id = courses.course_id
    JOIN subjects ON grades.subject_id = subjects.subject_id
    ORDER BY students.name ASC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit & Delete Grades</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center">📝 Edit & Delete Grades</h2>

    <div class="card shadow p-4">
        <table class="table table-bordered mt-4">
            <thead class="table-dark">
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Semester</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['course_name']) ?></td>
                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= htmlspecialchars($row['semester']) ?></td>
                        <td><?= htmlspecialchars($row['grade']) ?></td>
                        <td>
                            <a href="edit_grade.php?id=<?= $row['grade_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_grade.php?id=<?= $row['grade_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this grade?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="add_grades.php" class="btn btn-primary">Add Grades</a>
    </div>

</body>
</html>
