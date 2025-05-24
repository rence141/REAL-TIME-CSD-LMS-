<?php
session_start();
include 'db_connect.php';

// Ensure user is logged in as student
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = "";

// ✅ Fetch existing user data
$query = $conn_student->prepare("SELECT student_name, email, profile FROM students WHERE student_id = ?");
$query->bind_param("i", $student_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['student_name']);
    $profile_image = $user['profile']; // Keep existing if no new image uploaded

    // ✅ Handle image upload
    if (isset($_FILES['profile']) && $_FILES['profile']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile']['type'], $allowed_types)) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $new_filename = uniqid('prof_', true) . "." . pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile']['tmp_name'], $target_file)) {
                $profile_image = $target_file;
            } else {
                $message = "❌ Failed to upload new profile image.";
            }
        } else {
            $message = "❌ Only JPG, PNG, and GIF images are allowed.";
        }
    }

    if (empty($message)) {
        // ✅ Update database (fixed `$profile` -> `$profile_image`)
        $update_query = $conn_student->prepare("UPDATE students SET student_name = ?, profile = ? WHERE student_id = ?");
        $update_query->bind_param("ssi", $full_name, $profile_image, $student_id);

        if ($update_query->execute()) {
            $message = "✅ Profile updated successfully!";
            // Refresh user info
            $user['student_name'] = $full_name;
            $user['profile'] = $profile_image;
        } else {
            $message = "❌ Error updating profile.";
        }
    }
}

$query->close();
$conn_student->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; background: #f5f5f5; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .profile-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; }
        .profile-form h2 { margin-top: 0; margin-bottom: 20px; color: #202124; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        input[type="text"], input[type="email"], input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        input[type="email"] { background: #f5f5f5; color: #888; cursor: not-allowed; }
        .avatar-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
        .btn { background: #4285F4; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn:hover { background: #3367d6; }
        .message { margin-bottom: 15px; color: green; font-weight: 500; }
        .error { color: red; }
        .back-link { margin-top: 20px; display: block; text-align: center; color: #4285F4; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <form class="profile-form" action="profile_student.php" method="POST" enctype="multipart/form-data">
        <h2>Edit Profile</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'success') === false ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center;">
            <img src="<?= htmlspecialchars($user['profile'] ?? 'assets/default-avatar.png') ?>" alt="Profile Picture" class="avatar-preview">
        </div>

        <div class="form-group">
            <label for="student_name">Full Name:</label>
            <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($user['student_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
            <label for="profile_image">Profile Image:</label>
            <input type="file" id="profile" name="profile" accept="image/*">
        </div>

        <button type="submit" class="btn">Save Changes</button>

        <a href="dashboard_student.php" class="back-link">← Back to Dashboard</a>
    </form>
</body>
</html>
