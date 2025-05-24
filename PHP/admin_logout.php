<?php
session_start();
include 'db_connect.php'; // Database connection
session_destroy();
header("Location: admin_login.php");
exit();
?>
