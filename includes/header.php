<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Trip Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="/index.php">OTMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="/trips/list.php">Trips</a></li>
                    <li class="nav-item"><a class="nav-link" href="/bookings/my_bookings.php">My Bookings</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex">
                <?php if (isLoggedIn()): ?>
                    <a class="btn btn-outline-light btn-sm" href="/auth/logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-light btn-sm me-2" href="/auth/login.php">Login</a>
                    <a class="btn btn-warning btn-sm" href="/auth/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container pb-4">
