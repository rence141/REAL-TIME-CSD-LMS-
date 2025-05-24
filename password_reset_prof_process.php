<?php
require 'db.connect_professors.php';

$token = $_GET['token'] ?? '';
$showForm = false;
$feedback = "";

// Step 1: Validate token
if (!empty($token)) {
    // First check if token exists and is not expired
    $stmt = $conn->prepare("SELECT * FROM forgot_password WHERE reset_token = ? AND expired_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $showForm = true;

        // Step 2: Handle password reset POST
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword !== $confirmPassword) {
                $feedback = "<span style='color: red;'>Passwords do not match.</span>";
            } else {
                // Verify token again right before password update to prevent race conditions
                $stmt = $conn->prepare("SELECT * FROM forgot_password WHERE reset_token = ? AND email = ? AND expired_at > NOW()");
                $stmt->bind_param("ss", $token, $email);
                $stmt->execute();
                $verifyResult = $stmt->get_result();

                if ($verifyResult->num_rows === 1) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Start transaction
                    $conn->begin_transaction();

                    try {
                        // Update password in professors table
                        $stmt = $conn->prepare("UPDATE professors SET password = ? WHERE email = ?");
                        $stmt->bind_param("ss", $hashedPassword, $email);
                        $stmt->execute();

                        // Delete token to prevent reuse
                        $stmt = $conn->prepare("DELETE FROM forgot_password WHERE email = ?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();

                        $conn->commit();
                        $feedback = "<span style='color: green;'>Password successfully reset. You can now log in.</span>";
                        $showForm = false;
                    } catch (Exception $e) {
                        $conn->rollback();
                        $feedback = "<span style='color: red;'>An error occurred. Please try again.</span>";
                    }
                } else {
                    $feedback = "<span style='color: red;'>Token has expired during this process. Please request a new reset link.</span>";
                    $showForm = false;
                }
            }
        }
    } else {
        // More specific error message
        $stmt = $conn->prepare("SELECT * FROM forgot_password WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $feedback = "<span style='color: red;'>Token has expired. Please request a new password reset link.</span>";
        } else {
            $feedback = "<span style='color: red;'>Invalid token. Please check the link or request a new one.</span>";
        }
    }
} else {
    $feedback = "<span style='color: red;'>No token provided.</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | BU LMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #007BFF;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .feedback {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .password-rules {
            font-size: 12px;
            color: #666;
            margin-top: -15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Reset Your Password</h2>

    <div class="feedback"><?= $feedback ?></div>

    <?php if ($showForm): ?>
        <form method="POST">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required minlength="8">
            <div class="password-rules">(Minimum 8 characters)</div>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="8">

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>