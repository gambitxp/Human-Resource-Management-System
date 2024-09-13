<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include the database connection
include 'connection.php';

// Get the employee ID from the URL
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($employee_id) {
    try {
        // Prepare and execute the delete query
        $stmt = $pdo->prepare('DELETE FROM employees WHERE id = :id');
        $stmt->bindParam(':id', $employee_id);
        $stmt->execute();

        $_SESSION['success'] = 'Employee deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'Invalid employee ID.';
}

header('Location: list_employees.php'); // Redirect back to the employee list
exit;