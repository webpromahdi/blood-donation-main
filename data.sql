-- data.sql
-- Blood Donation System - Comprehensive Seed Data
-- Generated for Production-like Demo Environment
-- Compatible with blood_donation database schema

USE blood_donation;

-- =====================================================
-- CLEAR EXISTING DATA (Preserve blood_groups)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

-- Use DELETE instead of TRUNCATE to avoid foreign key constraint issues
DELETE FROM chat_conversations;
DELETE FROM chat_messages;
DELETE FROM notifications;
DELETE FROM certificates;
DELETE FROM appointments;
DELETE FROM donations;
DELETE FROM voluntary_donations;
DELETE FROM blood_requests;
DELETE FROM donor_health;
DELETE FROM donors;
DELETE FROM seekers;
DELETE FROM hospitals;
DELETE FROM announcements;
DELETE FROM users;

-- Reset auto-increment counters
ALTER TABLE chat_conversations AUTO_INCREMENT = 1;
ALTER TABLE chat_messages AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE certificates AUTO_INCREMENT = 1;
ALTER TABLE appointments AUTO_INCREMENT = 1;
ALTER TABLE donations AUTO_INCREMENT = 1;
ALTER TABLE voluntary_donations AUTO_INCREMENT = 1;
ALTER TABLE blood_requests AUTO_INCREMENT = 1;
ALTER TABLE donor_health AUTO_INCREMENT = 1;
ALTER TABLE donors AUTO_INCREMENT = 1;
ALTER TABLE seekers AUTO_INCREMENT = 1;
ALTER TABLE hospitals AUTO_INCREMENT = 1;
ALTER TABLE announcements AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USERS TABLE - All Roles (40 users)
-- Password for all: Password@123
-- BCrypt hash: $2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq
-- =====================================================
INSERT INTO users (id, email, password, name, phone, role, status, email_verified_at, created_at) VALUES
-- ADMINS (2)
(1, 'admin@bloodconnect.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Rafiqul Islam', '01711000001', 'admin', 'approved', NOW(), '2025-01-15 09:00:00'),
(2, 'superadmin@bloodconnect.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Kamal Hossain', '01711000002', 'admin', 'approved', NOW(), '2025-02-01 10:30:00'),

-- DONORS (20)
(3, 'farhan.ahmed@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Farhan Ahmed', '01712345001', 'donor', 'approved', NOW(), '2025-02-10 11:00:00'),
(4, 'nusrat.jahan@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Nusrat Jahan', '01712345002', 'donor', 'approved', NOW(), '2025-02-15 14:30:00'),
(5, 'tanvir.hasan@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Tanvir Hasan', '01812345003', 'donor', 'approved', NOW(), '2025-03-01 09:45:00'),
(6, 'ayesha.siddiqua@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Ayesha Siddiqua', '01912345004', 'donor', 'approved', NOW(), '2025-03-10 16:20:00'),
(7, 'mehedi.hasan@outlook.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Mehedi Hasan', '01612345005', 'donor', 'approved', NOW(), '2025-03-20 08:15:00'),
(8, 'rashida.begum@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Rashida Begum', '01512345006', 'donor', 'approved', NOW(), '2025-04-05 12:00:00'),
(9, 'shahin.alam@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Shahin Alam', '01712345007', 'donor', 'approved', NOW(), '2025-04-15 10:30:00'),
(10, 'farhana.islam@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Farhana Islam', '01812345008', 'donor', 'approved', NOW(), '2025-04-25 15:45:00'),
(11, 'imran.khan@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Imran Khan', '01912345009', 'donor', 'approved', NOW(), '2025-05-01 11:20:00'),
(12, 'sabina.yasmin@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Sabina Yasmin', '01612345010', 'donor', 'approved', NOW(), '2025-05-10 09:00:00'),
(13, 'rakib.hossain@outlook.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Rakib Hossain', '01512345011', 'donor', 'approved', NOW(), '2025-05-20 14:15:00'),
(14, 'nazma.akter@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Nazma Akter', '01712345012', 'donor', 'approved', NOW(), '2025-06-01 08:30:00'),
(15, 'arifur.rahman@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Arifur Rahman', '01812345013', 'donor', 'approved', NOW(), '2025-06-15 16:00:00'),
(16, 'moushumi.akter@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Moushumi Akter', '01912345014', 'donor', 'approved', NOW(), '2025-07-01 10:45:00'),
(17, 'kamrul.islam@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Kamrul Islam', '01612345015', 'donor', 'approved', NOW(), '2025-07-15 13:30:00'),
(18, 'sharmin.sultana@outlook.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Sharmin Sultana', '01512345016', 'donor', 'approved', NOW(), '2025-08-01 09:15:00'),
(19, 'zahid.hasan@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Zahid Hasan', '01712345017', 'donor', 'approved', NOW(), '2025-08-15 11:00:00'),
(20, 'rubina.khatun@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Rubina Khatun', '01812345018', 'donor', 'approved', NOW(), '2025-09-01 14:30:00'),
(21, 'nazmul.huda@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Nazmul Huda', '01912345019', 'donor', 'pending', NULL, '2025-12-20 10:00:00'),
(22, 'fatema.tuz@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Fatema Tuz Zohra', '01612345020', 'donor', 'pending', NULL, '2025-12-25 15:30:00'),

-- HOSPITALS (8)
(23, 'dhaka.medical@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Dhaka Medical College Hospital', '02-9661551', 'hospital', 'approved', NOW(), '2025-01-20 09:00:00'),
(24, 'ibnsina@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Ibn Sina Hospital Dhanmondi', '02-9116551', 'hospital', 'approved', NOW(), '2025-02-05 10:30:00'),
(25, 'square@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Square Hospital Ltd', '02-8159457', 'hospital', 'approved', NOW(), '2025-02-20 11:45:00'),
(26, 'apollo@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Apollo Hospitals Dhaka', '09666-787801', 'hospital', 'approved', NOW(), '2025-03-10 08:00:00'),
(27, 'united@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'United Hospital Limited', '02-8836000', 'hospital', 'approved', NOW(), '2025-04-01 09:30:00'),
(28, 'labaid@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Labaid Specialized Hospital', '09612-010101', 'hospital', 'approved', NOW(), '2025-05-15 14:00:00'),
(29, 'evercare@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Evercare Hospital Dhaka', '09643-443322', 'hospital', 'pending', NULL, '2025-11-01 10:00:00'),
(30, 'greenlife@hospital.bd', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Green Life Medical College Hospital', '02-8199400', 'hospital', 'pending', NULL, '2025-12-01 11:30:00'),

-- SEEKERS (10)
(31, 'karim.family@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Abdul Karim', '01711555001', 'seeker', 'approved', NOW(), '2025-03-01 10:00:00'),
(32, 'shafiq.ahmed@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Shafiqul Ahmed', '01711555002', 'seeker', 'approved', NOW(), '2025-03-15 11:30:00'),
(33, 'hasina.begum@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Hasina Begum', '01811555003', 'seeker', 'approved', NOW(), '2025-04-01 14:00:00'),
(34, 'jalal.uddin@outlook.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Jalal Uddin', '01911555004', 'seeker', 'approved', NOW(), '2025-05-01 09:15:00'),
(35, 'salma.khatun@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Salma Khatun', '01611555005', 'seeker', 'approved', NOW(), '2025-06-01 16:30:00'),
(36, 'nurul.amin@yahoo.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Nurul Amin', '01511555006', 'seeker', 'approved', NOW(), '2025-07-01 08:45:00'),
(37, 'monira.rahman@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Monira Rahman', '01711555007', 'seeker', 'approved', NOW(), '2025-08-01 12:00:00'),
(38, 'belal.hossain@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Belal Hossain', '01811555008', 'seeker', 'approved', NOW(), '2025-09-01 10:30:00'),
(39, 'sufia.akter@outlook.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Sufia Akter', '01911555009', 'seeker', 'approved', NOW(), '2025-10-01 15:00:00'),
(40, 'rafiq.mia@gmail.com', '$2y$10$D4GZRPX4eMQtUq7v8TgK0OJ5EKL3z7zVZL1xN5vH8WmB9yR6sC2Iq', 'Rafiq Mia', '01611555010', 'seeker', 'approved', NOW(), '2025-11-01 09:00:00');

-- =====================================================
-- DONORS TABLE (20 donors - user_id 3-22)
-- =====================================================
INSERT INTO donors (id, user_id, blood_group_id, age, weight, gender, city, address, is_available, total_donations, last_donation_date, next_eligible_date, created_at) VALUES
(1, 3, 1, 28, 72.50, 'male', 'Dhaka', 'House 45, Road 12, Dhanmondi, Dhaka-1209', TRUE, 8, '2025-09-15', '2025-12-15', '2025-02-10 11:00:00'),
(2, 4, 3, 25, 58.00, 'female', 'Dhaka', 'Flat 5B, Greenview Apartment, Gulshan-1', TRUE, 5, '2025-10-20', '2026-01-20', '2025-02-15 14:30:00'),
(3, 5, 7, 32, 78.00, 'male', 'Chittagong', 'House 23, GEC Circle, Chittagong', TRUE, 12, '2025-08-10', '2025-11-10', '2025-03-01 09:45:00'),
(4, 6, 5, 24, 55.50, 'female', 'Dhaka', '120/A, Mirpur-10, Dhaka-1216', TRUE, 3, '2025-11-05', '2026-02-05', '2025-03-10 16:20:00'),
(5, 7, 2, 35, 82.00, 'male', 'Sylhet', 'Subhanighat Road, Sylhet-3100', TRUE, 15, '2025-07-25', '2025-10-25', '2025-03-20 08:15:00'),
(6, 8, 8, 29, 62.00, 'female', 'Dhaka', 'House 78, Uttara Sector-7, Dhaka-1230', TRUE, 6, '2025-06-18', '2025-09-18', '2025-04-05 12:00:00'),
(7, 9, 4, 27, 70.00, 'male', 'Rajshahi', '45 Boalia Road, Rajshahi-6100', TRUE, 4, '2025-12-01', '2026-03-01', '2025-04-15 10:30:00'),
(8, 10, 6, 31, 65.00, 'female', 'Khulna', 'Shibbari More, Khulna-9100', TRUE, 7, '2025-05-30', '2025-08-30', '2025-04-25 15:45:00'),
(9, 11, 1, 26, 75.50, 'male', 'Dhaka', 'Tejgaon Industrial Area, Dhaka-1208', TRUE, 9, '2025-10-10', '2026-01-10', '2025-05-01 11:20:00'),
(10, 12, 7, 33, 60.00, 'female', 'Comilla', 'Kandirpar, Comilla-3500', TRUE, 2, '2025-11-25', '2026-02-25', '2025-05-10 09:00:00'),
(11, 13, 3, 30, 80.00, 'male', 'Dhaka', 'Bashundhara R/A, Block F, Dhaka', TRUE, 11, '2025-04-15', '2025-07-15', '2025-05-20 14:15:00'),
(12, 14, 5, 22, 52.00, 'female', 'Gazipur', 'Tongi, Gazipur-1710', TRUE, 1, '2025-12-10', '2026-03-10', '2025-06-01 08:30:00'),
(13, 15, 2, 38, 85.00, 'male', 'Narayanganj', 'Fatullah, Narayanganj-1400', TRUE, 18, '2025-09-05', '2025-12-05', '2025-06-15 16:00:00'),
(14, 16, 8, 23, 57.00, 'female', 'Dhaka', 'Mohammadpur, Dhaka-1207', FALSE, 0, NULL, NULL, '2025-07-01 10:45:00'),
(15, 17, 4, 29, 73.00, 'male', 'Barisal', 'Sadar Road, Barisal-8200', TRUE, 6, '2025-08-20', '2025-11-20', '2025-07-15 13:30:00'),
(16, 18, 6, 27, 59.00, 'female', 'Dhaka', 'Banani DOHS, Dhaka-1206', TRUE, 4, '2025-07-10', '2025-10-10', '2025-08-01 09:15:00'),
(17, 19, 1, 34, 77.00, 'male', 'Rangpur', 'Dhap Road, Rangpur-5400', TRUE, 10, '2025-11-15', '2026-02-15', '2025-08-15 11:00:00'),
(18, 20, 7, 26, 54.50, 'female', 'Mymensingh', 'Town Hall Road, Mymensingh-2200', TRUE, 3, '2025-10-25', '2026-01-25', '2025-09-01 14:30:00'),
(19, 21, 3, 25, 68.00, 'male', 'Dhaka', 'Farmgate, Dhaka-1215', TRUE, 0, NULL, NULL, '2025-12-20 10:00:00'),
(20, 22, 5, 28, 61.00, 'female', 'Bogra', 'Sherpur Road, Bogra-5800', TRUE, 0, NULL, NULL, '2025-12-25 15:30:00');

-- =====================================================
-- DONOR_HEALTH TABLE (Health records for all donors)
-- =====================================================
INSERT INTO donor_health (id, donor_id, height, blood_pressure_systolic, blood_pressure_diastolic, hemoglobin, has_diabetes, has_hypertension, has_heart_disease, has_blood_disorders, has_infectious_disease, has_asthma, has_allergies, has_recent_surgery, is_on_medication, smoking_status, alcohol_consumption, exercise_frequency, medications, allergies_details, last_medical_checkup, additional_notes, created_at) VALUES
(1, 1, 175.00, 120, 80, 14.50, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-08-15', 'Healthy donor, regular blood donor', '2025-02-10 11:00:00'),
(2, 2, 162.00, 115, 75, 12.80, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE, FALSE, FALSE, 'no', 'none', 'daily', NULL, 'Mild dust allergy', '2025-09-20', 'Good health, exercises regularly', '2025-02-15 14:30:00'),
(3, 3, 180.00, 125, 82, 15.20, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'occasionally', 'occasionally', 'weekly', NULL, NULL, '2025-07-10', 'Veteran donor with excellent record', '2025-03-01 09:45:00'),
(4, 4, 158.00, 110, 72, 12.50, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'daily', NULL, NULL, '2025-10-05', 'Young healthy donor', '2025-03-10 16:20:00'),
(5, 5, 178.00, 130, 85, 14.80, FALSE, TRUE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE, 'no', 'none', 'weekly', 'Amlodipine 5mg', NULL, '2025-06-25', 'Controlled hypertension, cleared for donation', '2025-03-20 08:15:00'),
(6, 6, 165.00, 118, 78, 13.20, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'rarely', NULL, NULL, '2025-05-18', 'O- Universal donor', '2025-04-05 12:00:00'),
(7, 7, 172.00, 122, 80, 14.00, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-11-01', 'Regular donor from Rajshahi', '2025-04-15 10:30:00'),
(8, 8, 160.00, 112, 74, 12.90, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'rarely', NULL, NULL, '2025-04-30', 'AB- rare blood type donor', '2025-04-25 15:45:00'),
(9, 9, 176.00, 118, 76, 15.00, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'daily', NULL, NULL, '2025-09-10', 'Active donor, works in Tejgaon', '2025-05-01 11:20:00'),
(10, 10, 157.00, 115, 75, 12.60, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-10-25', 'Healthy female donor', '2025-05-10 09:00:00'),
(11, 11, 182.00, 128, 84, 14.70, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'occasionally', 'occasionally', 'rarely', NULL, NULL, '2025-03-15', 'Long-time donor since 2020', '2025-05-20 14:15:00'),
(12, 12, 155.00, 108, 70, 12.30, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-11-10', 'Young first-time donor', '2025-06-01 08:30:00'),
(13, 13, 179.00, 135, 88, 15.50, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-08-05', 'Most experienced donor in system', '2025-06-15 16:00:00'),
(14, 14, 163.00, 110, 72, 11.80, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, 'no', 'none', 'rarely', 'Inhaler for asthma', 'Peanut allergy', '2025-06-01', 'Currently not available due to medication', '2025-07-01 10:45:00'),
(15, 15, 174.00, 120, 78, 14.20, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-07-20', 'Active donor from Barisal', '2025-07-15 13:30:00'),
(16, 16, 161.00, 114, 74, 13.00, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'daily', NULL, NULL, '2025-06-10', 'Fitness enthusiast donor', '2025-08-01 09:15:00'),
(17, 17, 177.00, 122, 80, 14.60, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-10-15', 'Reliable donor from Rangpur', '2025-08-15 11:00:00'),
(18, 18, 159.00, 112, 73, 12.70, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-09-25', 'Young healthy donor', '2025-09-01 14:30:00'),
(19, 19, 173.00, 118, 76, 14.10, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'rarely', NULL, NULL, '2025-11-20', 'New donor, pending first donation', '2025-12-20 10:00:00'),
(20, 20, 164.00, 116, 75, 12.90, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'no', 'none', 'weekly', NULL, NULL, '2025-12-01', 'New donor from Bogra', '2025-12-25 15:30:00');

-- =====================================================
-- HOSPITALS TABLE (8 hospitals - user_id 23-30)
-- =====================================================
INSERT INTO hospitals (id, user_id, registration_number, hospital_type, address, city, state, pincode, website, contact_person, operating_hours, license_expiry_date, has_blood_bank, total_requests, created_at) VALUES
(1, 23, 'DMCH-2020-001', 'government', 'Secretariat Road, Ramna', 'Dhaka', 'Dhaka Division', '1000', 'https://www.dmch.gov.bd', 'Dr. Mohammad Saiful Islam', '24/7', '2027-12-31', TRUE, 45, '2025-01-20 09:00:00'),
(2, 24, 'ISH-2019-045', 'private', 'House 48, Road 9/A, Dhanmondi', 'Dhaka', 'Dhaka Division', '1209', 'https://www.ibnsinahospital.com', 'Dr. Fatema Akhter', '08:00-22:00', '2026-06-30', TRUE, 32, '2025-02-05 10:30:00'),
(3, 25, 'SQH-2018-112', 'private', '18/F Bir Uttam Qazi Nuruzzaman Sarak, West Panthapath', 'Dhaka', 'Dhaka Division', '1205', 'https://www.squarehospital.com', 'Dr. Khandaker Ashraf Hossain', '24/7', '2027-03-15', TRUE, 28, '2025-02-20 11:45:00'),
(4, 26, 'APL-2017-089', 'private', 'Plot 81, Block E, Bashundhara R/A', 'Dhaka', 'Dhaka Division', '1229', 'https://www.apollodhaka.com', 'Dr. Shamima Nasrin', '24/7', '2026-09-30', TRUE, 22, '2025-03-10 08:00:00'),
(5, 27, 'UHL-2016-156', 'private', 'Plot 15, Road 71, Gulshan-2', 'Dhaka', 'Dhaka Division', '1212', 'https://www.uhlbd.com', 'Dr. Rezaul Karim', '24/7', '2026-12-31', TRUE, 18, '2025-04-01 09:30:00'),
(6, 28, 'LBD-2020-078', 'private', 'House 1, Road 4, Dhanmondi', 'Dhaka', 'Dhaka Division', '1205', 'https://www.labaidgroup.com', 'Dr. Shirin Akhter Banu', '08:00-23:00', '2027-06-30', TRUE, 15, '2025-05-15 14:00:00'),
(7, 29, 'EVR-2021-201', 'private', 'Plot 81, Block E, Bashundhara R/A', 'Dhaka', 'Dhaka Division', '1229', 'https://www.evercarebd.com', 'Dr. Nahid Sultana', '24/7', '2028-01-31', TRUE, 0, '2025-11-01 10:00:00'),
(8, 30, 'GRL-2021-189', 'private', '32, Bir Uttam K.M. Shafiullah Sarak, Green Road', 'Dhaka', 'Dhaka Division', '1205', 'https://www.greenlife.com.bd', 'Dr. Anwar Hossain', '24/7', '2027-08-31', FALSE, 0, '2025-12-01 11:30:00');

-- =====================================================
-- SEEKERS TABLE (10 seekers - user_id 31-40)
-- =====================================================
INSERT INTO seekers (id, user_id, city, address, total_requests, created_at) VALUES
(1, 31, 'Dhaka', 'House 12, Road 5, Banani, Dhaka-1213', 5, '2025-03-01 10:00:00'),
(2, 32, 'Dhaka', 'Flat 8C, Paradise Apartments, Uttara', 3, '2025-03-15 11:30:00'),
(3, 33, 'Chittagong', '45 Station Road, Chittagong-4000', 4, '2025-04-01 14:00:00'),
(4, 34, 'Sylhet', 'Zindabazar, Sylhet-3100', 2, '2025-05-01 09:15:00'),
(5, 35, 'Dhaka', '90/B Mirpur-11, Dhaka-1216', 6, '2025-06-01 16:30:00'),
(6, 36, 'Rajshahi', 'New Market Area, Rajshahi-6000', 2, '2025-07-01 08:45:00'),
(7, 37, 'Dhaka', 'House 34, Mohammadpur, Dhaka-1207', 3, '2025-08-01 12:00:00'),
(8, 38, 'Khulna', 'Daulatpur, Khulna-9100', 1, '2025-09-01 10:30:00'),
(9, 39, 'Dhaka', 'Green Road, Farmgate, Dhaka-1205', 4, '2025-10-01 15:00:00'),
(10, 40, 'Narayanganj', 'Chashara, Narayanganj-1400', 2, '2025-11-01 09:00:00');

-- =====================================================
-- BLOOD_REQUESTS TABLE (30 requests with varied statuses)
-- =====================================================
INSERT INTO blood_requests (id, request_code, requester_id, requester_type, patient_name, patient_age, contact_phone, contact_email, blood_group_id, quantity, units_fulfilled, hospital_id, hospital_name, city, required_date, medical_reason, urgency, status, admin_id, approved_at, rejected_at, rejection_reason, created_at) VALUES
-- COMPLETED REQUESTS (10)
(1, 'REQ-2025-0001', 31, 'seeker', 'Rahim Uddin', 55, '01711555001', 'karim.family@gmail.com', 1, 2, 2, 1, 'Dhaka Medical College Hospital', 'Dhaka', '2025-03-15', 'Heart surgery - bypass operation', 'emergency', 'completed', 1, '2025-03-10 10:00:00', NULL, NULL, '2025-03-08 09:00:00'),
(2, 'REQ-2025-0002', 23, 'hospital', 'Fatema Begum', 42, '01711123456', 'dmch@hospital.bd', 3, 3, 3, 1, 'Dhaka Medical College Hospital', 'Dhaka', '2025-03-25', 'Accident trauma - multiple injuries', 'emergency', 'completed', 1, '2025-03-20 14:00:00', NULL, NULL, '2025-03-18 11:30:00'),
(3, 'REQ-2025-0003', 32, 'seeker', 'Jamal Hossain', 38, '01711555002', 'shafiq.ahmed@yahoo.com', 7, 2, 2, 2, 'Ibn Sina Hospital Dhanmondi', 'Dhaka', '2025-04-10', 'Kidney transplant surgery', 'normal', 'completed', 1, '2025-04-05 09:30:00', NULL, NULL, '2025-04-01 16:00:00'),
(4, 'REQ-2025-0004', 24, 'hospital', 'Sakina Khatun', 28, '01811234567', 'ibnsina@hospital.bd', 5, 2, 2, 2, 'Ibn Sina Hospital Dhanmondi', 'Dhaka', '2025-05-01', 'Childbirth complications - caesarean', 'emergency', 'completed', 2, '2025-04-28 08:00:00', NULL, NULL, '2025-04-26 15:30:00'),
(5, 'REQ-2025-0005', 33, 'seeker', 'Kabir Ahmed', 65, '01811555003', 'hasina.begum@gmail.com', 2, 1, 1, 3, 'Square Hospital Ltd', 'Dhaka', '2025-05-20', 'Cancer treatment - chemotherapy', 'normal', 'completed', 1, '2025-05-15 11:00:00', NULL, NULL, '2025-05-12 10:00:00'),
(6, 'REQ-2025-0006', 25, 'hospital', 'Rasheda Sultana', 50, '01911345678', 'square@hospital.bd', 8, 2, 2, 3, 'Square Hospital Ltd', 'Dhaka', '2025-06-15', 'Liver cirrhosis treatment', 'normal', 'completed', 2, '2025-06-10 14:30:00', NULL, NULL, '2025-06-05 09:15:00'),
(7, 'REQ-2025-0007', 34, 'seeker', 'Selim Reza', 45, '01911555004', 'jalal.uddin@outlook.com', 4, 3, 3, 4, 'Apollo Hospitals Dhaka', 'Dhaka', '2025-07-01', 'Major abdominal surgery', 'emergency', 'completed', 1, '2025-06-28 10:00:00', NULL, NULL, '2025-06-25 14:00:00'),
(8, 'REQ-2025-0008', 35, 'seeker', 'Anwara Begum', 60, '01611555005', 'salma.khatun@gmail.com', 6, 1, 1, 5, 'United Hospital Limited', 'Dhaka', '2025-07-20', 'Anemia treatment', 'normal', 'completed', 1, '2025-07-15 09:00:00', NULL, NULL, '2025-07-10 11:30:00'),
(9, 'REQ-2025-0009', 26, 'hospital', 'Mizanur Rahman', 35, '01611456789', 'apollo@hospital.bd', 1, 2, 2, 4, 'Apollo Hospitals Dhaka', 'Dhaka', '2025-08-10', 'Accident - internal bleeding', 'emergency', 'completed', 2, '2025-08-05 16:00:00', NULL, NULL, '2025-08-02 08:30:00'),
(10, 'REQ-2025-0010', 36, 'seeker', 'Khadija Akter', 32, '01511555006', 'nurul.amin@yahoo.com', 7, 2, 2, 6, 'Labaid Specialized Hospital', 'Dhaka', '2025-08-25', 'Pregnancy complications', 'normal', 'completed', 1, '2025-08-20 13:00:00', NULL, NULL, '2025-08-15 10:45:00'),

-- IN_PROGRESS REQUESTS (8)
(11, 'REQ-2025-0011', 37, 'seeker', 'Aminul Islam', 48, '01711555007', 'monira.rahman@gmail.com', 3, 2, 1, 1, 'Dhaka Medical College Hospital', 'Dhaka', '2025-12-28', 'Heart valve replacement', 'emergency', 'in_progress', 1, '2025-12-20 09:00:00', NULL, NULL, '2025-12-18 14:30:00'),
(12, 'REQ-2025-0012', 27, 'hospital', 'Shamima Akter', 29, '01511567890', 'united@hospital.bd', 5, 3, 1, 5, 'United Hospital Limited', 'Dhaka', '2025-12-30', 'Severe dengue - platelet transfusion', 'emergency', 'in_progress', 2, '2025-12-22 11:00:00', NULL, NULL, '2025-12-20 16:00:00'),
(13, 'REQ-2025-0013', 38, 'seeker', 'Habibur Rahman', 52, '01811555008', 'belal.hossain@gmail.com', 2, 2, 0, 2, 'Ibn Sina Hospital Dhanmondi', 'Dhaka', '2026-01-05', 'Stomach ulcer surgery', 'normal', 'in_progress', 1, '2025-12-28 10:30:00', NULL, NULL, '2025-12-25 09:00:00'),
(14, 'REQ-2025-0014', 28, 'hospital', 'Nazrul Haque', 41, '01711678901', 'labaid@hospital.bd', 8, 1, 0, 6, 'Labaid Specialized Hospital', 'Dhaka', '2026-01-10', 'Bone marrow transplant', 'emergency', 'in_progress', 1, '2025-12-30 14:00:00', NULL, NULL, '2025-12-28 11:15:00'),
(15, 'REQ-2025-0015', 39, 'seeker', 'Roksana Parvin', 36, '01911555009', 'sufia.akter@outlook.com', 1, 2, 1, 3, 'Square Hospital Ltd', 'Dhaka', '2026-01-15', 'Appendix surgery', 'normal', 'in_progress', 2, '2026-01-02 09:45:00', NULL, NULL, '2025-12-30 15:30:00'),
(16, 'REQ-2025-0016', 23, 'hospital', 'Faruk Hossain', 58, '01811789012', 'dhaka.medical@hospital.bd', 4, 2, 1, 1, 'Dhaka Medical College Hospital', 'Dhaka', '2026-01-18', 'Prostate surgery', 'normal', 'in_progress', 1, '2026-01-05 11:30:00', NULL, NULL, '2026-01-02 10:00:00'),
(17, 'REQ-2025-0017', 40, 'seeker', 'Bilkis Begum', 44, '01611555010', 'rafiq.mia@gmail.com', 6, 1, 0, 4, 'Apollo Hospitals Dhaka', 'Dhaka', '2026-01-20', 'Thyroid surgery', 'normal', 'in_progress', 2, '2026-01-08 14:15:00', NULL, NULL, '2026-01-05 09:30:00'),
(18, 'REQ-2025-0018', 24, 'hospital', 'Mokbul Hossain', 62, '01911890123', 'ibnsina@hospital.bd', 7, 3, 0, 2, 'Ibn Sina Hospital Dhanmondi', 'Dhaka', '2026-01-22', 'Hip replacement surgery', 'normal', 'in_progress', 1, '2026-01-10 10:00:00', NULL, NULL, '2026-01-08 16:45:00'),

-- APPROVED/PENDING REQUESTS (6)
(19, 'REQ-2025-0019', 31, 'seeker', 'Shamsul Haq', 70, '01711555001', 'karim.family@gmail.com', 3, 2, 0, 1, 'Dhaka Medical College Hospital', 'Dhaka', '2026-01-28', 'Coronary artery bypass', 'emergency', 'approved', 1, '2026-01-15 09:00:00', NULL, NULL, '2026-01-12 11:00:00'),
(20, 'REQ-2025-0020', 25, 'hospital', 'Joleka Begum', 33, '01611901234', 'square@hospital.bd', 1, 1, 0, 3, 'Square Hospital Ltd', 'Dhaka', '2026-01-30', 'Gallbladder removal', 'normal', 'approved', 2, '2026-01-18 14:30:00', NULL, NULL, '2026-01-15 10:15:00'),
(21, 'REQ-2025-0021', 32, 'seeker', 'Rafiqul Alam', 47, '01711555002', 'shafiq.ahmed@yahoo.com', 5, 2, 0, 5, 'United Hospital Limited', 'Dhaka', '2026-02-01', 'Spinal surgery', 'normal', 'pending', NULL, NULL, NULL, NULL, '2026-01-18 15:00:00'),
(22, 'REQ-2025-0022', 33, 'seeker', 'Morjina Khatun', 56, '01811555003', 'hasina.begum@gmail.com', 2, 1, 0, 2, 'Ibn Sina Hospital Dhanmondi', 'Chittagong', '2026-02-05', 'Cataract surgery', 'normal', 'pending', NULL, NULL, NULL, NULL, '2026-01-20 09:30:00'),
(23, 'REQ-2025-0023', 26, 'hospital', 'Kamrul Hassan', 39, '01511012345', 'apollo@hospital.bd', 8, 2, 0, 4, 'Apollo Hospitals Dhaka', 'Dhaka', '2026-02-08', 'Kidney stone removal', 'normal', 'pending', NULL, NULL, NULL, NULL, '2026-01-22 11:45:00'),
(24, 'REQ-2025-0024', 34, 'seeker', 'Sumaiya Islam', 25, '01911555004', 'jalal.uddin@outlook.com', 4, 1, 0, 6, 'Labaid Specialized Hospital', 'Sylhet', '2026-02-10', 'Tonsil removal surgery', 'normal', 'pending', NULL, NULL, NULL, NULL, '2026-01-23 14:00:00'),

-- REJECTED REQUESTS (3)
(25, 'REQ-2025-0025', 35, 'seeker', 'Azizul Haque', 80, '01611555005', 'salma.khatun@gmail.com', 6, 5, 0, NULL, NULL, 'Dhaka', '2025-09-01', 'General surgery', 'normal', 'rejected', 1, NULL, '2025-08-28 10:00:00', 'Patient age exceeds safe limit for blood transfusion quantity requested', '2025-08-25 09:00:00'),
(26, 'REQ-2025-0026', 36, 'seeker', 'Unknown Patient', 0, '01711000000', 'nurul.amin@yahoo.com', 1, 1, 0, NULL, NULL, 'Rajshahi', '2025-10-15', 'Not specified', 'normal', 'rejected', 2, NULL, '2025-10-12 14:30:00', 'Incomplete patient information - please resubmit with proper details', '2025-10-10 11:00:00'),
(27, 'REQ-2025-0027', 37, 'seeker', 'Halima Akter', 45, '01811111111', 'monira.rahman@gmail.com', 7, 10, 0, NULL, NULL, 'Dhaka', '2025-11-20', 'Surgery', 'normal', 'rejected', 1, NULL, '2025-11-18 09:00:00', 'Requested quantity exceeds maximum allowed per single request', '2025-11-15 16:00:00'),

-- CANCELLED REQUESTS (3)
(28, 'REQ-2025-0028', 38, 'seeker', 'Farida Yasmin', 37, '01811555008', 'belal.hossain@gmail.com', 3, 2, 0, 3, 'Square Hospital Ltd', 'Dhaka', '2025-09-25', 'Hysterectomy', 'normal', 'cancelled', 1, '2025-09-20 10:00:00', NULL, NULL, '2025-09-18 14:30:00'),
(29, 'REQ-2025-0029', 39, 'seeker', 'Masud Rana', 42, '01911555009', 'sufia.akter@outlook.com', 5, 1, 0, 4, 'Apollo Hospitals Dhaka', 'Dhaka', '2025-10-30', 'Minor surgery', 'normal', 'cancelled', 2, '2025-10-25 09:30:00', NULL, NULL, '2025-10-22 11:15:00'),
(30, 'REQ-2025-0030', 40, 'seeker', 'Laila Begum', 51, '01611555010', 'rafiq.mia@gmail.com', 2, 2, 0, 5, 'United Hospital Limited', 'Dhaka', '2025-12-05', 'Tumor removal', 'normal', 'cancelled', 1, '2025-12-01 14:00:00', NULL, NULL, '2025-11-28 10:00:00');

-- =====================================================
-- DONATIONS TABLE (20 donations covering full lifecycle)
-- =====================================================
INSERT INTO donations (id, request_id, donor_id, status, quantity, accepted_at, started_at, reached_at, completed_at, cancelled_at, cancel_reason, created_at) VALUES
-- COMPLETED DONATIONS (12)
(1, 1, 1, 'completed', 1, '2025-03-10 11:00:00', '2025-03-14 08:00:00', '2025-03-14 09:30:00', '2025-03-14 11:00:00', NULL, NULL, '2025-03-10 11:00:00'),
(2, 1, 9, 'completed', 1, '2025-03-10 12:00:00', '2025-03-14 08:30:00', '2025-03-14 10:00:00', '2025-03-14 11:30:00', NULL, NULL, '2025-03-10 12:00:00'),
(3, 2, 2, 'completed', 1, '2025-03-20 15:00:00', '2025-03-24 09:00:00', '2025-03-24 10:30:00', '2025-03-24 12:00:00', NULL, NULL, '2025-03-20 15:00:00'),
(4, 2, 11, 'completed', 2, '2025-03-20 16:00:00', '2025-03-24 09:30:00', '2025-03-24 11:00:00', '2025-03-24 13:00:00', NULL, NULL, '2025-03-20 16:00:00'),
(5, 3, 3, 'completed', 2, '2025-04-05 10:30:00', '2025-04-09 10:00:00', '2025-04-09 11:30:00', '2025-04-09 13:00:00', NULL, NULL, '2025-04-05 10:30:00'),
(6, 4, 4, 'completed', 2, '2025-04-28 09:00:00', '2025-04-30 08:00:00', '2025-04-30 09:30:00', '2025-04-30 11:00:00', NULL, NULL, '2025-04-28 09:00:00'),
(7, 5, 5, 'completed', 1, '2025-05-15 12:00:00', '2025-05-19 09:00:00', '2025-05-19 10:30:00', '2025-05-19 12:00:00', NULL, NULL, '2025-05-15 12:00:00'),
(8, 6, 6, 'completed', 2, '2025-06-10 15:30:00', '2025-06-14 08:30:00', '2025-06-14 10:00:00', '2025-06-14 12:00:00', NULL, NULL, '2025-06-10 15:30:00'),
(9, 7, 7, 'completed', 2, '2025-06-28 11:00:00', '2025-06-30 09:00:00', '2025-06-30 10:30:00', '2025-06-30 12:30:00', NULL, NULL, '2025-06-28 11:00:00'),
(10, 7, 15, 'completed', 1, '2025-06-28 12:00:00', '2025-06-30 10:00:00', '2025-06-30 11:30:00', '2025-06-30 13:00:00', NULL, NULL, '2025-06-28 12:00:00'),
(11, 8, 8, 'completed', 1, '2025-07-15 10:00:00', '2025-07-19 09:00:00', '2025-07-19 10:30:00', '2025-07-19 12:00:00', NULL, NULL, '2025-07-15 10:00:00'),
(12, 10, 3, 'completed', 2, '2025-08-20 14:00:00', '2025-08-24 09:30:00', '2025-08-24 11:00:00', '2025-08-24 13:00:00', NULL, NULL, '2025-08-20 14:00:00'),

-- ON THE WAY / REACHED DONATIONS (4)
(13, 11, 11, 'reached', 1, '2025-12-20 10:00:00', '2025-12-27 08:00:00', '2025-12-27 09:30:00', NULL, NULL, NULL, '2025-12-20 10:00:00'),
(14, 12, 4, 'on_the_way', 1, '2025-12-22 12:00:00', '2025-12-29 08:30:00', NULL, NULL, NULL, NULL, '2025-12-22 12:00:00'),
(15, 15, 1, 'reached', 1, '2026-01-02 10:45:00', '2026-01-14 09:00:00', '2026-01-14 10:30:00', NULL, NULL, NULL, '2026-01-02 10:45:00'),
(16, 16, 15, 'on_the_way', 1, '2026-01-05 12:30:00', '2026-01-17 09:00:00', NULL, NULL, NULL, NULL, '2026-01-05 12:30:00'),

-- ACCEPTED DONATIONS (2)
(17, 9, 17, 'completed', 2, '2025-08-05 17:00:00', '2025-08-09 08:00:00', '2025-08-09 09:30:00', '2025-08-09 11:00:00', NULL, NULL, '2025-08-05 17:00:00'),
(18, 19, 2, 'accepted', 1, '2026-01-15 10:00:00', NULL, NULL, NULL, NULL, NULL, '2026-01-15 10:00:00'),

-- CANCELLED DONATIONS (2)
(19, 3, 10, 'cancelled', 1, '2025-04-05 11:30:00', '2025-04-08 09:00:00', NULL, NULL, '2025-04-08 10:00:00', 'Family emergency - unable to proceed', '2025-04-05 11:30:00'),
(20, 28, 11, 'cancelled', 1, '2025-09-20 11:00:00', NULL, NULL, NULL, '2025-09-22 09:00:00', 'Request cancelled by seeker', '2025-09-20 11:00:00');

-- =====================================================
-- APPOINTMENTS TABLE (15 appointments)
-- =====================================================
INSERT INTO appointments (id, donation_id, hospital_id, donor_id, appointment_date, appointment_time, notes, status, created_at) VALUES
(1, 1, 1, 1, '2025-03-14', '09:00:00', 'Regular donation appointment', 'completed', '2025-03-10 11:00:00'),
(2, 2, 1, 9, '2025-03-14', '09:30:00', 'First time at this hospital', 'completed', '2025-03-10 12:00:00'),
(3, 3, 1, 2, '2025-03-24', '10:00:00', 'B+ blood donation', 'completed', '2025-03-20 15:00:00'),
(4, 5, 2, 3, '2025-04-09', '10:30:00', 'O+ donation for kidney transplant', 'completed', '2025-04-05 10:30:00'),
(5, 6, 2, 4, '2025-04-30', '08:30:00', 'Emergency donation - AB+', 'completed', '2025-04-28 09:00:00'),
(6, 7, 3, 5, '2025-05-19', '09:30:00', 'A- negative donation', 'completed', '2025-05-15 12:00:00'),
(7, 8, 3, 6, '2025-06-14', '09:00:00', 'Universal donor - O-', 'completed', '2025-06-10 15:30:00'),
(8, 9, 4, 7, '2025-06-30', '09:30:00', 'B- donation for surgery', 'completed', '2025-06-28 11:00:00'),
(9, 11, 5, 8, '2025-07-19', '10:00:00', 'AB- rare blood type', 'completed', '2025-07-15 10:00:00'),
(10, 12, 6, 3, '2025-08-24', '10:00:00', 'Second donation this year', 'completed', '2025-08-20 14:00:00'),
(11, 13, 1, 11, '2025-12-27', '09:00:00', 'Emergency heart surgery support', 'confirmed', '2025-12-20 10:00:00'),
(12, 14, 5, 4, '2025-12-29', '09:00:00', 'Dengue emergency transfusion', 'scheduled', '2025-12-22 12:00:00'),
(13, 15, 3, 1, '2026-01-14', '09:30:00', 'Appendix surgery support', 'confirmed', '2026-01-02 10:45:00'),
(14, 16, 1, 15, '2026-01-17', '10:00:00', 'Prostate surgery - B- blood', 'scheduled', '2026-01-05 12:30:00'),
(15, 18, 1, 2, '2026-01-27', '10:30:00', 'Coronary bypass support', 'scheduled', '2026-01-15 10:00:00');

-- =====================================================
-- CERTIFICATES TABLE (12 certificates for completed donations)
-- =====================================================
INSERT INTO certificates (id, certificate_code, donation_id, donor_id, donor_name, blood_group, donation_date, hospital_name, quantity, issued_at, downloaded_at) VALUES
(1, 'CERT-2025-00001', 1, 1, 'Farhan Ahmed', 'A+', '2025-03-14', 'Dhaka Medical College Hospital', 1, '2025-03-14 12:00:00', '2025-03-14 18:30:00'),
(2, 'CERT-2025-00002', 2, 9, 'Imran Khan', 'A+', '2025-03-14', 'Dhaka Medical College Hospital', 1, '2025-03-14 12:30:00', '2025-03-15 10:00:00'),
(3, 'CERT-2025-00003', 3, 2, 'Nusrat Jahan', 'B+', '2025-03-24', 'Dhaka Medical College Hospital', 1, '2025-03-24 13:00:00', '2025-03-25 09:15:00'),
(4, 'CERT-2025-00004', 4, 11, 'Rakib Hossain', 'B+', '2025-03-24', 'Dhaka Medical College Hospital', 2, '2025-03-24 14:00:00', NULL),
(5, 'CERT-2025-00005', 5, 3, 'Tanvir Hasan', 'O+', '2025-04-09', 'Ibn Sina Hospital Dhanmondi', 2, '2025-04-09 14:00:00', '2025-04-10 11:30:00'),
(6, 'CERT-2025-00006', 6, 4, 'Ayesha Siddiqua', 'AB+', '2025-04-30', 'Ibn Sina Hospital Dhanmondi', 2, '2025-04-30 12:00:00', '2025-05-01 09:45:00'),
(7, 'CERT-2025-00007', 7, 5, 'Mehedi Hasan', 'A-', '2025-05-19', 'Square Hospital Ltd', 1, '2025-05-19 13:00:00', '2025-05-20 14:00:00'),
(8, 'CERT-2025-00008', 8, 6, 'Rashida Begum', 'O-', '2025-06-14', 'Square Hospital Ltd', 2, '2025-06-14 13:00:00', '2025-06-15 10:30:00'),
(9, 'CERT-2025-00009', 9, 7, 'Shahin Alam', 'B-', '2025-06-30', 'Apollo Hospitals Dhaka', 2, '2025-06-30 13:30:00', NULL),
(10, 'CERT-2025-00010', 10, 15, 'Kamrul Islam', 'B-', '2025-06-30', 'Apollo Hospitals Dhaka', 1, '2025-06-30 14:00:00', '2025-07-01 16:00:00'),
(11, 'CERT-2025-00011', 11, 8, 'Farhana Islam', 'AB-', '2025-07-19', 'United Hospital Limited', 1, '2025-07-19 13:00:00', '2025-07-20 11:15:00'),
(12, 'CERT-2025-00012', 12, 3, 'Tanvir Hasan', 'O+', '2025-08-24', 'Labaid Specialized Hospital', 2, '2025-08-24 14:00:00', '2025-08-25 09:00:00');

-- =====================================================
-- VOLUNTARY_DONATIONS TABLE (12 voluntary donations)
-- =====================================================
INSERT INTO voluntary_donations (id, donor_id, blood_group_id, city, availability_date, preferred_time, notes, status, approved_by_admin_id, approved_at, rejected_at, rejection_reason, hospital_id, scheduled_date, scheduled_time, created_at) VALUES
-- COMPLETED (4)
(1, 1, 1, 'Dhaka', '2025-06-15', 'morning', 'Regular voluntary donation - available all day', 'completed', 1, '2025-06-10 10:00:00', NULL, NULL, 1, '2025-06-15', '09:00:00', '2025-06-01 09:00:00'),
(2, 3, 7, 'Chittagong', '2025-07-20', 'afternoon', 'Would like to donate at Chittagong hospital', 'completed', 1, '2025-07-15 11:00:00', NULL, NULL, 2, '2025-07-20', '14:00:00', '2025-07-05 14:30:00'),
(3, 5, 2, 'Dhaka', '2025-08-10', 'morning', 'A- blood available for emergency reserve', 'completed', 2, '2025-08-05 09:30:00', NULL, NULL, 3, '2025-08-10', '10:30:00', '2025-07-25 10:00:00'),
(4, 13, 2, 'Dhaka', '2025-09-05', 'evening', 'Experienced donor - happy to help anytime', 'completed', 1, '2025-09-01 14:00:00', NULL, NULL, 4, '2025-09-05', '17:00:00', '2025-08-20 16:00:00'),

-- SCHEDULED (3)
(5, 9, 1, 'Dhaka', '2026-01-20', 'morning', 'Available for donation after work', 'scheduled', 1, '2026-01-10 10:00:00', NULL, NULL, 1, '2026-01-20', '09:30:00', '2025-12-28 11:00:00'),
(6, 17, 1, 'Rangpur', '2026-01-25', 'afternoon', 'Will travel to Dhaka if needed', 'scheduled', 2, '2026-01-15 11:30:00', NULL, NULL, 2, '2026-01-25', '14:30:00', '2026-01-02 09:00:00'),
(7, 2, 3, 'Dhaka', '2026-01-28', 'any', 'Flexible timing - call anytime', 'scheduled', 1, '2026-01-18 09:00:00', NULL, NULL, 5, '2026-01-28', '10:00:00', '2026-01-05 14:00:00'),

-- APPROVED (2)
(8, 11, 3, 'Dhaka', '2026-02-05', 'morning', 'B+ blood for emergency reserve', 'approved', 1, '2026-01-20 10:00:00', NULL, NULL, NULL, NULL, NULL, '2026-01-10 11:30:00'),
(9, 18, 7, 'Mymensingh', '2026-02-10', 'afternoon', 'O+ universal donor available', 'approved', 2, '2026-01-22 14:00:00', NULL, NULL, NULL, NULL, NULL, '2026-01-15 16:00:00'),

-- PENDING (2)
(10, 7, 4, 'Rajshahi', '2026-02-15', 'morning', 'First time voluntary donation', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-18 09:45:00'),
(11, 12, 5, 'Gazipur', '2026-02-20', 'any', 'AB+ available - rare blood type', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-20 10:30:00'),

-- REJECTED (1)
(12, 14, 8, 'Dhaka', '2025-10-15', 'morning', 'Want to donate blood', 'rejected', 1, NULL, '2025-10-12 09:00:00', 'Currently on medication - please apply after completing treatment', NULL, NULL, NULL, '2025-10-01 11:00:00');

-- =====================================================
-- ANNOUNCEMENTS TABLE (10 announcements)
-- =====================================================
INSERT INTO announcements (id, title, message, target_audience, priority, scheduled_at, expires_at, status, admin_id, created_at) VALUES
(1, 'Welcome to BloodConnect Bangladesh', 'We are thrilled to launch BloodConnect, Bangladesh''s premier blood donation management system. Our mission is to connect donors with those in need, making blood donation more accessible and efficient across the country. Together, we can save lives!', 'all', 'high', NULL, NULL, 'published', 1, '2025-01-15 10:00:00'),
(2, 'Emergency Blood Drive - Dhaka Medical College', 'We are organizing an emergency blood drive at Dhaka Medical College Hospital on February 15, 2025. All blood types are needed, especially O- and AB-. Please register through the app to participate. Refreshments will be provided to all donors.', 'donors', 'urgent', NULL, '2025-02-16 23:59:59', 'published', 1, '2025-02-01 09:00:00'),
(3, 'New Hospital Registration Guidelines', 'Attention all hospitals: We have updated our registration guidelines effective March 1, 2025. Please ensure your registration documents are up to date and submit any pending verifications through the portal. Contact admin@bloodconnect.bd for assistance.', 'hospitals', 'normal', NULL, NULL, 'published', 2, '2025-02-20 14:00:00'),
(4, 'Ramadan Blood Donation Campaign 2025', 'As Ramadan approaches, blood reserves typically decrease. We encourage all eligible donors to donate before and after fasting hours. Special donation camps will be arranged at major hospitals during Iftar timings. Check your eligibility status in the app.', 'donors', 'high', '2025-03-01 00:00:00', '2025-04-01 23:59:59', 'published', 1, '2025-02-25 11:30:00'),
(5, 'System Maintenance Notice', 'The BloodConnect system will undergo scheduled maintenance on March 15, 2025, from 2:00 AM to 5:00 AM BST. During this time, some features may be temporarily unavailable. We apologize for any inconvenience and thank you for your patience.', 'all', 'normal', '2025-03-14 12:00:00', '2025-03-16 00:00:00', 'published', 2, '2025-03-10 16:00:00'),
(6, 'World Blood Donor Day 2025', 'Join us in celebrating World Blood Donor Day on June 14, 2025! This year''s theme is "Thank you, blood donors!" Special certificates and recognition will be given to our most active donors. Events will be held at all partner hospitals.', 'all', 'high', '2025-06-01 00:00:00', '2025-06-30 23:59:59', 'published', 1, '2025-05-15 10:00:00'),
(7, 'Updated Eligibility Criteria for Donors', 'Important update: We have revised the donor eligibility criteria in accordance with WHO guidelines. Key changes include updated hemoglobin requirements and modified waiting periods. Please review the updated health questionnaire before your next donation.', 'donors', 'normal', NULL, NULL, 'published', 1, '2025-07-01 09:00:00'),
(8, 'Hospital Blood Stock Reporting Reminder', 'All partner hospitals are reminded to update their blood stock levels weekly through the BloodConnect portal. Accurate reporting helps us efficiently match donors with hospitals in need. Reports are due every Sunday by 6:00 PM.', 'hospitals', 'normal', NULL, NULL, 'published', 2, '2025-08-15 14:30:00'),
(9, 'Dengue Season Alert - Urgent Need for Platelets', 'With the dengue season upon us, there is an urgent need for platelet donations. Hospitals are experiencing a surge in dengue cases. If you are eligible, please consider donating. Your single donation can save up to 3 lives!', 'all', 'urgent', NULL, '2025-12-31 23:59:59', 'published', 1, '2025-09-01 08:00:00'),
(10, 'New Year Donation Drive 2026', 'Start the new year with a gift of life! Join our New Year Donation Drive from January 1-31, 2026. All donors participating will receive special recognition certificates and exclusive BloodConnect merchandise. Register now!', 'donors', 'high', '2026-01-01 00:00:00', '2026-01-31 23:59:59', 'published', 1, '2025-12-20 10:00:00');

-- =====================================================
-- NOTIFICATIONS TABLE (60 notifications for all roles)
-- =====================================================
INSERT INTO notifications (id, user_id, title, message, type, related_type, related_id, is_read, read_at, created_at) VALUES
-- ADMIN NOTIFICATIONS (10)
(1, 1, 'New Hospital Registration', 'Evercare Hospital Dhaka has submitted a registration request. Please review and approve.', 'info', 'hospital', 7, TRUE, '2025-11-02 09:00:00', '2025-11-01 10:00:00'),
(2, 1, 'Emergency Blood Request', 'Emergency request REQ-2025-0011 requires immediate attention - Heart valve replacement surgery.', 'warning', 'blood_request', 11, TRUE, '2025-12-18 15:00:00', '2025-12-18 14:30:00'),
(3, 1, 'New Donor Registration', 'Nazmul Huda has registered as a new donor. Pending approval.', 'info', 'user', 21, TRUE, '2025-12-20 11:00:00', '2025-12-20 10:00:00'),
(4, 1, 'Voluntary Donation Request', 'Shahin Alam submitted a voluntary donation request for Rajshahi area.', 'info', 'voluntary_donation', 10, FALSE, NULL, '2026-01-18 09:45:00'),
(5, 2, 'Weekly Report Ready', 'The weekly donation report for January 2026 is ready for review.', 'info', NULL, NULL, TRUE, '2026-01-08 10:00:00', '2026-01-08 08:00:00'),
(6, 2, 'New Hospital Registration', 'Green Life Medical College Hospital has submitted a registration request.', 'info', 'hospital', 8, FALSE, NULL, '2025-12-01 11:30:00'),
(7, 1, 'System Alert', 'Blood stock levels are critically low for O- blood type across partner hospitals.', 'warning', NULL, NULL, TRUE, '2026-01-15 09:00:00', '2026-01-15 08:00:00'),
(8, 2, 'Donation Milestone', 'Tanvir Hasan has completed 12 donations - eligible for Gold Donor certificate.', 'success', 'donor', 3, TRUE, '2025-08-25 10:00:00', '2025-08-24 14:00:00'),
(9, 1, 'Pending Approvals', 'You have 3 pending blood requests awaiting approval.', 'info', NULL, NULL, FALSE, NULL, '2026-01-22 09:00:00'),
(10, 1, 'Monthly Statistics', 'December 2025 statistics: 45 donations completed, 28 requests fulfilled.', 'info', NULL, NULL, TRUE, '2026-01-02 09:00:00', '2026-01-01 08:00:00'),

-- DONOR NOTIFICATIONS (25)
(11, 3, 'Welcome to BloodConnect', 'Thank you for registering as a blood donor. Your profile has been approved. Start saving lives today!', 'success', NULL, NULL, TRUE, '2025-02-10 12:00:00', '2025-02-10 11:00:00'),
(12, 3, 'Donation Request Match', 'Your blood type A+ matches request REQ-2025-0001. Can you help save a life?', 'request', 'blood_request', 1, TRUE, '2025-03-10 10:30:00', '2025-03-10 10:00:00'),
(13, 3, 'Donation Completed', 'Thank you for your donation! You have helped save a life. Certificate generated.', 'success', 'donation', 1, TRUE, '2025-03-14 12:30:00', '2025-03-14 12:00:00'),
(14, 3, 'Achievement Unlocked', 'Congratulations! You''ve completed 8 donations. You''re a Silver Donor!', 'success', NULL, NULL, TRUE, '2025-09-15 18:00:00', '2025-09-15 17:00:00'),
(15, 4, 'Welcome to BloodConnect', 'Welcome Nusrat! Your donor profile is now active. Check eligibility before donating.', 'success', NULL, NULL, TRUE, '2025-02-15 15:00:00', '2025-02-15 14:30:00'),
(16, 4, 'Eligibility Restored', 'Good news! You are now eligible to donate blood again. Your next eligible date has passed.', 'info', NULL, NULL, TRUE, '2026-01-21 08:00:00', '2026-01-20 08:00:00'),
(17, 5, 'Donation Request Match', 'Emergency request in Chittagong needs O+ blood. Can you respond?', 'request', 'blood_request', 3, TRUE, '2025-04-05 09:30:00', '2025-04-05 09:00:00'),
(18, 5, 'Donation Completed', 'Your donation for REQ-2025-0003 has been completed successfully. Thank you!', 'success', 'donation', 5, TRUE, '2025-04-09 14:00:00', '2025-04-09 13:30:00'),
(19, 6, 'Rare Blood Type Alert', 'As an O- donor, your blood is urgently needed. Consider donating when eligible.', 'warning', NULL, NULL, TRUE, '2025-05-01 10:00:00', '2025-05-01 09:00:00'),
(20, 7, 'Appointment Reminder', 'Reminder: You have a donation appointment tomorrow at Apollo Hospitals Dhaka at 9:30 AM.', 'info', 'appointment', 8, TRUE, '2025-06-29 18:00:00', '2025-06-29 17:00:00'),
(21, 8, 'Certificate Available', 'Your donation certificate CERT-2025-00008 is ready for download.', 'success', 'certificate', 8, TRUE, '2025-06-15 10:30:00', '2025-06-14 13:00:00'),
(22, 9, 'Donation Request Match', 'A patient needs A+ blood at Dhaka Medical College. Can you help?', 'request', 'blood_request', 9, TRUE, '2025-08-05 16:30:00', '2025-08-05 16:00:00'),
(23, 9, 'Voluntary Donation Approved', 'Your voluntary donation for January 20, 2026 has been scheduled at DMCH.', 'success', 'voluntary_donation', 5, TRUE, '2026-01-10 11:00:00', '2026-01-10 10:00:00'),
(24, 11, 'Donation Status Update', 'You have reached the hospital for request REQ-2025-0011. Please proceed to the blood bank.', 'info', 'donation', 13, TRUE, '2025-12-27 09:30:00', '2025-12-27 09:30:00'),
(25, 13, 'Milestone Achievement', 'Amazing! You''ve completed 18 donations. You are a Platinum Donor!', 'success', NULL, NULL, TRUE, '2025-09-06 10:00:00', '2025-09-05 17:30:00'),
(26, 15, 'Donation on the Way', 'You have started your journey for donation. Please update status when you reach.', 'info', 'donation', 16, FALSE, NULL, '2026-01-17 09:00:00'),
(27, 17, 'Voluntary Donation Scheduled', 'Your voluntary donation has been scheduled at Ibn Sina Hospital on January 25, 2026.', 'success', 'voluntary_donation', 6, TRUE, '2026-01-15 12:00:00', '2026-01-15 11:30:00'),
(28, 2, 'Donation Accepted', 'Nusrat Jahan has accepted your blood request for coronary bypass surgery.', 'success', 'donation', 18, FALSE, NULL, '2026-01-15 10:00:00'),
(29, 19, 'Registration Pending', 'Your donor registration is under review. We will notify you once approved.', 'info', NULL, NULL, FALSE, NULL, '2025-12-20 10:00:00'),
(30, 20, 'Registration Pending', 'Welcome! Your application is being reviewed by our admin team.', 'info', NULL, NULL, FALSE, NULL, '2025-12-25 15:30:00'),
(31, 3, 'New Announcement', 'New Year Donation Drive 2026 - Join us and receive special recognition!', 'announcement', 'announcement', 10, FALSE, NULL, '2025-12-20 10:00:00'),
(32, 4, 'New Announcement', 'Dengue Season Alert - Urgent need for platelet donations.', 'announcement', 'announcement', 9, TRUE, '2025-09-02 10:00:00', '2025-09-01 08:00:00'),
(33, 5, 'New Announcement', 'Updated Eligibility Criteria - Please review before your next donation.', 'announcement', 'announcement', 7, TRUE, '2025-07-02 09:00:00', '2025-07-01 09:00:00'),
(34, 6, 'New Announcement', 'World Blood Donor Day 2025 - Special recognition for active donors!', 'announcement', 'announcement', 6, TRUE, '2025-06-02 10:00:00', '2025-06-01 00:00:00'),
(35, 9, 'Eligibility Status', 'You will be eligible to donate again on January 10, 2026.', 'info', NULL, NULL, TRUE, '2025-10-11 09:00:00', '2025-10-10 09:00:00'),

-- HOSPITAL NOTIFICATIONS (15)
(36, 23, 'Registration Approved', 'Dhaka Medical College Hospital registration has been approved. Welcome to BloodConnect!', 'success', NULL, NULL, TRUE, '2025-01-21 10:00:00', '2025-01-20 09:00:00'),
(37, 23, 'Blood Request Approved', 'Your blood request REQ-2025-0002 for accident trauma has been approved.', 'success', 'blood_request', 2, TRUE, '2025-03-20 15:00:00', '2025-03-20 14:00:00'),
(38, 23, 'Donation Completed', 'Donation for request REQ-2025-0002 has been completed. 3 units received.', 'success', 'blood_request', 2, TRUE, '2025-03-24 14:00:00', '2025-03-24 13:00:00'),
(39, 24, 'Donor on the Way', 'Ayesha Siddiqua is on the way for donation request REQ-2025-0004.', 'info', 'donation', 6, TRUE, '2025-04-30 08:30:00', '2025-04-30 08:00:00'),
(40, 24, 'Voluntary Donor Assigned', 'A voluntary donor has been assigned to your hospital for January 28, 2026.', 'info', 'voluntary_donation', 7, FALSE, NULL, '2026-01-18 09:00:00'),
(41, 25, 'New Blood Request', 'New request REQ-2025-0020 pending approval for gallbladder removal surgery.', 'info', 'blood_request', 20, TRUE, '2026-01-18 15:00:00', '2026-01-15 10:15:00'),
(42, 25, 'Low Stock Alert', 'AB+ blood stock is running low. Consider requesting emergency donors.', 'warning', NULL, NULL, TRUE, '2025-11-10 09:00:00', '2025-11-10 08:00:00'),
(43, 26, 'Request Completed', 'Blood request REQ-2025-0009 has been completed successfully.', 'success', 'blood_request', 9, TRUE, '2025-08-09 12:00:00', '2025-08-09 11:30:00'),
(44, 26, 'Donor Reached', 'Donor has reached the hospital for request REQ-2025-0023. Please prepare for donation.', 'info', 'blood_request', 23, FALSE, NULL, '2026-01-22 11:45:00'),
(45, 27, 'Emergency Request Created', 'Emergency request REQ-2025-0012 for severe dengue has been approved. Donors notified.', 'success', 'blood_request', 12, TRUE, '2025-12-22 12:00:00', '2025-12-22 11:00:00'),
(46, 27, 'Donor Assigned', 'Ayesha Siddiqua has accepted request REQ-2025-0012. Currently on the way.', 'info', 'donation', 14, TRUE, '2025-12-29 09:00:00', '2025-12-29 08:30:00'),
(47, 28, 'Voluntary Donation Scheduled', 'Voluntary donor scheduled for your hospital on September 5, 2025.', 'info', 'voluntary_donation', 4, TRUE, '2025-09-01 15:00:00', '2025-09-01 14:00:00'),
(48, 28, 'Request Pending', 'Your blood request REQ-2025-0014 is being reviewed by admin.', 'info', 'blood_request', 14, TRUE, '2025-12-28 12:00:00', '2025-12-28 11:15:00'),
(49, 29, 'Registration Under Review', 'Your hospital registration is currently under review. Expected approval within 3-5 business days.', 'info', NULL, NULL, FALSE, NULL, '2025-11-01 10:00:00'),
(50, 30, 'Registration Submitted', 'Thank you for registering. Our team will verify your documents shortly.', 'info', NULL, NULL, FALSE, NULL, '2025-12-01 11:30:00'),

-- SEEKER NOTIFICATIONS (10)
(51, 31, 'Welcome to BloodConnect', 'Your account has been created. You can now submit blood requests.', 'success', NULL, NULL, TRUE, '2025-03-01 11:00:00', '2025-03-01 10:00:00'),
(52, 31, 'Request Approved', 'Your blood request REQ-2025-0001 has been approved. Donors are being notified.', 'success', 'blood_request', 1, TRUE, '2025-03-10 11:00:00', '2025-03-10 10:00:00'),
(53, 31, 'Request Completed', 'Great news! Blood request REQ-2025-0001 has been fulfilled. 2 units delivered.', 'success', 'blood_request', 1, TRUE, '2025-03-14 12:00:00', '2025-03-14 11:30:00'),
(54, 32, 'Request Status Update', 'Donor has accepted your request REQ-2025-0003. Donation in progress.', 'info', 'blood_request', 3, TRUE, '2025-04-05 11:00:00', '2025-04-05 10:30:00'),
(55, 33, 'Request Completed', 'Your request for cancer treatment has been fulfilled. Thank you for using BloodConnect.', 'success', 'blood_request', 5, TRUE, '2025-05-19 13:00:00', '2025-05-19 12:00:00'),
(56, 35, 'Request Rejected', 'Unfortunately, request REQ-2025-0025 could not be approved. Reason: Patient age exceeds safe limit.', 'error', 'blood_request', 25, TRUE, '2025-08-28 11:00:00', '2025-08-28 10:00:00'),
(57, 37, 'Emergency Request Created', 'Your emergency request REQ-2025-0011 has been prioritized. Matching donors now.', 'success', 'blood_request', 11, TRUE, '2025-12-20 10:00:00', '2025-12-20 09:00:00'),
(58, 38, 'Request in Progress', 'Donor is on the way for your request REQ-2025-0013. Expected arrival soon.', 'info', 'blood_request', 13, FALSE, NULL, '2025-12-28 10:30:00'),
(59, 39, 'Donor Reached Hospital', 'Donor has reached Square Hospital for your request REQ-2025-0015.', 'info', 'blood_request', 15, TRUE, '2026-01-14 11:00:00', '2026-01-14 10:30:00'),
(60, 40, 'Request Submitted', 'Your blood request has been submitted and is pending admin approval.', 'info', 'blood_request', 17, FALSE, NULL, '2026-01-08 14:15:00');

-- =====================================================
-- CHAT_MESSAGES TABLE (35+ realistic conversations)
-- =====================================================

-- Conversation 1: Seeker (31) ↔ Donor (3) about REQ-2025-0001
INSERT INTO chat_messages (id, conversation_id, sender_id, receiver_id, message, message_type, request_id, donation_id, is_read, read_at, created_at) VALUES
(1, 'conv_3_31', 31, 3, 'Assalamualaikum, I saw you accepted my blood request. Thank you so much!', 'text', 1, 1, TRUE, '2025-03-10 11:10:00', '2025-03-10 11:05:00'),
(2, 'conv_3_31', 3, 31, 'Walaikumassalam! Yes, I am happy to help. When do you need me at the hospital?', 'text', 1, 1, TRUE, '2025-03-10 11:15:00', '2025-03-10 11:12:00'),
(3, 'conv_3_31', 31, 3, 'The surgery is on March 14. Can you please come to DMCH blood bank at 9 AM?', 'text', 1, 1, TRUE, '2025-03-10 11:20:00', '2025-03-10 11:18:00'),
(4, 'conv_3_31', 3, 31, 'InshaAllah, I will be there. Is the patient doing okay?', 'text', 1, 1, TRUE, '2025-03-10 11:25:00', '2025-03-10 11:22:00'),
(5, 'conv_3_31', 31, 3, 'My father is stable now. Your donation will help save his life. JazakAllah Khair.', 'text', 1, 1, TRUE, '2025-03-10 11:30:00', '2025-03-10 11:28:00'),
(6, 'conv_3_31', 3, 31, 'Alhamdulillah, may Allah grant him a speedy recovery. See you on the 14th!', 'text', 1, 1, TRUE, '2025-03-10 11:35:00', '2025-03-10 11:32:00'),

-- Conversation 2: Hospital (23) ↔ Donor (9) about donation coordination
(7, 'conv_9_23', 23, 9, 'Dear Imran, thank you for accepting our blood request. Please confirm your appointment time.', 'text', 2, 2, TRUE, '2025-03-10 13:00:00', '2025-03-10 12:30:00'),
(8, 'conv_9_23', 9, 23, 'Good afternoon, I can come at 9:30 AM on March 14. Is that okay?', 'text', 2, 2, TRUE, '2025-03-10 13:10:00', '2025-03-10 13:05:00'),
(9, 'conv_9_23', 23, 9, 'Perfect. Please bring your national ID and come to the Blood Bank department, 2nd floor.', 'text', 2, 2, TRUE, '2025-03-10 13:20:00', '2025-03-10 13:15:00'),
(10, 'conv_9_23', 9, 23, 'Understood. I will bring my NID. Is there any food restriction before donation?', 'text', 2, 2, TRUE, '2025-03-10 13:30:00', '2025-03-10 13:25:00'),
(11, 'conv_9_23', 23, 9, 'Please have a light breakfast and drink plenty of water. Avoid fatty foods. Thank you!', 'text', 2, 2, TRUE, '2025-03-10 13:40:00', '2025-03-10 13:35:00'),

-- Conversation 3: Admin (1) ↔ Hospital (29) about registration
(12, 'conv_1_29', 1, 29, 'Assalamualaikum, we have received your hospital registration. Some documents need clarification.', 'text', NULL, NULL, TRUE, '2025-11-02 10:00:00', '2025-11-02 09:30:00'),
(13, 'conv_1_29', 29, 1, 'Walaikumassalam, thank you for reviewing. Which documents need clarification?', 'text', NULL, NULL, TRUE, '2025-11-02 10:15:00', '2025-11-02 10:10:00'),
(14, 'conv_1_29', 1, 29, 'The license expiry date on your submitted document is not clear. Please upload a clearer copy.', 'text', NULL, NULL, TRUE, '2025-11-02 10:30:00', '2025-11-02 10:25:00'),
(15, 'conv_1_29', 29, 1, 'I will upload the updated document today. The license is valid until January 2028.', 'text', NULL, NULL, FALSE, NULL, '2025-11-02 10:40:00'),

-- Conversation 4: Seeker (37) ↔ Donor (11) about emergency request
(16, 'conv_11_37', 37, 11, 'Hello, thank you for responding to my emergency request. My husband needs surgery urgently.', 'text', 11, 13, TRUE, '2025-12-20 10:30:00', '2025-12-20 10:15:00'),
(17, 'conv_11_37', 11, 37, 'I understand. I am leaving now for DMCH. I should reach in about 45 minutes.', 'text', 11, 13, TRUE, '2025-12-20 10:45:00', '2025-12-20 10:35:00'),
(18, 'conv_11_37', 37, 11, 'May Allah bless you. Please call when you reach. My number is 01711555007.', 'text', 11, 13, TRUE, '2025-12-20 11:00:00', '2025-12-20 10:50:00'),
(19, 'conv_11_37', 11, 37, 'I have reached the hospital. Going to blood bank now. InshaAllah everything will be fine.', 'text', 11, 13, TRUE, '2025-12-27 09:45:00', '2025-12-27 09:35:00'),
(20, 'conv_11_37', 37, 11, 'JazakAllah Khair! The doctors said the blood type matched perfectly. You are a lifesaver!', 'text', 11, 13, TRUE, '2025-12-27 12:00:00', '2025-12-27 11:30:00'),

-- Conversation 5: Admin (2) ↔ Donor (5) about voluntary donation
(21, 'conv_2_5', 2, 5, 'Dear Mehedi, your voluntary donation has been approved. You have been assigned to Square Hospital.', 'text', NULL, NULL, TRUE, '2025-08-05 10:00:00', '2025-08-05 09:30:00'),
(22, 'conv_2_5', 5, 2, 'Thank you! What time should I arrive on August 10?', 'text', NULL, NULL, TRUE, '2025-08-05 10:15:00', '2025-08-05 10:10:00'),
(23, 'conv_2_5', 2, 5, 'Please arrive at 10:30 AM. The hospital will provide refreshments after donation.', 'text', NULL, NULL, TRUE, '2025-08-05 10:30:00', '2025-08-05 10:25:00'),
(24, 'conv_2_5', 5, 2, 'Perfect, I will be there. Is A- blood in high demand right now?', 'text', NULL, NULL, TRUE, '2025-08-05 10:45:00', '2025-08-05 10:40:00'),
(25, 'conv_2_5', 2, 5, 'Yes, A- is a rare blood type. Your donation will help 2-3 patients potentially. Thank you!', 'text', NULL, NULL, TRUE, '2025-08-05 11:00:00', '2025-08-05 10:55:00'),

-- Conversation 6: Hospital (24) ↔ Donor (4) about emergency delivery
(26, 'conv_4_24', 24, 4, 'Ayesha, we need your AB+ blood urgently. A mother is having complications during delivery.', 'text', 4, 6, TRUE, '2025-04-28 09:30:00', '2025-04-28 09:15:00'),
(27, 'conv_4_24', 4, 24, 'I am on my way! I should reach in 30 minutes. Please prepare the blood bank.', 'text', 4, 6, TRUE, '2025-04-28 09:45:00', '2025-04-28 09:35:00'),
(28, 'conv_4_24', 24, 4, 'Thank you so much! Ask for Dr. Fatema at Blood Bank. She is waiting for you.', 'text', 4, 6, TRUE, '2025-04-28 10:00:00', '2025-04-28 09:50:00'),
(29, 'conv_4_24', 4, 24, 'Alhamdulillah, I have reached and completed the donation. How is the mother?', 'text', 4, 6, TRUE, '2025-04-30 12:30:00', '2025-04-30 12:00:00'),
(30, 'conv_4_24', 24, 4, 'Both mother and baby are safe! Your blood saved two lives today. JazakAllah!', 'text', 4, 6, TRUE, '2025-04-30 14:00:00', '2025-04-30 13:00:00'),

-- Conversation 7: Admin (1) ↔ Hospital (23) about report
(31, 'conv_1_23', 1, 23, 'DMCH team, congratulations! You have the highest number of successful donations this quarter.', 'text', NULL, NULL, TRUE, '2025-10-01 10:00:00', '2025-10-01 09:00:00'),
(32, 'conv_1_23', 23, 1, 'Thank you! Our blood bank team works very hard. BloodConnect has been very helpful.', 'text', NULL, NULL, TRUE, '2025-10-01 10:30:00', '2025-10-01 10:15:00'),
(33, 'conv_1_23', 1, 23, 'We would like to feature DMCH in our success stories. Would you be interested?', 'text', NULL, NULL, TRUE, '2025-10-01 11:00:00', '2025-10-01 10:45:00'),
(34, 'conv_1_23', 23, 1, 'Of course! Please coordinate with Dr. Saiful Islam for the interview.', 'text', NULL, NULL, TRUE, '2025-10-01 11:30:00', '2025-10-01 11:15:00'),

-- Conversation 8: Seeker (35) ↔ Donor (8) about donation
(35, 'conv_8_35', 35, 8, 'Sister, thank you for donating for my mother. May Allah reward you.', 'text', 8, 11, TRUE, '2025-07-19 13:00:00', '2025-07-19 12:30:00'),
(36, 'conv_8_35', 8, 35, 'It is my duty as a Muslim and a citizen. I pray for your mother recovery.', 'text', 8, 11, TRUE, '2025-07-19 13:30:00', '2025-07-19 13:15:00'),
(37, 'conv_8_35', 35, 8, 'The anemia treatment is going well. Doctors are hopeful. Thank you again!', 'text', 8, 11, TRUE, '2025-07-25 10:00:00', '2025-07-25 09:00:00');

-- =====================================================
-- CHAT_CONVERSATIONS TABLE (Metadata for conversations)
-- =====================================================
INSERT INTO chat_conversations (id, conversation_id, user_1_id, user_2_id, last_message_id, last_message_at, user_1_unread_count, user_2_unread_count, created_at) VALUES
(1, 'conv_3_31', 3, 31, 6, '2025-03-10 11:32:00', 0, 0, '2025-03-10 11:05:00'),
(2, 'conv_9_23', 9, 23, 11, '2025-03-10 13:35:00', 0, 0, '2025-03-10 12:30:00'),
(3, 'conv_1_29', 1, 29, 15, '2025-11-02 10:40:00', 1, 0, '2025-11-02 09:30:00'),
(4, 'conv_11_37', 11, 37, 20, '2025-12-27 11:30:00', 0, 0, '2025-12-20 10:15:00'),
(5, 'conv_2_5', 2, 5, 25, '2025-08-05 10:55:00', 0, 0, '2025-08-05 09:30:00'),
(6, 'conv_4_24', 4, 24, 30, '2025-04-30 13:00:00', 0, 0, '2025-04-28 09:15:00'),
(7, 'conv_1_23', 1, 23, 34, '2025-10-01 11:15:00', 0, 0, '2025-10-01 09:00:00'),
(8, 'conv_8_35', 8, 35, 37, '2025-07-25 09:00:00', 0, 0, '2025-07-19 12:30:00');

-- =====================================================
-- UPDATE COUNTERS AND STATISTICS
-- =====================================================

-- Update donor total_donations based on completed donations
UPDATE donors d SET total_donations = (
    SELECT COUNT(*) FROM donations don 
    WHERE don.donor_id = d.id AND don.status = 'completed'
);

-- Update seeker total_requests
UPDATE seekers s SET total_requests = (
    SELECT COUNT(*) FROM blood_requests br 
    WHERE br.requester_id = s.user_id AND br.requester_type = 'seeker'
);

-- Update hospital total_requests
UPDATE hospitals h SET total_requests = (
    SELECT COUNT(*) FROM blood_requests br 
    WHERE br.requester_id = h.user_id AND br.requester_type = 'hospital'
);

-- Update blood_requests units_fulfilled based on completed donations
UPDATE blood_requests br SET units_fulfilled = (
    SELECT COALESCE(SUM(d.quantity), 0) FROM donations d 
    WHERE d.request_id = br.id AND d.status = 'completed'
);

-- =====================================================
-- END OF SEED DATA
-- =====================================================
