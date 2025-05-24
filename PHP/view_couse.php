<?php
// Assuming a database connection is already established
session_start();
$isProfessor = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'professor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Courses</h1>
        <table class="table table-bordered mt-4">
            <thead class="table-dark">
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Credits</th>
                    <th>Professor</th>
                    <?php if ($isProfessor): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch courses from the database
                $query = "SELECT * FROM courses";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['course_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['credits']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['professor_name']) . "</td>";
                        if ($isProfessor) {
                            echo "<td>";
                            echo "<a href='edit_course.php?id=" . $row['course_id'] . "' class='btn btn-warning btn-sm'>Edit</a> ";
                            echo "<a href='delete_course.php?id=" . $row['course_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='" . ($isProfessor ? 5 : 4) . "' class='text-center'>No courses found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <?php if ($isProfessor): ?>
            <a href="add_course.php" class="btn btn-primary">Add New Course</a>
        <?php endif; ?>
    </div>
</body>
</html>
