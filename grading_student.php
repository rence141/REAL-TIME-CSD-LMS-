<?php
session_start();
require 'db_connect.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Create connection to professors database
$conn_professors = new mysqli('localhost', 'root', '003421.!', 'lms_dashborad_professors');

// Get student information
$stmt = $conn_student->prepare("SELECT student_name, profile FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_name = $student['student_name'];
$profile_image = !empty($student['profile']) ? $student['profile'] : "Default_avatar.jpg.png";

// Get enrolled courses with grades
$grades_query = "
    SELECT 
        c.course_id,
        c.course_name,
        c.course_code,
        GROUP_CONCAT(DISTINCT 
            CONCAT(
                a.activity_name, ': ', 
                s.grade,
                ' (',
                CASE 
                    WHEN s.grade >= 97 THEN '1.00'
                    WHEN s.grade >= 94 THEN '1.25'
                    WHEN s.grade >= 91 THEN '1.50'
                    WHEN s.grade >= 88 THEN '1.75'
                    WHEN s.grade >= 85 THEN '2.00'
                    WHEN s.grade >= 82 THEN '2.25'
                    WHEN s.grade >= 79 THEN '2.50'
                    WHEN s.grade >= 76 THEN '2.75'
                    WHEN s.grade >= 75 THEN '3.00'
                    ELSE '5.00'
                END,
                ')'
            ) ORDER BY a.activity_name SEPARATOR '<br>'
        ) as activity_grades,
        AVG(s.grade) as average_grade,
        CASE 
            WHEN AVG(s.grade) >= 97 THEN '1.00'
            WHEN AVG(s.grade) >= 94 THEN '1.25'
            WHEN AVG(s.grade) >= 91 THEN '1.50'
            WHEN AVG(s.grade) >= 88 THEN '1.75'
            WHEN AVG(s.grade) >= 85 THEN '2.00'
            WHEN AVG(s.grade) >= 82 THEN '2.25'
            WHEN AVG(s.grade) >= 79 THEN '2.50'
            WHEN AVG(s.grade) >= 76 THEN '2.75'
            WHEN AVG(s.grade) >= 75 THEN '3.00'
            ELSE '5.00'
        END as grade_equivalent
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN activities a ON c.course_id = a.course_id
    LEFT JOIN submissions s ON a.activity_id = s.activity_id AND s.student_id = ?
    WHERE e.student_id = ?
    GROUP BY c.course_id, c.course_name, c.course_code
    ORDER BY c.course_name";

$stmt = $conn_professors->prepare($grades_query);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$grades_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #34A853;
            --danger-color: #EA4335;
            --warning-color: #FBBC05;
            --dark-color: #202124;
            --light-color: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        body {
            font-family: 'Google Sans', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .grades-table th, 
        .grades-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .grades-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .grades-table tr:hover {
            background-color: #f8f9fa;
        }

        .grade {
            font-weight: 500;
            color: var(--primary-color);
        }

        .activity-grades {
            font-size: 14px;
            line-height: 1.6;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #3367d6;
        }

        .back-button i {
            margin-right: 8px;
        }

        .no-grades {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .grades-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_student.php" class="back-button">
            <i class="material-icons">arrow_back</i>
            Back to Dashboard
        </a>

        <div class="header">
            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile">
            <h1><?= htmlspecialchars($student_name) ?>'s Grades</h1>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Course Code</th>
                    <th>Activities & Grades</th>
                    <th>Average Grade</th>
                    <th>Grade Equivalent</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($grades_result->num_rows > 0): ?>
                    <?php while ($row = $grades_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                            <td><?= htmlspecialchars($row['course_code']) ?></td>
                            <td class="activity-grades">
                                <?= $row['activity_grades'] ? $row['activity_grades'] : 'No grades yet' ?>
                            </td>
                            <td class="grade">
                                <?= $row['average_grade'] ? number_format($row['average_grade'], 2) . '%' : 'N/A' ?>
                            </td>
                            <td class="grade">
                                <?= $row['grade_equivalent'] ?: 'N/A' ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-grades">No grades available yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Add any interactive features here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Highlight row on hover
            const rows = document.querySelectorAll('.grades-table tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseover', () => {
                    row.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseout', () => {
                    row.style.backgroundColor = '';
                });
            });
        });
    </script>
</body>
</html> 