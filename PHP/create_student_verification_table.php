<?php
require_once 'db_connect.php';

// Create student_verification_codes table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS student_verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn_student->query($sql) === TRUE) {
    echo "Student verification codes table created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn_student->error;
}

$conn_student->close();
?> 
