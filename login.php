<?php
// login.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$step    = $_POST['step'] ?? 'email';
$error   = '';
$success = '';
$email   = $_POST['email'] ?? '';
$name    = $_POST['name'] ?? '';

// ---- STEP 1: Submit email ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email') {
    $email = trim($_POST['email']);
    $name  = trim($_POST['name']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
        $step  = 'email';
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user && empty($name)) {
            $error = "New user? Please enter your name too.";
            $step  = 'email';
        } else {
            if (!$user) {
                $stmt2 = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                $stmt2->bind_param("ss", $name, $email);
                $stmt2->execute();
                $user_id   = $conn->insert_id;
                $user_name = $name;
            } else {
                $user_id   = $user['id'];
                $user_name = $user['name'];
            }

            // Generate OTP
            $otp_value = generateOTP();
            $expiry    = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt3     = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
            $stmt3->bind_param("ssi", $otp_value, $expiry, $user_id);
            $stmt3->execute();

            // Try email (suppressed — may fail on localhost)
            @sendOTPEmail($email, $otp_value, $user_name);

            
            $_SESSION['pending_user_id'] = $user_id;
            

            $success = "OTP generated for <strong>" . htmlspecialchars($email) . "</strong>.";
            $step    = 'otp';
        }
    }
}

// ---- STEP 2: Verify OTP ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'otp') {
    $otp_entered = trim($_POST['otp_code'] ?? '');
    $user_id     = $_SESSION['pending_user_id'] ?? 0;

    $stmt = $conn->prepare("SELECT id, name, otp, otp_expiry FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = "Session expired. Please try again.";
        $step  = 'email';
    } elseif ($user['otp'] !== $otp_entered) {
        $error = "Incorrect OTP. Please try again.";
        $step  = 'otp';
    } elseif (strtotime($user['otp_expiry']) < time()) {
        $error = "OTP has expired. Please request a new one.";
        $step  = 'email';
    } else {
        $conn->query("UPDATE users SET otp=NULL, otp_expiry=NULL, is_verified=1 WHERE id={$user['id']}");
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        unset($_SESSION['pending_user_id']);
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - DoctorBook</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-logo">🩺 DoctorBook</div>
    <h2><?= $step === 'otp' ? 'Enter OTP' : 'Login / Register' ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($step !== 'otp'): ?>
    <form method="POST" action="">
        <input type="hidden" name="step" value="email">
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="you@example.com"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="form-group">
            <label>Full Name <small>(required for new users)</small></label>
            <input type="text" name="name" placeholder="Your full name"
                   value="<?= htmlspecialchars($name) ?>">
        </div>
        <button type="submit" class="btn-primary btn-full">Send OTP →</button>
    </form>

    <?php else: ?>


    <p class="otp-info">Enter the 6-digit OTP below.</p>
    <form method="POST" action="">
        <input type="hidden" name="step" value="otp">
        <div class="form-group">
            <label>OTP</label>
            <input type="text" name="otp_code" placeholder="123456"
                   maxlength="6" class="otp-input" required autofocus>
        </div>
        <button type="submit" class="btn-primary btn-full">Verify OTP ✓</button>
    </form>
    <p class="resend-link"><a href="login.php">← Resend OTP</a></p>
    <?php endif; ?>

    <p class="back-link"><a href="index.php">← Back to Doctor Profile</a></p>
</div>

</body>
</html>