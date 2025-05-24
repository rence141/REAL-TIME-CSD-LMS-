<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor's courses
$courses_query = $conn->prepare("SELECT course_id, course_name FROM courses WHERE professor_id = ?");
$courses_query->bind_param("i", $professor_id);
$courses_query->execute();
$courses_result = $courses_query->get_result();

// Fetch professor information
$prof_query = $conn->prepare("SELECT Full_Name as professor_name FROM professors WHERE id = ?");
$prof_query->bind_param("i", $professor_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$prof_data = $prof_result->fetch_assoc();
$professor_name = $prof_data['professor_name'] ?? 'Unknown Professor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Add Theme Support -->
    <?php include 'includes/theme-includes.php'; addThemeHeaders(); ?>
    
    <style>
        /* Light mode (default) theme variables */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --primary-color: #1a73e8;
            --secondary-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --border-color: #e9ecef;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            --btn-primary: #1a73e8;
            --btn-hover: #1557b0;
        }

        /* General styles */
        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .card {
            background-color: var(--bg-primary);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }
        
        .btn-primary {
            background-color: var(--btn-primary);
            border-color: var(--btn-primary);
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background-color: var(--btn-hover);
            border-color: var(--btn-hover);
        }

        /* Status button styles */
        .btn-group .btn {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px;
            margin: 0 2px;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }

        /* Present button */
        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
            background-color: #ffffff;
        }

        .btn-outline-success:hover,
        .btn-outline-success.active {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
        }

        /* Absent button */
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
            background-color: #ffffff;
        }

        .btn-outline-danger:hover,
        .btn-outline-danger.active {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
        }

        /* Late button */
        .btn-outline-warning {
            color: #ffc107;
            border-color: #ffc107;
            background-color: #ffffff;
        }

        .btn-outline-warning:hover,
        .btn-outline-warning.active {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000000;
        }

        /* Active state for all buttons */
        .btn-group .btn.active {
            font-weight: 500;
            transform: translateY(1px);
        }

        /* Remove default button styles */
        .btn-success,
        .btn-danger,
        .btn-warning {
            background-color: #ffffff;
        }

        .btn-success:hover,
        .btn-success.active {
            background-color: #28a745;
            color: #ffffff;
        }

        .btn-danger:hover,
        .btn-danger.active {
            background-color: #dc3545;
            color: #ffffff;
        }

        .btn-warning:hover,
        .btn-warning.active {
            background-color: #ffc107;
            color: #000000;
        }
        
        .form-control, .form-select {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px 12px;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .table th {
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-color);
            padding: 12px 16px;
        }

        .table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .student-list {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-top: 20px;
        }

        /* Card header specific styles */
        .card-header {
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Back button */
        .btn-outline-secondary {
            color: var(--text-secondary);
            border-color: var(--border-color);
            background-color: transparent;
        }

        .btn-outline-secondary:hover {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        /* Save button */
        .btn-save {
            background-color: var(--btn-primary);
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
        }

        .btn-save:hover {
            background-color: var(--btn-hover);
        }

        .btn-save:disabled {
            background-color: var(--border-color);
            cursor: not-allowed;
        }

        /* Form labels */
        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 8px;
        }

        /* Notes input */
        .notes-input {
            font-size: 14px;
            padding: 6px 12px;
        }

        /* Alert styles */
        .alert {
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 0;
        }

        .alert-info {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--text-primary);
            border: 1px solid rgba(26, 115, 232, 0.2);
        }
    </style>
</head>
<body>
    <!-- Add Theme Toggles at the top of the page -->
    <div class="container-fluid mb-3">
        <?php addThemeToggles(); ?>
    </div>

    <div class="container my-5">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Mark Attendance</h2>
                <a href="attendance_prof.php" class="btn btn-outline-secondary">
                    <i class="material-icons align-middle">arrow_back</i> Back
                </a>
            </div>
            <div class="card-body">
                <form id="attendanceForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select id="courseSelect" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php while ($course = $courses_result->fetch_assoc()): ?>
                                    <option value="<?= $course['course_id'] ?>">
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" id="attendanceDate" class="form-control" required
                                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </form>

                <div id="studentList" class="student-list mt-4">
                    <!-- Students will be loaded here -->
                </div>

                <div class="text-end mt-4">
                    <button type="button" id="submitAttendance" class="btn btn-primary" disabled>
                        <i class="material-icons align-middle">save</i> Save Attendance
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courseSelect = document.getElementById('courseSelect');
            const attendanceDate = document.getElementById('attendanceDate');
            const studentList = document.getElementById('studentList');
            const submitBtn = document.getElementById('submitAttendance');
            
            // Load students when course is selected
            courseSelect.addEventListener('change', loadStudents);
            
            function loadStudents() {
                const courseId = courseSelect.value;
                if (!courseId) {
                    studentList.innerHTML = '<div class="alert alert-info">Please select a course.</div>';
                    submitBtn.disabled = true;
                    return;
                }
                
                // Show loading state
                studentList.innerHTML = '<div class="alert alert-info">Loading students...</div>';
                
                fetch(`get_enrolled_students.php?course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        if (data.message && (!data.students || data.students.length === 0)) {
                            studentList.innerHTML = `<div class="alert alert-info">${data.message}</div>`;
                            submitBtn.disabled = true;
                            return;
                        }

                        const students = data.students || data;
                        
                        if (students.length === 0) {
                            studentList.innerHTML = '<div class="alert alert-info">No students enrolled in this course.</div>';
                            submitBtn.disabled = true;
                            return;
                        }
                        
                        let html = '<div class="table-responsive"><table class="table">';
                        html += '<thead><tr><th>Student</th><th>Block</th><th>Status</th><th>Notes</th></tr></thead><tbody>';
                        
                        students.forEach(student => {
                            html += `
                                <tr data-student-id="${student.student_id}">
                                    <td>${student.student_name}</td>
                                    <td>${student.block || 'N/A'}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-success btn-sm status-btn" data-status="Present">Present</button>
                                            <button type="button" class="btn btn-outline-danger btn-sm status-btn" data-status="Absent">Absent</button>
                                            <button type="button" class="btn btn-outline-warning btn-sm status-btn" data-status="Late">Late</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm notes-input" placeholder="Optional notes">
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table></div>';
                        studentList.innerHTML = html;
                        submitBtn.disabled = false;
                        
                        // Add click handlers for status buttons
                        document.querySelectorAll('.status-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const row = this.closest('tr');
                                row.querySelectorAll('.status-btn').forEach(b => {
                                    b.classList.remove('active', 'btn-success', 'btn-danger', 'btn-warning');
                                    b.classList.add('btn-outline-' + getStatusColor(b.dataset.status));
                                });
                                this.classList.remove('btn-outline-' + getStatusColor(this.dataset.status));
                                this.classList.add('active', 'btn-' + getStatusColor(this.dataset.status));
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        studentList.innerHTML = `<div class="alert alert-danger">Error loading students: ${error.message}</div>`;
                        submitBtn.disabled = true;
                    });
            }
            
            function getStatusColor(status) {
                switch(status) {
                    case 'Present': return 'success';
                    case 'Absent': return 'danger';
                    case 'Late': return 'warning';
                    default: return 'secondary';
                }
            }
            
            // Handle form submission
            submitBtn.addEventListener('click', function() {
                const courseId = courseSelect.value;
                const date = attendanceDate.value;
                const attendance = [];
                
                let isValid = true;
                document.querySelectorAll('#studentList tr[data-student-id]').forEach(row => {
                    const studentId = row.dataset.studentId;
                    const statusBtn = row.querySelector('.status-btn.active');
                    const notes = row.querySelector('.notes-input').value;
                    
                    if (!statusBtn) {
                        isValid = false;
                        row.classList.add('table-danger');
                    } else {
                        row.classList.remove('table-danger');
                        attendance.push({
                            student_id: studentId,
                            status: statusBtn.dataset.status,
                            notes: notes
                        });
                    }
                });
                
                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Status',
                        text: 'Please mark attendance status for all students.'
                    });
                    return;
                }
                
                // Submit attendance data
                fetch('mark_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId,
                        date: date,
                        attendance: attendance
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = 'attendance_prof.php';
                        });
                    } else {
                        throw new Error(data.error || 'Failed to mark attendance');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                });
            });
        });
    </script>

    <!-- Add Theme Scripts -->
    <?php addThemeScripts(); ?>
</body>
</html> 