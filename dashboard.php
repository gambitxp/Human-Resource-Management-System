<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get the sidebar color from the session
$sidebarColor = isset($_SESSION['sidebar_color']) ? $_SESSION['sidebar_color'] : '#343a40';

// Initialize variables
$employeeCount = 0;
$designationCount = 0;
$departmentCount = 0;
$processingCount = 0;
$requestedApprovalCount = 0;
$changeRequestCount = 0;

// Include the database connection
include 'connection.php';

try {
    // Get counts from the database
    $stmt = $pdo->query('SELECT COUNT(*) AS count FROM employees');
    $employeeCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) AS count FROM designations');
    $designationCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) AS count FROM departments');
    $departmentCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM leave_applications WHERE status = 'pending'");
    $processingCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE status_of_registration = 'pending'");
    $requestedApprovalCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM change_requests WHERE status = 'pending'");
    $changeRequestCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // Log error for debugging
    error_log('Database error: ' . $e->getMessage());
    $error = 'Database error: Please try again later.';
}

// Ensure the variables are defined
if (!isset($employeeCount)) $employeeCount = 0;
if (!isset($designationCount)) $designationCount = 0;
if (!isset($departmentCount)) $departmentCount = 0;
if (!isset($processingCount)) $processingCount = 0;
if (!isset($requestedApprovalCount)) $requestedApprovalCount = 0;
if (!isset($changeRequestCount)) $changeRequestCount = 0;

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
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            margin: 0;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: <?php echo htmlspecialchars($sidebarColor); ?>;
            padding-top: 20px;
            transition: transform 0.3s ease; /* Smooth transition */
        }
        .sidebar.hidden {
            transform: translateX(-250px); /* Move sidebar out of view */
        }
        .sidebar a {
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
            transition: margin-left 0.3s ease; /* Smooth transition */
        }
        .content.shift {
            margin-left: 0; /* Adjust content margin when sidebar is hidden */
        }
        .header-btns {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .menu-btn, .logout-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 7px;
            cursor: pointer;
            box-shadow: 0 2px 7px rgba(0,0,0,0.5);
            margin-left:10px;
        }
        .logout-btn {
            background-color: #dc3545;
        }
        .menu-btn:hover, .logout-btn:hover {
            opacity: 0.8;
        }
        .admin-info {
            position: fixed;
            top: 10px;
            right: 160px; /* Adjusted to not overlap with menu button */
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            border-radius: 7px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1;
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
        .main-title {
            font-size: 4rem;
            color: #000;
            text-align: center;
            margin-top: 70px;
        }
        .info-frames {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .info-frame {
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 20px;
            width: 18%; /* Adjusted to fit 5 items */
            text-align: center;
            color: #fff; /* White text color for better contrast */
        }
        .info-frame h3 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .info-frame p {
            font-size: 1.2rem;
            color: #fff; /* White text color for better contrast */
        }
        /* Specific colors for each frame */
        .info-frame.employees {
            background-color: #FFC0CB; /* Light red */
        }
        .info-frame.designations {
            background-color: #87CEEB; /* Light blue */
        }
        .info-frame.departments {
            background-color: #90EE90; /* Light green */
        }
        .info-frame.processing {
            background-color: #ADD8E6; /* Light blue */
        }
        .info-frame.requested-approval {
            background-color: #FFCCCB; /* Yellow */
            color: #000; /* Black text for better contrast on yellow */
        }
        .info-frame.change-requests {
            background-color: #FFDDC1; /* Light peach */
            color: #000; /* Black text for better contrast on peach */
        }
        .text-red {
            color: red;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.png" alt="Logo" class="logo">
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_panel.php">Manage Users</a>
        <a href="admin_settings.php">Settings</a>

        <a href="dashboard.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content" id="content">
        <div class="header-btns">
            <button class="menu-btn" id="menuBtn">Menu</button>
            <a href="dashboard.php?logout=true" class="logout-btn">Logout</a>
        </div>
        <div class="admin-info">
            <img src="images/admin.png" alt="Admin" class="admin-img">
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <h1 class="main-title">Dashboard</h1>
        <div class="info-frames">
            <div class="info-frame employees">
                <h3><?php echo htmlspecialchars($employeeCount); ?></h3>
                <p>Employees</p>
            </div>
            <div class="info-frame designations">
                <h3><?php echo htmlspecialchars($designationCount); ?></h3>
                <p>Designations</p>
            </div>
            <div class="info-frame departments">
                <h3><?php echo htmlspecialchars($departmentCount); ?></h3>
                <p>Departments</p>
            </div>
            <div class="info-frame processing">
                <h3 class="<?php echo $processingCount > 0 ? 'text-red' : ''; ?>">
                    <?php echo htmlspecialchars($processingCount); ?>
                </h3>
                <p>Processing</p>
            </div>
            <div class="info-frame requested-approval">
                <h3 class="<?php echo $requestedApprovalCount > 0 ? 'text-red' : ''; ?>">
                    <?php echo htmlspecialchars($requestedApprovalCount); ?>
                </h3>
                <p>Requested Approval</p>
            </div>
            <div class="info-frame change-requests">
                <h3 class="<?php echo $changeRequestCount > 0 ? 'text-red' : ''; ?>">
                    <?php echo htmlspecialchars($changeRequestCount); ?>
                </h3>
                <p>Change Requests Info</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('menuBtn').addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            var content = document.getElementById('content');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                content.classList.remove('shift');
            } else {
                sidebar.classList.add('hidden');
                content.classList.add('shift');
            }
        });
    </script>
</body>
</html>