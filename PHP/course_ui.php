<?php
include 'db_connect.php'; // connection to the DB
session_start();

if (!isset($_GET['course_id'])) {
    die("Course ID not specified.");
}

$course_id = intval($_GET['course_id']);

// Fetch course details
$course_query = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$course_query->bind_param("i", $course_id);
$course_query->execute();
$result = $course_query->get_result();

if ($result->num_rows === 0) {
    die("Course not found.");
}

$course = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($course['course_name']) ?></title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <style>
        body { font-family: Arial; margin: 20px; }
        .header { display: flex; align-items: center; }
        .header img { width: 150px; height: 100px; object-fit: cover; margin-right: 20px; }
        .actions button { margin-right: 10px; }
    </style>
</head>
<body>

<div class="header">
    <img src="uploads/title_cards/<?= $course['course_id'] ?>.jpg" alt="Title Card">
    <div>
        <h1><?= htmlspecialchars($course['course_name']) ?></h1>
        <p><?= htmlspecialchars($course['description']) ?></p>
        <p><strong>Course Code:</strong> <?= $course['course_code'] ?> | <strong>Section:</strong> <?= $course['section'] ?> | <strong>Semester:</strong> <?= $course['semester'] ?></p>
    </div>
</div>

<div class="actions">
    <form action="post_announcement.php" method="post">
        <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <textarea name="announcement" placeholder="Post an announcement..." required></textarea><br>
        <button type="submit">Post Announcement</button>
    </form>

    <hr>

    <form action="upload_assignment.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <input type="text" name="title" placeholder="Assignment title" required><br>
        <input type="file" name="assignment_file" accept=".pdf,.doc,.docx,.ppt,.pptx" required><br>
        <button type="submit">Upload Assignment</button>
    </form>

    <hr>

    <form action="update_course.php" method="post">
        <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <button type="submit" name="action" value="drop">Drop Course</button>
    </form>

    <hr>

    <a href="edit_students.php?course_id=<?= $course_id ?>">Manage Students</a>
</div>

</body>
</html>
