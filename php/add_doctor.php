<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit();
}
$name  = trim($_POST['name']           ?? '');
$spec  = trim($_POST['specialization'] ?? '');
$exp   = intval($_POST['experience']   ?? 0);
$fee   = floatval($_POST['fee']        ?? 0);
$avail = trim($_POST['availability']   ?? '');
if (!$name||!$spec||!$exp||!$fee||!$avail) {
    echo json_encode(['status'=>'error','message'=>'All fields required.']); exit();
}
$stmt = mysqli_prepare($conn, "INSERT INTO doctors (name, specialization, experience, fee, availability) VALUES (?,?,?,?,?)");
mysqli_stmt_bind_param($stmt, 'ssidd', $name, $spec, $exp, $fee, $avail);
// fix: use correct types
$stmt2 = mysqli_prepare($conn, "INSERT INTO doctors (name, specialization, experience, fee, availability) VALUES (?,?,?,?,?)");
mysqli_stmt_bind_param($stmt2, 'ssdds', $name, $spec, $exp, $fee, $avail);
echo json_encode(mysqli_stmt_execute($stmt2)
    ? ['status'=>'success']
    : ['status'=>'error','message'=>'Failed to add doctor.']);
?>
