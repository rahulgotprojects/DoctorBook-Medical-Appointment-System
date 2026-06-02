-- Doctor Appointment Booking System
-- DB: MySQL

CREATE DATABASE IF NOT EXISTS doctor_appointment;
USE doctor_appointment;

-- Doctors Table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    degree VARCHAR(100) DEFAULT 'MBBS, MD',
    specialization VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'assets/doctor.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clinics Table
CREATE TABLE clinics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    clinic_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    first_visit_fee DECIMAL(10,2) DEFAULT 600.00,
    followup_fee DECIMAL(10,2) DEFAULT 300.00,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Slots Table
CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clinic_id INT NOT NULL,
    slot_time TIME NOT NULL,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
);

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    otp VARCHAR(10) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments Table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    clinic_id INT NOT NULL,
    slot_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    status ENUM('booked','cancelled') DEFAULT 'booked',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot_date (slot_id, appointment_date)
);

-- =====================
-- SAMPLE DATA
-- =====================

INSERT INTO doctors (name, degree, specialization) VALUES
('Dr. Rahul Yadav', 'MBBS, MD', 'Pediatrician');

INSERT INTO clinics (doctor_id, clinic_name, address, first_visit_fee, followup_fee) VALUES
(1, 'Radha Little Steps Pediatrics', 'Radha Little Steps Pediatrics clinic tata motors hatkesh Hatkesh Udhog Nagar, Konkan Division, Maharashtra, India, 401107', 600.00, 300.00);

INSERT INTO slots (clinic_id, slot_time) VALUES
(1, '18:15:00'),
(1, '18:30:00'),
(1, '18:45:00'),
(1, '19:00:00'),
(1, '19:15:00'),
(1, '19:30:00'),
(1, '19:45:00'),
(1, '20:00:00'),
(1, '20:15:00'),
(1, '20:30:00');
