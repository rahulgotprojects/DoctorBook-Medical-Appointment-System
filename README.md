# DoctorBook — PHP Appointment System
## Setup Instructions

### Requirements
- PHP 7.4+ with `mail()` enabled (or use PHPMailer for real email)
- MySQL 5.7+
- Apache/Nginx (XAMPP / WAMP / MAMP locally)

---

### Step 1: Import Database
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Create a new database: `doctor_appointment`
3. Click **Import** and upload `database.sql`

---

### Step 2: Configure DB Connection
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL user
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'doctor_appointment');
```

---

### Step 3: Place Project
Copy the entire folder to your web server root:
- XAMPP: `C:/xampp/htdocs/doctor_appointment/`
- WAMP: `C:/wamp64/www/doctor_appointment/`

---

### Step 4: Open in Browser
```
http://localhost/doctor_appointment/
```

---

### File Structure
```
doctor_appointment/
├── index.php            ← Doctor profile + slot booking
├── login.php            ← Email + OTP authentication
├── book.php             ← Booking handler
├── my_appointments.php  ← User's appointments
├── logout.php           ← Logout
├── database.sql         ← MySQL schema + sample data
├── includes/
│   ├── db.php           ← Database connection
│   └── auth.php         ← Session + OTP helpers
├── css/
│   └── style.css        ← All styles
└── js/
    └── main.js          ← Booking modal JS
```

---

### Features Implemented
✅ Doctor profile page  
✅ Clinic selector (dropdown)  
✅ Date picker for appointment  
✅ Time slots — Available (white) / Booked (grey)  
✅ User login via Email + OTP  
✅ Book appointment (logged-in users only)  
✅ My Appointments page  
✅ Cancel appointment  
✅ MySQL: Doctors, Clinics, Slots, Users, Appointments tables  
✅ Responsive design  

---

### Email OTP
- For **localhost**, PHP's `mail()` may not work without an SMTP relay.
- Recommended: Use **PHPMailer** with Gmail SMTP.
- Install: `composer require phpmailer/phpmailer`
- Then replace the `sendOTPEmail()` function in `includes/auth.php` with PHPMailer code.

---

### Note
For testing OTP locally without email, you can **temporarily** add this line in `login.php` after OTP is generated:
```php
echo "<div style='background:yellow;padding:10px'>DEV OTP: $otp</div>";
```
Remove this before submitting!
