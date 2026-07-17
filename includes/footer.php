<?php if (!isLoggedIn()): ?>
    </main>
    <footer class="public-footer py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-1 text-dark fw-semibold">&copy; <?= date("Y") ?> Trip Ease. All rights reserved.</p>
            <p class="mb-0 text-muted small">Make travel easy — Online Trip Management System</p>
        </div>
    </footer>
<?php else: ?>
            </main>
            <footer class="app-footer py-4 mt-auto">
                <div class="container-fluid px-4 text-center">
                    <p class="mb-1 fw-semibold app-footer-title">&copy; <?= date("Y") ?> Trip Ease. All rights reserved.</p>
                    <p class="mb-0 text-muted small">Make travel easy &mdash; Online Trip Management System</p>
                </div>
            </footer>
        </div><!-- /.main-content -->
    </div><!-- /.sidebar-layout -->
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var root = document.documentElement;

    function getTheme() {
        return root.getAttribute("data-theme") === "dark" ? "dark" : "light";
    }

    function setTheme(theme) {
        root.setAttribute("data-theme", theme);
        try {
            localStorage.setItem("tripease-theme", theme);
        } catch (e) {}
        document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
            btn.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
            btn.title = theme === "dark" ? "Switch to light mode" : "Switch to dark mode";
        });
    }

    document.addEventListener("click", function (event) {
        var btn = event.target.closest("[data-theme-toggle]");
        if (!btn) {
            return;
        }
        event.preventDefault();
        setTheme(getTheme() === "dark" ? "light" : "dark");
    });

    setTheme(getTheme());
})();
</script>
</body>
</html>
