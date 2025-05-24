<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Make sure path is correct

function sendResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Lorenzezz0987@gmail.com'; // Gmail address
        $mail->Password = 'ceqd fmip fgld mlgb';     //  generated App Password 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('Rence@gmail.com', 'CSD LMS'); // Sender email and name
        $mail->addAddress($toEmail);
        // Generate the token first
        $token = bin2hex(random_bytes(32));

        // Now build the complete reset link
        $resetLink = "http://localhost/bup_SADD/PHP/password_reset_prof_process.php?token=" . $token;

        // Then use it in the email content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "Click this link to reset your password: <a href='$resetLink'>Reset Your Password</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

