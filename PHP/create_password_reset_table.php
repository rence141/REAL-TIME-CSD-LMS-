<?php
require_once 'db_connect.php';

// Create password_reset_codes table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (professor_id) REFERENCES professors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Password reset codes table created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 
