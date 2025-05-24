<?php
session_start();
include 'db_connect.php'; // Contains $conn (professor DB) and $conn_student (student DB)

if (!isset($_SESSION['professor_id'])) {
    echo "<script>alert('Please log in first!'); window.location.href='login_professor.php';</script>";
    exit();
}

$professor_id = $_SESSION['professor_id'];

// Fetch professor information
$prof_query = $conn->prepare("SELECT Full_Name as professor_name, email as professor_email, profile_image FROM professors WHERE id = ?");
$prof_query->bind_param("i", $professor_id);
$prof_query->execute();
$prof_result = $prof_query->get_result();
$prof_data = $prof_result->fetch_assoc();

$professor_name = $prof_data['professor_name'] ?? 'Unknown Professor';
$professor_email = $prof_data['professor_email'] ?? 'No email available';
$profile_image = $prof_data['profile_image'] ?? '../IMAGES/default_profile.png';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch courses taught by professor
$courses = $conn->query("SELECT course_id, course_name FROM courses WHERE professor_id = $professor_id ORDER BY course_name ASC");

// Fetch enrollments (students per course)
$enrollments = $conn->query("SELECT student_id, course_id FROM enrollments");

// Fetch all students from student DB
$students = [];
$student_query = $conn_student->query("SELECT student_id, student_name, block FROM students");
while ($row = $student_query->fetch_assoc()) {
    $students[$row['student_id']] = $row;
}

// Fetch pending appeals count
$pending_appeals_query = "
    SELECT COUNT(*) as pending_count 
    FROM attendance_appeals aa
    JOIN attendance a ON aa.attendance_id = a.attendance_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE c.professor_id = ? AND aa.status = 'pending'";
$stmt = $conn->prepare($pending_appeals_query);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_count = $pending_result->fetch_assoc()['pending_count'];

// Filter form values
$filter_course = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build attendance query without joining 'students' table
$query = "SELECT a.attendance_id, a.date, a.status, a.notes, 
                 a.course_id, a.student_id, c.course_name,
                 aa.id as appeal_id, aa.status as appeal_status
          FROM attendance a
          JOIN courses c ON a.course_id = c.course_id
          LEFT JOIN attendance_appeals aa ON a.attendance_id = aa.attendance_id
          WHERE c.professor_id = $professor_id";

if (!empty($filter_course)) {
    $query .= " AND a.course_id = " . (int)$filter_course;
}
if (!empty($filter_status)) {
    $query .= " AND a.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($filter_date)) {
    $query .= " AND a.date = '" . $conn->real_escape_string($filter_date) . "'";
}

$query .= " ORDER BY a.date DESC";

$attendance_query = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Attendance Records</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #34A853;
            --danger-color: #EA4335;
            --warning-color: #FBBC05;
            --dark-color: #202124;
            --light-color: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #3c4043;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Google-style */
         .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            box-shadow: var(--shadow-md);  /* This is the key shadow */
            transition: transform 0.3s ease;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .sidebar-logo {
            height: 40px;
            margin-right: 12px;
        }
        
        .app-name {
            font-size: 18px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .nav-menu {
            padding: 8px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            cursor: pointer;
            transition: background-color 0.2s;
            color: #5f6368;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background-color: #f1f3f4;
        }
        
        .nav-item.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }
        
        .nav-item i {
            margin-right: 16px;
            font-size: 20px;
        }

        /* Professor Profile Styles */
        .professor-profile {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.12);
            margin-bottom: 8px;
            background-color: #f8f9fa;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid #4285F4;
        }

        .professor-info {
            display: flex;
            flex-direction: column;
        }

        .professor-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .professor-email {
            font-size: 12px;
            color: #5f6368;
            margin-top: 2px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 24px;
            background-color: #f5f5f5;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 16px;
        }

        /* Filter Form Styles */
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
        }

        .form-label {
            color: #5f6368;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-select, .form-control {
            border: 1px solid #dadce0;
            border-radius: 4px;
            padding: 8px 12px;
            color: #3c4043;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
            color: #5f6368;
            font-weight: 500;
            padding: 12px 16px;
        }

        .table td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        /* Status Colors */
        .status-present { background-color: #e6f4ea !important; }
        .status-absent { background-color: #fce8e6 !important; }
        .status-late { background-color: #fff3e0 !important; }
        .status-excused { background-color: #f3f4f6 !important; }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #3367d6;
            border-color: #3367d6;
        }

        .btn-outline-secondary {
            color: #5f6368;
            border-color: #dadce0;
        }

        .btn-outline-secondary:hover {
            background-color: #f1f3f4;
            color: #3c4043;
            border-color: #dadce0;
        }

        .btn-warning {
            background-color: #4285F4;
            border-color: #4285F4;
            color: #fff;
        }

        .btn-warning:hover {
            background-color: #3367d6;
            border-color: #3367d6;
            color: #fff;
        }

        .btn-danger {
            background-color: #1a73e8;
            border-color: #1a73e8;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #1557b0;
            border-color: #1557b0;
        }

        .btn-sm {
            padding: 4px 12px;
            font-size: 13px;
        }

        /* Appeal Button Styles */
        .btn-outline-secondary:disabled {
            color: #9aa0a6;
            border-color: #dadce0;
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .btn-outline-primary:disabled {
            color: #9aa0a6;
            border-color: #dadce0;
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .btn i {
            font-size: 16px;
            vertical-align: text-bottom;
            margin-right: 4px;
        }

        /* Status Badge Styles */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge i {
            font-size: 14px;
            vertical-align: text-bottom;
            margin-right: 4px;
        }

        /* Additional styles for the buttons */
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .btn-group .btn .material-icons {
            font-size: 18px;
        }

        /* Tooltip styles */
        [title] {
            position: relative;
        }

        [title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 8px;
            background-color: rgba(0,0,0,0.8);
            color: white;
            font-size: 12px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/Screenshot 2025-04-21 162933.png" alt="BU Logo" class="sidebar-logo">
            <span class="app-name">Computer Science Department</span>
            
        </div>
        
        <nav class="nav-menu">
            <div class="professor-profile">
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-image">
                <div class="professor-info">
                    <span class="professor-name"><?= htmlspecialchars($professor_name) ?></span>
                    <div class="professor-email">
                        <?php echo htmlspecialchars($professor_email); ?>
                    </div>
                </div>
            </div>
            
            <a href="dashboard_professor.php" class="nav-item">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="attendance_prof.php" class="nav-item active">
                <i class="material-icons">check_circle</i>
                <span>Attendance</span>
            </a>
            <a href="grades.php" class="nav-item">
                <i class="material-icons">grade</i>
                <span>Grade Entry</span>
            </a>
            <a href="schedule.php" class="nav-item">
                <i class="material-icons">calendar_today</i>
                <span>Schedules</span>
            </a>
            <a href="manage_courses.php" class="nav-item">
                <i class="material-icons">class</i>
                <span>Course Management</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="material-icons">bar_chart</i>
                <span>Reports</span>
            </a>
          
        </nav>

        <!-- Modify the View Appeals Button to show notification -->
        <a href="view_appeals.php" class="btn btn-primary mt-3 position-relative" style="width: 100%;">
            <i class="material-icons">gavel</i>
            View Attendance Appeals
            <?php if ($pending_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $pending_count ?>
                    <span class="visually-hidden">pending appeals</span>
                </span>
            <?php endif; ?>
        </a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">Attendance Records</h2>
                <a href="dashboard_professor.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

            <!-- Filter Form -->
            <div class="card-body">
                <form method="get" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select">
                                <option value="">All Courses</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?= $course['course_id'] ?>" <?= $filter_course == $course['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Present" <?= $filter_status == 'Present' ? 'selected' : '' ?>>Present</option>
                                <option value="Absent" <?= $filter_status == 'Absent' ? 'selected' : '' ?>>Absent</option>
                                <option value="Late" <?= $filter_status == 'Late' ? 'selected' : '' ?>>Late</option>
                                <option value="Excused" <?= $filter_status == 'Excused' ? 'selected' : '' ?>>Excused</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="view_attendance_prof.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                <!-- Attendance Records Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Block</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Appeal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendance_query && $attendance_query->num_rows > 0): ?>
                                <?php while ($record = $attendance_query->fetch_assoc()): 
                                    $status_class = 'status-' . strtolower($record['status']);
                                    $student_id = $record['student_id'];
                                    $student_name = $students[$student_id]['student_name'] ?? 'Unknown';
                                    $block = $students[$student_id]['block'] ?? 'N/A';
                                ?>
                                    <tr class="<?= $status_class ?>">
                                        <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                        <td><?= htmlspecialchars($record['course_name']) ?></td>
                                        <td><?= htmlspecialchars($student_name) ?></td>
                                        <td><?= htmlspecialchars($block) ?></td>
                                        <td><?= htmlspecialchars($record['status']) ?></td>
                                        <td><?= htmlspecialchars($record['notes']) ?></td>
                                        <td>
                                            <?php if ($record['appeal_id']): ?>
                                                <?php if ($record['appeal_status'] == 'pending'): ?>
                                                    <a href="view_appeals.php?appeal_id=<?= $record['appeal_id'] ?>" 
                                                       class="btn btn-warning btn-sm">
                                                        <i class="material-icons">notification_important</i>
                                                        Review Appeal
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge <?= $record['appeal_status'] == 'approved' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= ucfirst($record['appeal_status']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="material-icons small">info</i>
                                                    <?= $record['status'] == 'Present' ? 'No Appeal Needed' : 'No Appeal Submitted' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_attendance.php?id=<?= $record['attendance_id'] ?>" 
                                                   class="btn btn-sm btn-warning me-2" 
                                                   title="Edit Attendance">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="Delete Attendance"
                                                        onclick="confirmDelete(<?= $record['attendance_id'] ?>)">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No attendance records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="mark_attendance_form.php" class="btn btn-primary">
                        <i class="material-icons">add</i> Mark New Attendance
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(attendanceId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This attendance record will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4285F4',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete_attendance.php?id=${attendanceId}`;
                }
            });
        }
    </script>
    <!-- Add SweetAlert2 for better confirmation dialogs -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
