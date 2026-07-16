<?php if (!isLoggedIn()): ?>
    </main>
    <footer class="public-footer py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-1 text-dark fw-semibold">&copy; <?= date("Y") ?> TripEase. All rights reserved.</p>
            <p class="mb-0 text-muted small">Make travel easy — Online Trip Management System</p>
        </div>
    </footer>
<?php else: ?>
            </main>
            <footer class="bg-white py-4 mt-auto border-top border-light">
                <div class="container-fluid px-4 text-center">
                    <p class="mb-1 text-dark fw-semibold">&copy; <?= date("Y") ?> TripEase. All rights reserved.</p>
                    <p class="mb-0 text-muted small">Make travel easy &mdash; Online Trip Management System</p>
                </div>
            </footer>
        </div><!-- /.main-content -->
    </div><!-- /.sidebar-layout -->
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
