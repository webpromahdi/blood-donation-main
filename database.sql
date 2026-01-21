-- Blood Donation System Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS blood_donation;
USE blood_donation;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'donor', 'hospital', 'seeker') NOT NULL DEFAULT 'donor',
    
    -- Donor-specific fields
    blood_group VARCHAR(5),
    age INT,
    weight INT,
    city VARCHAR(100),
    address TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Hospital-specific fields
    registration_number VARCHAR(100),
    hospital_address TEXT,
    website VARCHAR(255),
    contact_person VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create index on email for faster lookups
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- =====================================================
-- BLOOD REQUESTS TABLE
-- Stores all blood requests from hospitals and seekers
-- =====================================================
CREATE TABLE IF NOT EXISTS blood_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_code VARCHAR(20) UNIQUE NOT NULL,  -- e.g., 'REQ001'
    
    -- Requester (Hospital or Seeker)
    requester_id INT NOT NULL,
    requester_type ENUM('hospital', 'seeker') NOT NULL,
    
    -- Patient Information
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(255),
    
    -- Blood Request Details
    blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    hospital_id INT,  -- FK to users where role='hospital' (for seeker requests)
    hospital_name VARCHAR(255),
    city VARCHAR(100),
    required_date DATE NOT NULL,
    medical_reason TEXT,
    
    -- Urgency & Status
    urgency ENUM('normal', 'emergency') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Admin Processing
    admin_id INT,  -- FK to users where role='admin'
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Blood requests indexes
CREATE INDEX idx_requests_status ON blood_requests(status);
CREATE INDEX idx_requests_blood_type ON blood_requests(blood_type);
CREATE INDEX idx_requests_urgency ON blood_requests(urgency);
CREATE INDEX idx_requests_requester ON blood_requests(requester_id, requester_type);
CREATE INDEX idx_requests_code ON blood_requests(request_code);

-- =====================================================
-- DONATIONS TABLE
-- Tracks donation journey from acceptance to completion
-- =====================================================
CREATE TABLE IF NOT EXISTS donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    donor_id INT NOT NULL,
    
    -- Donation Status
    status ENUM('accepted', 'on_the_way', 'reached', 'completed', 'cancelled') DEFAULT 'accepted',
    
    -- Journey Timestamps
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,        -- When donor starts journey (on_the_way)
    reached_at TIMESTAMP NULL,        -- When donor reaches hospital
    completed_at TIMESTAMP NULL,      -- When donation is done
    cancelled_at TIMESTAMP NULL,
    cancel_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Donations indexes
CREATE INDEX idx_donations_request ON donations(request_id);
CREATE INDEX idx_donations_donor ON donations(donor_id);
CREATE INDEX idx_donations_status ON donations(status);

-- =====================================================
-- ANNOUNCEMENTS TABLE
-- Stores system announcements created by admins
-- =====================================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Target audience: 'all', 'donors', 'hospitals', 'seekers'
    target_audience ENUM('all', 'donors', 'hospitals', 'seekers') DEFAULT 'all',
    
    -- Priority level
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Scheduling
    scheduled_at TIMESTAMP NULL,
    
    -- Status
    status ENUM('draft', 'scheduled', 'published', 'archived') DEFAULT 'published',
    
    -- Admin who created it
    admin_id INT NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Announcements indexes
CREATE INDEX idx_announcements_status ON announcements(status);
CREATE INDEX idx_announcements_target ON announcements(target_audience);
CREATE INDEX idx_announcements_priority ON announcements(priority);
CREATE INDEX idx_announcements_scheduled ON announcements(scheduled_at);

-- =====================================================
-- DONOR HEALTH TABLE
-- Stores health information for donors
-- =====================================================
CREATE TABLE IF NOT EXISTS donor_health (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL UNIQUE,
    
    -- Physical measurements
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    
    -- Health conditions
    has_diabetes BOOLEAN DEFAULT FALSE,
    has_hypertension BOOLEAN DEFAULT FALSE,
    has_heart_disease BOOLEAN DEFAULT FALSE,
    has_blood_disorders BOOLEAN DEFAULT FALSE,
    has_infectious_disease BOOLEAN DEFAULT FALSE,
    
    -- Additional info
    medications TEXT,
    allergies TEXT,
    last_checkup DATE,
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Donor health indexes
CREATE INDEX idx_donor_health_donor ON donor_health(donor_id);
