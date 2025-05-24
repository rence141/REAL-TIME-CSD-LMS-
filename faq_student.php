<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in as a student
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student FAQ - SADD LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <style>
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        .faq-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 24px;
            transition: box-shadow 0.3s ease;
        }

        .faq-header {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        .faq-header i {
            font-size: 32px;
            margin-right: 16px;
            color: #0066cc;
            transition: transform 0.3s ease;
        }

        .faq-header h1 {
            margin: 0;
            font-size: 24px;
            color: #202124;
            font-weight: 500;
        }

        .faq-section {
            margin-top: 32px;
        }

        .faq-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .faq-question {
            padding: 16px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.2s;
        }

        .faq-question:hover {
            background-color: rgba(0, 102, 204, 0.05);
        }

        .faq-question h3 {
            margin: 0;
            font-size: 16px;
            color: #202124;
            font-weight: 500;
        }

        .faq-question i {
            color: #0066cc;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            padding: 16px;
            border-top: 1px solid #e0e0e0;
            display: none;
            color: #5f6368;
            line-height: 1.6;
        }

        .faq-item.active .faq-question {
            background-color: rgba(0, 102, 204, 0.05);
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-item.active .faq-answer {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-title {
            color: #0066cc;
            font-size: 20px;
            margin: 32px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0066cc;
        }

        /* Footer Styles */
        .footer {
            background: #f8f9fa;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            margin-top: 40px;
            border-radius: 8px;
        }

        .footer p {
            color: #5f6368;
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .footer strong {
            color: #202124;
        }

        @media (max-width: 768px) {
            .faq-container {
                padding: 16px;
            }

            .faq-header h1 {
                font-size: 20px;
            }

            .category-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation menu -->
        

        <main class="main-content">
            <div class="faq-container">
                <div class="faq-card">
                    <div class="faq-header">
                        <i class="material-icons">help</i>
                        <h1>Frequently Asked Questions</h1>
                    </div>

                    <!-- General Questions -->
                    <h2 class="category-title"><i class="material-icons">info</i> General Questions</h2>
                    <div class="faq-section">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I reset my password?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                To reset your password, click on the "Forgot Password" link on the login page. Enter your registered email address, and you'll receive instructions to reset your password. If you don't receive the email, please contact support.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How can I update my profile information?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                Go to your Dashboard and click on your profile picture or name. Select "Edit Profile" to update your information. You can change your contact details, profile picture, and notification preferences.
                            </div>
                        </div>
                    </div>

                    <!-- Attendance -->
                    <h2 class="category-title"><i class="material-icons">check_circle</i> Attendance</h2>
                    <div class="faq-section">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How do I view my attendance record?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                Click on the "Attendance" tab in the navigation menu. You can view your attendance records for each course, including dates, times, and status. The system also shows your attendance percentage for each subject.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What should I do if there's an error in my attendance?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                If you notice any discrepancies in your attendance record, contact your professor immediately. Provide the date, time, and any evidence of your attendance. Your professor can review and correct any errors.
                            </div>
                        </div>
                    </div>

                    <!-- Submissions -->
                    <h2 class="category-title"><i class="material-icons">assignment_turned_in</i> Submissions</h2>
                    <div class="faq-section">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What file formats are accepted for submissions?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                The system accepts common file formats including PDF, DOC, DOCX, PPT, PPTX, and image files (JPG, PNG). Maximum file size is 25MB per submission. For specific requirements, please check your assignment instructions.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>Can I submit assignments after the deadline?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                Late submission policies vary by professor and course. Some assignments may allow late submissions with penalties, while others may not accept them at all. Check your course syllabus or contact your professor for specific policies.
                            </div>
                        </div>
                    </div>

                    <!-- Grades -->
                    <h2 class="category-title"><i class="material-icons">grade</i> Grades</h2>
                    <div class="faq-section">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>How is my final grade calculated?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                Final grades are calculated based on the grading system outlined in your course syllabus. This typically includes assignments, quizzes, exams, class participation, and other course requirements. Each component is weighted according to the course policy.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">
                                <h3>What should I do if I disagree with a grade?</h3>
                                <i class="material-icons">expand_more</i>
                            </div>
                            <div class="faq-answer">
                                First, review the grading criteria and feedback provided. If you still have concerns, contact your professor during office hours to discuss the grade. Be prepared to explain your reasoning and provide any relevant documentation.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Copyright Footer -->
                <div class="footer">
                    <p><strong>© <?php echo date('Y'); ?> BU Polangui CSD Department</strong></p>
                    <p>This is a property of BU Polangui CSD Department.</p>
                    <p>All copyrights are exclusive for the developers of this system.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add click event listeners to FAQ questions
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                const isActive = item.classList.contains('active');
                
                // Close all other items
                document.querySelectorAll('.faq-item').forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });

                // Toggle current item
                item.classList.toggle('active', !isActive);
            });
        });
    </script>
</body>
</html> 