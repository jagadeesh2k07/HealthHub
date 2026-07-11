<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit();
}
$id   = intval($_POST['id'] ?? 0);
$stmt = mysqli_prepare($conn, "DELETE FROM doctors WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
echo json_encode(mysqli_stmt_execute($stmt)
    ? ['status'=>'success']
    : ['status'=>'error','message'=>'Delete failed.']);
?>
