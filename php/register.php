<?php
session_start();
include '../db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name  = trim($_POST['lastName']  ?? '');
    $email      = trim($_POST['email']     ?? '');
    $phone      = trim($_POST['phone']     ?? '');
    $password   = $_POST['password']       ?? '';

    if (!$first_name || !$last_name || !$email || !$password) {
        echo json_encode(['status'=>'error','message'=>'All fields are required.']); exit();
    }
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, 's', $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) > 0) {
        echo json_encode(['status'=>'error','message'=>'Email already registered.']); exit();
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt   = mysqli_prepare($conn, "INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES (?,?,?,?,?,'patient')");
    mysqli_stmt_bind_param($stmt, 'sssss', $first_name, $last_name, $email, $hashed, $phone);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status'=>'success','message'=>'Account created successfully!']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Registration failed. Try again.']);
    }
}
?>