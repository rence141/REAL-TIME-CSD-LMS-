<?php
session_start();
require_once 'db_connect.php';

// Redirect if no reset pending
if (!isset($_SESSION['reset_pending']) || !isset($_SESSION['professor_id']) || !isset($_SESSION['reset_email'])) {
    header('Location: login_professor.php');
    exit();
}

$error = '';
$professor_id = $_SESSION['professor_id'];
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verification_code'])) {
        $submitted_code = trim($_POST['verification_code']);
        
        try {
            // First check if there's any code for this professor
            $check_stmt = $conn->prepare("
                SELECT code, is_used, expires_at 
                FROM password_reset_codes 
                WHERE professor_id = ? 
                AND code = ?
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $check_stmt->bind_param("ss", $professor_id, $submitted_code);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Invalid verification code. Please check and try again.";
            } else {
                $code_data = $result->fetch_assoc();
                
                if ($code_data['is_used'] == 1) {
                    $error = "This verification code has already been used. Please request a new code.";
                } else if (strtotime($code_data['expires_at']) < time()) {
                    $error = "This verification code has expired. Please request a new code.";
                } else {
                    // Valid code, mark it as used
                    $update_stmt = $conn->prepare("
                        UPDATE password_reset_codes 
                        SET is_used = 1 
                        WHERE professor_id = ? AND code = ?
                    ");
                    $update_stmt->bind_param("ss", $professor_id, $submitted_code);
                    
                    if ($update_stmt->execute()) {
                        // Store verification success in session
                        $_SESSION['code_verified'] = true;
                        
                        // Redirect to password reset page
                        header('Location: reset_password_prof.php');
                        exit();
                    } else {
                        $error = "System error. Please try again.";
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            $error = "An error occurred during verification. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code | BU LMS</title>
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
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #2c3e50;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
            letter-spacing: 2px;
        }

        input[type="text"]:focus {
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

        .email-display {
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #2c3e50;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../IMAGES/BUPC_Logo.png" alt="BU Logo" class="logo">
            <h1>Verify Your Email</h1>
            <p class="description">Enter the 6-digit verification code sent to your email</p>
        </div>

        <div class="email-display">
            <span class="material-icons">mail</span>
            Code sent to: <?php echo htmlspecialchars($email); ?>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <span class="material-icons">error</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="verification_code">Verification Code</label>
                <div class="input-container">
                    <span class="material-icons">lock</span>
                    <input type="text" id="verification_code" name="verification_code" required 
                           placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}"
                           title="Please enter a 6-digit code">
                </div>
            </div>

            <button type="submit" class="btn">
                <span class="material-icons">check_circle</span>
                Verify Code
            </button>

            <div class="links">
                <a href="forgot_password_prof.php">
                    <span class="material-icons">arrow_back</span>
                    Back to Forgot Password
                </a>
            </div>
        </form>
    </div>
</body>
</html> 