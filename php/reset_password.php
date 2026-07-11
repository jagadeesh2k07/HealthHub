<?php
include '../db.php';
$action = $_POST['action'] ?? '';

if ($action === 'check_email') {
    $email = trim($_POST['email'] ?? '');
    $stmt  = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    echo json_encode(mysqli_stmt_num_rows($stmt) > 0
        ? ['status'=>'success']
        : ['status'=>'error','message'=>'No account found with this email.']);
    exit();
}

if ($action === 'verify_password') {
    $email   = trim($_POST['email'] ?? '');
    $current = $_POST['currentPassword'] ?? '';
    $stmt    = mysqli_prepare($conn, "SELECT password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    echo json_encode($row && password_verify($current, $row['password'])
        ? ['status'=>'success']
        : ['status'=>'error','message'=>'Incorrect current password.']);
    exit();
}

if ($action === 'reset') {
    $email   = trim($_POST['email'] ?? '');
    $newPw   = $_POST['newPassword'] ?? '';
    $hashed  = password_hash($newPw, PASSWORD_DEFAULT);
    $stmt    = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $hashed, $email);
    echo json_encode(mysqli_stmt_execute($stmt)
        ? ['status'=>'success']
        : ['status'=>'error','message'=>'Failed to update password.']);
    exit();
}
?>
