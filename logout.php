<?php
/**
 * HealthJobsGlobal.com - Logout Handler
 */
require_once __DIR__ . '/includes/config.php';

// Unset user session
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}
session_destroy();

// Redirect to home page with query param
header("Location: " . BASE_URL . "/index.php?logged_out=1");
exit;
