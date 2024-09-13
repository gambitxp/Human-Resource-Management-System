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

// Include the connection file
require_once 'connection.php';

$leaveDetails = [];
$error = '';
$searchQuery = '';

// Capture search query from GET request
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
}

// Fetch leave details based on search query
try {
    $sql = 'SELECT la.id, e.name, e.middle_name, e.surname, la.from_date, la.to_date, la.leave_type, la.leave_days, e.annual_leave_balance
            FROM leave_applications la
            JOIN employees e ON la.username = e.username
            WHERE la.status = "Approved"';
    
    if ($searchQuery) {
        $sql .= ' AND (e.name LIKE :searchQuery OR la.leave_type LIKE :searchQuery)';
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($searchQuery) {
        $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%');
    }
    
    $stmt->execute();
    $leaveDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Handle the "Back to Pending" action
if (isset($_POST['back_to_pending'])) {
    $leaveId = $_POST['leave_id'];
    try {
        // Fetch the leave application details
        $stmt = $pdo->prepare('SELECT leave_days, username FROM leave_applications WHERE id = :id');
        $stmt->execute(['id' => $leaveId]);
        $leaveApplication = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($leaveApplication) {
            $leaveDays = $leaveApplication['leave_days'];
            $username = $leaveApplication['username'];

            // Update the employee's leave balance by adding back the leave days
            $stmt = $pdo->prepare('UPDATE employees SET annual_leave_balance = annual_leave_balance + :leaveDays WHERE username = :username');
            $stmt->execute(['leaveDays' => $leaveDays, 'username' => $username]);

            // Set the leave application status back to "Pending"
            $stmt = $pdo->prepare('UPDATE leave_applications SET status = "Pending" WHERE id = :id');
            $stmt->execute(['id' => $leaveId]);

            header('Location: user_leave_details.php'); // Refresh page after status update
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Handle the "Complete" action
if (isset($_POST['complete_leave'])) {
    $leaveId = $_POST['leave_id'];
    try {
        // Set the leave application status to "Complete"
        $stmt = $pdo->prepare('UPDATE leave_applications SET status = "Complete" WHERE id = :id');
        $stmt->execute(['id' => $leaveId]);

        header('Location: user_leave_details.php'); // Refresh page after status update
        exit;
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get the sidebar color for the admin user from the database if available
try {
    $adminUserId = 1; // Replace this with the actual admin user ID
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
    <title>User Leave Details</title>
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
        .logout-button {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
    <script>
        // Confirmation before setting status back to "Pending"
        function confirmAction(leaveId, action) {
            if (action === 'pending' && confirm('Are you sure you want to set this leave application back to Pending?')) {
                document.getElementById('backToPendingForm-' + leaveId).submit();
            } else if (action === 'complete' && confirm('Are you sure you want to mark this leave as Complete? This action is not reversible.')) {
                document.getElementById('completeForm-' + leaveId).submit();
            }
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.png" alt="Logo" class="logo">
        </div>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_panel.php">Manage Users</a>
   
        <a href="applications.php">Application</a>
        <a href="user_leave_details.php">User Leave Details</a>
        <a href="admin_panel.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>User Leave Details</h1>
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="user_leave_details.php" class="form-inline mb-4">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search by name or leave type" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Display Error Message -->
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <!-- Display Leave Details Table -->
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Middle Name</th>
                    <th>Surname</th>
                    <th>Leave Start Date</th>
                    <th>Leave End Date</th>
                    <th>Leave Type</th>
                    <th>Leave Days</th>
                    <th>Annual Leave Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leaveDetails)) { ?>
                    <?php foreach ($leaveDetails as $detail) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail['id']); ?></td>
                            <td><?php echo htmlspecialchars($detail['name']); ?></td>
                            <td><?php echo htmlspecialchars($detail['middle_name']); ?></td>
                            <td><?php echo htmlspecialchars($detail['surname']); ?></td>
                            <td><?php echo htmlspecialchars($detail['from_date']); ?></td>
                            <td><?php echo htmlspecialchars($detail['to_date']); ?></td>
                            <td><?php echo htmlspecialchars($detail['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($detail['leave_days']); ?></td>
                            <td><?php echo htmlspecialchars($detail['annual_leave_balance']); ?></td>
                            <td>
                                <form id="backToPendingForm-<?php echo $detail['id']; ?>" method="POST" action="user_leave_details.php">
                                    <input type="hidden" name="leave_id" value="<?php echo $detail['id']; ?>">
                                    <input type="hidden" name="back_to_pending" value="1">
                                    <button type="button" class="btn btn-warning" onclick="confirmAction(<?php echo $detail['id']; ?>, 'pending')">
                                        Back to Pending
                                    </button>
                                </form>
                                
                                <form id="completeForm-<?php echo $detail['id']; ?>" method="POST" action="user_leave_details.php">
                                    <input type="hidden" name="leave_id" value="<?php echo $detail['id']; ?>">
                                    <input type="hidden" name="complete_leave" value="1">
                                    <button type="button" class="btn btn-success mt-2" onclick="confirmAction(<?php echo $detail['id']; ?>, 'complete')">
                                        Complete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10" class="text-center">No approved leave applications found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>