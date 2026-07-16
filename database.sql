CREATE DATABASE IF NOT EXISTS online_trip_management;
USE online_trip_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS agent_assignments;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS itineraries;
DROP TABLE IF EXISTS package_destinations;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('traveler', 'admin', 'agent') NOT NULL DEFAULT 'traveler',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    profile_image VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    country VARCHAR(120) NOT NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_days INT NOT NULL,
    max_participants INT NOT NULL DEFAULT 1,
    available_slots INT NOT NULL DEFAULT 1,
    image VARCHAR(255) NULL,
    inclusions TEXT NULL,
    exclusions TEXT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    created_by INT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_packages_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS package_destinations (
    package_id INT NOT NULL,
    destination_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id, destination_id),
    CONSTRAINT fk_pd_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_destination FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS itineraries (
    itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    day_number INT NOT NULL,
    activity_title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    activity_time TIME NULL,
    location VARCHAR(180) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_itineraries_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    num_travelers INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(120) NULL,
    payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT NULL,
    review_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT uq_reviews_user_package UNIQUE (user_id, package_id),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS agent_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    package_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_agent_package UNIQUE (agent_id, package_id),
    CONSTRAINT fk_assignments_agent FOREIGN KEY (agent_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password_hash, role, status)
SELECT
    'System Admin',
    'admin@tripease.local',
    '$2y$10$nxWTaiW.fYZ9YgPt/VA9/.RTxckKYLnr1qDvrlXJbUFLXTX9D3fIq',
    'admin',
    'active'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@tripease.local'
);
