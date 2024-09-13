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
$departments = [];
$designations = [];
$error = '';
$successMessage = '';

// Include database connection
include 'connection.php';

try {
    // Fetch departments
    $stmt = $pdo->query('SELECT id, name FROM departments');
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch designations
    $stmt = $pdo->query('SELECT id, name FROM designations');
    $designations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $middle_name = trim($_POST['middle_name']);
        $surname = trim($_POST['surname']);
        $suffix = trim($_POST['suffix']);
        $blood_type = trim($_POST['blood_type']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);
        $department_id = (int)$_POST['department_id'];
        $designation_id = (int)$_POST['designation_id'];

        // Validate inputs
        if (empty($name) || empty($surname) || empty($blood_type) || empty($email) || empty($phone_number) || empty($address) || empty($username) || empty($password) || empty($role) || $department_id <= 0 || $designation_id <= 0) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (!preg_match('/^[0-9]{11}$/', $phone_number)) {
            $error = 'Invalid phone number. It should be 10 digits.';
        } else {
            try {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Begin transaction
                $pdo->beginTransaction();
                
                // Insert new employee
                $stmt = $pdo->prepare('INSERT INTO employees (name, middle_name, surname, suffix, blood_type, email, phone_number, address, username, password, role, department_id, designation_id, login_date) VALUES (:name, :middle_name, :surname, :blood_type, :email, :phone_number, :address, :username, :password, :role, :department_id, :designation_id, NOW())');
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':middle_name', $middle_name);
                $stmt->bindParam(':surname', $surname);
                $stmt->bindParam(':suffix', $suffix);
                $stmt->bindParam(':blood_type', $blood_type);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':username', $username);          
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':department_id', $department_id);
                $stmt->bindParam(':designation_id', $designation_id);
                $stmt->execute();
                
                // Insert into users table
                $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':role', $role);
                $stmt->execute();
                
                // Commit transaction
                $pdo->commit();
                
                $successMessage = 'Employee added successfully!';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
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
    <title>Add Employee</title>
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
        <h1>Add Employee</h1>
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
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="surname">Surname</label>
                <input type="text" id="surname" name="surname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="surname">Suffix</label>
                <input type="text" id="suffix" name="suffix" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="blood_type">Blood Type</label>
                <input type="text" id="blood_type" name="blood_type" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div><div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role" class="form-control" required>
        <option value="">Select Role</option>
        <option value="admin">admin</option>
        <option value="user">user</option>
        <option value="approver">approver</option>
    </select>
</div>
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-control" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department) { ?>
                        <option value="<?php echo htmlspecialchars($department['id']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="designation_id">Designation</label>
                <select id="designation_id" name="designation_id" class="form-control" required>
                    <option value="">Select Designation</option>
                    <?php foreach ($designations as $designation) { ?>
                        <option value="<?php echo htmlspecialchars($designation['id']); ?>"><?php echo htmlspecialchars($designation['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Employee</button>
        </form>
    </div>
</body>
</html>