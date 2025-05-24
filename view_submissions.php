<?php
session_start();
require 'db_connect.php';

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    die("Professor not logged in.");
}

// Get activity ID from URL
$activity_id = $_GET['activity_id'] ?? null;
if (!$activity_id) {
    die("Activity ID is required.");
}

// Fetch activity details
$stmt = $conn->prepare("
    SELECT a.*, c.course_name 
    FROM activities a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.activity_id = ? AND c.professor_id = ?
");
$stmt->bind_param("ii", $activity_id, $professor_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    die("Activity not found or you don't have permission to view it.");
}

// Fetch all submissions for this activity with student info from student database
$stmt = $conn->prepare("
    SELECT s.* 
    FROM submissions s
    WHERE s.activity_id = ? AND s.professor_id = ?
    ORDER BY s.submission_date DESC
");
$stmt->bind_param("ii", $activity_id, $professor_id);
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get student names from student database
$student_names = [];
if (!empty($submissions)) {
    $student_ids = array_column($submissions, 'student_id');
    $student_ids_str = implode(',', $student_ids);
    
    $student_query = "SELECT student_id, student_name FROM students WHERE student_id IN ($student_ids_str)";
    $student_result = $conn_student->query($student_query);
    
    while ($row = $student_result->fetch_assoc()) {
        $student_names[$row['student_id']] = $row['student_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Submissions - <?= htmlspecialchars($activity['activity_name']) ?></title>
   <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
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
            --box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
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
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: var(--dark-color);
            font-size: 24px;
        }

        .activity-info {
            color: #5f6368;
            margin-top: 8px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #e8f0fe;
            border-radius: 4px;
        }

        .submissions-table {
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-collapse: collapse;
            overflow: hidden;
        }

        .submissions-table th,
        .submissions-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .submissions-table th {
            background-color: #f8f9fa;
            color: #5f6368;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-column {
            min-width: 120px;
            text-align: center;
        }

        .submissions-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-width: 90px;
            justify-content: center;
        }

        .status-badge i {
            font-size: 16px;
        }

        .status-on-time {
            background-color: #e6f4ea;
            color: #137333;
        }

        .status-late {
            background-color: #fce8e6;
            color: #c5221f;
        }

        .status-graded {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }

        .status-not-graded {
            background-color: #f1f3f4;
            color: #5f6368;
        }

        .status-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            white-space: nowrap;
        }

        .download-link {
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
        }

        .download-link:hover {
            text-decoration: underline;
        }

        .download-link i {
            font-size: 18px;
        }

        .grade-btn {
            background-color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: white;
        }

        .grade-btn:hover {
            opacity: 0.9;
            background-color: #1a73e8;
        }

        .grade-btn i {
            font-size: 18px;
        }

        .no-submissions {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: var(--border-radius);
            color: #5f6368;
            margin-top: 20px;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            width: 300px;
            font-size: 14px;
        }

        .table-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="activity_view.php?activity_id=<?= $activity_id ?>" class="back-btn">
            <i class="material-icons">arrow_back</i>
            Back to Activity
        </a>

        <div class="header">
            <h1><?= htmlspecialchars($activity['activity_name']) ?></h1>
            <div class="activity-info">
                Course: <?= htmlspecialchars($activity['course_name']) ?> |
                Due Date: <?= date('M j, Y', strtotime($activity['due_date'])) ?>
            </div>
        </div>

        <div class="table-actions">
            <input type="text" id="searchInput" class="search-box" placeholder="Search submissions..." onkeyup="searchTable()">
            <div class="submission-stats">
                Total Submissions: <?= count($submissions) ?>
            </div>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="no-submissions">
                <i class="material-icons" style="font-size: 48px; color: #5f6368;">assignment</i>
                <h2>No Submissions Yet</h2>
                <p>There are no student submissions for this activity yet.</p>
            </div>
        <?php else: ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Submission Date</th>
                        <th class="status-column">Status</th>
                        <th class="status-column">Submission Status</th>
                        <th>Grade</th>
                        <th>File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($student_names[$submission['student_id']] ?? 'Unknown Student') ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($submission['submission_date'])) ?></td>
                            <td class="status-column">
                                <?php
                                $due_date = strtotime($activity['due_date']);
                                $submission_date = strtotime($submission['submission_date']);
                                $status_class = $submission_date <= $due_date ? 'status-on-time' : 'status-late';
                                $status_text = $submission_date <= $due_date ? 'On Time' : 'Late';
                                $status_icon = $submission_date <= $due_date ? 'check_circle' : 'warning';
                                ?>
                                <span class="status-badge <?= $status_class ?>">
                                    <i class="material-icons"><?= $status_icon ?></i>
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td class="status-column">
                                <?php
                                $grade_status_class = $submission['grade'] ? 'status-graded' : 'status-not-graded';
                                $grade_status_text = $submission['grade'] ? 'Graded' : 'Pending';
                                $grade_status_icon = $submission['grade'] ? 'task_alt' : 'pending';
                                ?>
                                <span class="status-badge <?= $grade_status_class ?>">
                                    <i class="material-icons"><?= $grade_status_icon ?></i>
                                    <?= $grade_status_text ?>
                                </span>
                            </td>
                            <td>
                                <?= $submission['grade'] ? $submission['grade'] . '/100' : 'Not graded' ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(basename($submission['file_path'])) ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= htmlspecialchars($submission['file_path']) ?>" class="download-link" download>
                                    <i class="material-icons">download</i>
                                    Download
                                </a>
                                <a href="grade_submission.php?submission_id=<?= $submission['submission_id'] ?>" class="grade-btn">
                                    <i class="material-icons">grade</i>
                                    Grade
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
    function searchTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.querySelector('.submissions-table');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const studentName = rows[i].cells[0].textContent.toLowerCase();
            rows[i].style.display = studentName.includes(filter) ? '' : 'none';
        }
    }
    </script>
</body>
</html>
