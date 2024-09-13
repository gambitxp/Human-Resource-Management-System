<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include the database connection
include 'connection.php';

// Initialize variables
$employee = [];
$error = '';
$message = '';
$sidebarColor = '#343a40'; // Default sidebar color

try {
    // Fetch user details from the employees table
    $stmt = $pdo->prepare('SELECT id, name, middle_name, surname, email, annual_leave_balance, username FROM employees WHERE username = ?');
    $stmt->execute([$_SESSION['username']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user is an admin based on user_id
    $isAdmin = ($employee['id'] === 1); // Assuming user_id of 1 is an admin

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

    // Handle leave application submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fromDate = $_POST['from_date'];
        $toDate = $_POST['to_date'];
        $leaveType = $_POST['leave_type'];
        $reason = $_POST['reason']; // Get reason for leave
        $userId = $employee['id']; // Get user ID from employee data

        // Calculate leave days
        $date1 = new DateTime($fromDate);
        $date2 = new DateTime($toDate);
        $interval = $date1->diff($date2);
        $leaveDays = $interval->days + 1; // Add 1 to include both start and end dates

        // Check if there is sufficient leave balance
        if ($employee['annual_leave_balance'] >= $leaveDays) {
            // Insert the leave application
            $stmt = $pdo->prepare('INSERT INTO leave_applications (user_id, username, name, middle_name, surname, email, from_date, to_date, leave_type, reason, processing_number, leave_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $employee['username'], $employee['name'], $employee['middle_name'], $employee['surname'], $employee['email'], $fromDate, $toDate, $leaveType, $reason, 1, $leaveDays]);

            $message = 'Leave application submitted successfully!';
        } else {
            $error = 'Insufficient leave balance!';
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave</title>
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
            background-color: <?php echo htmlspecialchars($sidebarColor); ?>;
            color: #fff;
            padding: 15px;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            position: fixed;
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
                    <a class="nav-link" href="application.php">Apply for Leave</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_panel.php?logout=true">Logout</a>
                </li>
            </ul>
        </div>
        <div class="main-content">
            <div class="panel-content">
                <h1 class="mt-5">Apply for Leave</h1>
                <?php if (isset($message)) { ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php } ?>
                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>
                <form method="post" action="application.php">
                    <div class="form-group">
                        <label for="from_date">From Date:</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" required>
                    </div>
                    <div class="form-group">
                        <label for="to_date">To Date:</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" required>
                    </div>
                    <div class="form-group">
                        <label for="leave_type">Type of Leave:</label>
                        <select class="form-control" id="leave_type" name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Casual Leave">Casual Leave</option>
                            <option value="Annual Leave">Annual Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason for Leave:</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>