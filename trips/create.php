<?php
declare(strict_types=1);
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();
redirectTo("/admin/packages/create.php");
