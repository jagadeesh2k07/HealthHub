<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit();
}

$id   = intval($_POST['id'] ?? 0);
$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM appointments WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appt   = mysqli_fetch_assoc($result);

if (!$appt) {
    echo json_encode(['status'=>'error','message'=>'Appointment not found.']); exit();
}

if ($role === 'patient') {
    if ($appt['patient_id'] !== $uid) {
        echo json_encode(['status'=>'error','message'=>'Unauthorized.']); exit();
    }
    $apptDateTime = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
    $now          = time();
    $diff         = $apptDateTime - $now;

    if ($diff < 4 * 3600) {
        echo json_encode(['status'=>'error','message'=>'Cancellation not allowed within 4 hours of appointment.']); exit();
    }
}

$cancelledBy = $role;
$reason      = trim($_POST['reason'] ?? 'Cancelled by ' . $role);

$upd = mysqli_prepare($conn, "UPDATE appointments SET status='cancelled', cancelled_by=?, cancel_reason=? WHERE id=?");
mysqli_stmt_bind_param($upd, 'ssi', $cancelledBy, $reason, $id);

echo json_encode(mysqli_stmt_execute($upd)
    ? ['status'=>'success']
    : ['status'=>'error','message'=>'Failed to cancel.']);
?>
