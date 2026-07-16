<?php
declare(strict_types=1);

require_once __DIR__ . "/app.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION["user_id"]);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectTo("/auth/login.php");
    }
}

function currentUserRole(): string
{
    return $_SESSION["user_role"] ?? "traveler";
}

function isAdmin(): bool
{
    return currentUserRole() === "admin";
}

function isTraveler(): bool
{
    return currentUserRole() === "traveler";
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        redirectTo("/index.php");
    }
}

function requireTraveler(): void
{
    requireLogin();
    if (!isTraveler()) {
        redirectTo("/trips/list.php");
    }
}
