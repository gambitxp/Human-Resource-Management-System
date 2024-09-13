<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
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

$role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #007bff; /* Blue background color */
        }
        .container {
            margin-top: 20px;
        }
        .logout-button {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logout-button">
            <a href="index.php?logout=true" class="btn btn-danger">Logout</a>
        </div>
        <h1 class="mt-5">Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

        <div class="mt-4">
            <?php if ($role === 'admin') { ?>
                <a href="admin_panel.php" class="btn btn-primary">Go to Admin Panel</a>
            <?php } elseif ($role === 'user') { ?>
                <a href="user_panel.php" class="btn btn-primary">Go to User Panel</a>
            <?php } else { ?>
                <p>Your role is not recognized. Please contact the administrator.</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>