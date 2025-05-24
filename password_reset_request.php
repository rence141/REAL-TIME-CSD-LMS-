<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ensure correct path to PHPMailer's autoloader

function sendResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'Lorenzezz0987.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Lorenzezz0987@gmail.com'; // Your Gmail address
        $mail->Password = 'ceqd fmip fgld mlgb'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Rence@gmail.com', 'CSD LMS');  // Sender email and name
        $mail->addAddress($toEmail); // Recipient's email address

        // Content - ensure HTML email body
        $mail->isHTML(true); // Enable HTML in the email
        $mail->Subject = 'Password Reset Request';
        
        // Using <a> tag to insert the reset link in the email body
        $mail->Body    = "Click the link below to reset your password: <br><br>" .
                         "<a href='$resetLink'>$resetLink</a>";

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

// Logic for generating reset token and sending the email

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists in the database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate reset token
        $token = bin2hex(random_bytes(50)); // Token for the reset link
        date_default_timezone_set('Asia/Manila'); // or whatever timezone you want


        // Insert token into the database
        $query = "INSERT INTO forgot_password (email, reset_token, expired_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $email, $token, $expiration);
        $stmt->execute();

        // Create the reset link
        $resetLink = "http://localhost/bup_SADD/PHP/password_reset_prof_process.php?token=" . $token;

        // Send the reset email with the generated link
        if (sendResetEmail($email, $resetLink)) {
            echo "A password reset link has been sent to your email.";
        }
    } else {
        echo "No account found with that email address.";
    }
}
?>

<html>
    <body>
        <h1>Your request has been sent to your email!</h1>
    </body>
</html>