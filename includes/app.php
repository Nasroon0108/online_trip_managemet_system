<?php
declare(strict_types=1);

if (!defined("APP_BASE_PATH")) {
    define("APP_BASE_PATH", "/online_trip_managemet_system");
}

function appUrl(string $path = ""): string
{
    $base = rtrim(APP_BASE_PATH, "/");
    if ($path === "") {
        return $base;
    }

    return $base . "/" . ltrim($path, "/");
}

function redirectTo(string $path): void
{
    header("Location: " . appUrl($path));
    exit;
}

function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function consumeFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION["flash"])) {
        return null;
    }
    $flash = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $flash;
}

function bookingStatusClass(string $status): string
{
    return match ($status) {
        "confirmed" => "text-success",
        "completed" => "text-primary",
        "cancelled" => "text-danger",
        default => "text-muted",
    };
}
