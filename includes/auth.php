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

function isAgent(): bool
{
    return currentUserRole() === "agent";
}

function dashboardPath(): string
{
    if (isAdmin()) {
        return "/admin/index.php";
    }
    if (isAgent()) {
        return "/agent/index.php";
    }
    return "/trips/list.php";
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

    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_role"] = $user["role"];
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

function requireAgent(): void
{
    requireLogin();
    if (!isAgent()) {
        setFlash("danger", "Agent access required.");
        redirectTo(dashboardPath());
    }
}

function requireStaff(): void
{
    requireLogin();
    if (!isAdmin() && !isAgent()) {
        setFlash("danger", "Staff access required.");
        redirectTo(dashboardPath());
    }
}

function agentCanAccessPackage(PDO $pdo, int $packageId, int $agentId): bool
{
    $stmt = $pdo->prepare(
        "SELECT assignment_id FROM agent_assignments
         WHERE package_id = :package_id AND agent_id = :agent_id
         LIMIT 1"
    );
    $stmt->execute([
        "package_id" => $packageId,
        "agent_id" => $agentId,
    ]);
    return (bool)$stmt->fetch();
}

function agentCanAccessBooking(PDO $pdo, int $bookingId, int $agentId): bool
{
    $stmt = $pdo->prepare(
        "SELECT b.booking_id
         FROM bookings b
         INNER JOIN agent_assignments aa ON aa.package_id = b.package_id
         WHERE b.booking_id = :booking_id AND aa.agent_id = :agent_id
         LIMIT 1"
    );
    $stmt->execute([
        "booking_id" => $bookingId,
        "agent_id" => $agentId,
    ]);
    return (bool)$stmt->fetch();
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
