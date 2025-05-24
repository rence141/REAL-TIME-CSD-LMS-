<?php
session_start();
include 'db_connect.php'; // Database connection

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Access denied! Admins only.'); window.location.href='admin_login.php';</script>";
    exit();
}

// Fetch all professors
$professors = $conn->query("SELECT id, Full_Name, email, employeeid_number, profile_image FROM professors");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Professors</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="text-center"> Manage Professors</h2>

    <!-- Add New Professor Button -->
    <div class="text-end mb-3">
        <a href="admin_add_professor.php" class="btn btn-success"> Add Professor</a>
    </div>

    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Profile</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Employee ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $professors->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><img src="<?= $row['profile_image'] ?>" alt="Profile" width="50" height="50"></td>
                    <td><?= $row['Full_Name'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['employeeid_number'] ?></td>
                    <td>
                        <a href="admin_delete_professor.php?id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this professor?')"> Terminate</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="text-center mt-4">
        <a href="admin_dashboard.php" class="btn btn-secondary"> Back to Dashboard</a>
    </div>

</body>
</html>
