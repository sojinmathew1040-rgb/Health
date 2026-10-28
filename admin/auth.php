<?php
/**
 * Admin Authentication & Session Management
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_user']);
}

function getLoggedInAdmin(): array {
    return $_SESSION['admin_user'] ?? [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@healthjobs.com',
        'name' => 'Chief Medical Director',
        'role' => 'Super Administrator'
    ];
}

function requireAdminAuth(): void {
    if (!isAdminLoggedIn()) {
        $loginUrl = 'login.php';
        $currentSelf = (string)($_SERVER['PHP_SELF'] ?? '');
        if (basename($currentSelf) !== 'login.php') {
            header("Location: {$loginUrl}");
            exit;
        }
    }
}
