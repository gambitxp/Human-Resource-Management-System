<?php
session_start();

// Include the connection file
require_once 'connection.php';

$error = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    try {
        // Check if the email exists
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate a unique reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

            // Save the reset token and expiry time in the database
            $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?');
            $stmt->execute([$resetToken, $expiryTime, $email]);

            // Send the reset link to the user's email
            $resetLink = "http://yourdomain.com/reset_password.php?token=$resetToken";
            $subject = 'Password Reset Request';
            $message = "Hello, \n\nWe received a request to reset your password. You can reset it using the following link: \n\n$resetLink\n\nIf you did not request this, please ignore this email.";
            $headers = 'From: no-reply@yourdomain.com';

            if (mail($email, $subject, $message, $headers)) {
                $successMessage = 'A password reset link has been sent to your email.';
            } else {
                $error = 'Failed to send the password reset email. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-image: url('images/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #007bff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .reset-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .reset-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1 class="reset-title">Password Reset</h1>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <?php if (isset($successMessage)) { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php } ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Request Password Reset</button>
        </form>
        <p class="mt-3 text-center"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>