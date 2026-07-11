<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Please login first.']); exit();
}

$patient_id = $_SESSION['user_id'];
$doctor_id  = intval($_POST['doctor_id']       ?? 0);
$date       = trim($_POST['appointment_date']  ?? '');
$time       = trim($_POST['appointment_time']  ?? '');

if (!$doctor_id || !$date || !$time) {
    echo json_encode(['status'=>'error','message'=>'All fields are required.']); exit();
}

$check = mysqli_prepare($conn, "SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status != 'cancelled'");
mysqli_stmt_bind_param($check, 'iss', $doctor_id, $date, $time);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    echo json_encode(['status'=>'error','message'=>'This slot is already booked. Please choose another time.']); exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?,?,?,?,'pending')");
mysqli_stmt_bind_param($stmt, 'iiss', $patient_id, $doctor_id, $date, $time);

echo json_encode(mysqli_stmt_execute($stmt)
    ? ['status'=>'success','message'=>'Appointment booked successfully!']
    : ['status'=>'error','message'=>'Booking failed. Try again.']);
?>
