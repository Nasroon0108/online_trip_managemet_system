# TripEase — Online Trip Management System

A web-based trip planning and booking platform built with PHP, MySQL, and Bootstrap 5.

## Features

### Traveler
- Register and login
- Browse packages with pagination and ratings
- View package details, destinations, and day-by-day itinerary
- Book packages with traveler count
- Complete mock/sandbox payment
- View booking history and cancel bookings
- Write reviews after trip completion
- Update profile (name, phone, password)

### Admin
- Dashboard with revenue, booking stats, and popular destinations
- Manage destinations (create, edit, activate/deactivate)
- Manage packages (create, edit, assign destinations, activate/deactivate)
- Manage itineraries per package
- Manage bookings (complete/cancel)
- Manage users (activate/deactivate)

## Tech Stack
- Backend: PHP (PDO)
- Database: MySQL
- Frontend: Bootstrap 5

## Setup Steps
1. Put this folder in your web server root (for example `htdocs/online_trip_managemet_system` in XAMPP).
2. Import `database.sql` in phpMyAdmin (recreates all tables and seeds the admin account).
3. Update `config/db.php` if your MySQL username/password differs.
4. Start Apache and MySQL in XAMPP.
5. Open: `http://localhost/online_trip_managemet_system/index.php`

## Default Admin Login
- Email: `admin@tripease.local`
- Password: `admin123`

## Suggested Test Flow
1. Login as admin and create destinations + packages + itinerary items.
2. Register a traveler account.
3. Browse packages, open package detail, and create a booking.
4. Complete mock payment on the payment page.
5. As admin, mark the booking as completed.
6. As traveler, submit a review from `My Bookings` or the package page.
7. Check admin dashboard reports and package ratings.

## Project Structure
```
/config         Database connection
/includes       Header, footer, auth helpers
/admin          Admin dashboard and CRUD modules
/trips          Package browsing and detail pages
/bookings       Booking creation and history
/payments       Mock payment flow
/reviews        Traveler reviews
/profile        Profile management
/assets         CSS and static assets
database.sql    Database schema
index.php       Home page
```

## Notes
- Card details in the payment form are sandbox-only and are not stored.
- Re-importing `database.sql` will reset existing data.
