<?php
$servername = "localhost";
$username = "root";  // Change if necessary
$password = "003421.!";
$dbname = "lms_database_student"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Correct SQL query
$activities_query = "SELECT * FROM activities ORDER BY created_at DESC LIMIT 5";
$activities_result = $conn->query($activities_query);

if (!$activities_result) {
    die("Error: " . $conn->error);
}

?>

<div class="activity-box">
    <h4> Activity log</h4>
    <ul>
        <?php while ($row = $activities_result->fetch_assoc()) { ?>
            <li><strong><?= $row["activity_type"] ?></strong> - <?= $row["details"] ?> (<?= $row["created_at"] ?>)</li>
        <?php } ?>
    </ul>
</div>
