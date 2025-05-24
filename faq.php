<?php
// faq.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Frequently Asked Questions - Find answers to common queries about our LMS for professors">
    <title>FAQ - Frequently Asked Questions</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #0073e6;
        }
        .faq-item {
            margin-bottom: 20px;
        }
        .faq-question {
            font-weight: bold;
            cursor: pointer;
            margin: 0;
        }
        .faq-answer {
            display: none;
            margin-top: 10px;
            line-height: 1.6;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const questions = document.querySelectorAll(".faq-question");
            questions.forEach(question => {
                question.addEventListener("click", () => {
                    const answer = question.nextElementSibling;
                    const isVisible = answer.style.display === "block";
                    document.querySelectorAll(".faq-answer").forEach(a => a.style.display = "none");
                    answer.style.display = isVisible ? "none" : "block";
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <div class="faq-item">
            <p class="faq-question">How do I create a course?</p>
            <div class="faq-answer">
                <p>You can create a course by navigating to the "Courses" section, clicking "Create Course," and filling out the required details.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">How can I upload course materials?</p>
            <div class="faq-answer">
                <p>To upload course materials, go to the "Course Management" section, select your course, and upload files under the "Materials" tab.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">Can I track student progress?</p>
            <div class="faq-answer">
                <p>Yes, you can track student progress through the "Progress" tab in your course dashboard.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">How do I grade assignments?</p>
            <div class="faq-answer">
                <p>You can grade assignments by navigating to the "Assignments" section, selecting a submission, and providing feedback and a grade.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">How can I schedule quizzes or exams?</p>
            <div class="faq-answer">
                <p>To schedule quizzes or exams, go to the "Assessments" section, create a new assessment, and set the date and time.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">What should I do if a student reports an issue?</p>
            <div class="faq-answer">
                <p>If a student reports an issue, you can address it by using the messaging feature or contacting support for assistance.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">Can I communicate with students?</p>
            <div class="faq-answer">
                <p>Yes, you can communicate with students through the messaging feature or the course discussion forums.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">How do I manage course enrollments?</p>
            <div class="faq-answer">
                <p>You can manage course enrollments by visiting the "Enrollments" section in your course dashboard.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">Are there tools for live sessions?</p>
            <div class="faq-answer">
                <p>Yes, the platform provides tools for live sessions, such as video conferencing and screen sharing, accessible through the "Live Sessions" tab.</p>
            </div>
        </div>
        <div class="faq-item">
            <p class="faq-question">Where can I find platform guidelines for instructors?</p>
            <div class="faq-answer">
                <p>You can find platform guidelines for instructors in the "Instructor Resources" section of the website.</p>
            </div>
        </div>
    </div>
</body>
</html>