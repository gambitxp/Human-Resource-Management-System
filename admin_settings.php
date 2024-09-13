<?php
session_start();

// Simulate admin login for testing purposes
// REMOVE THIS CODE IN PRODUCTION AND USE REAL LOGIN SYSTEM
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1; // Simulate a user ID; adjust as needed

// Include the database connection
include 'connection.php'; // Ensure this file contains the necessary database connection setup

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check if user_id is set in session
if (!isset($_SESSION['user_id'])) {
    die('User ID is not set in session.');
}

$userId = $_SESSION['user_id'];

// Handle sidebar color change
if (isset($_POST['sidebar_color'])) {
    $sidebarColor = trim($_POST['sidebar_color']);
    
    // Validate color input
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $sidebarColor)) {
        try {
            // Update sidebar color in the database
            $stmt = $pdo->prepare('INSERT INTO user_settings (user_id, sidebar_color) VALUES (:user_id, :sidebar_color)
                                   ON DUPLICATE KEY UPDATE sidebar_color = :sidebar_color');
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':sidebar_color', $sidebarColor);
            $stmt->execute();
            
            $_SESSION['sidebar_color'] = $sidebarColor; // Update session variable
        } catch (PDOException $e) {
            die('Database error: ' . $e->getMessage());
        }
    } else {
        die('Invalid color format.');
    }
}

// Get the sidebar color from the database if available
try {
    $stmt = $pdo->prepare('SELECT sidebar_color FROM user_settings WHERE user_id = :user_id');
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    $sidebarColor = $settings['sidebar_color'] ?? '#343a40'; // Default color if not set
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: <?php echo htmlspecialchars($sidebarColor); ?>;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
}
        .sidebar-header {
    display: flex;
    justify-content: center; /* Center logo horizontally */
    align-items: center;
    margin-bottom: 20px; /* Space below the header */
}

.logo {
    width: 170px; /* Adjust size as needed */
    height: auto;


        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
        <img src="images/logo.png" alt="Logo" class="logo">
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_panel.php">Manage Users</a>
        <a href="admin_settings.php">Settings</a>
        <!-- Other menu items -->
        <a href="admin_settings.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>Settings</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="sidebar_color">Sidebar Color:</label>
                <input type="color" id="sidebar_color" name="sidebar_color" value="<?php echo htmlspecialchars($sidebarColor); ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</body>
</html>