<?php
session_start();

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

// Include the database connection
include 'connection.php'; // Ensure this is included before using $pdo

// Get sidebar color from session
$sidebarColor = isset($_SESSION['sidebar_color']) ? $_SESSION['sidebar_color'] : '#343a40';

// Handle form submission
$successMessage = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designationName = trim($_POST['designation_name']);
    
    if (empty($designationName)) {
        $error = 'Designation name is required.';
    } else {
        try {
            // Insert new designation
            $stmt = $pdo->prepare('INSERT INTO designations (name) VALUES (:name)');
            $stmt->bindParam(':name', $designationName);
            $stmt->execute();
            
            $successMessage = 'Designation added successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Assuming the admin user ID is known and is, for example, 1
$adminUserId = 1; // Replace this with the actual admin user ID

// Get the sidebar color for the admin user from the database if available
try {
    $stmt = $pdo->prepare('SELECT sidebar_color FROM user_settings WHERE user_id = :user_id');
    $stmt->bindParam(':user_id', $adminUserId, PDO::PARAM_INT);
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
    <title>Manage Designations</title>
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
            transition: background-color 0.3s ease;
        }
        .sidebar a {
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            text-align: left;
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

        <a href="applications.php">Application</a>
        <a href="recommend_applications.php">Recommend Application</a>
        <a href="add_department.php">Add Department</a>
        <a href="list_departments.php">List Department</a>
        <a href="designations.php">Add Designation</a>
        <a href="list_designations.php">List Designations</a>
        <a href="add_employee.php">Add Employee</a>
        <a href="list_employees.php">List of Employees</a>
        <a href="user_leave_details.php">User Leave Details</a>
        <a href="admin_panel.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>Add Designation</h1>
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if ($successMessage) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="designation_name">Designation Name</label>
                <input type="text" id="designation_name" name="designation_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Designation</button>
        </form>
    </div>
</body>
</html>