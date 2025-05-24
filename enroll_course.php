<?php
// enrollment_student.php

// Simulated existing data (normally this would come from a database)
$enrollments = [
    ['enrollment_id' => 1, 'student_id' => 'S101', 'course_id' => 'C202', 'enrolled_at' => '2025-05-01'],
    ['enrollment_id' => 2, 'student_id' => 'S102', 'course_id' => 'C203', 'enrolled_at' => '2025-05-02']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $new_id = count($enrollments) + 1;
    $enrolled_at = date('Y-m-d');

    if ($student_id && $course_id) {
        $enrollments[] = [
            'enrollment_id' => $new_id,
            'student_id' => $student_id,
            'course_id' => $course_id,
            'enrolled_at' => $enrolled_at
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Enrollment</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <style>
        body { font-family: Arial; padding: 20px; }
        form { margin-bottom: 20px; }
        input[type="text"] { padding: 5px; margin-right: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>

    <h2>Enroll a Student</h2>
    <form method="POST">
        <input type="text" name="student_id" placeholder="Student ID" required />
        <input type="text" name="course_id" placeholder="Course ID" required />
        <button type="submit">Enroll</button>
    </form>

    <h3>Enrollment Records</h3>
    <table>
        <thead>
            <tr>
                <th>Enrollment ID</th>
                <th>Student ID</th>
                <th>Course ID</th>
                <th>Enrolled At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enrollments as $enroll): ?>
                <tr>
                    <td><?= htmlspecialchars($enroll['enrollment_id']) ?></td>
                    <td><?= htmlspecialchars($enroll['student_id']) ?></td>
                    <td><?= htmlspecialchars($enroll['course_id']) ?></td>
                    <td><?= htmlspecialchars($enroll['enrolled_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
