<?php
declare(strict_types=1);
require_once __DIR__ . "/includes/auth.php";

if (isLoggedIn()) {
    redirectTo(dashboardPath());
}

require_once __DIR__ . "/includes/header.php";
?>

<section class="landing-hero">
    <div class="landing-hero-media" aria-hidden="true"></div>
    <div class="container landing-hero-content">
        <p class="landing-brand">Trip Ease</p>
        <h1 class="landing-title">Travel planning,<br>made operational.</h1>
        <p class="landing-subtitle">
            A complete trip management platform for travelers and administrators —
            from package browsing to booking, payments, itineraries, and reviews.
        </p>
        <div class="d-flex gap-3 flex-wrap">
            <a class="btn btn-brand btn-lg px-4" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Login to continue</a>
        </div>
    </div>
</section>

<section class="landing-roles py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="section-title mb-2">Built for two roles</h2>
            <p class="text-muted mb-0">Travelers book trips. Admins run the platform.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <article class="role-card h-100">
                    <div class="role-icon"><i class="fa-solid fa-suitcase-rolling"></i></div>
                    <h3>Traveler</h3>
                    <p>Browse packages, view itineraries, book trips, complete sandbox payment, and leave reviews after completion.</p>
                </article>
            </div>
            <div class="col-md-5">
                <article class="role-card h-100">
                    <div class="role-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Admin</h3>
                    <p>Manage destinations, packages, itineraries, bookings, users, and view revenue and report insights.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="landing-features py-5">
    <div class="container">
        <div class="features-panel">
            <div class="row align-items-stretch g-4">
                <div class="col-lg-7">
                    <h2 class="section-title mb-2">What you can do</h2>
                    <p class="text-muted mb-4">Everything from discovery to payment and reviews stays in one workspace.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="feature-tile">
                                <div class="feature-tile-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                                <h3>Browse packages</h3>
                                <p>Explore destinations, durations, and pricing before you book.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-tile">
                                <div class="feature-tile-icon"><i class="fa-solid fa-route"></i></div>
                                <h3>Follow itineraries</h3>
                                <p>See day-by-day plans so every trip stays clear and organized.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-tile">
                                <div class="feature-tile-icon"><i class="fa-solid fa-calendar-check"></i></div>
                                <h3>Book &amp; pay</h3>
                                <p>Reserve seats, track booking status, and complete sandbox payment.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-tile">
                                <div class="feature-tile-icon"><i class="fa-solid fa-star"></i></div>
                                <h3>Leave reviews</h3>
                                <p>Share feedback after a completed trip to help other travelers.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="features-cta h-100 d-flex flex-column justify-content-center">
                        <h4 class="mb-2">Ready to start?</h4>
                        <p class="mb-3">Login first. The sidebar workspace appears only after authentication.</p>
                        <a class="btn btn-brand w-100" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
