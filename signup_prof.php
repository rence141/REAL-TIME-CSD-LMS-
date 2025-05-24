<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Signup | BU LMS</title>
<link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        /* Background */
        body {
            background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Sign-Up Box */
        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            width: 800px;
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
                width: 90%;
            }
            .left-box {
                display: none;
            }
            .right-box {
                width: 100%;
            }
        }
    </style>

    <script>
        function validatePasswords() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirmPassword").value;
            var error = document.getElementById("passwordError");

            if (password.length < 6) {
                error.innerHTML = "Password must be at least 6 characters.";
                return false;
            } else if (password !== confirmPassword) {
                error.innerHTML = "Passwords do not match.";
                return false;
            } else {
                error.innerHTML = "";
                return true;
            }
        }

        function checkPasswordStrength() {
            var password = document.getElementById("password").value;
            var strengthMessage = document.getElementById("passwordStrength");

            if (password.length < 6) {
                strengthMessage.innerHTML = "Weak password.";
                strengthMessage.style.color = "red";
            } else if (password.length < 8) {
                strengthMessage.innerHTML = "Moderate password.";
                strengthMessage.style.color = "orange";
            } else {
                strengthMessage.innerHTML = "Strong password!";
                strengthMessage.style.color = "green";
            }
        }
    </script>
</head>
<body>

    <div class="signup-container">
        <!-- Left Side (Image) -->
        <div class="left-box"></div>

        <!-- Right Side (Form) -->
        <div class="right-box">
            <h2>Professor Sign-Up</h2>
            <p class="text-muted text-center">Create your account to get started</p>

            <form action="signup_process.php" method="POST" onsubmit="return validatePasswords();">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" class="form-control" name="employeeid_number" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required onkeyup="checkPasswordStrength();">
                    <small id="passwordStrength" style="color: grey;"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                    <small id="passwordError" style="color: red;"></small>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
                <p class="login-link">Already have an account? <a href="login_professors.php">Sign In</a></p>
            </form>
        </div>
    </div>

</body>
</html>
