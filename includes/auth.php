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

function dashboardPath(): string
{
    if (isAdmin()) {
        return "/admin/index.php";
    }
    return "/dashboard/index.php";
}

/**
 * Sync session role/status from DB so deactivated or re-roled users lose access immediately.
 */
function refreshSessionUser(?PDO $pdo = null): void
{
    if (!isLoggedIn()) {
        return;
    }

    $db = $pdo ?? ($GLOBALS["pdo"] ?? null);
    if (!$db instanceof PDO) {
        return;
    }

    $stmt = $db->prepare("SELECT name, role, status FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute(["id" => (int)$_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user || $user["status"] !== "active") {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        setFlash("warning", "Your account is inactive or no longer available. Please log in again.");
        redirectTo("/auth/login.php");
    }

    // Legacy agent accounts are treated as travelers after role removal.
    $role = $user["role"] === "admin" ? "admin" : "traveler";
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_role"] = $role;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectTo("/auth/login.php");
    }
    refreshSessionUser();
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        setFlash("danger", "Admin access required.");
        redirectTo(dashboardPath());
    }
}

function requireTraveler(): void
{
    requireLogin();
    if (!isTraveler()) {
        setFlash("danger", "Traveler access required.");
        redirectTo(dashboardPath());
    }
}

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") . '">';
}

function verifyCsrf(?string $token): void
{
    $sessionToken = $_SESSION["csrf_token"] ?? "";
    if ($sessionToken === "" || !is_string($token) || !hash_equals($sessionToken, $token)) {
        setFlash("danger", "Invalid security token. Please try again.");
        redirectTo(dashboardPath());
    }
}
