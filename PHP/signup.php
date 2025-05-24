<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Signup</title>
<link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            background: url('../IMAGES/457309351_1203233487470206_8298743086820818178_n (3).png') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            width: 900px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: row;
        }

        .left-box {
            width: 50%;
            background: url('../IMAGES/Screenshot 2025-02-27 182517.png') no-repeat center center/cover;
            border-radius: 10px 0 0 10px;
            background-size: contain;
        }

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

        .form-control {
            border-radius: 5px;
        }

        .btn-primary {
            background-color:rgb(48, 158, 255);
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #1a252f;
        }

        .form-check-label {
            font-size: 14px;
        }

        .login-link {
            display: block;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

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
</head>
<body>

    <div class="signup-container">
        <div class="left-box"></div>

        <div class="right-box">
            <h2>Student Signup</h2>
            <p class="text-muted text-center">Create your account</p>

            <form action="signup_student_process.php" method="POST">

                <div class="mb-3">
                    <label for="Student_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="student_name" name="student_name" placeholder="Enter Fullname" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <!-- Course -->
                <div class="mb-3">
                    <label for="block" class="form-label">Block</label>
                    <select class="form-control" id="block" name="block" required>
                        <option value="" disabled selected>Select your block</option>
                        <option value="1-A">1-A</option>
                        <option value="1-B">1-B</option>
                        <option value="1-C">1-C</option>
                        <option value="1-D">1-D</option>
                        <option value="3-A">3-A</option>
                        <option value="3-B">3-B</option>
                        <option value="3-C">3-C</option>
                        <option value="3-D">3-D</option>
                        <option value="4-A">4-A</option>
                        <option value="4-B">4-B</option>
                        <option value="4-C">4-C</option>
                        <option value="4-D">4-D</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="section" class="form-label">Year</label>
                    <select class="form-control" id="year" name="section" required>
                        <option value="" disabled selected>Select your section</option>
                        <option value="BSIS">BSIS</option>
                        <option value="BSIT-Anim">BSIT-Anim</option>
                        <option value="BSCS">BSCS</option>
                        <option value="BSIT">BSIT</option>
                    </select>
                </div>

                <!-- Student ID (Automatically assigned, so hidden) -->
                <input type="hidden" name="studentid_number" value="AUTO">

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <!-- Terms & Conditions -->
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">I agree to the <a href="#">Terms & Conditions</a></label>
                </div>

                <!-- Sign Up Button -->
                <button type="submit" class="btn btn-primary">Sign Up</button>

                <!-- Already have an account? -->
                <a href="login.php" class="login-link">Already have an account? Login here</a>
            </form>
        </div>
    </div>

</body>
</html>
