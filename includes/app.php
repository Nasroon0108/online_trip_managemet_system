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

function userAvatarUrl(?string $image): ?string
{
    if ($image === null || trim($image) === "") {
        return null;
    }
    return appUrl("/uploads/avatars/" . rawurlencode($image));
}

/**
 * Render an avatar circle: uploaded photo if present, otherwise the name initial.
 */
function avatarCircle(string $name, ?string $image, string $extraClasses = "", string $style = ""): string
{
    $url = userAvatarUrl($image);
    $classes = trim("avatar-circle " . $extraClasses);
    $styleAttr = $style !== "" ? ' style="' . htmlspecialchars($style, ENT_QUOTES, "UTF-8") . '"' : "";

    if ($url !== null) {
        return '<span class="' . htmlspecialchars($classes, ENT_QUOTES, "UTF-8") . ' avatar-circle-img"' . $styleAttr . '>'
            . '<img src="' . htmlspecialchars($url, ENT_QUOTES, "UTF-8") . '"'
            . ' alt="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '"'
            . ' style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">'
            . '</span>';
    }

    $initial = strtoupper(substr(trim($name) !== "" ? $name : "U", 0, 1));
    return '<span class="' . htmlspecialchars($classes, ENT_QUOTES, "UTF-8") . '"' . $styleAttr . '>'
        . htmlspecialchars($initial, ENT_QUOTES, "UTF-8")
        . '</span>';
}
