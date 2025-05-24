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
    <title>Admin Dashboard | BU LMS</title>
    <link rel="icon" href="../IMAGES/BUPC_Logo.png" type="image/png">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Theme Support -->
    <?php 
    require_once 'includes/theme-includes.php';
    addThemeHeaders();
    ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            height: 100vh;
            position: fixed;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .app-name {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .nav-item i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #3498db;
        }
        
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #3498db;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            min-height: 100vh;
        }
        
        .text-center {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 2rem;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
            display: inline-block;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header {
                padding: 10px;
            }
            
            .app-name, .nav-item span {
                display: none;
            }
            
            .nav-item {
                justify-content: center;
                padding: 15px 0;
            }
            .theme-dark .nav-item span,
            .theme-colorblind .nav-item span {
                display: inline;
                color: #fff;
            }
            .nav-item i {
                margin-right: 0;
                font-size: 1.5rem;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../IMAGES/BUPC_Logo.png" alt="BU Logo" class="sidebar-logo">
            <span class="app-name">BU LMS</span>
        </div>
        <nav class="nav-menu">
            <!-- Theme Toggles -->
            
            
            <!-- Navigation Items -->
            <a href="admin_dashboard.php" class="nav-item active">
                <i class="material-icons">dashboard</i>
                <span>Dashboard</span>
            </a>
            <a href="manage_professors.php" class="nav-item">
                <i class="material-icons">people</i>
                <span>Manage Professors</span>
            </a>
           
            
            
            <a href="admin_logout.php" class="nav-item">
                <i class="material-icons">exit_to_app</i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
    
    <div class="main-content">
        <h2 class="text-center">Admin Dashboard</h2>
        
       
            
            <div class="card">
                <div class="card-icon">
                    <i class="material-icons">people</i>
                </div>
                <h3>Professors</h3>
                <p>Manage professor accounts and courses</p>
            </div>
            
           
            
            
    
    <!-- Theme Scripts -->
    <?php addThemeScripts(); ?>
</body>
</html>