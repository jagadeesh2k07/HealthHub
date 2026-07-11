<?php
include '../db.php';
$email = trim($_POST['email'] ?? '');
$stmt  = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
echo json_encode(['taken' => mysqli_stmt_num_rows($stmt) > 0]);
?>
