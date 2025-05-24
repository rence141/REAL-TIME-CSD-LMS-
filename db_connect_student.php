<?php
$servername = "localhost";
$username = "root";
$password = "003421.!";
$dbname1 = "lms_database_student";
$dbname2 = "lms_dashborad_professors"; // Corrected typo

// Connect to students database
$conn_student = new mysqli($servername, $username, $password, $dbname1);

// Connect to professors database
$conn_professors = new mysqli($servername, $username, $password, $dbname2);

// Check connections
if ($conn_student->connect_error) {
    die("Student DB connection failed: " . $conn_student->connect_error);
}
if ($conn_professors->connect_error) {
    die("Professors DB connection failed: " . $conn_professors->connect_error);
}
?>