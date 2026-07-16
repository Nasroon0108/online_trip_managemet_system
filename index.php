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
        <p class="landing-brand">TripEase</p>
        <h1 class="landing-title">Travel planning,<br>made operational.</h1>
        <p class="landing-subtitle">
            A complete trip management platform for travelers, trip agents, and administrators —
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
            <h2 class="section-title mb-2">Built for three roles</h2>
            <p class="text-muted mb-0">Each role gets a focused workspace after login.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <article class="role-card h-100">
                    <div class="role-icon"><i class="fa-solid fa-suitcase-rolling"></i></div>
                    <h3>Traveler</h3>
                    <p>Browse packages, view itineraries, book trips, complete sandbox payment, and leave reviews after completion.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="role-card h-100">
                    <div class="role-icon"><i class="fa-solid fa-person-hiking"></i></div>
                    <h3>Trip Agent</h3>
                    <p>Work on assigned packages, build day-by-day itineraries, and keep trip activity details accurate for travelers.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="role-card h-100">
                    <div class="role-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Admin</h3>
                    <p>Manage destinations, packages, bookings, users, agent assignments, and view revenue/report insights.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="landing-demo py-5">
    <div class="container">
        <div class="demo-panel">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="section-title mb-3">Demo accounts</h2>
                    <p class="text-muted mb-4">Use these seeded accounts to explore each role after importing `database.sql`.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="demo-account">
                                <div class="small text-muted">Admin</div>
                                <div class="fw-semibold">admin@tripease.local</div>
                                <div class="small">admin123</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="demo-account">
                                <div class="small text-muted">Agent</div>
                                <div class="fw-semibold">agent@tripease.local</div>
                                <div class="small">agent123</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="demo-account">
                                <div class="small text-muted">Traveler</div>
                                <div class="fw-semibold">traveler@tripease.local</div>
                                <div class="small">traveler123</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="demo-cta">
                        <h4 class="mb-2">Ready to start?</h4>
                        <p class="mb-3 text-muted">Login first. The sidebar workspace appears only after authentication.</p>
                        <a class="btn btn-brand w-100" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
