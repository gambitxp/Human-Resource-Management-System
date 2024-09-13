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

// Initialize variables
$employees = [];
$error = '';
$successMessage = '';
$searchTerm = '';

// Include the database connection
include 'connection.php';

try {
    // Get search term from request
    if (isset($_POST['search'])) {
        $searchTerm = trim($_POST['search']);
    }

    // Fetch employee details with search functionality
    $query = '
        SELECT e.id, e.name, e.middle_name, e.surname, e.username, e.email, e.phone_number, e.address, 
               e.department_id, e.designation_id, d.name AS department_name, des.name AS designation_name
        FROM employees e
        JOIN departments d ON e.department_id = d.id
        JOIN designations des ON e.designation_id = des.id
        WHERE e.name LIKE :searchTerm 
           OR e.middle_name LIKE :searchTerm 
           OR e.surname LIKE :searchTerm 
           OR e.username LIKE :searchTerm 
           OR e.email LIKE :searchTerm
    ';

    $stmt = $pdo->prepare($query);
    $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle password update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
        $employee_id = (int)$_POST['employee_id'];
        $new_password = trim($_POST['new_password']);

        if (empty($new_password)) {
            $error = 'Password cannot be empty.';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the new password
                $stmt = $pdo->prepare('UPDATE employees SET password = :password WHERE id = :id');
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $employee_id);
                $stmt->execute();

                $successMessage = 'Password updated successfully!';
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }

   // Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Fetch the username from the employees table
        $stmt = $pdo->prepare('SELECT username FROM employees WHERE id = :id');
        $stmt->bindParam(':id', $delete_id);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            $username = $employee['username'];

            // Delete from the users table using the username
            $stmt = $pdo->prepare('DELETE FROM users WHERE username = :username');
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Delete from the employees table using the id
            $stmt = $pdo->prepare('DELETE FROM employees WHERE id = :id');
            $stmt->bindParam(':id', $delete_id);
            $stmt->execute();

            // Commit the transaction
            $pdo->commit();

            // Redirect after successful deletion
            header('Location: list_employees.php'); // Refresh the page
            exit;
        } else {
            // Handle the case where no employee was found
            $error = 'Employee not found.';
        }
    } catch (PDOException $e) {
        // Rollback the transaction if something went wrong
        $pdo->rollBack();
        $error = 'Database error: ' . $e->getMessage();
    }
}

    // Fetch departments and designations for dropdowns
    $departments = [];
    $designations = [];

    try {
        $deptStmt = $pdo->query('SELECT id, name FROM departments');
        $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        $desigStmt = $pdo->query('SELECT id, name FROM designations');
        $designations = $desigStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
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
    <title>List of Employees</title>
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

        @media print {
            body * {
                visibility: hidden;
            }
            .content, .content * {
                visibility: visible;
            }
            .content {
                margin-left: 0;
            }
            .content .btn, .form-group {
                display: none;
            }
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
        <a href="list_employees.php">List of Employees</a>
        <a href="user_leave_details.php">User Leave Details</a>
        <a href="list_employees.php?logout=true" class="text-danger">Logout</a>
    </div>
    <div class="content">
        <h1>List of Employees</h1>
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button onclick="printEmployees()" class="btn btn-primary">Print Employees</button>
        </div>
        <!-- Search Form -->
        <form method="post" class="mb-4">
            <div class="form-group">
                <label for="search">Search Employees:</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if ($successMessage) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php } ?>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Middle Name</th>
                    <th>Surname</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($employees)) { ?>
                    <?php $count = 1; ?>
                    <?php foreach ($employees as $employee) { ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['middle_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['surname']); ?></td>
                            <td><?php echo htmlspecialchars($employee['username']); ?></td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($employee['address']); ?></td>
                            <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['designation_name']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editPasswordModal<?php echo $employee['id']; ?>">Edit Password</button>
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#editEmployeeModal<?php echo $employee['id']; ?>">Edit Details</button>
                                <a href="list_employees.php?delete_id=<?php echo $employee['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="11" class="text-center">No employees found.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Password Modal -->
    <?php foreach ($employees as $employee) { ?>
        <div class="modal fade" id="editPasswordModal<?php echo $employee['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editPasswordModalLabel<?php echo $employee['id']; ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPasswordModalLabel<?php echo $employee['id']; ?>">Edit Password for <?php echo htmlspecialchars($employee['name']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="list_employees.php">
                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                            <div class="form-group">
                                <label for="new_password<?php echo $employee['id']; ?>">New Password:</label>
                                <input type="password" class="form-control" id="new_password<?php echo $employee['id']; ?>" name="new_password" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Edit Employee Details Modal -->
    <?php foreach ($employees as $employee) { ?>
        <div class="modal fade" id="editEmployeeModal<?php echo $employee['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editEmployeeModalLabel<?php echo $employee['id']; ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEmployeeModalLabel<?php echo $employee['id']; ?>">Edit Details for <?php echo htmlspecialchars($employee['name']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="edit_employee.php">
                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                            <div class="form-group">
                                <label for="name<?php echo $employee['id']; ?>">Name:</label>
                                <input type="text" class="form-control" id="name<?php echo $employee['id']; ?>" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                            </div>
                            <!-- Add other fields here as needed -->
                            <button type="submit" class="btn btn-primary">Update Details</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <script>
        function printEmployees() {
            window.print();
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>