-- Create attendance_appeals table
CREATE TABLE IF NOT EXISTS attendance_appeals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attendance_id INT NOT NULL,
    student_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    admin_remarks TEXT NULL,
    FOREIGN KEY (attendance_id) REFERENCES attendance(attendance_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (processed_by) REFERENCES professors(professor_id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('submission', 'appeal', 'attendance', 'absence_warning') NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'professor') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0
);

-- Add indexes for better performance
CREATE INDEX idx_notifications_user ON notifications(user_id, user_type);
CREATE INDEX idx_notifications_created_at ON notifications(created_at); 