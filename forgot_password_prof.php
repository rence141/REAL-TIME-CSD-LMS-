<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone
date_default_timezone_set('Asia/Manila');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        // Check if email exists in professors table
        $stmt = $conn->prepare("SELECT id, Full_Name FROM professors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $professor_id = $row['id'];
            $professor_name = $row['Full_Name'];
            
            // Generate verification code
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $current_time = date('Y-m-d H:i:s');
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_EXPIRY . ' minutes'));
            
            try {
                // Begin transaction
                $conn->begin_transaction();
                
                // Delete any existing unused codes
                $delete_stmt = $conn->prepare("
                    DELETE FROM password_reset_codes 
                    WHERE professor_id = ? AND is_used = 0
                ");
                $delete_stmt->bind_param("i", $professor_id);
                $delete_stmt->execute();
                
                // Store verification code
                $verify_stmt = $conn->prepare("
                    INSERT INTO password_reset_codes (professor_id, code, created_at, expires_at)
                    VALUES (?, ?, ?, ?)
                ");
                $verify_stmt->bind_param("isss", $professor_id, $verification_code, $current_time, $expires_at);
                
                if ($verify_stmt->execute()) {
                    // Send email using PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // Enable debug mode for troubleshooting
                        $mail->SMTPDebug = 2; // Enable verbose debug output
                        $mail->Debugoutput = function($str, $level) {
                            error_log("SMTP Debug: $str");
                        };

                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USERNAME;
                        $mail->Password = SMTP_PASSWORD;
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = SMTP_PORT;

                        // Set timeout and keep alive
                        $mail->Timeout = 60;
                        $mail->SMTPKeepAlive = true;
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        // Recipients
                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                        $mail->addAddress($email, $professor_name);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Verification Code';
                        $mail->Body = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <h2 style='color: #1a73e8;'>Password Reset Request</h2>
                                <p>Dear {$professor_name},</p>
                                <p>We received a request to reset your password. Your verification code is:</p>
                                <div style='background-color: #f5f5f5; padding: 15px; text-align: center; margin: 20px 0;'>
                                    <h1 style='color: #1a73e8; margin: 0;'>{$verification_code}</h1>
                                </div>
                                <p>This code will expire in " . PASSWORD_RESET_EXPIRY . " minutes.</p>
                                <p>If you didn't request this password reset, please ignore this email.</p>
                                <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                                    Best regards,<br>
                                    " . SMTP_FROM_NAME . "
                                </p>
                            </div>
                        ";
                        $mail->AltBody = "Your verification code is: {$verification_code}\nThis code will expire in " . PASSWORD_RESET_EXPIRY . " minutes.";

                        if($mail->send()) {
                            $conn->commit();
                            // Store necessary info in session
                            $_SESSION['reset_pending'] = true;
                            $_SESSION['professor_id'] = $professor_id;
                            $_SESSION['reset_email'] = $email;
                            
                            // For testing, store the code in session (remove in production)
                            $_SESSION['verification_code'] = $verification_code;
                            
                            // Redirect to verification page
                            header('Location: verify_reset.php');
                            exit();
                        } else {
                            error_log("Mailer Error: " . $mail->ErrorInfo);
                            throw new Exception("Failed to send email: " . $mail->ErrorInfo);
                        }
                    } catch (Exception $e) {
                        error_log("Email Exception: " . $e->getMessage());
                        throw new Exception("Email sending failed: " . $e->getMessage());
                    }
                } else {
                    throw new Exception("Failed to store verification code");
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Reset process failed: " . $e->getMessage());
                $error = "Failed to send verification code. Error: " . $e->getMessage();
            }
        } else {
            $error = "No account found with this email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | BU LMS</title>
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

        input[type="email"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #2c3e50;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="email"]:focus {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../IMAGES/BUPC_Logo.png" alt="BU Logo" class="logo">
            <h1>Forgot Password</h1>
            <p class="description">Enter your email address and we'll send you a verification code to reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <span class="material-icons">error</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-container">
                    <span class="material-icons">email</span>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address">
                </div>
            </div>

            <button type="submit" class="btn">
                <span class="material-icons">send</span>
                Send Verification Code
            </button>

            <div class="links">
                <a href="login_professors.php">
                    <span class="material-icons">arrow_back</span>
                    Back to Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>