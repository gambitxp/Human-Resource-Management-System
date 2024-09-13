<?php
session_start();
require 'connection.php'; // Include the connection file

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $designation_id = $_POST['designation_id'] ?? '';

    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $error = 'Username already exists.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $error = 'Email is already registered.';
                } else {
                    // Insert into users table
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $registration_date = date('Y-m-d H:i:s'); // Capture current registration date
                    $stmt = $pdo->prepare('INSERT INTO users (username, password, email, role, status_of_registration, registration_date) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$username, $hashedPassword, $email, 'user', 'pending', $registration_date]);

                    // Retrieve the ID of the newly inserted user
                    $userId = $pdo->lastInsertId();

                    // Check if department_id and designation_id are valid
                    $stmt = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
                    $stmt->execute([$department_id]);
                    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                        $error = 'Invalid department ID.';
                    } else {
                        $stmt = $pdo->prepare('SELECT id FROM designations WHERE id = ?');
                        $stmt->execute([$designation_id]);
                        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                            $error = 'Invalid designation ID.';
                        } else {
                            // Insert into employees table
                            $stmt = $pdo->prepare('
                                INSERT INTO employees (
                                    name, middle_name, surname, department_id, designation_id, username, email, password, role
                                ) VALUES (
                                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                                )
                            ');
                            $stmt->execute([
                                $name, $middle_name, $surname, $department_id, $designation_id, $username, $email, $hashedPassword, 'user'
                            ]);

                            // Retrieve the ID of the newly inserted employee
                            $employeeId = $pdo->lastInsertId();

                            // Update users table with registration_id
                            $stmt = $pdo->prepare('UPDATE users SET registration_id = ? WHERE id = ?');
                            $stmt->execute([$employeeId, $userId]);

                            // Optionally, you can set a session variable or redirect
                            $_SESSION['registration_username'] = $username;
                            $_SESSION['registration_id'] = $employeeId; // Store the registration ID
                            header('Location: login.php');
                            exit;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch departments and designations
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

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #007bff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .register-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1 class="register-title">Register</h1>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post">
            <div class="form-group">
                <label for="name">First Name:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name:</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name">
            </div>
            <div class="form-group">
                <label for="surname">Surname:</label>
                <input type="text" class="form-control" id="surname" name="surname" required>
            </div>
            <div class="form-group">
                <label for="department_id">Department:</label>
                <select class="form-control" id="department_id" name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department) { ?>
                        <option value="<?php echo htmlspecialchars($department['id']); ?>">
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="designation_id">Designation:</label>
                <select class="form-control" id="designation_id" name="designation_id" required>
                    <option value="">Select Designation</option>
                    <?php foreach ($designations as $designation) { ?>
                        <option value="<?php echo htmlspecialchars($designation['id']); ?>">
                            <?php echo htmlspecialchars($designation['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="mt-3 text-center"><a href="login.php">Already have an account? Login here.</a></p>
    </div>
</body>
</html>