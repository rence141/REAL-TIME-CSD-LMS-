<?php
session_start();
include 'db_connect.php'; // Ensure the correct path to your database connection file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendVerificationEmail($toEmail, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Lorenzezz0987@gmail.com';
        $mail->Password = 'ceqd fmip fgld mlgb';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Rence@gmail.com', 'CSD LMS');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SADD LMS - Login Verification Code';
        $mail->Body = "
            <h2>Your Verification Code</h2>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this code, please ignore this email.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize user input
    $identifier = trim(filter_input(INPUT_POST, 'email_or_employeeid', FILTER_SANITIZE_STRING));
    $password = $_POST['password']; // Password sanitization handled separately as it's hashed

    // Validate input
    if (!empty($identifier) && !empty($password)) {
        // Query to check for case-sensitive credentials
        $stmt = $conn->prepare("SELECT * FROM professors WHERE BINARY email = ? OR BINARY employeeid_number = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password securely
            if (password_verify($password, $user['password'])) {
                // Generate and send verification code
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store verification code
                $verify_stmt = $conn->prepare("INSERT INTO professor_verification_codes (professor_id, code, expires_at) VALUES (?, ?, ?)");
                $verify_stmt->bind_param("iss", $user['id'], $code, $expires_at);
                
                if ($verify_stmt->execute()) {
                    if (sendVerificationEmail($user['email'], $code)) {
                        // Store necessary info in session
                        $_SESSION['verification_pending'] = true;
                        $_SESSION['professor_id'] = $user['id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        
                        // Redirect to verification page
                        header('Location: verify_login.php');
                        exit();
                    } else {
                        echo "<script>alert('Failed to send verification code. Please try again.'); window.location.href='login_professors.php';</script>";
                    }
                } else {
                    echo "<script>alert('Error generating verification code. Please try again.'); window.location.href='login_professors.php';</script>";
                }
                $verify_stmt->close();
            } else {
                // Incorrect password
                echo "<script>alert('Incorrect password! Please try again.'); window.location.href='login_professors.php';</script>";
            }
        } else {
            // User not found
            echo "<script>alert('User not found! Please check your email or Employee ID.'); window.location.href='login_professors.php';</script>";
        }
        $stmt->close();
    } else {
        // Missing input fields
        echo "<script>alert('Please fill in both fields.'); window.location.href='login_professors.php';</script>";
    }
}
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Signup</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">


    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Custom Styles -->
    <style>
        /* Background */
        body {
            background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover;
            display: flex;
            background-size: cover;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Signup Box */
        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            width: 900px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: row;
        }

        /* Left Side Image */
        .left-box {
            width: 50%;
            background: url('../IMAGES/Screenshot 2025-02-27 182517.png') no-repeat center center/cover;
            border-radius: 10px 0 0 10px;
            background-size: contain;
        }

        /* Right Side Form */
        .right-box {
            width: 50%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right-box h2 {
            color: #2c3e50;
            font-weight: bold;
            text-align: center;
        }

        /* Input Fields */
        .form-control {
            border-radius: 5px;
        }

        /* Button */
        .btn-primary {
            background-color: #2c3e50;
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #1a252f;
        }

        /* Terms & Conditions */
        .form-check-label {
            font-size: 14px;
        }

        /* Already have an account? */
        .login-link {
            display: block;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
                width: 80%;
            }
            .left-box {
                display: none;
            }
            .right-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="signup-container">
        <!-- Left Side (Image) -->
        <div class="left-box"></div>

        <!-- Right Side (Form) -->
        <div class="right-box">
            <h2>Sign In</h2>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
    <!-- Email OR Employee ID (Combined Input) -->
    <div class="mb-3">
        <label for="email_or_employeeid" class="form-label">Email or Employee ID</label>
        <input type="text" class="form-control" id="email_or_employeeid" name="email_or_employeeid" placeholder="Enter your email or Employee ID" required>
    </div>

    <!-- Password -->
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
    </div>

    <!-- Sign In Button -->
    <button type="submit" class="btn btn-primary">Sign in</button>

    <!-- Don't have an account? -->
    <p class="login-link">
   Dont Have an account? <a href="signup_prof.php">Sign up</a> | 
    <a href="forgot_password_prof.php">Forgot Password?</a>
</p>

</form>

</body>
</html>
