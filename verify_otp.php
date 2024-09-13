<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $username = $_SESSION['registration_username'] ?? '';
    
    if (empty($otp)) {
        $error = 'Please enter the OTP.';
    } else {
        $pdo = new PDO('mysql:host=localhost;dbname=user_auth', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT otp, expiration FROM user_otp WHERE username = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $error = 'Invalid OTP.';
        } else {
            $storedOtp = $result['otp'];
            $expiration = $result['expiration'];

            if ($otp === $storedOtp && new DateTime() < new DateTime($expiration)) {
                // OTP is valid, complete registration
                $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, $_SESSION['hashed_password'], 'user']);

                // Clean up OTP record
                $stmt = $pdo->prepare('DELETE FROM user_otp WHERE username = ?');
                $stmt->execute([$username]);

                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid or expired OTP.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
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
        .verify-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .verify-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <h1 class="verify-title">Verify OTP</h1>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post">
            <div class="form-group">
                <label for="otp">OTP:</label>
                <input type="text" class="form-control" id="otp" name="otp" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify OTP</button>
        </form>
    </div>
</body>
</html>