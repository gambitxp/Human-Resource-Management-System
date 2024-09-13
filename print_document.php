<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include the connection file
require_once 'connection.php';

try {
    // Get the ID from the URL
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Fetch the leave application details
    $stmt = $pdo->prepare('SELECT la.id, la.from_date, la.to_date, e.name, e.middle_name, e.surname
                           FROM leave_applications la
                           JOIN employees e ON e.username = la.username
                           WHERE la.id = ?');
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Leave application not found.');
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: auto;
        }
        .details {
            margin-bottom: 20px;
        }
        .details p {
            margin: 5px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
    <script>
        // Automatically trigger print dialog when the page is loaded
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <div class="container">
        <h1>Leave Application Report</h1>
        <div class="details">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($application['name']) . ' ' . htmlspecialchars($application['middle_name']) . ' ' . htmlspecialchars($application['surname']); ?></p>
            <p><strong>Leave Start Date:</strong> <?php echo htmlspecialchars($application['from_date']); ?></p>
            <p><strong>Leave End Date:</strong> <?php echo htmlspecialchars($application['to_date']); ?></p>
        </div>
        <!-- Optional: include a button for manual printing -->
        <button class="no-print" onclick="window.print();">Print Document</button>
    </div>
</body>
</html>