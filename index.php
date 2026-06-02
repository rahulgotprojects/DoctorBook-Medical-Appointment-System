<?php
// index.php — Doctor Profile & Appointment Booking
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Get doctor (default: first doctor, or by ID)
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;

$doctor = $conn->query("SELECT * FROM doctors WHERE id = $doctor_id")->fetch_assoc();
if (!$doctor) { die("Doctor not found."); }

// Get clinics for this doctor
$clinics = $conn->query("SELECT * FROM clinics WHERE doctor_id = $doctor_id")->fetch_all(MYSQLI_ASSOC);

// Selected clinic
$selected_clinic_id = isset($_GET['clinic_id']) ? (int)$_GET['clinic_id'] : ($clinics[0]['id'] ?? 0);
$selected_date = isset($_GET['appointment_date']) ? date('Y-m-d', strtotime($_GET['appointment_date'])) : date('Y-m-d');

// Get slots with booking status
$slots = [];
if ($selected_clinic_id) {
    $stmt = $conn->prepare("
        SELECT s.id, s.slot_time,
               IF(a.id IS NOT NULL, 1, 0) AS is_booked,
               a.user_id AS booked_by
        FROM slots s
        LEFT JOIN appointments a ON a.slot_id = s.id
            AND a.appointment_date = ?
            AND a.status = 'booked'
        WHERE s.clinic_id = ?
        ORDER BY s.slot_time ASC
    ");
    $stmt->bind_param("si", $selected_date, $selected_clinic_id);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get selected clinic details
$clinic = null;
foreach ($clinics as $c) {
    if ($c['id'] == $selected_clinic_id) { $clinic = $c; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($doctor['name']) ?> - Book Appointment</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container header-inner">
        <div class="logo">🩺 DoctorBook</div>
        <nav>
            <?php if (isLoggedIn()): ?>
                <span class="user-name">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="my_appointments.php" class="btn-nav">My Appointments</a>
                <a href="logout.php" class="btn-nav btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-nav">Login / Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container main-content">

    <!-- DOCTOR CARD -->
    <div class="doctor-card">
        <div class="doctor-info">
            <div class="doctor-avatar">
                <img src="css/doctor-icon.svg" alt="Doctor" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2235%22 r=%2225%22 fill=%22%234a90d9%22/><path d=%22M10 95 Q10 65 50 65 Q90 65 90 95%22 fill=%22%234a90d9%22/><rect x=%2242%22 y=%2268%22 width=%2216%22 height=%228%22 fill=%22white%22/><rect x=%2246%22 y=%2264%22 width=%228%22 height=%2216%22 fill=%22white%22/></svg>'">
            </div>
            <div class="doctor-details">
                <h1><?= htmlspecialchars($doctor['name']) ?></h1>
                <p class="degree"><?= htmlspecialchars($doctor['degree']) ?></p>
                <p class="specialization"><?= htmlspecialchars($doctor['specialization']) ?></p>
                <a href="#" class="btn-profile">View Profile</a>
            </div>
        </div>
        <div class="badge-inclinic">
            <span>🏥</span><br>In-clinic
        </div>
    </div>

    <!-- BOOKING SECTION -->
    <div class="booking-section">
        <div class="booking-header">
            <h2>Book Appointment</h2>
            <?php if ($clinic): ?>
            <div class="fee-info">
                <span class="fee-tag">First Visit Fees: ₹<?= number_format($clinic['first_visit_fee'], 0) ?> Pay at Clinic</span><br>
                <span class="fee-tag">Follow Up Fees: ₹<?= number_format($clinic['followup_fee'], 0) ?> Pay at Clinic</span>
            </div>
            <?php endif; ?>
        </div>

        <form method="GET" action="" class="booking-form">
            <!-- Clinic Select -->
            <div class="form-row">
                <label>Clinic Name</label>
                <div class="form-control-wrap">
                    <select name="clinic_id" onchange="this.form.submit()">
                        <?php foreach ($clinics as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selected_clinic_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['clinic_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($clinic): ?>
                    <p class="clinic-address"><?= htmlspecialchars($clinic['address']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Date -->
            <div class="form-row">
                <label>Appointment Date</label>
                <div class="form-control-wrap">
                    <input type="date" name="appointment_date"
                           value="<?= htmlspecialchars($selected_date) ?>"
                           min="<?= date('Y-m-d') ?>"
                           onchange="this.form.submit()">
                </div>
            </div>

            <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
        </form>

        <!-- Slots -->
        <div class="slots-section">
            <div class="slots-legend">
                <span class="legend-available">▬ Available</span>
                <span class="legend-booked">▬ Booked</span>
            </div>

            <?php if (empty($slots)): ?>
                <p class="no-slots">No slots available for this clinic.</p>
            <?php else: ?>
            <div class="slots-grid">
                <?php foreach ($slots as $slot):
                    $time_fmt = date('h:i A', strtotime($slot['slot_time']));
                    $is_booked = $slot['is_booked'];
                    $my_slot   = isLoggedIn() && $slot['booked_by'] == $_SESSION['user_id'];
                ?>
                    <?php if ($is_booked): ?>
                        <div class="slot booked" title="Booked"><?= $time_fmt ?></div>
                    <?php else: ?>
                        <button class="slot available"
                                onclick="confirmBooking(<?= $slot['id'] ?>, '<?= $time_fmt ?>', '<?= $selected_date ?>', <?= $selected_clinic_id ?>)">
                            <?= $time_fmt ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- BOOKING MODAL -->
<div id="bookingModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Confirm Appointment</h3>
        <p id="modal-details"></p>
        <?php if (!isLoggedIn()): ?>
            <p class="modal-note">⚠️ You must be logged in to book an appointment.</p>
            <a href="login.php" class="btn-primary">Login / Register</a>
            <button onclick="closeModal()" class="btn-secondary">Cancel</button>
        <?php else: ?>
            <form method="POST" action="book.php">
                <input type="hidden" name="slot_id" id="modal_slot_id">
                <input type="hidden" name="clinic_id" value="<?= $selected_clinic_id ?>">
                <input type="hidden" name="appointment_date" value="<?= $selected_date ?>">
                <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
                <button type="submit" class="btn-primary">✅ Confirm Booking</button>
                <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<footer class="site-footer">
    <p>© <?= date('Y') ?> DoctorBook — Online Appointment System</p>
</footer>

<script src="js/main.js"></script>
</body>
</html>