<?php
declare(strict_types=1);
require_once __DIR__ . "/../includes/app.php";
session_start();
session_unset();
session_destroy();
redirectTo("/index.php");
