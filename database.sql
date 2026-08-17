-- ============================================================
-- Grad Collab - Database Setup
-- ============================================================
-- HOW TO USE:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click the "Import" tab
-- 3. Choose this file and click "Go"
-- ============================================================

-- Create the database if it doesn't already exist
CREATE DATABASE IF NOT EXISTS grad_collab;

-- Switch to the grad_collab database
USE grad_collab;

-- ============================================================
-- Create the users table
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id    INT PRIMARY KEY AUTO_INCREMENT,        -- Auto-incrementing unique ID
    name       VARCHAR(100) NOT NULL,                  -- User's full name
    email      VARCHAR(150) NOT NULL UNIQUE,           -- Email (must be unique - no duplicates)
    password   VARCHAR(255) NOT NULL,                  -- Bcrypt hashed password
    role       ENUM('student','supervisor','admin') NOT NULL,  -- Role-based access control
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP    -- Account creation time
);

-- ============================================================
-- Insert the demo admin account
-- Email:    admin@gradcollab.com
-- Password: admin123
--
-- IMPORTANT: The hash below may not match on all PHP versions.
-- If login fails with admin123, run this in your browser:
--   http://localhost/grad_collab/generate_hash.php
-- Copy the output hash and run:
--   UPDATE users SET password = '<paste_hash>' WHERE email = 'admin@gradcollab.com';
-- ============================================================

INSERT IGNORE INTO users (name, email, password, role)
VALUES (
    'Admin User',
    'admin@gradcollab.com',
    '$2y$10$32E7eO6RYztYpfXW3grJiOVG.bHqYLAxxxADpsgxEgwr6b4RPd2.u',
    'admin'
);

-- Verify the table was created correctly:
-- SELECT * FROM users;

-- ============================================================
-- Feature 2: Student Research Profile
-- Stores academic and research preference data for students.
-- Links to the users table via user_id.
-- ============================================================
CREATE TABLE IF NOT EXISTS student_profiles (
    profile_id           INT PRIMARY KEY AUTO_INCREMENT,
    user_id              INT NOT NULL UNIQUE,                       -- One profile per student
    cgpa                 DECIMAL(3,2) NOT NULL,                     -- e.g. 3.50 (0.00 – 4.00)
    semester             TINYINT NOT NULL,                          -- 1 to 12
    availability         ENUM('Available','Partially Available','Not Available') NOT NULL DEFAULT 'Available',
    preferred_domains    VARCHAR(255) DEFAULT NULL,                 -- Comma-separated, e.g. "Database, AI"
    skills               VARCHAR(255) DEFAULT NULL,                 -- Comma-separated, e.g. "PHP, MySQL"
    preferred_supervisor VARCHAR(150) DEFAULT NULL,                 -- Free text supervisor name
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- Feature 3: Research / Thesis Team Posts
-- A student (the team leader) creates a post to find teammates.
-- ============================================================
CREATE TABLE IF NOT EXISTS team_posts (
    team_id              INT PRIMARY KEY AUTO_INCREMENT,
    leader_id            INT NOT NULL,                              -- References users.user_id
    title                VARCHAR(200) NOT NULL,
    abstract             TEXT NOT NULL,
    domain               VARCHAR(150) NOT NULL,                     -- e.g. "Database"
    tags                 VARCHAR(255) DEFAULT NULL,                 -- Comma-separated tags
    required_teammates   INT NOT NULL DEFAULT 1,                   -- How many teammates needed
    deadline             DATE NOT NULL,                             -- Application deadline
    minimum_cgpa         DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    eligible_semesters   VARCHAR(100) DEFAULT NULL,                 -- e.g. "10, 11, 12"
    required_skills      VARCHAR(255) DEFAULT NULL,                 -- Comma-separated skills
    preferred_supervisor VARCHAR(150) DEFAULT NULL,
    status               ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES users(user_id) ON DELETE CASCADE
);

