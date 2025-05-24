<?php
session_start();
require_once 'db_connect.php';

// Check if reset is confirmed through proper verification
if (!isset($_SESSION['code_verified']) || !isset($_SESSION['professor_id']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password_prof.php');
    exit();
}

$error = '';
$success = '';
$professor_id = $_SESSION['professor_id'];
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate password
        if (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            try {
                // Begin transaction
                $conn->begin_transaction();

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE professors SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $professor_id);

                if ($update_stmt->execute()) {
                    $conn->commit();

                    // Clear all reset-related session variables
                    unset($_SESSION['reset_pending']);
                    unset($_SESSION['code_verified']);
                    unset($_SESSION['professor_id']);
                    unset($_SESSION['reset_email']);

                    // Set success message and redirect
                    $_SESSION['password_reset_success'] = true;
                    header('Location: login_professors.php');
                    exit();
                } else {
                    throw new Exception("Failed to update password");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'An error occurred while resetting your password. Please try again.';
                error_log("Password reset failed for professor_id: $professor_id. Error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 120px;
            margin-bottom: 1rem;
        }

        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 0.5rem;
        }

        .description {
            color: #5F6368;
            font-size: 14px;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .material-icons {
            position: absolute;
            left: 12px;
            color: #5F6368;
            cursor: pointer;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #2c3e50;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 0.5rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background-color: #fde8e7;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .success {
            color: #27ae60;
            font-size: 14px;
            margin: 1rem 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background-color: #e8f5e9;
            padding: 1rem;
            border-radius: 4px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .btn:hover {
            background-color: #1a252f;
            transform: translateY(-1px);
        }

        .password-requirements {
            font-size: 12px;
            color: #5F6368;
            margin-top: 0.5rem;
            padding-left: 0.5rem;
        }

        .links {
            margin-top: 1.5rem;
            text-align: center;
        }

        .links a {
            color: #2c3e50;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .links .material-icons {
            position: static;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../IMAGES/BUPC_Logo.png" alt="BU Logo" class="logo">
            <h1>Reset Password</h1>
            <p class="description">Please enter your new password below</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <span class="material-icons">error</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <span class="material-icons">check_circle</span>
                <?php echo $success; ?>
            </div>
            <div class="links">
                <a href="login_professor.php">
                    <span class="material-icons">login</span>
                    Proceed to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-container">
                        <span class="material-icons" onclick="togglePassword('new_password')">visibility</span>
                        <input type="password" id="new_password" name="new_password" required 
                               minlength="8" placeholder="Enter new password">
                    </div>
                    <div class="password-requirements">
                        Password must be at least 8 characters long
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-container">
                        <span class="material-icons" onclick="togglePassword('confirm_password')">visibility</span>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               minlength="8" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="btn">
                    <span class="material-icons">lock_reset</span>
                    Reset Password
                </button>

                <div class="links">
                    <a href="login_professors.php">
                        <span class="material-icons">arrow_back</span>
                        Back to Login
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.previousElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</body>
</html>