<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include the connection file
require_once 'connection.php';

$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$applications = [];
$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

try {
    // Prepare SQL query based on the selected status filter
    $sql = 'SELECT id, username, status, from_date, to_date FROM leave_applications WHERE username = ?';
    
    if ($statusFilter !== 'all') {
        $sql .= ' AND status = ?';
    }
    
    $stmt = $pdo->prepare($sql);
    $params = [$_SESSION['username']];
    
    if ($statusFilter !== 'all') {
        $params[] = $statusFilter;
    }
    
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
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
    <title>Status of Application</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #F2F3F5; /* Blue background color */
        }
        .container-fluid {
            display: flex;
            height: 100vh;
            margin: 0;
        }
        .sidebar {
            background-color: <?php echo htmlspecialchars($sidebarColor); ?>; /* Use dynamic sidebar color */
            color: #fff;
            padding: 15px;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            position: fixed;
        }
        .sidebar h2 {
            color: #fff;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
        }
        .sidebar-header {
            display: flex;
            justify-content: center; /* Center logo horizontally */
            align-items: center;
            margin-bottom: 20px; /* Space below the header */
        }
        .sidebar img {
            max-width: 100%; /* Adjust size as needed */
            height: auto;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
        }
        .panel-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .alert {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        .btn-print {
            background-color: #28a745; /* Green color */
            color: white;
            border: none;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-print:hover {
            background-color: #218838; /* Darker green */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="sidebar">
                     <div class="sidebar-header">
                <img src="images/logo.png" alt="Logo">
            </div>
           
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="user_panel.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="application.php">Application</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="status_of_application.php">Status of Application</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_panel.php?logout=true">Logout</a>
                </li>
            </ul>
        </div>
        <div class="main-content">
            <div class="panel-content">
                <h1 class="mt-5">Status of Application</h1>
                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <a href="status_of_application.php?status=all" class="btn btn-primary">All</a>
                    <a href="status_of_application.php?status=pending" class="btn btn-secondary">Pending</a>
                    <a href="status_of_application.php?status=approved" class="btn btn-success">Approved</a>
                    <a href="status_of_application.php?status=complete" class="btn btn-info">Complete</a>
                </div>

                <div class="mb-4">
                    <h2>Leave Applications</h2>
                    <?php if ($applications) { ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Application Date</th>
                                    <th>Leave End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $application) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($application['id']); ?></td>
                                        <td><?php echo htmlspecialchars($application['username']); ?></td>
                                        <td><?php echo htmlspecialchars($application['status']); ?></td>
                                        <td><?php echo htmlspecialchars($application['from_date']); ?></td>
                                        <td><?php echo htmlspecialchars($application['to_date']); ?></td>
                                        <td>
                                            <?php if (strtolower($application['status']) === 'approved') { ?>
                                                <a href="print_document.php?id=<?php echo $application['id']; ?>" class="btn btn-print" target="_blank">Print Document</a>
                                            <?php } else { ?>
                                                <p>Not Approved</p>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <p>No applications found.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>