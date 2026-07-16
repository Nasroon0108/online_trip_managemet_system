<?php
declare(strict_types=1);

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
        header("Location: /auth/login.php");
        exit;
    }
}
