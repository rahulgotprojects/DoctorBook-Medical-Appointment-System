<?php
// my_appointments.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = isset($_GET['success']);

// Cancel appointment
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $appt_id = (int)$_GET['cancel'];
    $conn->query("UPDATE appointments SET status='cancelled' WHERE id=$appt_id AND user_id=$user_id");
    header("Location: my_appointments.php");
    exit;
}

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.*, d.name AS doctor_name, d.specialization,
           c.clinic_name, c.address,
           DATE_FORMAT(s.slot_time, '%h:%i %p') AS slot_time_fmt,
           DATE_FORMAT(a.appointment_date, '%d %b %Y') AS date_fmt
    FROM appointments a
    JOIN clinics c ON c.id = a.clinic_id
    JOIN doctors d ON d.id = c.doctor_id
    JOIN slots s ON s.id = a.slot_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, s.slot_time ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Appointments - DoctorBook</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <div class="logo"><a href="index.php">🩺 DoctorBook</a></div>
        <nav>
            <span class="user-name">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="logout.php" class="btn-nav btn-outline">Logout</a>
        </nav>
    </div>
</header>

<div class="container main-content">
    <h2 class="page-title">My Appointments</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Appointment booked successfully!</div>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <p>📅 You have no appointments yet.</p>
            <a href="index.php" class="btn-primary">Book an Appointment</a>
        </div>
    <?php else: ?>
    <div class="appointments-list">
        <?php foreach ($appointments as $a): ?>
        <div class="appt-card <?= $a['status'] === 'cancelled' ? 'cancelled' : '' ?>">
            <div class="appt-header">
                <div>
                    <h3><?= htmlspecialchars($a['doctor_name']) ?></h3>
                    <p class="appt-spec"><?= htmlspecialchars($a['specialization']) ?></p>
                </div>
                <span class="appt-status <?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
            </div>
            <div class="appt-body">
                <div class="appt-detail">
                    <span>🏥</span>
                    <div>
                        <strong><?= htmlspecialchars($a['clinic_name']) ?></strong>
                        <p><?= htmlspecialchars($a['address']) ?></p>
                    </div>
                </div>
                <div class="appt-detail">
                    <span>📅</span>
                    <span><?= $a['date_fmt'] ?></span>
                </div>
                <div class="appt-detail">
                    <span>⏰</span>
                    <span><?= $a['slot_time_fmt'] ?></span>
                </div>
            </div>
            <?php if ($a['status'] === 'booked' && strtotime($a['appointment_date']) >= strtotime('today')): ?>
            <div class="appt-actions">
                <a href="my_appointments.php?cancel=<?= $a['id'] ?>"
                   class="btn-cancel"
                   onclick="return confirm('Cancel this appointment?')">Cancel Appointment</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© <?= date('Y') ?> DoctorBook</p>
</footer>

</body>
</html>
