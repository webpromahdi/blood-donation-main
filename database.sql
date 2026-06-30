-- =====================================================
-- BLOOD DONATION SYSTEM - NORMALIZED DATABASE SCHEMA
-- BloodConnect Production-Ready Schema
-- =====================================================

-- Drop existing database and create fresh
DROP DATABASE IF EXISTS blood_donation;
CREATE DATABASE blood_donation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blood_donation;

-- =====================================================
-- BLOOD GROUPS TABLE (Reference Table)
-- Stores all valid blood group types
-- =====================================================
CREATE TABLE blood_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blood_type VARCHAR(5) NOT NULL UNIQUE,
    can_donate_to JSON,
    can_receive_from JSON,
    description VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert standard blood groups with compatibility data
INSERT INTO blood_groups (blood_type, can_donate_to, can_receive_from, description) VALUES
('A+', '["A+", "AB+"]', '["A+", "A-", "O+", "O-"]', 'A Positive'),
('A-', '["A+", "A-", "AB+", "AB-"]', '["A-", "O-"]', 'A Negative'),
('B+', '["B+", "AB+"]', '["B+", "B-", "O+", "O-"]', 'B Positive'),
('B-', '["B+", "B-", "AB+", "AB-"]', '["B-", "O-"]', 'B Negative'),
('AB+', '["AB+"]', '["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"]', 'AB Positive - Universal Recipient'),
('AB-', '["AB+", "AB-"]', '["A-", "B-", "AB-", "O-"]', 'AB Negative'),
('O+', '["A+", "B+", "AB+", "O+"]', '["O+", "O-"]', 'O Positive'),
('O-', '["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"]', '["O-"]', 'O Negative - Universal Donor');

-- =====================================================
-- SEED DATA: Test Users
-- Insert test users for development/testing
-- =====================================================

-- Note: These will be inserted AFTER tables are created (see bottom of file)

-- =====================================================
-- USERS TABLE (Authentication Base)
-- Core user data for all roles
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'donor', 'hospital', 'seeker') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- DONORS TABLE (Donor-Specific Data)
-- Extended information for donors only
-- =====================================================
CREATE TABLE donors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    blood_group_id INT NOT NULL,
    age INT,
    weight DECIMAL(5,2),
    gender ENUM('male', 'female', 'other'),
    city VARCHAR(100),
    address TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    total_donations INT DEFAULT 0,
    last_donation_date DATE NULL,
    next_eligible_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id) ON DELETE RESTRICT,
    
    INDEX idx_donors_user (user_id),
    INDEX idx_donors_blood_group (blood_group_id),
    INDEX idx_donors_city (city),
    INDEX idx_donors_available (is_available)
) ENGINE=InnoDB;

-- =====================================================
-- DONOR_HEALTH TABLE (Health Information)
-- Health records for donors - separate for privacy
-- =====================================================
CREATE TABLE donor_health (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL UNIQUE,
    
    -- Physical measurements
    height DECIMAL(5,2),
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    hemoglobin DECIMAL(4,2),
    
    -- Health conditions (boolean flags)
    has_diabetes BOOLEAN DEFAULT FALSE,
    has_hypertension BOOLEAN DEFAULT FALSE,
    has_heart_disease BOOLEAN DEFAULT FALSE,
    has_blood_disorders BOOLEAN DEFAULT FALSE,
    has_infectious_disease BOOLEAN DEFAULT FALSE,
    has_asthma BOOLEAN DEFAULT FALSE,
    has_allergies BOOLEAN DEFAULT FALSE,
    has_recent_surgery BOOLEAN DEFAULT FALSE,
    is_on_medication BOOLEAN DEFAULT FALSE,
    
    -- Lifestyle
    smoking_status ENUM('no', 'occasionally', 'regularly') DEFAULT 'no',
    alcohol_consumption ENUM('none', 'occasionally', 'regularly') DEFAULT 'none',
    exercise_frequency ENUM('rarely', 'weekly', 'daily') DEFAULT 'rarely',
    
    -- Additional info
    medications TEXT,
    allergies_details TEXT,
    last_medical_checkup DATE,
    additional_notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
    INDEX idx_donor_health_donor (donor_id)
) ENGINE=InnoDB;

-- =====================================================
-- SEEKERS TABLE (Seeker-Specific Data)
-- Extended information for blood seekers
-- =====================================================
CREATE TABLE seekers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    city VARCHAR(100),
    address TEXT,
    total_requests INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_seekers_user (user_id),
    INDEX idx_seekers_city (city)
) ENGINE=InnoDB;

-- =====================================================
-- HOSPITALS TABLE (Hospital-Specific Data)
-- Extended information for hospitals only
-- =====================================================
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    registration_number VARCHAR(100) UNIQUE,
    hospital_type ENUM('government', 'private', 'charity') DEFAULT 'private',
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    website VARCHAR(255),
    contact_person VARCHAR(255),
    operating_hours VARCHAR(100),
    license_expiry_date DATE,
    has_blood_bank BOOLEAN DEFAULT FALSE,
    total_requests INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_hospitals_user (user_id),
    INDEX idx_hospitals_city (city),
    INDEX idx_hospitals_registration (registration_number)
) ENGINE=InnoDB;

-- =====================================================
-- BLOOD_REQUESTS TABLE
-- Blood requests from hospitals and seekers
-- =====================================================
CREATE TABLE blood_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_code VARCHAR(20) NOT NULL UNIQUE,
    
    -- Requester info (polymorphic)
    requester_id INT NOT NULL,
    requester_type ENUM('hospital', 'seeker') NOT NULL,
    
    -- Patient information
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(255),
    
    -- Blood request details
    blood_group_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    units_fulfilled INT DEFAULT 0,
    
    -- Hospital for delivery
    hospital_id INT NULL,
    hospital_name VARCHAR(255),
    city VARCHAR(100),
    
    -- Dates and urgency
    required_date DATE NOT NULL,
    medical_reason TEXT,
    urgency ENUM('normal', 'emergency') DEFAULT 'normal',
    
    -- Status tracking
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Admin processing
    admin_id INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id) ON DELETE RESTRICT,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_requests_code (request_code),
    INDEX idx_requests_requester (requester_id, requester_type),
    INDEX idx_requests_blood_group (blood_group_id),
    INDEX idx_requests_status (status),
    INDEX idx_requests_urgency (urgency),
    INDEX idx_requests_date (required_date)
) ENGINE=InnoDB;

-- =====================================================
-- DONATIONS TABLE
-- Tracks donation lifecycle from acceptance to completion
-- =====================================================
CREATE TABLE donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    donor_id INT NOT NULL,
    
    -- Donation status
    status ENUM('accepted', 'on_the_way', 'reached', 'completed', 'cancelled') DEFAULT 'accepted',
    quantity INT DEFAULT 1,
    
    -- Journey timestamps
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    reached_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
    
    INDEX idx_donations_request (request_id),
    INDEX idx_donations_donor (donor_id),
    INDEX idx_donations_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- APPOINTMENTS TABLE
-- Scheduled appointments for donations
-- =====================================================
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donation_id INT NULL,
    hospital_id INT NOT NULL,
    donor_id INT NOT NULL,
    
    -- Appointment details
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    
    -- Status
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE SET NULL,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
    
    INDEX idx_appointments_donation (donation_id),
    INDEX idx_appointments_hospital (hospital_id),
    INDEX idx_appointments_donor (donor_id),
    INDEX idx_appointments_date (appointment_date)
) ENGINE=InnoDB;

-- =====================================================
-- CERTIFICATES TABLE
-- Donation certificates for completed donations
-- =====================================================
CREATE TABLE certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_code VARCHAR(50) NOT NULL UNIQUE,
    donation_id INT NOT NULL UNIQUE,
    donor_id INT NOT NULL,
    
    -- Certificate details
    donor_name VARCHAR(255) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    donation_date DATE NOT NULL,
    hospital_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    
    -- Timestamps
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    downloaded_at TIMESTAMP NULL,
    
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
    
    INDEX idx_certificates_code (certificate_code),
    INDEX idx_certificates_donation (donation_id),
    INDEX idx_certificates_donor (donor_id)
) ENGINE=InnoDB;

-- =====================================================
-- ANNOUNCEMENTS TABLE
-- System announcements from admin
-- =====================================================
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Target audience
    target_audience ENUM('all', 'donors', 'hospitals', 'seekers') DEFAULT 'all',
    
    -- Priority and scheduling
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    scheduled_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- Status
    status ENUM('draft', 'scheduled', 'published', 'archived') DEFAULT 'published',
    
    -- Admin who created it
    admin_id INT NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_announcements_status (status),
    INDEX idx_announcements_target (target_audience),
    INDEX idx_announcements_priority (priority),
    INDEX idx_announcements_scheduled (scheduled_at)
) ENGINE=InnoDB;

-- =====================================================
-- NOTIFICATIONS TABLE
-- User notifications
-- =====================================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Notification content
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'request', 'donation', 'announcement') DEFAULT 'info',
    
    -- Related entities (polymorphic)
    related_type VARCHAR(50) NULL,
    related_id INT NULL,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read),
    INDEX idx_notifications_type (type)
) ENGINE=InnoDB;

-- =====================================================
-- VOLUNTARY_DONATIONS TABLE
-- Tracks voluntary donation requests from donors
-- =====================================================
CREATE TABLE voluntary_donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL,
    blood_group_id INT NOT NULL,
    city VARCHAR(100) NOT NULL,
    availability_date DATE NOT NULL,
    preferred_time ENUM('morning', 'afternoon', 'evening', 'any') DEFAULT 'any',
    notes TEXT,
    
    -- Status tracking (added 'scheduled' for hospital confirmation)
    status ENUM('pending', 'approved', 'scheduled', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Admin processing
    approved_by_admin_id INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    
    -- Hospital assignment (after approval)
    hospital_id INT NULL,
    scheduled_date DATE NULL,
    scheduled_time TIME NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
    
    INDEX idx_voluntary_donor (donor_id),
    INDEX idx_voluntary_blood_group (blood_group_id),
    INDEX idx_voluntary_city (city),
    INDEX idx_voluntary_status (status),
    INDEX idx_voluntary_date (availability_date)
) ENGINE=InnoDB;

-- =====================================================
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Message Identity
    conversation_id VARCHAR(50) NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    
    -- Message content
    message TEXT NOT NULL,
    message_type ENUM('text', 'system') DEFAULT 'text',
    
    -- Related context (optional)
    request_id INT NULL,
    donation_id INT NULL,
    voluntary_donation_id INT NULL,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE SET NULL,
    FOREIGN KEY (voluntary_donation_id) REFERENCES voluntary_donations(id) ON DELETE SET NULL,
    
    -- CRITICAL INDEXES
    INDEX idx_conversation (conversation_id, created_at DESC),
    INDEX idx_sender (sender_id, created_at DESC),
    INDEX idx_receiver (receiver_id, is_read, created_at DESC),
    INDEX idx_unread (receiver_id, is_read, created_at DESC),
    INDEX idx_request_context (request_id, created_at DESC),
    INDEX idx_donation_context (donation_id, created_at DESC)
) ENGINE=InnoDB;

-- ============================================
-- CHAT_CONVERSATIONS TABLE (Metadata Cache)
-- Optional but recommended for unread counts
-- ============================================
CREATE TABLE chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id VARCHAR(50) NOT NULL UNIQUE,
    user_1_id INT NOT NULL,
    user_2_id INT NOT NULL,
    
    last_message_id INT NULL,
    last_message_at TIMESTAMP NULL,
    
    user_1_unread_count INT DEFAULT 0,
    user_2_unread_count INT DEFAULT 0,
    
    is_blocked BOOLEAN DEFAULT FALSE,
    blocked_by INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    
    INDEX idx_users (user_1_id, user_2_id)
) ENGINE=InnoDB;

-- ============================================
-- TRIGGER: Create notification on new chat message
-- ============================================
DELIMITER //
CREATE TRIGGER tr_new_chat_message
AFTER INSERT ON chat_messages
FOR EACH ROW
BEGIN
    INSERT INTO notifications 
    (user_id, title, message, type, related_type, related_id, is_read, created_at)
    SELECT 
        NEW.receiver_id,
        'New Message',
        CONCAT(u.name, ' sent you a message'),
        'info',
        'chat_message',
        NEW.id,
        0,
        NOW()
    FROM users u WHERE u.id = NEW.sender_id;
END;//
DELIMITER ;

-- =====================================================
-- DEFAULT ADMIN USER
-- Password: admin123 (hashed with bcrypt)
-- =====================================================
INSERT INTO users (email, password, name, phone, role, status) VALUES
('admin@bloodconnect.com', '$2y$10$PoNoNzrsSKW3WEOkAiWUOuZA1H57AMsK6SpBrDyGF6z1SXpmpiIWW', 'System Admin', '1234567890', 'admin', 'approved');

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for donor details with user info
CREATE VIEW v_donor_details AS
SELECT 
    d.id AS donor_id,
    d.user_id,
    u.email,
    u.name,
    u.phone,
    u.status AS account_status,
    bg.blood_type AS blood_group,
    d.age,
    d.weight,
    d.gender,
    d.city,
    d.address,
    d.is_available,
    d.total_donations,
    d.last_donation_date,
    d.next_eligible_date,
    u.created_at AS member_since
FROM donors d
JOIN users u ON d.user_id = u.id
JOIN blood_groups bg ON d.blood_group_id = bg.id;

-- View for hospital details with user info
CREATE VIEW v_hospital_details AS
SELECT 
    h.id AS hospital_id,
    h.user_id,
    u.email,
    u.name,
    u.phone,
    u.status AS account_status,
    h.registration_number,
    h.hospital_type,
    h.address,
    h.city,
    h.state,
    h.pincode,
    h.website,
    h.contact_person,
    h.operating_hours,
    h.has_blood_bank,
    h.total_requests,
    u.created_at AS member_since
FROM hospitals h
JOIN users u ON h.user_id = u.id;

-- View for seeker details with user info
CREATE VIEW v_seeker_details AS
SELECT 
    s.id AS seeker_id,
    s.user_id,
    u.email,
    u.name,
    u.phone,
    u.status AS account_status,
    s.city,
    s.address,
    s.total_requests,
    u.created_at AS member_since
FROM seekers s
JOIN users u ON s.user_id = u.id;

-- View for blood request details
CREATE VIEW v_request_details AS
SELECT 
    r.id AS request_id,
    r.request_code,
    r.requester_id,
    r.requester_type,
    u.name AS requester_name,
    u.email AS requester_email,
    u.phone AS requester_phone,
    r.patient_name,
    r.patient_age,
    r.contact_phone,
    r.contact_email,
    bg.blood_type,
    r.quantity,
    r.units_fulfilled,
    r.hospital_name,
    r.city,
    r.required_date,
    r.medical_reason,
    r.urgency,
    r.status,
    r.approved_at,
    r.rejected_at,
    r.rejection_reason,
    r.created_at
FROM blood_requests r
JOIN users u ON r.requester_id = u.id
JOIN blood_groups bg ON r.blood_group_id = bg.id;

-- =====================================================
-- SEED DATA: Test Users
-- =====================================================

-- Insert test users (basic auth data only)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES
(14, 'Mahdi Al Hasan', 'donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01537288022', 'donor', 'approved', '2026-01-15 11:22:31', '2026-01-15 11:22:31'),
(15, 'Admin', 'admin@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01537288022', 'admin', 'approved', '2026-01-15 11:24:45', '2026-01-15 11:24:45'),
(16, 'Test Hospital', 'testhospital@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01712345678', 'hospital', 'approved', '2026-01-15 11:31:59', '2026-01-15 11:31:59'),
(17, 'Mahdi Al Hasan', 'seeker@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01537288022', 'seeker', 'approved', '2026-01-15 11:34:49', '2026-01-15 11:34:49'),
(18, 'City Hospital', 'hospital@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01537288022', 'hospital', 'approved', '2026-01-15 11:40:00', '2026-01-15 11:40:00');

-- Insert donor-specific data (user_id 14 is donor with A+ blood)
INSERT INTO `donors` (`user_id`, `blood_group_id`, `age`, `weight`, `city`, `address`) VALUES
(14, (SELECT id FROM blood_groups WHERE blood_type = 'A+'), 21, 70.00, 'Dhaka North', 'Solmaid high school,Vatara,Notun bazar');

-- Initialize donor_health record for the donor
INSERT INTO `donor_health` (`donor_id`) VALUES
((SELECT id FROM donors WHERE user_id = 14));

-- Insert hospital-specific data (user_id 16 and 18 are hospitals)
INSERT INTO `hospitals` (`user_id`, `registration_number`, `address`, `city`, `website`, `contact_person`) VALUES
(16, 'REG-99999', '123 Test Street', 'Dhaka', '', 'Dr. Test'),
(18, 'Reg123', 'Solmaid high school,Vatara,Notun bazar', 'Dhaka North', 'https://www.ibnsinatrust.com/', 'Rafi');

-- Insert seeker-specific data (user_id 17 is seeker)
INSERT INTO `seekers` (`user_id`) VALUES
(17);

-- =====================================================
-- END OF SCHEMA
-- =====================================================


-- =====================================================
-- MASSIVE SEED DATA
-- =====================================================

-- Insert more Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `status`) VALUES
(101, 'Rahim Uddin', 'rahim.donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01700000001', 'donor', 'approved'),
(102, 'Karim Hassan', 'karim.donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01700000002', 'donor', 'approved'),
(103, 'Aisha Rahman', 'aisha.donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01700000003', 'donor', 'approved'),
(104, 'Sajid Islam', 'sajid.donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01700000004', 'donor', 'approved'),
(105, 'Nafisa Ali', 'nafisa.donor@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01700000005', 'donor', 'approved'),
(106, 'Apollo Hospital', 'apollo@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01800000001', 'hospital', 'approved'),
(107, 'Square Hospital', 'square@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01800000002', 'hospital', 'approved'),
(108, 'United Hospital', 'united@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01800000003', 'hospital', 'approved'),
(109, 'Jamil Seeker', 'jamil.seeker@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01900000001', 'seeker', 'approved'),
(110, 'Sadia Seeker', 'sadia.seeker@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01900000002', 'seeker', 'approved'),
(111, 'Mina Seeker', 'mina.seeker@test.com', '$2y$10$DalHTI6lyfa/QG5njATTMeENUrCNyavVtjaZrS0myCpoxyBUHZRK6', '01900000003', 'seeker', 'approved');

-- Insert Donors Details
INSERT INTO `donors` (`user_id`, `blood_group_id`, `age`, `weight`, `gender`, `city`, `address`, `is_available`, `total_donations`) VALUES
(101, (SELECT id FROM blood_groups WHERE blood_type = 'O+'), 25, 65, 'male', 'Dhaka', 'Mirpur 10, Dhaka', TRUE, 2),
(102, (SELECT id FROM blood_groups WHERE blood_type = 'A-'), 30, 75, 'male', 'Chittagong', 'Agrabad, Chittagong', TRUE, 1),
(103, (SELECT id FROM blood_groups WHERE blood_type = 'B+'), 22, 55, 'female', 'Sylhet', 'Zindabazar, Sylhet', TRUE, 0),
(104, (SELECT id FROM blood_groups WHERE blood_type = 'AB+'), 28, 80, 'male', 'Dhaka', 'Dhanmondi 27, Dhaka', FALSE, 5),
(105, (SELECT id FROM blood_groups WHERE blood_type = 'O-'), 26, 60, 'female', 'Rajshahi', 'Shaheb Bazar, Rajshahi', TRUE, 3);

-- Initialize donor_health
INSERT INTO `donor_health` (`donor_id`) VALUES
((SELECT id FROM donors WHERE user_id = 101)),
((SELECT id FROM donors WHERE user_id = 102)),
((SELECT id FROM donors WHERE user_id = 103)),
((SELECT id FROM donors WHERE user_id = 104)),
((SELECT id FROM donors WHERE user_id = 105));

-- Insert Hospital Details
INSERT INTO `hospitals` (`user_id`, `registration_number`, `hospital_type`, `address`, `city`, `state`, `contact_person`, `has_blood_bank`) VALUES
(106, 'REG-APOLLO', 'private', 'Bashundhara R/A', 'Dhaka', 'Dhaka', 'Dr. Shafiq', TRUE),
(107, 'REG-SQUARE', 'private', 'Panthapath', 'Dhaka', 'Dhaka', 'Dr. Amin', TRUE),
(108, 'REG-UNITED', 'private', 'Gulshan 2', 'Dhaka', 'Dhaka', 'Dr. Kamal', TRUE);

-- Insert Seeker Details
INSERT INTO `seekers` (`user_id`, `city`, `address`, `total_requests`) VALUES
(109, 'Dhaka', 'Mohammadpur, Dhaka', 1),
(110, 'Chittagong', 'Halishahar, Chittagong', 2),
(111, 'Sylhet', 'Subidbazar, Sylhet', 1);

-- Insert Blood Requests
INSERT INTO `blood_requests` (`request_code`, `requester_id`, `requester_type`, `patient_name`, `patient_age`, `contact_phone`, `blood_group_id`, `quantity`, `city`, `required_date`, `urgency`, `status`) VALUES
('REQ-0001', 109, 'seeker', 'Anisur Rahman', 45, '01900000001', (SELECT id FROM blood_groups WHERE blood_type = 'O+'), 2, 'Dhaka', CURDATE() + INTERVAL 2 DAY, 'normal', 'approved'),
('REQ-0002', 106, 'hospital', 'Tariqul Islam', 32, '01800000001', (SELECT id FROM blood_groups WHERE blood_type = 'AB-'), 1, 'Dhaka', CURDATE() + INTERVAL 1 DAY, 'emergency', 'pending'),
('REQ-0003', 110, 'seeker', 'Salma Begum', 55, '01900000002', (SELECT id FROM blood_groups WHERE blood_type = 'B+'), 3, 'Chittagong', CURDATE() + INTERVAL 5 DAY, 'normal', 'approved'),
('REQ-0004', 108, 'hospital', 'Kamal Hossain', 60, '01800000003', (SELECT id FROM blood_groups WHERE blood_type = 'O-'), 1, 'Dhaka', CURDATE(), 'emergency', 'in_progress');

-- Insert Announcements
INSERT INTO `announcements` (`title`, `message`, `target_audience`, `priority`, `admin_id`) VALUES
('Welcome to BloodConnect', 'Thank you for joining our mission to save lives. Please update your profile.', 'all', 'normal', (SELECT id FROM users WHERE role = 'admin' LIMIT 1)),
('Urgent Need of O- Blood', 'There is an emergency requirement of O- blood in United Hospital, Dhaka. Eligible donors please respond.', 'donors', 'urgent', (SELECT id FROM users WHERE role = 'admin' LIMIT 1));
