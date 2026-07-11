<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit();
}
$id     = intval($_POST['id']     ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['pending','confirmed','cancelled'];
if (!in_array($status, $allowed)) { echo json_encode(['status'=>'error','message'=>'Invalid status.']); exit(); }
$stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
mysqli_stmt_bind_param($stmt, 'si', $status, $id);
echo json_encode(mysqli_stmt_execute($stmt)
    ? ['status'=>'success']
    : ['status'=>'error','message'=>'Update failed.']);
?>
