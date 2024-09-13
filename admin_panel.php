<?php
session_start();

// Include the connection.php file for database connection
include 'connection.php';

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

// Handle role update
if (isset($_POST['update_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute([':role' => $newRole, ':id' => $userId]);
        
        $success = 'User role updated successfully!';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Handle approval and declination
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $action = $_GET['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare('UPDATE users SET status_of_registration = "approved" WHERE id = :id');
        } elseif ($action === 'pending') {
            $stmt = $pdo->prepare('UPDATE users SET status_of_registration = "pending" WHERE id = :id');
        }
        $stmt->execute([':id' => $userId]);
        
        $success = 'User registration status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $userId = $_POST['user_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    try {
        // Update password in both tables
        $stmt = $pdo->prepare('UPDATE users SET password = :password, userlock = 0, passwordlock = 0 WHERE id = :id');
        $stmt->execute([':password' => $newPassword, ':id' => $userId]);

        $stmt = $pdo->prepare('UPDATE employees SET password = :password WHERE id = :id');
        $stmt->execute([':password' => $newPassword, ':id' => $userId]);

        $success = 'Password updated successfully and account unlocked!';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get sidebar color from session
$sidebarColor = isset($_SESSION['sidebar_color']) ? $_SESSION['sidebar_color'] : '#343a40';

// Fetch users from the database
$users = [];
$error = '';
$success = '';
$showUnlockForm = false;

try {
    $stmt = $pdo->query('SELECT id, username, role, status_of_registration, userlock, passwordlock FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Show unlock form if needed
if (isset($_GET['unlock_id'])) {
    $unlockId = $_GET['unlock_id'];
    $showUnlockForm = true;

    // Fetch user details
    try {
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$unlockId]);
        $unlockUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
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
    <title>Admin Panel</title>
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
        .unlock-btn {
            background-color: #dc3545; /* Red background for unlock button */
            color: #fff;
            border: none;
        }
        .unlock-btn:hover {
            background-color: #c82333;
        }
    </style>
    <script>
        function confirmRoleChange(event) {
            var confirmation = confirm("Are you sure you want to change the role of this user?");
            if (!confirmation) {
                event.preventDefault(); // Prevent form submission if not confirmed
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
        <a href="recommend_applications.php">Recommend Application</a>
        <a href="add_department.php">Add Department</a>
        <a href="list_departments.php">List Department</a>
        <a href="designations.php">Designation</a>
        <a href="list_designations.php">List Designation</a>
        <a href="add_employee.php">Add Employee</a>
        <a href="list_employees.php">List of Employee</a>
        <a href="user_leave_details.php">User Leave Details</a>
        <a href="info.php">Developer Info</a>
        <a href="User_Info.php">User Leave and other Info</a>
        <a href="admin_panel.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>Manage Users</h1>
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>
        <?php if ($showUnlockForm && isset($unlockUser)) { ?>
            <h2>Unlock User</h2>
            <form method="post" action="admin_panel.php">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($unlockUser['id']); ?>">
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        <?php } ?>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status of Registration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) { ?>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <!-- Role select dropdown and Save button in the same column -->
                                <form method="post" action="admin_panel.php" class="form-inline" onsubmit="confirmRoleChange(event)">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <select name="role" class="form-control mr-2">
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>user</option>
                                        <option value="approver" <?php echo $user['role'] === 'approver' ? 'selected' : ''; ?>>approver</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-primary">Save</button>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars($user['status_of_registration']); ?></td>
                            <td>
                                <?php if ($user['userlock'] == 1 || $user['passwordlock'] == 1) { ?>
                                    <a href="admin_panel.php?unlock_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn unlock-btn btn-sm">Unlock</a>
                                <?php } else { ?>
                                    <a href="admin_panel.php?action=approve&user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-success btn-sm">Approve</a>
                                    <a href="admin_panel.php?action=pending&user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-danger btn-sm">Pending</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center">No users found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>