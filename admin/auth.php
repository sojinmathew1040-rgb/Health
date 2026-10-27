<?php
/**
 * Admin Authentication & Session Management
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_user']);
}

function getLoggedInAdmin() {
    return $_SESSION['admin_user'] ?? [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@healthjobs.com',
        'name' => 'Chief Medical Director',
        'role' => 'Super Administrator'
    ];
}

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        $loginUrl = 'login.php';
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header("Location: {$loginUrl}");
            exit;
        }
    }
}
