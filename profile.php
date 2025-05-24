<?php
session_start();
include 'db_connect.php';

$professor_id = $_SESSION['professor_id'];
$message = "";

// ✅ Fetch existing user data
$query = $conn->prepare("SELECT Full_Name, email, profile_image FROM professors WHERE id = ?");
$query->bind_param("i", $professor_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $profile_image = $user['profile_image']; // Keep existing if no new image

    // ✅ Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $new_filename = uniqid('prof_', true) . "." . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image = $target_file;
            } else {
                $message = "Failed to upload new profile image.";
            }
        } else {
            $message = "Only JPG, PNG, and GIF images are allowed.";
        }
    }

    if (empty($message)) {
        // ✅ Update DB
        $update_query = $conn->prepare("UPDATE professors SET Full_Name = ?, profile_image = ? WHERE id = ?");
        $update_query->bind_param("ssi", $full_name, $profile_image, $professor_id);
        if ($update_query->execute()) {
            $message = "Profile updated successfully!";
            // Refresh user info
            $user['Full_Name'] = $full_name;
            $user['profile_image'] = $profile_image;
        } else {
            $message = "Error updating profile.";
        }
    }
}

$query->close();
$conn->close();
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
    <form class="profile-form" action="profile.php" method="POST" enctype="multipart/form-data">
        <h2>Edit Profile</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'success') === false ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center;">
            <img src="<?= htmlspecialchars($user['profile_image'] ?? 'assets/default-avatar.png') ?>" alt="Profile Picture" class="avatar-preview">
        </div>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['Full_Name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
            <label for="profile_image">Profile Image:</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
        </div>

        <button type="submit" class="btn">Save Changes</button>

        <a href="dashboard_professor.php" class="back-link">← Back to Dashboard</a>
    </form>
</body>
</html>
