<?php
/**
 * Admin Panel - Logout
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['admin_user']);
session_destroy();

header('Location: login.php?msg=' . urlencode('You have been logged out successfully.'));
exit;
