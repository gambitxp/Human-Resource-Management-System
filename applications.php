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

// Get sidebar color from session
$sidebarColor = isset($_SESSION['sidebar_color']) ? $_SESSION['sidebar_color'] : '#343a40';

// Include the database connection
require 'connection.php'; // Use the external connection file

$applications = [];
$error = '';

// Fetch leave applications
try {
    $stmt = $pdo->query("SELECT * FROM leave_applications WHERE status = 'pending'");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle approval or decline
    if (isset($_POST['action']) && isset($_POST['application_id'])) {
        $applicationId = intval($_POST['application_id']);
        $status = $_POST['action'] === 'approve' ? 'approved' : 'declined';

        // Update application status in the database
        $stmt = $pdo->prepare("UPDATE leave_applications SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $applicationId]);

        if ($status === 'approved') {
            // Fetch application details
            $stmt = $pdo->prepare("SELECT user_id, from_date, to_date FROM leave_applications WHERE id = :id");
            $stmt->execute([':id' => $applicationId]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                $fromDate = new DateTime($application['from_date']);
                $toDate = new DateTime($application['to_date']);
                $leaveDays = $toDate->diff($fromDate)->days + 1; // Add 1 to include both start and end dates
                $employeeId = intval($application['user_id']);
                
                // Update the employee's leave balance
                $stmt = $pdo->prepare("UPDATE employees SET annual_leave_balance = annual_leave_balance - :leaveDays WHERE id = :employeeId");
                $stmt->execute([':leaveDays' => $leaveDays, ':employeeId' => $employeeId]);
            }
        }

        // Redirect to the applications page to avoid form resubmission issues
        header('Location: applications.php');
        exit;
    }
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
    <title>Leave Applications</title>
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
        <a href="list_designations.php">List Designation</a>
        <a href="add_employee.php">Add Employee</a>
        <a href="list_employees.php">List of Employee</a>
        <a href="user_leave_details.php">User Leave Details</a>
        <a href="applications.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>Manage Leave Applications</h1>
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($applications)) { ?>
                    <?php foreach ($applications as $application) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($application['id']); ?></td>
                            <td><?php echo htmlspecialchars($application['name']); ?></td>
                            <td><?php echo htmlspecialchars($application['middle_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['surname']); ?></td>
                            <td><?php echo htmlspecialchars($application['from_date']); ?></td>
                            <td><?php echo htmlspecialchars($application['to_date']); ?></td>
                            <td><?php echo htmlspecialchars($application['status']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application['id']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                    <button type="submit" name="action" value="decline" class="btn btn-danger">Decline</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="text-center">No applications found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>