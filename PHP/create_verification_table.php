<?php
require_once 'db_connect.php';

// Create verification_codes table
$sql = "CREATE TABLE IF NOT EXISTS professor_verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_used BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (professor_id) REFERENCES professors(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Verification codes table created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 
