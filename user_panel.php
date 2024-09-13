<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include the connection file
require_once 'connection.php';

$employee = [];
$error = '';
$message = '';
$reason = '';

// Fetch user details from the employees table with designation name based on the logged-in username
try {
    $stmt = $pdo->prepare('
        SELECT e.id, e.name, e.email, d.name AS designation 
        FROM employees e 
        JOIN designations d ON e.designation_id = d.id 
        WHERE e.username = ?
    ');
    $stmt->execute([$_SESSION['username']]); // Use the logged-in username from session
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle profile update request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_change'])) {
        $reason = trim($_POST['reason']);
        if (!empty($reason)) {
            // Insert the reason into a request table or handle as needed
            $stmt = $pdo->prepare('INSERT INTO change_requests (user_id, reason) VALUES (?, ?)');
            $stmt->execute([$employee['id'], $reason]);
            $message = 'Request submitted successfully!';
        } else {
            $error = 'Please provide a reason for the request.';
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
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
    <title>User Panel</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #F2F3F5;
        }
        .container-fluid {
            display: flex;
            height: 100vh;
            margin: 0;
        }
        .sidebar {
            color: #fff;
            padding: 15px;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            position: fixed;
            background-color: <?php echo htmlspecialchars($sidebarColor); ?>;
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
        .sidebar a {
            color: #fff;
            text-decoration: none;
        }
        .sidebar a:hover {
            text-decoration: underline;
        }
        .main-content {
            margin-left: 250px;
            padding: 15px;
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
                <?php if (!empty($message)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php } ?>
                <?php if (!empty($error)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>
                <div class="mb-4">
                    <h2>Profile Information</h2>
                    <?php if (!empty($employee)) { ?>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
                        <p><strong>Designation:</strong> <?php echo htmlspecialchars($employee['designation']); ?></p>
                        <!-- Request to Change Info Form -->
                        <form method="post">
                            <div class="form-group">
                                <label for="reason">Reason for Change:</label>
                                <textarea id="reason" name="reason" class="form-control" rows="3"><?php echo htmlspecialchars($reason); ?></textarea>
                            </div>
                            <button type="submit" name="request_change" class="btn btn-primary">Request to Change My Info</button>
                        </form>
                    <?php } else { ?>
                        <p>No profile information available.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>