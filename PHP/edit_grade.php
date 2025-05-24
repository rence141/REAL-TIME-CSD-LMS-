<?php
session_start();
require 'db_connect.php';

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    header("Location: login_professors.php");
    exit();
}

// Get grade ID from URL
$grade_id = $_GET['grade_id'] ?? null;
if (!$grade_id) {
    die("Grade ID is required.");
}

// Fetch grade details with submission and student info
$stmt = $conn->prepare("
    SELECT sg.*, s.student_id, s.submission_date, s.file_path, 
           a.activity_name, a.due_date, a.activity_id
    FROM submission_grades sg
    JOIN submissions s ON sg.submission_id = s.submission_id
    JOIN activities a ON s.activity_id = a.activity_id
    WHERE sg.grade_id = ? AND s.professor_id = ?
");
$stmt->bind_param("ii", $grade_id, $professor_id);
$stmt->execute();
$grade_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$grade_data) {
    die("Grade not found or you don't have permission to edit it.");
}

// Get student name from student database
$student_stmt = $conn_student->prepare("SELECT student_name FROM students WHERE student_id = ?");
$student_stmt->bind_param("i", $grade_data['student_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();
$student_name = $student_data['student_name'] ?? 'Unknown Student';
$student_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_grade = floatval($_POST['grade']);
    $new_feedback = trim($_POST['feedback']);
    
    if ($new_grade < 0 || $new_grade > 100) {
        $error = "Grade must be between 0 and 100";
    } else {
        // Update grade
        $update_stmt = $conn->prepare("
            UPDATE submission_grades 
            SET grade = ?, feedback = ?, graded_at = CURRENT_TIMESTAMP
            WHERE grade_id = ? AND graded_by = ?
        ");
        $update_stmt->bind_param("dsii", $new_grade, $new_feedback, $grade_id, $professor_id);
        
        if ($update_stmt->execute()) {
            // Update grade in submissions table
            $submissions_update = $conn->prepare("
                UPDATE submissions 
                SET grade = ? 
                WHERE submission_id = ?
            ");
            $submissions_update->bind_param("di", $new_grade, $grade_data['submission_id']);
            $submissions_update->execute();
            $submissions_update->close();
            
            $success = sprintf(
                "Grade successfully updated for %s!\nPrevious Grade: %.2f\nNew Grade: %.2f",
                htmlspecialchars($student_name),
                floatval($grade_data['grade']),
                $new_grade
            );
            
            // Refresh grade data
            $stmt = $conn->prepare("SELECT * FROM submission_grades WHERE grade_id = ?");
            $stmt->bind_param("i", $grade_id);
            $stmt->execute();
            $grade_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Error updating grade: " . $conn->error;
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade - <?= htmlspecialchars($student_name) ?></title>
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
            --transition: all 0.2s ease-in-out;
        }

        body {
            font-family: 'Google Sans', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 24px;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 0;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        h1 {
            color: var(--dark-color);
            font-size: 24px;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .submission-info {
            margin-bottom: 24px;
            background: var(--light-color);
            padding: 16px;
            border-radius: var(--border-radius);
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            color: #5f6368;
        }

        .info-label {
            width: 140px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }

        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }

        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            background: #1a73e8;
            box-shadow: 0 2px 4px rgba(26, 115, 232, 0.3);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #137333;
        }

        .alert-error {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #c5221f;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-on-time {
            background: #e6f4ea;
            color: #137333;
        }

        .status-late {
            background: #fce8e6;
            color: #c5221f;
        }

        .grade-history {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #dadce0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="view_submissions.php?activity_id=<?= $grade_data['activity_id'] ?>" class="back-btn">
            <i class="material-icons">arrow_back</i>
            Back to Submissions
        </a>

        <div class="card">
            <h1>
                <i class="material-icons">edit</i>
                Edit Grade
            </h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="material-icons">error</i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="material-icons">check_circle</i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="submission-info">
                <div class="info-row">
                    <span class="info-label">Student:</span>
                    <span><?= htmlspecialchars($student_name) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Activity:</span>
                    <span><?= htmlspecialchars($grade_data['activity_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Submitted:</span>
                    <span>
                        <?= date('M j, Y g:i A', strtotime($grade_data['submission_date'])) ?>
                        <?php
                        $due_date = strtotime($grade_data['due_date']);
                        $submission_date = strtotime($grade_data['submission_date']);
                        $status_class = $submission_date <= $due_date ? 'status-on-time' : 'status-late';
                        $status_text = $submission_date <= $due_date ? 'On Time' : 'Late';
                        ?>
                        <span class="status-badge <?= $status_class ?>">
                            <i class="material-icons" style="font-size: 16px;">
                                <?= $submission_date <= $due_date ? 'check_circle' : 'warning' ?>
                            </i>
                            <?= $status_text ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Updated:</span>
                    <span><?= date('M j, Y g:i A', strtotime($grade_data['graded_at'])) ?></span>
                </div>
            </div>

            <form method="POST" id="gradeForm" onsubmit="return confirmUpdate(event)">
                <div class="form-group">
                    <label for="grade">Grade (0-100)</label>
                    <input type="number" 
                           id="grade" 
                           name="grade" 
                           min="0" 
                           max="100" 
                           step="0.01" 
                           value="<?= htmlspecialchars($grade_data['grade']) ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" 
                              name="feedback" 
                              placeholder="Provide feedback for the student..."><?= htmlspecialchars($grade_data['feedback']) ?></textarea>
                </div>

                <button type="submit" class="btn">
                    <i class="material-icons">save</i>
                    Update Grade
                </button>
            </form>
        </div>
    </div>

    <script>
    function confirmUpdate(event) {
        event.preventDefault();
        
        const currentGrade = <?= json_encode($grade_data['grade']) ?>;
        const newGrade = document.getElementById('grade').value;
        const studentName = <?= json_encode($student_name) ?>;
        
        const message = `Are you sure you want to update the grade for ${studentName}?\n\n` +
                       `Current Grade: ${currentGrade}\n` +
                       `New Grade: ${newGrade}`;
                       
        if (confirm(message)) {
            document.getElementById('gradeForm').submit();
        }
        
        return false;
    }
    </script>
</body>
</html>
