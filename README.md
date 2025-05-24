#   CSD LMS (Computer Science Department Learning Management System)
# BUPC Logo

# Overview
# The CSD LMS is a comprehensive web application designed for the Computer Science Department to facilitate course management, activity tracking, and academic administration for professors, students, and administrators.

# Features
# Admin Features
# Professor Management – Add, edit, and remove professor accounts
# Schedule Management – Create and modify class schedules
# System Administration – Control user roles and permissions
# Dashboard – Overview of system activity and statistics

# Professor Features
# Activity Management – Create, edit, and track student activities
# Schedule Management – Set class schedules and deadlines
# Grading System – Record and manage student grades
# Attendance Tracking – Monitor student attendance

Student Features
Activity Submission – Upload assignments and projects
 Course Materials – Access lecture notes and resources
 Grade Viewing – Check grades and feedback
 Schedule Access – View class timetables

Technologies Used
Frontend: HTML5, CSS3, JavaScript, Bootstrap

Backend: PHP

Database: MySQL

Security: Password hashing, session management

Installation & Setup
Requirements
Web server (Apache/Nginx)

PHP 7.4+

MySQL 5.7+

Steps
Clone the repository:

bash
git clone https://github.com/your-repo/csd-lms.git
Import the database:

bash
mysql -u username -p database_name < csd_lms.sql
Configure db_connect.php:

php
$conn = new mysqli("localhost", "db_username", "db_password", "database_name");
Run on localhost:

bash
php -S localhost:8000
Usage
Admin Access
 Login URL: admin_login.php
 Default Admin Credentials (if applicable)

Professor Access
 Login URL: login_professors.php

Student Access
 Login URL: login.php

Security Notes
 Password Encryption – Uses PHP password_hash()
 SQL Injection Protection – Prepared statements
 Session Validation – Checks user roles before access

Screenshots
(Optional: Add screenshots of the dashboard, activity management, etc.)

Future Improvements
 Mobile Responsiveness – Better UI for smartphones
 Notification System – Email/SMS alerts for deadlines
 API Integration – For external tools (Zoom, Google Classroom)

License
 MIT License – Free for academic use

Contributors
PREPOTENTE LORENZE NINO
FAITH ANN SANADO
JAMES SARIBA
JEANNIE FETIL
MHELARRY VALEZA

# Email:CSD-LMS@gmail.com
