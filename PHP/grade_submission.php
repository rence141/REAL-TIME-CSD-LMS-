<?php
session_start();
require 'db_connect.php';

// Ensure professor is logged in
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    header("Location: login_professors.php");
    exit();
}

// Create submission_grades table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS submission_grades (
    grade_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    grade DECIMAL(5,2) NOT NULL,
    feedback TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(submission_id),
    FOREIGN KEY (graded_by) REFERENCES professors(id)
)";
$conn->query($create_table_sql);

// Get submission ID from URL
$submission_id = $_GET['submission_id'] ?? null;
if (!$submission_id) {
    die("Submission ID is required.");
}

// Fetch submission details with student info
$stmt = $conn->prepare("
    SELECT s.*, a.activity_name, a.due_date 
    FROM submissions s
    JOIN activities a ON s.activity_id = a.activity_id
    WHERE s.submission_id = ? AND s.professor_id = ?
");
$stmt->bind_param("ii", $submission_id, $professor_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$submission) {
    die("Submission not found or you don't have permission to grade it.");
}

// Get student name from student database
$student_stmt = $conn_student->prepare("SELECT student_name FROM students WHERE student_id = ?");
$student_stmt->bind_param("i", $submission['student_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();
$student_name = $student_data['student_name'] ?? 'Unknown Student';
$student_stmt->close();

// Check if submission is already graded
$stmt = $conn->prepare("SELECT * FROM submission_grades WHERE submission_id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$existing_grade = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = floatval($_POST['grade']);
    $feedback = trim($_POST['feedback']);
    
    if ($grade < 0 || $grade > 100) {
        $error = "Grade must be between 0 and 100";
    } else {
        if ($existing_grade) {
            // Update existing grade
            $stmt = $conn->prepare("
                UPDATE submission_grades 
                SET grade = ?, feedback = ?, graded_by = ?, graded_at = CURRENT_TIMESTAMP
                WHERE submission_id = ?
            ");
            $stmt->bind_param("dsii", $grade, $feedback, $professor_id, $submission_id);
        } else {
            // Insert new grade
            $stmt = $conn->prepare("
                INSERT INTO submission_grades (submission_id, grade, feedback, graded_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("idsi", $submission_id, $grade, $feedback, $professor_id);
        }
        
        if ($stmt->execute()) {
            // Update grade in submissions table
            $update_stmt = $conn->prepare("UPDATE submissions SET grade = ? WHERE submission_id = ?");
            $update_stmt->bind_param("di", $grade, $submission_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $success = "Grade submitted successfully!";
            // Refresh grade data
            $stmt = $conn->prepare("SELECT * FROM submission_grades WHERE submission_id = ?");
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $existing_grade = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error saving grade: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - <?= htmlspecialchars($student_name) ?></title>
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
        }

        .submission-info {
            margin-bottom: 24px;
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

        .submission-file {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
        }

        .file-icon {
            color: var(--primary-color);
            font-size: 24px;
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
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background: #1a73e8;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="view_submissions.php?activity_id=<?= $submission['activity_id'] ?>" class="back-btn">
            <i class="material-icons">arrow_back</i>
            Back to Submissions
        </a>

        <div class="card">
            <h1>Grade Submission</h1>

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
                    <span><?= htmlspecialchars($submission['activity_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Submitted:</span>
                    <span>
                        <?= date('M j, Y g:i A', strtotime($submission['submission_date'])) ?>
                        <?php
                        $due_date = strtotime($submission['due_date']);
                        $submission_date = strtotime($submission['submission_date']);
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
            </div>

            <div class="submission-file">
                <i class="material-icons file-icon">description</i>
                <div>
                    <div style="font-weight: 500;"><?= htmlspecialchars(basename($submission['file_path'])) ?></div>
                    <a href="<?= htmlspecialchars($submission['file_path']) ?>" 
                       class="back-btn" 
                       download 
                       style="margin: 4px 0 0 0;">
                        <i class="material-icons" style="font-size: 18px;">download</i>
                        Download Submission
                    </a>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="grade">Grade (0-100)</label>
                    <input type="number" 
                           id="grade" 
                           name="grade" 
                           min="0" 
                           max="100" 
                           step="0.01" 
                           value="<?= $existing_grade ? htmlspecialchars($existing_grade['grade']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" 
                              name="feedback" 
                              placeholder="Provide feedback for the student..."><?= $existing_grade ? htmlspecialchars($existing_grade['feedback']) : '' ?></textarea>
                </div>

                <button type="submit" class="btn">
                    <i class="material-icons">save</i>
                    <?= $existing_grade ? 'Update Grade' : 'Submit Grade' ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html> 
