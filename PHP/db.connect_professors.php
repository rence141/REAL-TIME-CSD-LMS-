<?php
$servername = "localhost";
$username = "root";
$password = "003421.!";
$dbname = "lms_dashborad_professors";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
