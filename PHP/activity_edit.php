<?php
require 'db_connect.php'; 
session_start();

// Get professor_id from session
// it ensures that the user is logged in as a professor
$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
    header("Location: login_professors.php");
    exit();
}

// Validate activity id from GET request
// it ensures that activity id is set and is a valid number
if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id'])) {
    die("Invalid Activity ID.");
}
$activity_id = intval($_GET['activity_id']);

// Fetch activity details from the database
$stmt = $conn->prepare("
    SELECT * FROM activities WHERE activity_id = ?
");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    die("Activity not found.");
}

// Handle form submission for editing activity
// Initialize variables
$success = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_name = trim($_POST['activity_name']);
    $due_date = $_POST['due_date'];

    // Validate inputs
    if (empty($activity_name)) {
        $error = "Activity name is required.";
    } elseif (empty($due_date)) {
        $error = "Due date is required.";
    } else {
        // Update the activity in the database
        $stmt = $conn->prepare("
            UPDATE activities 
            SET activity_name = ?, description = ?, due_date = ?
            WHERE activity_id = ?
        ");
        $activity_desc = trim($_POST['description']);


        $stmt->bind_param("sssi", $activity_name, $activity_desc, $due_date, $activity_id);

        if ($stmt->execute()) {
            $success = "Activity updated successfully!";
            // Refresh data
            $activity['activity_name'] = $activity_name;
            $activity['description'] = $activity_desc;
            $activity['due_date'] = $due_date;
        } else {
            $error = "Error updating activity: {$conn_professors->error}";
        }
        $stmt->close();

        
    }
}
?>

                        <!DOCTYPE html>
                        <html lang="en">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Edit Activity</title>
                            <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
                            <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
                            <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                        
                        <style>
                            body {
                            font-family: 'Google Sans', sans-serif;
                            margin: 0;
                            padding: 20px;
                        }
                        .main-content {
                            max-width: 600px;
                            margin: auto;
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                        }
                        h1 {
                            font-size: 24px;
                        }
                        label {
                            display: block;
                            margin-top: 16px;
                            font-weight: bold;
                        }
                        input, textarea {
                            width: 100%;
                            padding: 10px;
                            border: 1px solid #DADCE0;
                            border-radius: 5px;
                            margin-top: 6px;
                        }
                        .btn {
                            padding: 12px 16px;
                            border-radius: 5px;
                            cursor: pointer;
                            font-weight: bold;
                            text-decoration: none;
                            display: inline-block;
                            border: none;
                        }
                        .btn-primary {
                            background-color: #4285F4;
                            color: white;
                        }
                        .btn-outline {
                            background: white;
                            border: 1px solid #DADCE0;
                            color: black;
                        }
                        .notification {
                            padding: 12px;
                            border-radius: 5px;
                            margin-bottom: 16px;
                        }
                        .success {
                            background: #E6F4EA;
                            color: #34A853;
                        }
                        .error {
                            background: #FCE8E6;
                            color: #EA4335;
                        }
                        delete {
                            background-color:rgb(242, 6, 6);
                            color:rgb(250, 250, 250);
                        }

                        </style>
                        </head>
                        <body>

                <div class="main-content">
                    <h1>Edit Activity</h1>

                    <!-- Notifications -->
                    <?php if ($success): ?>
                        <div class="notification success">
                            <i class="material-icons">check_circle</i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="notification error">
                            <i class="material-icons">error</i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                                    <form action="" method="POST">
                    <label>Activity Name:</label>
                    <input type="text" name="activity_name" value="<?php echo htmlspecialchars($activity['activity_name']); ?>" required>

                    <label>Description:</label>
                    <textarea name="description" required><?php echo htmlspecialchars($activity['description']); ?></textarea>

                    <label>Due Date:</label>
                    <input type="date" name="due_date" value="<?php echo htmlspecialchars($activity['due_date']); ?>" required>

                    <button type="submit" class="btn btn-primary">Update Activity</button>
                </form>

                <div style="margin-top: 16px;">
                    <!-- Use a link styled as a button for the "Terminate" action -->
                    <a href="professor_delete_activity.php?activity_id=<?= $activity_id ?>" class="btn btn-danger" 
                    onclick="return confirm('Do you want to permanently delete this activity?')">Terminate</a>
                </div>


                            <script>
                            document.addEventListener("DOMContentLoaded", function () {
                            console.log("Edit Activity page loaded");

                            // Example: Prevent accidental form submission
                            document.querySelector("form").addEventListener("submit", function (event) {
                                if (!confirm("Are you sure you want to update this activity?")) {
                                    event.preventDefault();
                                }
                            });
                        });</script>
    
</body>
</html>

