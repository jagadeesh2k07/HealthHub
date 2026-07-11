<?php
session_start();
include '../db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id, first_name, last_name, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['user_id']    = $row['id'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name']  = $row['last_name'];
        $_SESSION['role']       = $row['role'];

        $redirect = $row['role'] === 'admin' ? 'admin.php' : 'dashboard.php';
        echo json_encode(['status'=>'success','message'=>'Login successful!','redirect'=>$redirect]);
    } elseif (!$row) {
        echo json_encode(['status'=>'error','message'=>'No account found with this email.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Incorrect password.']);
    }
}
?>