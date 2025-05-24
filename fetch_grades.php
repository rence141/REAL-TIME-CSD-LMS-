<?php
include 'db_connect.php';

$course_id = $_POST['course_id'] ?? '';
$subject_id = $_POST['subject_id'] ?? '';
$course_block = $_POST['course_block'] ?? '';
$semester = $_POST['semester'] ?? '';

// Query to fetch grades
$query = "
    SELECT students.id, students.name, courses.course_name, subjects.subject_name, 
           grades.grade, grades.semester
    FROM grades
    JOIN students ON grades.student_id = students.id
    JOIN courses ON students.course_id = courses.course_id
    JOIN subjects ON grades.subject_id = subjects.subject_id
    WHERE 1 = 1
";

if ($course_id) {
    $query .= " AND courses.course_id = '$course_id'";
}
if ($subject_id) {
    $query .= " AND grades.subject_id = '$subject_id'";
}
if ($course_block) {
    $query .= " AND students.course_block = '$course_block'";
}
if ($semester) {
    $query .= " AND grades.semester = '$semester'";
}

$query .= " ORDER BY students.name ASC";

$result = $conn->query($query);

echo '<table class="table table-bordered">';
echo '<thead class="table-dark">';
echo '<tr>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Course</th>
        <th>Subject</th>
        <th>Semester</th>
        <th>Grade</th>
        <th>Final Grade</th>
      </tr>';
echo '</thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $grade = $row['grade'];

    // Convert raw grade to final grade
    if ($grade >= 96) {
        $final_grade = "1.0";
    } elseif ($grade >= 90) {
        $final_grade = "1.5";
    } elseif ($grade >= 85) {
        $final_grade = "2.0";
    } elseif ($grade >= 80) {
        $final_grade = "2.5";
    } elseif ($grade >= 75) {
        $final_grade = "3.0";
    } elseif ($grade >= 60) {
        $final_grade = "4.0";
    } else {
        $final_grade = "5.0";
    }

    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['name']}</td>
            <td>{$row['course_name']}</td>
            <td>{$row['subject_name']}</td>
            <td>{$row['semester']}</td>
            <td>{$grade}</td>
            <td><strong>{$final_grade}</strong></td>
          </tr>";
}

echo '</tbody></table>';
?>
