<?php
session_start();
include 'db_connect.php';
require 'vendor/autoload.php'; // Ensure you have PhpSpreadsheet installed

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["grades_file"])) {
    $file = $_FILES["grades_file"]["tmp_name"];
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    $stmt = $conn->prepare("INSERT INTO grades (professor_id, student_id, subject, grade) VALUES (?, ?, ?, ?)");

    foreach ($data as $row) {
        list($student_id, $subject, $grade) = $row;

        if (is_numeric($student_id) && !empty($subject) && is_numeric($grade) && $grade >= 0 && $grade <= 100) {
            $stmt->bind_param("iisd", $_SESSION['professor_id'], $student_id, $subject, $grade);
            $stmt->execute();
        }
    }

    echo "<script>alert('✅ Grades uploaded successfully!'); window.location.href='view_grades.php';</script>";
}
?>
