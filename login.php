<?php
session_start();

// Include the connection file
require_once 'connection.php';

// Initialize or reset failed login attempts in the session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Fetch user details from the database
        $stmt = $pdo->prepare('SELECT id, password, role, userlock, passwordlock, status_of_registration FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if the user's registration is pending
            if ($user['status_of_registration'] === 'pending') {
                $error = 'Your registration is pending approval. Please contact the admin to complete your registration.';
            } elseif ($user['userlock'] || $user['passwordlock']) {
                // Check if the user account is locked
                $error = 'Your account is locked due to too many failed login attempts. Please contact the admin to unlock your account.';
            } else {
                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Reset login attempts after successful login
                    $_SESSION['login_attempts'] = 0;

                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on user role
                    if ($user['role'] === 'admin') {
                        header('Location: admin_panel.php');
                    } elseif ($user['role'] === 'user') {
                        header('Location: user_panel.php');
                    } else {
                        // Handle unexpected roles
                        $_SESSION['logged_in'] = false; // Log out the user
                        $error = 'Unexpected role assigned. Please contact the administrator.';
                    }
                    exit;
                } else {
                    // Increment failed login attempts
                    $_SESSION['login_attempts']++;

                    // Check if the user has reached the maximum login attempts
                    if ($_SESSION['login_attempts'] >= 3) {
                        // Lock the user account
                        $stmt = $pdo->prepare('UPDATE users SET userlock = TRUE WHERE username = ?');
                        $stmt->execute([$username]);

                        $error = 'Too many failed login attempts. Your account has been locked. Please contact the admin for assistance.';
                    } else {
                        $remaining_attempts = 3 - $_SESSION['login_attempts'];
                        $error = "Invalid username or password. You have $remaining_attempts more attempt(s) before your account is locked.";
                    }
                }
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: admin_panel.php');
    } elseif ($role === 'user') {
        header('Location: user_panel.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-image: url('images/background.png'); /* Path to your background image */
            background-size: cover; /* Scale the image to cover the viewport */
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Prevent the image from repeating */
            background-color: #007bff; /* Fallback color in case the image does not cover the entire background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full viewport height */
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.8); /* Slightly transparent background to improve readability */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Login</h1>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="mt-3 text-center"><a href="register.php">Don't have an account? Register here.</a></p>
    </div>
</body>
</html>