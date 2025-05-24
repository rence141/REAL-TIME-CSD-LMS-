<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Access denied! Admins only.'); window.location.href='admin_login.php';</script>";
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Attendance View</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
</head>
<body>
<h2>Attendance Records</h2>
<!-- Code to fetch and display a
<?php
$professors = $conn->query("SELECT id, Full_Name, email, employeeid_number, profile_image FROM professors");
?>

 <tbody>
 <?php while ($row = $professors->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><img src="<?= $row['profile_image'] ?>" alt="Profile" width="50" height="50"></td>
                    <td><?= $row['Full_Name'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['employeeid_number'] ?></td>
                    <td>
                        <a href="admin_edit_professor.php?id=<?= $row['id'] ?>" class="btn btn-warning">✏ Edit</a>
                        <a href="admin_delete_professor.php?id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this professor?')">❌ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
</body>
</html>
