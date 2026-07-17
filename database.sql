CREATE DATABASE IF NOT EXISTS online_trip_management;
USE online_trip_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS itineraries;
DROP TABLE IF EXISTS package_destinations;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS agent_assignments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('traveler', 'admin') NOT NULL DEFAULT 'traveler',
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

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Kasun Admin', 'admin@tripease.local', '$2y$10$nxWTaiW.fYZ9YgPt/VA9/.RTxckKYLnr1qDvrlXJbUFLXTX9D3fIq', 'admin', 'active');

INSERT INTO users (name, email, password_hash, phone, role, status) VALUES
('Sajani Fernando', 'traveler@tripease.local', '$2y$10$CvhWnE2FrOc0Su/5.2fC9OjP2Xcmo6zGZ3xQui7hoMBqQqDbCtbza', '0712345678', 'traveler', 'active');

INSERT INTO destinations (name, country, description, status) VALUES
('Kandy', 'Sri Lanka', 'Cultural capital in the hills, home to the Temple of the Tooth and scenic Kandy Lake.', 'active'),
('Ella', 'Sri Lanka', 'Hill-country town famous for Nine Arch Bridge, Little Adam''s Peak, and tea estate views.', 'active'),
('Sigiriya', 'Sri Lanka', 'UNESCO rock fortress with frescoes, palace ruins, and panoramic jungle views.', 'active'),
('Nuwara Eliya', 'Sri Lanka', 'Cool-climate tea country known as Little England, with plantations and colonial charm.', 'active'),
('Galle', 'Sri Lanka', 'Southern coastal city with a Dutch fort, rampart walks, cafes, and heritage streets.', 'active'),
('Mirissa', 'Sri Lanka', 'Beach town popular for whale watching, surfing, and relaxed southern coastline vibes.', 'active');

INSERT INTO packages
(title, description, price, duration_days, max_participants, available_slots, inclusions, exclusions, start_date, end_date, created_by, status)
VALUES
(
    'Hill Country Tea Trail',
    'A scenic Sri Lankan hill-country circuit covering Nuwara Eliya tea estates and Ella viewpoints. Ideal for nature lovers and couples.',
    54999.00, 5, 20, 16,
    'Hotel stay, breakfast, tea factory visit, sightseeing transfers, local guide support',
    'Flights, lunch/dinner, train tickets (optional), personal expenses',
    DATE_ADD(CURDATE(), INTERVAL 20 DAY),
    DATE_ADD(CURDATE(), INTERVAL 24 DAY),
    1, 'active'
),
(
    'Cultural Triangle Heritage Tour',
    'Explore Kandy and Sigiriya with temple visits, fortress climbs, and cultural evenings in Sri Lanka''s heritage heartland.',
    69999.00, 6, 25, 20,
    '3–4 star hotels, breakfast, private AC transport, listed site entry tickets',
    'Flights, jeep safari fees, shopping expenses',
    DATE_ADD(CURDATE(), INTERVAL 35 DAY),
    DATE_ADD(CURDATE(), INTERVAL 40 DAY),
    1, 'active'
),
(
    'Southern Coast Escape',
    'Sun and sea along Sri Lanka''s south coast — Galle Fort walks and Mirissa beach days with optional whale watching.',
    42999.00, 4, 30, 28,
    'Beach hotel, breakfast, airport/city pickup, Galle Fort walking tour',
    'Flights, whale-watching boat fees, alcohol, nightlife tickets',
    DATE_ADD(CURDATE(), INTERVAL 12 DAY),
    DATE_ADD(CURDATE(), INTERVAL 15 DAY),
    1, 'active'
),
(
    'Ella Highlands Adventure',
    'Active hill-country package with Little Adam''s Peak hikes, Nine Arch Bridge, and tea estate trails around Ella.',
    48999.00, 5, 18, 12,
    'Hotel stay, breakfast, local sightseeing transfers, trek guidance',
    'Adventure activity fees, flights, train seat upgrades',
    DATE_ADD(CURDATE(), INTERVAL 45 DAY),
    DATE_ADD(CURDATE(), INTERVAL 49 DAY),
    1, 'active'
);

INSERT INTO package_destinations (package_id, destination_id) VALUES
(1, 4), (1, 2),
(2, 1), (2, 3),
(3, 5), (3, 6),
(4, 2);

INSERT INTO itineraries (package_id, day_number, activity_title, description, activity_time, location) VALUES
(1, 1, 'Arrive Nuwara Eliya', 'Check-in and evening stroll around Gregory Lake.', '15:00:00', 'Nuwara Eliya'),
(1, 2, 'Tea Estate Tour', 'Visit a working tea factory and plantation viewpoint.', '09:30:00', 'Nuwara Eliya'),
(1, 3, 'Transfer to Ella', 'Scenic hill drive with waterfall photo stops.', '10:00:00', 'Ella'),
(1, 4, 'Nine Arch Bridge', 'Morning visit to the bridge and nearby tea trails.', '08:00:00', 'Ella'),
(1, 5, 'Departure', 'Breakfast and checkout assistance.', '09:00:00', 'Ella'),
(2, 1, 'Arrive Kandy', 'City orientation and evening lake walk.', '16:00:00', 'Kandy'),
(2, 2, 'Temple of the Tooth', 'Guided temple visit and cultural show.', '09:00:00', 'Kandy'),
(2, 3, 'Travel to Sigiriya', 'Road transfer with village lunch stop.', '08:30:00', 'Sigiriya'),
(2, 4, 'Sigiriya Rock Fortress', 'Guided climb and museum visit.', '07:30:00', 'Sigiriya'),
(2, 5, 'Village & Cave Temples', 'Optional Dambulla caves or village cycling.', '10:00:00', 'Sigiriya'),
(2, 6, 'Departure', 'Checkout and transfer support.', '09:00:00', 'Sigiriya'),
(3, 1, 'Arrive Galle', 'Hotel check-in and Fort rampart walk.', '14:00:00', 'Galle Fort'),
(3, 2, 'Galle Heritage Day', 'Dutch fort, lighthouse, and cafe streets.', '10:00:00', 'Galle'),
(3, 3, 'Mirissa Beach Day', 'Beach time with optional whale-watching trip.', '07:00:00', 'Mirissa'),
(3, 4, 'Departure', 'Checkout and airport/city drop.', '09:30:00', 'Mirissa'),
(4, 1, 'Arrive Ella', 'Check-in and Main Street cafe evening.', '16:00:00', 'Ella'),
(4, 2, 'Little Adam''s Peak', 'Sunrise hike and viewpoint photography.', '06:00:00', 'Ella'),
(4, 3, 'Nine Arch & Ravana Falls', 'Iconic bridge visit and waterfall stop.', '09:30:00', 'Ella'),
(4, 4, 'Tea Trails Leisure', 'Optional estate walk or spa afternoon.', '11:00:00', 'Ella'),
(4, 5, 'Departure', 'Checkout assistance.', '09:00:00', 'Ella');

INSERT INTO bookings (user_id, package_id, num_travelers, total_price, status, booking_date) VALUES
(2, 3, 2, 85998.00, 'confirmed', NOW()),
(2, 1, 2, 109998.00, 'completed', DATE_SUB(NOW(), INTERVAL 40 DAY));

UPDATE packages SET available_slots = available_slots - 2 WHERE package_id IN (1, 3);

INSERT INTO payments (booking_id, amount, payment_method, status, transaction_ref, payment_date) VALUES
(1, 85998.00, 'mock_card', 'success', 'TXN-DEMO-SOUTH-001', NOW()),
(2, 109998.00, 'mock_card', 'success', 'TXN-DEMO-HILL-001', DATE_SUB(NOW(), INTERVAL 40 DAY));

INSERT INTO reviews (user_id, package_id, rating, comment, review_date) VALUES
(2, 1, 5, 'Beautiful tea estates and cool Ella mornings. A wonderful Sri Lankan hill-country trip!', DATE_SUB(NOW(), INTERVAL 35 DAY));

