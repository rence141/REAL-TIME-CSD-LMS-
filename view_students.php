<?php
session_start();
require 'db_connect.php'; // Include the connection

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    die("Professor not logged in.");
}

// Fetch course_id (make sure it's passed in the URL or session)
$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    die("Course ID is required.");
}

// Fetch the enrolled students along with their course details from the professor's database
$stmt_professors = $conn->prepare("
    SELECT 
        e.enrollment_id,
        e.student_id,
        e.course_id,
        e.enrolled_at,
        c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.course_id = ?
");

$stmt_professors->bind_param("i", $course_id);
$stmt_professors->execute();
$enrolled_students = $stmt_professors->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_professors->close();

// Check if we have any students
if (empty($enrolled_students)) {
    die("No students found for the course.");
}

// Extract student IDs
$student_ids = array_column($enrolled_students, 'student_id');
$student_ids_str = implode(',', $student_ids);

// Fetch student names and profile images from the students' database
$stmt_students = $conn_student->prepare("
    SELECT student_id, student_name, profile 
    FROM students 
    WHERE student_id IN ($student_ids_str)
");

$stmt_students->execute();
$student_details = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_students->close();

// Check if we have student details
if (empty($student_details)) {
    die("No student details found.");
}

// Map student_id to student_name and profile image for easy lookup
$student_info_map = [];
foreach ($student_details as $student) {
    if (isset($student['student_id'], $student['student_name'], $student['profile'])) {
        $student_info_map[$student['student_id']] = [
            'student_name' => $student['student_name'],
            'profile' => $student['profile'] ?? 'assets/default-avatar.png'  // Default avatar if not set
        ];
    } else {
        echo "Warning: Missing student_name, student_id, or profile image in student details.";
    }
}

// Fetch course name (from professor DB)
$stmt_courses = $conn->prepare("
    SELECT course_name FROM courses 
    WHERE course_id = ? AND professor_id = ?
");
$stmt_courses->bind_param("ii", $course_id, $professor_id);
$stmt_courses->execute();
$course_data = $stmt_courses->get_result()->fetch_assoc();
$course_name = $course_data['course_name'] ?? "Unknown Course";
$stmt_courses->close();

$conn->close(); // Close professor DB connection
$conn_student->close(); // Close student DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrolled Students</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f7f7f7;
        }
        h2 {
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 15px;
            background: rgb(43, 88, 252);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4285F4;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-name {
            font-weight: 500;
        }
    </style>
</head>
<body>

<h2>Enrolled Students for Course: <?= htmlspecialchars($course_name) ?></h2>

<button onclick="goBack()">⬅ Back</button>
<input type="text" id="searchBar" placeholder="Search students..." onkeyup="filterTable()">

<?php if (empty($enrolled_students)): ?>
    <p>No students enrolled in this course.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Enrollment ID</th>
                <th>Student</th>
                <th>Course Name</th>
                <th>Enrolled At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enrolled_students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['enrollment_id']) ?></td>
                    <td>
                        <div class="student-info">
                            <img src="<?= htmlspecialchars($student_info_map[$student['student_id']]['profile']) ?>" alt="Profile Picture" class="avatar">
                            <span class="student-name"><?= htmlspecialchars($student_info_map[$student['student_id']]['student_name'] ?? 'Unknown') ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($student['course_name']) ?></td>
                    <td><?= htmlspecialchars($student['enrolled_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
function goBack() {
    window.history.back();
}

function filterTable() {
    let input = document.getElementById("searchBar").value.toLowerCase();
    let rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
        let studentName = row.querySelector(".student-name").textContent.toLowerCase();
        let studentID = row.cells[0].textContent.toLowerCase();
        row.style.display = (studentName.includes(input) || studentID.includes(input)) ? "" : "none";
    });
}
</script>

</body>
</html>
