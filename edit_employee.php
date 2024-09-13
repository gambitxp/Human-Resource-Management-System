<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include the database connection
include 'connection.php';

$error = '';
$successMessage = '';

// Initialize form data
$employee_id = $name = $middle_name = $surname = $suffix = $username = $email = $phone_number = $address = '';
$department_id = $designation_id = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get employee details from POST request
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $designation_id = isset($_POST['designation_id']) ? (int)$_POST['designation_id'] : 0;
    $password = isset($_POST['password']) ? trim($_POST['password']) : ''; // Get the password if it's provided

    // Validate the input
    if (empty($name) || empty($username) || empty($email)) {
        $error = 'Name, Username, and Email are required.';
    } else {
        try {
            // Update employee details in the database
            $stmt = $pdo->prepare('
                UPDATE employees 
                SET name = :name, middle_name = :middle_name, surname = :surname,suffix = :suffix, username = :username, 
                    email = :email, phone_number = :phone_number, address = :address, 
                    department_id = :department_id, designation_id = :designation_id
                WHERE id = :id
            ');
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':middle_name', $middle_name);
            $stmt->bindParam(':surname', $surname);
            $stmt->bindParam(':suffix', $suffix);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':designation_id', $designation_id);
            $stmt->bindParam(':id', $employee_id);
            $stmt->execute();

            // Check if the password needs to be updated
            if (!empty($password)) {
                // Hash the new password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Update both username and password in the users table based on registration_id
                $stmt = $pdo->prepare('
                    UPDATE users 
                    SET username = :username, password = :password 
                    WHERE registration_id = :registration_id
                ');
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':registration_id', $employee_id);
                $stmt->execute();
            } else {
                // If no password is provided, only update the username
                $stmt = $pdo->prepare('
                    UPDATE users 
                    SET username = :username 
                    WHERE registration_id = :registration_id
                ');
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':registration_id', $employee_id);
                $stmt->execute();
            }

            $successMessage = 'Employee and user details updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Employee</h1>

        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if ($successMessage) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php } ?>

        <!-- Edit Employee Form -->
        <form method="post" action="edit_employee.php">
            <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name:</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>">
            </div>
            <div class="form-group">
                <label for="surname">Surname:</label>
                <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlspecialchars($surname); ?>">
            </div>
                        <div class="form-group">
                <label for="surname">Suffix:</label>
                <input type="text" class="form-control" id="suffix" name="suffix" value="<?php echo htmlspecialchars($suffix); ?>">
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
            <div class="form-group">
                <label for="department_id">Department:</label>
                <select class="form-control" id="department_id" name="department_id">
                    <?php foreach ($departments as $department) { ?>
                        <option value="<?php echo $department['id']; ?>" <?php echo ($department['id'] == $department_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="designation_id">Designation:</label>
                <select class="form-control" id="designation_id" name="designation_id">
                    <?php foreach ($designations as $designation) { ?>
                        <option value="<?php echo $designation['id']; ?>" <?php echo ($designation['id'] == $designation_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($designation['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Details</button>
            <a href="list_employees.php" class="btn btn-secondary">Back to List</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>