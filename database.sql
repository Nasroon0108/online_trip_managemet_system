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

INSERT INTO users (name, email, password_hash, role, status) VALUES
('System Admin', 'admin@tripease.local', '$2y$10$nxWTaiW.fYZ9YgPt/VA9/.RTxckKYLnr1qDvrlXJbUFLXTX9D3fIq', 'admin', 'active');

INSERT INTO users (name, email, password_hash, phone, role, status) VALUES
('Trip Agent', 'agent@tripease.local', '$2y$10$VTZ.mvoCZkytUcBSJOev3uTdfxFiJY66GCOVPeblXlTcs7WSwbUNG', '9876543210', 'agent', 'active');

INSERT INTO users (name, email, password_hash, phone, role, status) VALUES
('Aisha Traveler', 'traveler@tripease.local', '$2y$10$CvhWnE2FrOc0Su/5.2fC9OjP2Xcmo6zGZ3xQui7hoMBqQqDbCtbza', '9123456780', 'traveler', 'active');

INSERT INTO destinations (name, country, description, status) VALUES
('Munnar', 'India', 'Hill station known for tea plantations, misty mountains, and cool climate.', 'active'),
('Alleppey', 'India', 'Backwaters destination famous for houseboats, canals, and lagoon views.', 'active'),
('Jaipur', 'India', 'The Pink City with forts, palaces, bazaars, and rich Rajasthani culture.', 'active'),
('Udaipur', 'India', 'City of lakes with romantic palace views and heritage architecture.', 'active'),
('Goa', 'India', 'Coastal escape with beaches, seafood, and lively nightlife.', 'active'),
('Manali', 'India', 'Himalayan town for adventure activities, snow views, and scenic valleys.', 'active');

INSERT INTO packages
(title, description, price, duration_days, max_participants, available_slots, inclusions, exclusions, start_date, end_date, created_by, status)
VALUES
(
    'Kerala Tea & Backwaters Escape',
    'A relaxing Kerala circuit covering Munnar tea estates and Alleppey houseboat stays. Ideal for couples and families looking for nature and calm travel.',
    18999.00, 5, 20, 16,
    'Hotel stay, breakfast, houseboat night, sightseeing transfers, guide support',
    'Flights, lunch/dinner (except houseboat), personal expenses',
    DATE_ADD(CURDATE(), INTERVAL 20 DAY),
    DATE_ADD(CURDATE(), INTERVAL 24 DAY),
    1, 'active'
),
(
    'Royal Rajasthan Heritage Tour',
    'Explore Jaipur and Udaipur with guided fort visits, cultural evenings, and heritage hotel stays.',
    24999.00, 6, 25, 20,
    '4-star hotels, breakfast, private AC transport, monument entry for listed sites',
    'Flights, camel ride fees, shopping expenses',
    DATE_ADD(CURDATE(), INTERVAL 35 DAY),
    DATE_ADD(CURDATE(), INTERVAL 40 DAY),
    1, 'active'
),
(
    'Goa Beach Getaway',
    'Sun, sand, and easy days across North Goa beaches with optional water sports.',
    12999.00, 4, 30, 28,
    'Beach hotel, breakfast, airport pickup, one water-sport voucher',
    'Flights, nightlife tickets, alcohol',
    DATE_ADD(CURDATE(), INTERVAL 12 DAY),
    DATE_ADD(CURDATE(), INTERVAL 15 DAY),
    1, 'active'
),
(
    'Manali Adventure Week',
    'Mountain adventure package with Solang Valley activities and local sightseeing.',
    16999.00, 5, 18, 12,
    'Hotel stay, breakfast, local sightseeing transfers, adventure activity booking help',
    'Adventure activity fees, flights, winter gear rental',
    DATE_ADD(CURDATE(), INTERVAL 45 DAY),
    DATE_ADD(CURDATE(), INTERVAL 49 DAY),
    1, 'active'
);

INSERT INTO package_destinations (package_id, destination_id) VALUES
(1, 1), (1, 2),
(2, 3), (2, 4),
(3, 5),
(4, 6);

INSERT INTO itineraries (package_id, day_number, activity_title, description, activity_time, location) VALUES
(1, 1, 'Arrive Munnar', 'Check-in and evening tea garden walk.', '15:00:00', 'Munnar'),
(1, 2, 'Tea Estate Tour', 'Visit tea museum and viewpoint trails.', '09:30:00', 'Munnar'),
(1, 3, 'Transfer to Alleppey', 'Scenic road transfer and canal orientation.', '10:00:00', 'Alleppey'),
(1, 4, 'Houseboat Cruise', 'Full-day backwater cruise with onboard meals.', '08:00:00', 'Alleppey Backwaters'),
(1, 5, 'Departure', 'Breakfast and checkout assistance.', '09:00:00', 'Alleppey'),
(2, 1, 'Jaipur Arrival', 'City orientation and local market stroll.', '16:00:00', 'Jaipur'),
(2, 2, 'Amber Fort Visit', 'Guided tour of Amber Fort and Jal Mahal viewpoint.', '09:00:00', 'Jaipur'),
(2, 3, 'Travel to Udaipur', 'Road transfer with evening lake walk.', '08:30:00', 'Udaipur'),
(2, 4, 'City Palace Tour', 'Heritage walk and boat ride on Lake Pichola.', '10:00:00', 'Udaipur'),
(2, 5, 'Cultural Evening', 'Folk performance and leisure shopping.', '18:00:00', 'Udaipur'),
(2, 6, 'Departure', 'Checkout and transfer support.', '09:00:00', 'Udaipur'),
(3, 1, 'Arrive Goa', 'Hotel check-in and beach time.', '14:00:00', 'Calangute'),
(3, 2, 'North Goa Tour', 'Visit key beaches and fort viewpoints.', '10:00:00', 'North Goa'),
(3, 3, 'Water Sports Day', 'Optional jet ski/parasailing session.', '11:00:00', 'Baga Beach'),
(3, 4, 'Departure', 'Checkout and airport drop.', '09:30:00', 'Goa'),
(4, 1, 'Arrive Manali', 'Check-in and Mall Road walk.', '16:00:00', 'Manali'),
(4, 2, 'Solang Valley', 'Adventure zone visit and photo stops.', '09:00:00', 'Solang Valley'),
(4, 3, 'Local Sightseeing', 'Hadimba Temple and surrounding viewpoints.', '10:00:00', 'Manali'),
(4, 4, 'Leisure Day', 'Optional spa or cafe hopping.', '11:00:00', 'Manali'),
(4, 5, 'Departure', 'Checkout assistance.', '09:00:00', 'Manali');

INSERT INTO agent_assignments (agent_id, package_id) VALUES
(2, 1),
(2, 2),
(2, 4);

INSERT INTO bookings (user_id, package_id, num_travelers, total_price, status, booking_date) VALUES
(3, 3, 2, 25998.00, 'confirmed', NOW()),
(3, 1, 2, 37998.00, 'completed', DATE_SUB(NOW(), INTERVAL 40 DAY));

UPDATE packages SET available_slots = available_slots - 2 WHERE package_id IN (1, 3);

INSERT INTO payments (booking_id, amount, payment_method, status, transaction_ref, payment_date) VALUES
(1, 25998.00, 'mock_card', 'success', 'TXN-DEMO-GOA-001', NOW()),
(2, 37998.00, 'mock_card', 'success', 'TXN-DEMO-KER-001', DATE_SUB(NOW(), INTERVAL 40 DAY));

INSERT INTO reviews (user_id, package_id, rating, comment, review_date) VALUES
(3, 1, 5, 'Beautiful tea gardens and peaceful houseboat stay. Highly recommended!', DATE_SUB(NOW(), INTERVAL 35 DAY));

