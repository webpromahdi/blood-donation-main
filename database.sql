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
