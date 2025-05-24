<?php
$host = 'localhost';
$username = 'root';
$password = '003421.!';
$database = 'lms_dashborad_professors';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connect to student database
$database_student = 'lms_database_student';
$conn_student = new mysqli($host, $username, $password, $database_student);

if ($conn_student->connect_error) {
    die("Connection to student database failed: " . $conn_student->connect_error);
}
?>
