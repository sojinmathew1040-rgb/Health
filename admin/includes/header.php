<?php
/**
 * Admin Panel - Global Header Component
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../auth.php';

// Check Authentication
requireAdminAuth();

$adminUser = getLoggedInAdmin();
$currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Control Center — HealthJobsGlobal'); ?></title>
  <link rel="stylesheet" href="assets/css/admin.css?v=1.0">
</head>
<body>

<div class="admin-wrapper">
<?php require_once __DIR__ . '/sidebar.php'; ?>
