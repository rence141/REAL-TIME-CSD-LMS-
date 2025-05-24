<?php
session_start();
require_once 'db_connect.php';

// Check if user is in verification process
if (!isset($_SESSION['verification_pending']) || !isset($_SESSION['professor_id'])) {
    header('Location: login_professors.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login - SADD LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .verification-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .verification-container h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }

        .code-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .code-input input {
            width: 40px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #2c3e50;
            border-radius: 5px;
        }

        .btn-verify {
            background-color: #2c3e50;
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: bold;
            color: white;
            border-radius: 5px;
        }

        .btn-verify:hover {
            background-color: #1a252f;
        }

        .resend-link {
            text-align: center;
            margin-top: 15px;
        }

        #timer {
            color: #2c3e50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2>Verify Your Login</h2>
        <p class="text-center">Please enter the 6-digit verification code sent to your email.</p>
        
        <form id="verificationForm" method="POST" action="process_verification.php">
            <div class="code-input">
                <input type="text" maxlength="1" pattern="[0-9]" required>
                <input type="text" maxlength="1" pattern="[0-9]" required>
                <input type="text" maxlength="1" pattern="[0-9]" required>
                <input type="text" maxlength="1" pattern="[0-9]" required>
                <input type="text" maxlength="1" pattern="[0-9]" required>
                <input type="text" maxlength="1" pattern="[0-9]" required>
            </div>
            <input type="hidden" name="verification_code" id="verificationCode">
            <button type="submit" class="btn-verify">Verify</button>
        </form>

        <?php if (isset($_SESSION['verification_error'])): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $_SESSION['verification_error']; unset($_SESSION['verification_error']); ?>
            </div>
        <?php endif; ?>

        <div class="resend-link">
            <p>Didn't receive the code?</p>
            <p>You can request a new code in <span id="timer">10:00</span></p>
            <a href="#" id="resendLink" style="display: none;">Resend Code</a>
        </div>
    </div>

    <script>
        // Handle input fields
        const inputs = document.querySelectorAll('.code-input input');
        const form = document.getElementById('verificationForm');
        const verificationCodeInput = document.getElementById('verificationCode');

        // Function to update the hidden input with the complete code
        function updateVerificationCode() {
            const code = Array.from(inputs).map(input => input.value || '').join('');
            verificationCodeInput.value = code;
            console.log('Updated code:', code); // Debug line
        }

        inputs.forEach((input, index) => {
            // Only allow numbers
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });

            input.addEventListener('input', (e) => {
                // Remove any non-numeric characters
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                if (e.target.value.length === 1) {
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                }
                updateVerificationCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
                // Update code on next tick after backspace
                setTimeout(updateVerificationCode, 0);
            });

            // Handle paste event
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                if (pastedData) {
                    // Distribute pasted numbers across inputs
                    for (let i = 0; i < pastedData.length && i < inputs.length; i++) {
                        inputs[i].value = pastedData[i];
                    }
                    if (pastedData.length === 6) {
                        inputs[5].focus();
                    } else {
                        inputs[Math.min(pastedData.length, 5)].focus();
                    }
                    updateVerificationCode();
                }
            });
        });

        // Handle form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            updateVerificationCode();
            const code = verificationCodeInput.value;
            console.log('Submitting code:', code); // Debug line
            
            if (code.length === 6) {
                form.submit();
            } else {
                alert('Please enter all 6 digits of the verification code.');
            }
        });

        // Timer functionality
        let timeLeft = 600; // 10 minutes in seconds
        const timerDisplay = document.getElementById('timer');
        const resendLink = document.getElementById('resendLink');

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft === 0) {
                timerDisplay.style.display = 'none';
                resendLink.style.display = 'block';
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        updateTimer();

        // Handle resend
        resendLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('send_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'resend=true'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    timeLeft = 600;
                    timerDisplay.style.display = 'inline';
                    resendLink.style.display = 'none';
                    updateTimer();
                    alert('New verification code sent!');
                } else {
                    alert('Failed to send new verification code. Please try again.');
                }
            });
        });
    </script>
</body>
</html> 
