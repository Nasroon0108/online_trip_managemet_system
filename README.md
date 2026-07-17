# Trip Ease — Online Trip Management System

A web-based trip planning and booking platform built with PHP, MySQL, and Bootstrap 5.

## Features

### Traveler
- Dedicated dashboard with trip overview, upcoming cards, activity, and booking ledger
- Register and login
- Browse packages with search, pagination, and ratings
- View package details, destinations, and day-by-day itinerary
- Book packages with traveler count
- Complete mock/sandbox payment
- View all bookings and paid bookings separately
- Cancel eligible bookings
- Write reviews after trip completion
- Update profile (name, phone, password)

### Admin
- Dashboard with revenue, booking stats, and popular destinations
- Manage destinations (create, edit, activate/deactivate)
- Manage packages (create, edit, assign destinations, activate/deactivate)
- Manage itineraries per package
- Manage all bookings and filter paid/unpaid
- Manage users and roles (traveler / admin)
- Preview the public package catalog

## Tech Stack
- Backend: PHP (PDO)
- Database: MySQL
- Frontend: Bootstrap 5

## Setup Steps
1. Put this folder in your web server root (for example `htdocs/online_trip_managemet_system` in XAMPP).
2. Import `database.sql` in phpMyAdmin (recreates all tables and seeds demo data).
3. Update `config/db.php` if your MySQL username/password differs.
4. Start Apache and MySQL in XAMPP.
5. Open: `http://localhost/online_trip_managemet_system/index.php`

## Default Accounts
- Admin: `admin@tripease.local` / `admin123`
- Traveler: `traveler@tripease.local` / `traveler123`

## Demo Data Included
- 6 Sri Lankan destinations (Kandy, Ella, Sigiriya, Nuwara Eliya, Galle, Mirissa)
- 4 packages with day-by-day itineraries (hill country, cultural triangle, south coast, Ella adventure)
- Sample traveler booking (confirmed Southern Coast + completed Hill Country with review)

## Roles
| Role | Access |
|---|---|
| Traveler | Dashboard, browse packages, book, pay, paid bookings, review, profile |
| Admin | Full console: destinations, packages, itineraries, bookings, paid bookings, users, reports |

## Suggested Test Flow
1. Login as admin and create destinations + packages + itinerary items.
2. Register a traveler account (or use the demo traveler).
3. Open the traveler Dashboard, then browse packages and create a booking.
4. Complete mock payment on the payment page.
5. Check Paid Bookings for the successful payment.
6. As admin, mark the booking as completed.
7. As traveler, submit a review from My Bookings or the package page.
8. Check admin dashboard reports and package ratings.

## Project Structure
```
/config         Database connection
/includes       Header, footer, auth helpers
/admin          Admin dashboard and CRUD modules
/dashboard      Traveler dashboard
/trips          Package browsing and detail pages
/bookings       Booking creation, history, paid ledger
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
