<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include the database connection
include 'connection.php';

// Initialize variables
$error = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate form data
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $name = trim($_POST['name']);
    $middle_name = trim($_POST['middle_name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $designation_id = isset($_POST['designation_id']) ? (int)$_POST['designation_id'] : 0;

    // Validate input data
    if (empty($name) || empty($surname) || empty($email) || empty($phone_number) || empty($address)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Prepare and execute the update query
            $stmt = $pdo->prepare('
                UPDATE employees
                SET name = :name, middle_name = :middle_name, surname = :surname, email = :email, phone_number = :phone_number, address = :address, department_id = :department_id, designation_id = :designation_id
                WHERE id = :id
            ');
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':middle_name', $middle_name);
            $stmt->bindParam(':surname', $surname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':department_id', $department_id);
            $stmt->bindParam(':designation_id', $designation_id);
            $stmt->bindParam(':id', $employee_id);
            $stmt->execute();

            $successMessage = 'Employee details updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Redirect or render response
if ($error) {
    $_SESSION['error'] = $error;
} else {
    $_SESSION['success'] = $successMessage;
}

header('Location: list_employees.php'); // Redirect back to the employee list
exit;