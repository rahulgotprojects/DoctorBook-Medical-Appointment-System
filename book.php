<?php
// book.php — Handle appointment booking
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$slot_id   = (int)($_POST['slot_id'] ?? 0);
$clinic_id = (int)($_POST['clinic_id'] ?? 0);
$date = isset($_POST['appointment_date']) ? date('Y-m-d', strtotime($_POST['appointment_date'])) : '';
$doctor_id = (int)($_POST['doctor_id'] ?? 1);

// Validate date
if (!$slot_id || !$clinic_id || !$date) {
    header("Location: index.php?error=invalid");
    exit;
}

// Check if slot is already booked
$check = $conn->prepare("SELECT id FROM appointments WHERE slot_id=? AND appointment_date=? AND status='booked'");
$check->bind_param("is", $slot_id, $date);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?doctor_id=$doctor_id&clinic_id=$clinic_id&appointment_date=$date&error=slot_taken");
    exit;
}

// Book appointment
$stmt = $conn->prepare("INSERT INTO appointments (user_id, clinic_id, slot_id, appointment_date) VALUES (?,?,?,?)");
$stmt->bind_param("iiis", $user_id, $clinic_id, $slot_id, $date);

if ($stmt->execute()) {
    header("Location: my_appointments.php?success=1");
} else {
    header("Location: index.php?doctor_id=$doctor_id&error=db_error");
}
exit;
?>