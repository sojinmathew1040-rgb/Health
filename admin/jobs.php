<?php
/**
 * Admin Panel - Clinical Jobs Directory & Vacancy Management
 * Apple Level Pro Redesign - Zero Sideways Scroll, SF Pro Aesthetics, Responsive macOS Architecture
 * Route: http://localhost/Health/admin/jobs.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requireAdminAuth();

// Handle Actions (Add, Edit, Delete, Toggle Status) BEFORE sending any HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add Job
    if ($action === 'create_job') {
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $department = trim($_POST['department'] ?? 'Doctors & Clinical Specialists');
        $specialty = trim($_POST['specialty'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $council_req = trim($_POST['council_req'] ?? 'Council Registered');
        $status = trim($_POST['status'] ?? 'Active');
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');

        if (!empty($title) && !empty($company)) {
            $stmt = $con->prepare("INSERT INTO `jobs` (`title`, `company`, `department`, `specialty`, `location`, `salary`, `experience`, `qualification`, `council_req`, `status`, `urgent`, `featured`, `description`, `posted_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("sssssssssiiss", $title, $company, $department, $specialty, $location, $salary, $experience, $qualification, $council_req, $status, $urgent, $featured, $description);
                $stmt->execute();
                $stmt->close();
                $targetUrl = 'jobs.php?success=' . urlencode("Job '{$title}' created successfully!");
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // 2. Edit Job
    if ($action === 'update_job') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $council_req = trim($_POST['council_req'] ?? 'Council Registered');
        $status = trim($_POST['status'] ?? 'Active');
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && !empty($title)) {
            $stmt = $con->prepare("UPDATE `jobs` SET `title` = ?, `company` = ?, `department` = ?, `specialty` = ?, `location` = ?, `salary` = ?, `experience` = ?, `qualification` = ?, `council_req` = ?, `status` = ?, `urgent` = ?, `featured` = ?, `description` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("sssssssssiisi", $title, $company, $department, $specialty, $location, $salary, $experience, $qualification, $council_req, $status, $urgent, $featured, $description, $id);
                $stmt->execute();
                $stmt->close();
                $targetUrl = 'jobs.php?success=' . urlencode("Job updated successfully!");
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // 3. Delete Job
    if ($action === 'delete_job') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `jobs` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                $targetUrl = 'jobs.php?success=' . urlencode("Job listing deleted.");
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // 4. Quick Status Toggle
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'Active');
        if ($id > 0) {
            $stmt = $con->prepare("UPDATE `jobs` SET `status` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newStatus, $id);
                $stmt->execute();
                $stmt->close();
                $targetUrl = 'jobs.php?success=' . urlencode("Status updated to {$newStatus}.");
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }
}

// Clean up any lingering clinical jobs whose parent hospital has been deleted
cleanupOrphanJobs($con);

// Fetch Metrics & Counts
$totalJobsCount = 0;
$activeJobsCount = 0;
$urgentJobsCount = 0;
$featuredJobsCount = 0;

if (!empty($con) && !$con->connect_error) {
    $resCounts = $con->query("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_c,
        SUM(CASE WHEN urgent = 1 THEN 1 ELSE 0 END) as urgent_c,
        SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_c
        FROM `jobs`");
    if ($resCounts && $cRow = $resCounts->fetch_assoc()) {
        $totalJobsCount = (int)$cRow['total'];
        $activeJobsCount = (int)($cRow['active_c'] ?? 0);
        $urgentJobsCount = (int)($cRow['urgent_c'] ?? 0);
        $featuredJobsCount = (int)($cRow['featured_c'] ?? 0);
    }
}

// Search and Filter Handling
$filterTab = trim($_GET['tab'] ?? 'all');
$filterDept = trim($_GET['department'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM `jobs` WHERE 1=1";
$params = [];
$types = "";

if ($filterTab === 'active') {
    $sql .= " AND `status` = 'Active'";
} elseif ($filterTab === 'urgent') {
    $sql .= " AND `urgent` = 1";
} elseif ($filterTab === 'featured') {
    $sql .= " AND `featured` = 1";
}

if (!empty($filterDept)) {
    $sql .= " AND `department` = ?";
    $params[] = $filterDept;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $sql .= " AND (`title` LIKE ? OR `company` LIKE ? OR `specialty` LIKE ? OR `location` LIKE ?)";
    $likeQ = "%" . $searchQuery . "%";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= "ssss";
}

$sql .= " ORDER BY `id` DESC";

$jobs = [];
if (!empty($con) && !$con->connect_error) {
    if (!empty($params)) {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $jobs[] = $row;
            }
            $stmt->close();
        }
    } else {
        $res = $con->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $jobs[] = $row;
            }
        }
    }
}

// Fetch Departments
$departments = [
    'Doctors & Clinical Specialists',
    'Nursing Specialties',
    'Diagnostics, Pathology & Pharmacy',
    'Allied Health & Paramedical',
    'Hospital Operations & Leadership'
];
if (!empty($con) && !$con->connect_error) {
    $resD = $con->query("SELECT DISTINCT name FROM `departments` ORDER BY name ASC");
    if ($resD && $resD->num_rows > 0) {
        $departments = [];
        while ($d = $resD->fetch_assoc()) {
            $departments[] = $d['name'];
        }
    }
}

// Single Job to Edit if requested via GET
$editingJob = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $con->prepare("SELECT * FROM `jobs` WHERE id = ?");
    if ($stmtEdit) {
        $stmtEdit->bind_param("i", $editId);
        $stmtEdit->execute();
        $resEdit = $stmtEdit->get_result();
        $editingJob = $resEdit->fetch_assoc();
        $stmtEdit->close();
    }
}

$pageTitle = "Clinical Jobs Directory — HealthJobsGlobal Admin";
$pageHeading = "Clinical Jobs & Vacancies Management";

$quickActionBtn = '<button class="apple-btn-primary" onclick="openAddJobModal()" style="font-size: 0.82rem; padding: 7px 16px;">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Post New Opening</span>
</button>';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Apple Level Pro Clinical Jobs Styling (Zero Sideways Scroll, SF Pro Aesthetics) -->
<style>
:root {
  --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", 'Plus Jakarta Sans', sans-serif;
  --apple-bg: #F5F7FA;
  --apple-card-bg: rgba(255, 255, 255, 0.95);
  --apple-border: rgba(226, 232, 240, 0.85);
  --apple-blue: #0071E3;
  --apple-blue-hover: #0077ED;
  --apple-green: #34C759;
  --apple-green-hover: #2DAE4E;
  --apple-amber: #FF9500;
  --apple-red: #FF3B30;
  --apple-text-main: #1D1D1F;
  --apple-text-muted: #6E6E73;
  --apple-text-light: #86868B;
  --apple-card-shadow: 0 2px 14px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
  --apple-card-hover: 0 10px 30px -4px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.03);
}

.admin-body {
  font-family: var(--apple-font) !important;
  overflow-x: hidden !important;
  max-width: 100% !important;
  padding: 24px !important;
  box-sizing: border-box !important;
}

/* Luminous Apple Pro Hero Card */
.apple-hero-card {
  position: relative;
  background: linear-gradient(135deg, #06192E 0%, #0A2E50 50%, #021224 100%);
  border-radius: 20px;
  padding: 30px 36px;
  margin-bottom: 24px;
  color: #FFFFFF;
  box-shadow: 0 14px 34px -8px rgba(6, 25, 46, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 24px;
  overflow: hidden;
}

.apple-hero-card::after {
  content: "";
  position: absolute;
  top: -80px;
  right: -80px;
  width: 260px;
  height: 260px;
  background: radial-gradient(circle, rgba(0, 113, 227, 0.4) 0%, rgba(52, 199, 89, 0) 70%);
  pointer-events: none;
  filter: blur(40px);
}

.apple-hero-left {
  max-width: 680px;
  z-index: 1;
}

.apple-pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 12px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #E2E8F0;
  margin-bottom: 12px;
}

.apple-hero-title {
  font-family: var(--apple-font);
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.025em;
  color: #FFFFFF;
  margin: 0 0 8px 0;
  line-height: 1.25;
}

.apple-hero-sub {
  color: #94A3B8;
  font-size: 0.92rem;
  line-height: 1.5;
  margin: 0;
}

/* Luminous Indicator Pills */
.apple-metrics-group {
  display: flex;
  gap: 12px;
  z-index: 1;
  flex-wrap: wrap;
}

.apple-metric-pill {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  padding: 14px 22px;
  text-align: center;
  min-width: 110px;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-metric-pill:hover {
  background: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
}

.apple-metric-val {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1;
  margin-bottom: 4px;
}

.apple-metric-lbl {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #CBD5E1;
}

/* Pulsing Status Dots */
.pulse-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  position: relative;
  margin-right: 6px;
}

.pulse-dot.amber {
  background: #FF9500;
  box-shadow: 0 0 0 0 rgba(255, 149, 0, 0.7);
  animation: pulse-amber 2s infinite;
}

.pulse-dot.emerald {
  background: #34C759;
  box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7);
  animation: pulse-emerald 2s infinite;
}

@keyframes pulse-amber {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 149, 0, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(255, 149, 0, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 149, 0, 0); }
}

@keyframes pulse-emerald {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(52, 199, 89, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0); }
}

/* Apple Segmented Control (macOS Pill Track) */
.apple-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.apple-segmented-track {
  background: #E8EDF2;
  border-radius: 9999px;
  padding: 4px;
  display: inline-flex;
  gap: 3px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06);
}

.apple-segment-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border-radius: 9999px;
  font-size: 0.84rem;
  font-weight: 600;
  color: #64748B;
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  white-space: nowrap;
}

.apple-segment-item:hover {
  color: #0F172A;
}

.apple-segment-item.active {
  background: #FFFFFF;
  color: #0F172A;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
}

.apple-count-bubble {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 9999px;
  background: #F1F5F9;
  color: #475569;
}

.apple-segment-item.active .apple-count-bubble {
  background: #0071E3;
  color: #FFFFFF;
}

/* Spotlight Style Search Input */
.apple-spotlight-box {
  position: relative;
  width: 300px;
  max-width: 100%;
}

.apple-spotlight-input {
  width: 100%;
  height: 40px;
  padding: 0 40px 0 38px;
  border-radius: 9999px;
  background: #FFFFFF;
  border: 1px solid var(--apple-border);
  font-size: 0.86rem;
  font-family: var(--apple-font);
  color: var(--apple-text-main);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  outline: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
}

.apple-spotlight-input:focus {
  border-color: var(--apple-blue);
  box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.18), 0 2px 6px rgba(0, 0, 0, 0.04);
}

.apple-spotlight-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--apple-text-light);
  pointer-events: none;
}

.apple-spotlight-badge {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--apple-text-light);
  background: #F1F5F9;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid #E2E8F0;
  pointer-events: none;
}

/* Apple Pro Zero-Scroll Cards Feed */
.apple-cards-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  box-sizing: border-box;
}

.apple-employer-card {
  width: 100%;
  box-sizing: border-box;
  background: #FFFFFF;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.85);
  box-shadow: var(--apple-card-shadow);
  padding: 22px 26px;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  overflow: hidden;
}

.apple-employer-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--apple-card-hover);
  border-color: rgba(203, 213, 225, 0.95);
}

.apple-employer-card.is-pending {
  border-left: 5px solid var(--apple-amber);
  background: linear-gradient(180deg, #FFFFFF 0%, #FFFCF7 100%);
}

.apple-employer-card.is-verified {
  border-left: 5px solid var(--apple-green);
}

/* Card Header Strip */
.apple-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 14px;
  padding-bottom: 16px;
  border-bottom: 1px solid #F1F5F9;
  margin-bottom: 18px;
}

.apple-org-identity {
  display: flex;
  align-items: center;
  gap: 16px;
}

.apple-squircle-avatar {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.05rem;
  letter-spacing: -0.02em;
  color: #FFFFFF;
  background: linear-gradient(135deg, #0284C7, #0369A1);
  box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
  flex-shrink: 0;
}

.apple-org-title {
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: -0.015em;
  color: var(--apple-text-main);
  margin: 0 0 3px 0;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.apple-subtext {
  font-size: 0.82rem;
  color: var(--apple-text-muted);
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* Status Capsules */
.apple-status-capsule {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.apple-status-capsule.pending {
  background: #FFFBEB;
  color: #92400E;
  border: 1px solid #FDE68A;
}

.apple-status-capsule.active {
  background: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

/* Card Content Balanced Grid (Zero Horizontal Overflow) */
.apple-info-grid {
  display: grid;
  grid-template-columns: 1.2fr 1.1fr 1.3fr;
  gap: 20px;
  margin-bottom: 20px;
}

@media (max-width: 1024px) {
  .apple-info-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .apple-info-grid {
    grid-template-columns: 1fr;
  }
}

.apple-info-cell {
  background: #F8FAFC;
  border-radius: 12px;
  padding: 14px 16px;
  border: 1px solid #EDF2F7;
}

.apple-cell-header {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--apple-text-light);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.apple-cell-main {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--apple-text-main);
  margin-bottom: 2px;
}

.apple-cell-sub {
  font-size: 0.8rem;
  color: var(--apple-text-muted);
  line-height: 1.4;
}

.apple-badge-pill {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  background: #E2E8F0;
  color: #334155;
  margin-right: 4px;
  margin-top: 4px;
}

.apple-service-tag {
  background: #E0F2FE;
  color: #0369A1;
  font-weight: 600;
}

/* Apple Pro Action Bar */
.apple-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #F1F5F9;
}

.apple-footer-meta {
  font-size: 0.78rem;
  color: var(--apple-text-light);
  display: flex;
  align-items: center;
  gap: 12px;
}

.apple-action-btn-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* Apple Styled Buttons */
.apple-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--apple-green);
  color: #FFFFFF;
  border: none;
  border-radius: 9999px;
  padding: 8px 18px;
  font-size: 0.84rem;
  font-weight: 700;
  font-family: var(--apple-font);
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(52, 199, 89, 0.35);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-decoration: none;
}

.apple-btn-primary:hover {
  background: var(--apple-green-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(52, 199, 89, 0.45);
}

.apple-btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F1F5F9;
  color: #334155;
  border: 1px solid #E2E8F0;
  border-radius: 9999px;
  padding: 8px 16px;
  font-size: 0.84rem;
  font-weight: 600;
  font-family: var(--apple-font);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-decoration: none;
}

.apple-btn-secondary:hover {
  background: #E2E8F0;
  color: #0F172A;
}

.apple-btn-icon-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 9999px;
  background: #FFF1F2;
  color: #E11D48;
  border: 1px solid #FFE4E6;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-btn-icon-danger:hover {
  background: #FFE4E6;
  color: #BE123C;
  transform: scale(1.05);
}

.apple-form-group {
  margin-bottom: 16px;
}

.apple-form-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--apple-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 6px;
}

.apple-input, .apple-select, .apple-textarea {
  width: 100%;
  font-family: var(--apple-font);
  font-size: 0.88rem;
  color: var(--apple-text-main);
  background: #FFFFFF;
  border: 1px solid #CBD5E1;
  border-radius: 10px;
  padding: 10px 14px;
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.apple-input:focus, .apple-select:focus, .apple-textarea:focus {
  border-color: var(--apple-blue);
  box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
}

/* Apple Pro macOS Sheet Modal */
.apple-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  padding: 20px;
}

.apple-modal-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.apple-modal-sheet {
  background: #FFFFFF;
  border-radius: 22px;
  width: 100%;
  max-width: 760px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(226, 232, 240, 0.8);
  overflow: hidden;
  transform: scale(0.96) translateY(10px);
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.apple-modal-overlay.active .apple-modal-sheet {
  transform: scale(1) translateY(0);
}

.apple-modal-sheet-header {
  padding: 22px 28px;
  border-bottom: 1px solid #F1F5F9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #FAFAFA;
}

.apple-close-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #E2E8F0;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #475569;
  font-weight: 700;
  font-size: 1.1rem;
  transition: all 0.15s;
}

.apple-close-circle:hover {
  background: #CBD5E1;
  color: #0F172A;
}

.apple-modal-sheet > form {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
  margin: 0;
}

.apple-modal-sheet-body {
  padding: 22px 28px;
  overflow-y: auto !important;
  flex: 1 1 auto;
  min-height: 0;
}

/* Apple Settings-Style Grouped Rows */
.apple-settings-group {
  background: #F8FAFC;
  border-radius: 14px;
  border: 1px solid #EDF2F7;
  overflow: hidden;
  margin-bottom: 20px;
}

.apple-settings-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 13px 18px;
  border-bottom: 1px solid #EDF2F7;
}

.apple-settings-row:last-child {
  border-bottom: none;
}

.apple-settings-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--apple-text-muted);
}

.apple-settings-val {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--apple-text-main);
  text-align: right;
}

.apple-modal-sheet-footer {
  padding: 16px 28px;
  border-top: 1px solid #F1F5F9;
  background: #FAFAFA;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  position: sticky;
  bottom: 0;
  z-index: 10;
}
</style>

<!-- Apple Pro Luminous Hero Header -->
<div class="apple-hero-card">
  <div class="apple-hero-left">
    <div class="apple-pill-tag">
      <span class="pulse-dot emerald"></span>
      <span>Clinical Talent Architecture & Vacancies</span>
    </div>
    <h2 class="apple-hero-title">Clinical Jobs & Openings Directory</h2>
    <p class="apple-hero-sub">
      Coordinate verified medical appointments, physician consultancies, nursing leadership vacancies, and allied healthcare careers across partner hospital networks.
    </p>
  </div>

  <div class="apple-metrics-group">
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #34C759;">
        <span class="pulse-dot emerald" style="margin-right: 2px;"></span><?php echo $activeJobsCount; ?>
      </div>
      <div class="apple-metric-lbl">Live & Active Jobs</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #FFB020;">
        <span class="pulse-dot amber" style="margin-right: 2px;"></span><?php echo $urgentJobsCount; ?>
      </div>
      <div class="apple-metric-lbl">Urgent Postings</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #AF52DE;">
        <?php echo $featuredJobsCount; ?>
      </div>
      <div class="apple-metric-lbl">Featured Openings</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #FFFFFF;">
        <?php echo $totalJobsCount; ?>
      </div>
      <div class="apple-metric-lbl">Total Catalog</div>
    </div>
  </div>
</div>

<!-- Apple Toolbar: Segmented Controls + Spotlight Search -->
<div class="apple-toolbar">
  <div class="apple-segmented-track">
    <a href="jobs.php?tab=all" class="apple-segment-item <?php echo $filterTab === 'all' ? 'active' : ''; ?>">
      <span>All Vacancies</span>
      <span class="apple-count-bubble"><?php echo $totalJobsCount; ?></span>
    </a>
    <a href="jobs.php?tab=active" class="apple-segment-item <?php echo $filterTab === 'active' ? 'active' : ''; ?>">
      <span class="pulse-dot emerald"></span>
      <span>Active Only</span>
      <span class="apple-count-bubble"><?php echo $activeJobsCount; ?></span>
    </a>
    <a href="jobs.php?tab=urgent" class="apple-segment-item <?php echo $filterTab === 'urgent' ? 'active' : ''; ?>">
      <span class="pulse-dot amber"></span>
      <span>Urgent Hires</span>
      <span class="apple-count-bubble"><?php echo $urgentJobsCount; ?></span>
    </a>
    <a href="jobs.php?tab=featured" class="apple-segment-item <?php echo $filterTab === 'featured' ? 'active' : ''; ?>">
      <span>Featured</span>
      <span class="apple-count-bubble"><?php echo $featuredJobsCount; ?></span>
    </a>
  </div>

  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <!-- Live Spotlight Search -->
    <div class="apple-spotlight-box">
      <svg class="apple-spotlight-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" class="apple-spotlight-input" placeholder="Search role, hospital, specialty..." id="appleJobSearchInput" value="<?php echo htmlspecialchars($searchQuery); ?>">
      <span class="apple-spotlight-badge" id="searchMatchCount"><?php echo count($jobs); ?></span>
    </div>

    <!-- Department Selector Dropdown -->
    <form method="GET" action="jobs.php" id="deptFilterForm" style="margin: 0;">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($filterTab); ?>">
      <select name="department" class="apple-select" onchange="document.getElementById('deptFilterForm').submit()" style="padding: 9px 14px; font-size: 0.82rem; height: 40px; border-radius: 9999px; background: #FFF; width: auto; font-weight: 600;">
        <option value="">All Clinical Departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filterDept === $dept) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($dept); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <button type="button" class="apple-btn-primary" onclick="openAddJobModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>Post New Job</span>
    </button>
  </div>
</div>

<!-- Apple Pro Zero-Scroll Clinical Jobs Feed -->
<div class="apple-cards-container" id="jobCardsContainer">
  <?php if (empty($jobs)): ?>
    <div class="apple-empty-state" style="padding: 60px 24px; text-align: center; background: #FFFFFF; border-radius: 20px; border: 1px dashed #CBD5E1;">
      <div style="font-size: 3rem; margin-bottom: 12px;">🩺</div>
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--apple-text-main); margin-bottom: 8px;">No Clinical Openings Found</h3>
      <p style="font-size: 0.92rem; color: var(--apple-text-muted); max-width: 480px; margin: 0 auto 20px auto; line-height: 1.55;">
        No clinical job postings match your active search filters. You can clear filters or publish a new medical opening immediately.
      </p>
      <button type="button" class="apple-btn-primary" onclick="openAddJobModal()" style="display: inline-flex;">
        <span>+ Post First Clinical Job &rarr;</span>
      </button>
    </div>
  <?php else: ?>
    <script>
      window.allJobsData = window.allJobsData || {};
    </script>
    <?php foreach ($jobs as $job): 
      $jId = (int)$job['id'];
      $isActive = ($job['status'] === 'Active');
      $isUrgent = !empty($job['urgent']);
      $isFeatured = !empty($job['featured']);
      $avatarText = substr(preg_replace('/[^A-Za-z]/', '', $job['company'] ?: 'HSP'), 0, 4);
      if (!$avatarText) $avatarText = 'CLIN';
    ?>
      <script>
        window.allJobsData[<?php echo $jId; ?>] = <?php echo json_encode($job, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
      </script>

      <div class="apple-employer-card <?php echo $isActive ? 'is-verified' : 'is-pending'; ?>" data-search-target="<?php echo htmlspecialchars(strtolower($job['title'] . ' ' . $job['company'] . ' ' . $job['specialty'] . ' ' . $job['department'] . ' ' . $job['location'])); ?>">
        
        <!-- Header Strip -->
        <div class="apple-card-header">
          <div class="apple-org-identity">
            <div class="apple-squircle-avatar" style="background: <?php echo $isUrgent ? 'linear-gradient(135deg, #E11D48, #BE123C)' : 'linear-gradient(135deg, #0071E3, #0284C7)'; ?>;">
              <?php echo htmlspecialchars(strtoupper($avatarText)); ?>
            </div>
            <div>
              <h3 class="apple-org-title">
                <span><?php echo htmlspecialchars($job['title']); ?></span>
                <?php if ($isUrgent): ?>
                  <span class="apple-badge-pill" style="background: #FFE4E6; color: #BE123C; font-weight: 800; font-size: 0.68rem;">URGENT RECRUITMENT</span>
                <?php endif; ?>
                <?php if ($isFeatured): ?>
                  <span class="apple-badge-pill" style="background: #F3E8FF; color: #7E22CE; font-weight: 700; font-size: 0.68rem;">FEATURED</span>
                <?php endif; ?>
              </h3>
              <div class="apple-subtext">
                <span style="font-weight: 700; color: #0F172A;"><?php echo htmlspecialchars($job['company']); ?></span>
                <span>·</span>
                <span class="apple-badge-pill apple-service-tag"><?php echo htmlspecialchars($job['specialty'] ?: $job['department']); ?></span>
                <span>·</span>
                <span>Ref: JOB-<?php echo str_pad($job['id'], 5, '0', STR_PAD_LEFT); ?></span>
              </div>
            </div>
          </div>

          <!-- Status Indicator Pill -->
          <div>
            <?php if ($job['status'] === 'Active'): ?>
              <div class="apple-status-capsule active">
                <span class="pulse-dot emerald"></span>
                <span>Active & Live Opening</span>
              </div>
            <?php elseif ($job['status'] === 'Pending'): ?>
              <div class="apple-status-capsule pending">
                <span class="pulse-dot amber"></span>
                <span>Pending Review</span>
              </div>
            <?php else: ?>
              <div class="apple-status-capsule" style="background: #F1F5F9; color: #64748B;">
                <span>● Closed Vacancy</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 3-Column Balanced Information Grid -->
        <div class="apple-info-grid">
          
          <!-- Column 1: Clinical Department & Council -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/></svg>
              <span>Clinical Specialization</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars($job['specialty'] ?: 'General Practice'); ?>
            </div>
            <div class="apple-cell-sub">
              <?php echo htmlspecialchars($job['department']); ?>
            </div>
            <div style="margin-top: 6px;">
              <span class="apple-badge-pill" style="background: #ECFDF5; color: #065F46; font-weight: 600;">
                ✓ <?php echo htmlspecialchars($job['council_req'] ?: 'State / NMC Registered'); ?>
              </span>
            </div>
          </div>

          <!-- Column 2: Campus & Experience Requirements -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>Campus & Experience</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars($job['location'] ?: 'India'); ?>
            </div>
            <div class="apple-cell-sub" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" title="<?php echo htmlspecialchars($job['qualification'] ?: 'MBBS / Specialist Degree'); ?>">
              Edu: <?php echo htmlspecialchars($job['qualification'] ?: 'MBBS / Specialist Degree'); ?>
            </div>
            <div style="margin-top: 6px;">
              <span class="apple-badge-pill" style="background: #EEF2F6; color: #475569; font-weight: 600;">
                ⏱ <?php echo htmlspecialchars($job['experience'] ?: 'Standard Experience'); ?>
              </span>
            </div>
          </div>

          <!-- Column 3: Compensation & Cadre -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
              <span>Remuneration & Cadre</span>
            </div>
            <div class="apple-cell-main" style="color: #0284C7; font-size: 1rem; font-weight: 800;">
              <?php echo htmlspecialchars($job['salary'] ?: 'Competitive Industry Standard'); ?>
            </div>
            <div class="apple-cell-sub">
              Hospital Sponsored Benefits & Insurance
            </div>
            <div style="margin-top: 6px;">
              <span class="apple-badge-pill" style="background: #F8FAFC; border: 1px solid #E2E8F0; color: #0F172A; font-weight: 600;">
                Institutional Package
              </span>
            </div>
          </div>

        </div>

        <!-- Footer Action Strip -->
        <div class="apple-card-footer">
          <div class="apple-footer-meta">
            <span>Posted: <strong><?php echo !empty($job['posted_at']) ? date('M d, Y', strtotime($job['posted_at'])) : 'Recently'; ?></strong></span>
            <span>·</span>
            <span>Ref: <strong>#<?php echo $job['id']; ?></strong></span>
          </div>

          <div class="apple-action-btn-group">
            
            <!-- Inspect Full Details -->
            <button type="button" class="apple-btn-secondary" onclick="openInspectJobModalById(<?php echo $jId; ?>)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <span>Inspect Details</span>
            </button>

            <!-- Public Showcase Link -->
            <a href="../job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="apple-btn-secondary" style="color: var(--apple-blue);" title="View Public Job Page">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
              <span>Public View &nearr;</span>
            </a>

            <!-- Edit Job Button -->
            <button type="button" class="apple-btn-secondary" onclick="openEditJobModalById(<?php echo $jId; ?>)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              <span>Edit</span>
            </button>

            <!-- Quick Status Change Form -->
            <form method="POST" action="jobs.php" style="display:inline;">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
              <select name="new_status" onchange="this.form.submit()" class="apple-select" style="padding: 4px 10px; font-size: 0.76rem; font-weight: 700; height: 32px; width: auto; border-radius: 9999px; cursor: pointer;">
                <option value="Active" <?php echo ($job['status'] === 'Active') ? 'selected' : ''; ?>>🟢 Active</option>
                <option value="Pending" <?php echo ($job['status'] === 'Pending') ? 'selected' : ''; ?>>🟡 Pending</option>
                <option value="Closed" <?php echo ($job['status'] === 'Closed') ? 'selected' : ''; ?>>🔴 Closed</option>
              </select>
            </form>

            <!-- Delete Action -->
            <form method="POST" action="jobs.php" onsubmit="return confirm('Permanently remove job opening: <?php echo addslashes($job['title']); ?>?');" style="display:inline;">
              <input type="hidden" name="action" value="delete_job">
              <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
              <button type="submit" class="apple-btn-icon-danger" title="Delete Job Opening">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </form>

          </div>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Apple macOS Sheet Modal: Inspect Job Details -->
<div class="apple-modal-overlay" id="inspectJobModal">
  <div class="apple-modal-sheet" style="max-width: 740px;">
    <div class="apple-modal-sheet-header">
      <div>
        <h3 id="mdlJobTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Clinical Job Title</h3>
        <div id="mdlJobStatusBadge" style="margin-top: 4px;"></div>
      </div>
      <button class="apple-close-circle" onclick="closeInspectJobModal()">&times;</button>
    </div>

    <div class="apple-modal-sheet-body">
      <!-- Group 1: Hospital & Specialty -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        1. Clinical Specialization & Institution
      </div>
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label">Healthcare Facility / Hospital</span>
          <span class="apple-settings-val" id="mdlJobCompany">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Clinical Department</span>
          <span class="apple-settings-val" id="mdlJobDepartment">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Sub-Specialty Focus</span>
          <span class="apple-settings-val" id="mdlJobSpecialty">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Location / Campus</span>
          <span class="apple-settings-val" id="mdlJobLocation">-</span>
        </div>
      </div>

      <!-- Group 2: Compensation & Requirements -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        2. Remuneration & Candidate Prerequisites
      </div>
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label">Annual Salary Package</span>
          <span class="apple-settings-val" id="mdlJobSalary" style="color: var(--apple-blue); font-weight: 800;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Required Clinical Experience</span>
          <span class="apple-settings-val" id="mdlJobExperience">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Educational Qualification</span>
          <span class="apple-settings-val" id="mdlJobQualification">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Medical Council / Regulatory Requirement</span>
          <span class="apple-settings-val" id="mdlJobCouncilReq">-</span>
        </div>
      </div>

      <!-- Group 3: Clinical Role Description -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        3. Detailed Clinical Responsibilities
      </div>
      <div class="apple-settings-group" style="padding: 16px 20px;">
        <div id="mdlJobDescription" style="font-size: 0.88rem; color: #334155; line-height: 1.6; white-space: pre-wrap;">-</div>
      </div>
    </div>

    <div class="apple-modal-sheet-footer">
      <button type="button" class="apple-btn-secondary" onclick="closeInspectJobModal()">Close</button>
      <div id="mdlInspectActions"></div>
    </div>
  </div>
</div>

<!-- Apple macOS Sheet Modal: Post New Job -->
<div class="apple-modal-overlay" id="addJobModal">
  <div class="apple-modal-sheet" style="max-width: 760px;">
    <div class="apple-modal-sheet-header">
      <div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Post Clinical Opening</h3>
        <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">Publish verified vacancy to healthcare network and candidate exchange</div>
      </div>
      <button class="apple-close-circle" onclick="closeAddJobModal()">&times;</button>
    </div>

    <form method="POST" action="jobs.php">
      <input type="hidden" name="action" value="create_job">
      <div class="apple-modal-sheet-body">
        
        <!-- Group 1: Position & Facility -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          1. Position & Healthcare Establishment
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div class="apple-form-group">
            <label class="apple-form-label">Position Title / Clinical Role *</label>
            <input type="text" name="title" class="apple-input" placeholder="e.g. Senior Consultant – Interventional Cardiology" required>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Hospital / Network Name *</label>
              <input type="text" name="company" class="apple-input" placeholder="e.g. VPS Lakeshore Hospital" required>
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Campus Location *</label>
              <input type="text" name="location" class="apple-input" placeholder="e.g. Nettoor, Kochi, Kerala, India" required>
            </div>
          </div>
        </div>

        <!-- Group 2: Specialty & Department -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          2. Clinical Discipline & Hierarchy
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Clinical Department</label>
              <select name="department" class="apple-select">
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Sub-Specialty Focus</label>
              <input type="text" name="specialty" class="apple-input" placeholder="e.g. Interventional Cardiology, Trauma Care">
            </div>
          </div>
        </div>

        <!-- Group 3: Compensation & Qualifications -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          3. Remuneration & Qualifications
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Salary Package</label>
              <input type="text" name="salary" class="apple-input" placeholder="e.g. ₹40,00,000 - ₹50,00,000 / year">
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Experience Required</label>
              <input type="text" name="experience" class="apple-input" placeholder="e.g. 5+ Years Post-MD/DNB">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Required Qualifications</label>
              <input type="text" name="qualification" class="apple-input" placeholder="e.g. MBBS, MD, DM / MCh">
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Medical Council / Regulatory Requirement</label>
              <input type="text" name="council_req" class="apple-input" placeholder="e.g. Active State Medical Council / NMC">
            </div>
          </div>

          <div class="apple-form-group" style="margin-bottom: 0;">
            <label class="apple-form-label">Clinical Overview & Requirements</label>
            <textarea name="description" class="apple-textarea" rows="3" placeholder="Cath lab volume, outpatient expectations, emergency call rotations..."></textarea>
          </div>
        </div>

        <!-- Group 4: Status & Priority Badges -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 18px; display: flex; gap: 24px; flex-wrap: wrap;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.86rem; font-weight: 600;">
            <input type="checkbox" name="urgent" value="1" style="width: 17px; height: 17px; accent-color: #E11D48;">
            <span>Mark as Urgent Recruitment</span>
          </label>
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.86rem; font-weight: 600;">
            <input type="checkbox" name="featured" value="1" checked style="width: 17px; height: 17px; accent-color: #0071E3;">
            <span>Highlight as Featured Opening</span>
          </label>
        </div>

      </div>

      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeAddJobModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Publish Clinical Job</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Apple macOS Sheet Modal: Edit Existing Job -->
<div class="apple-modal-overlay <?php echo $editingJob ? 'active' : ''; ?>" id="editJobModal">
  <div class="apple-modal-sheet" style="max-width: 760px;">
    <div class="apple-modal-sheet-header">
      <div>
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Edit Clinical Job #<?php echo $editingJob ? $editingJob['id'] : ''; ?></h3>
        <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">Update role specifications, requirements, and compensation</div>
      </div>
      <button class="apple-close-circle" onclick="closeEditJobModal()">&times;</button>
    </div>

    <form method="POST" action="jobs.php" id="editJobForm">
      <input type="hidden" name="action" value="update_job">
      <input type="hidden" name="id" id="editJobId" value="<?php echo $editingJob ? $editingJob['id'] : ''; ?>">
      
      <div class="apple-modal-sheet-body">
        
        <!-- Group 1: Position & Hospital -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          1. Position & Healthcare Establishment
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div class="apple-form-group">
            <label class="apple-form-label">Position Title *</label>
            <input type="text" name="title" id="editJobTitle" class="apple-input" value="<?php echo htmlspecialchars($editingJob['title'] ?? ''); ?>" required>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Hospital / Network Name *</label>
              <input type="text" name="company" id="editJobCompany" class="apple-input" value="<?php echo htmlspecialchars($editingJob['company'] ?? ''); ?>" required>
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Location *</label>
              <input type="text" name="location" id="editJobLocation" class="apple-input" value="<?php echo htmlspecialchars($editingJob['location'] ?? ''); ?>" required>
            </div>
          </div>
        </div>

        <!-- Group 2: Department & Sub-Specialty -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          2. Clinical Discipline & Hierarchy
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Clinical Department</label>
              <select name="department" id="editJobDepartment" class="apple-select">
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (($editingJob['department'] ?? '') === $dept) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Sub-Specialty Focus</label>
              <input type="text" name="specialty" id="editJobSpecialty" class="apple-input" value="<?php echo htmlspecialchars($editingJob['specialty'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <!-- Group 3: Compensation & Qualifications -->
        <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
          3. Remuneration & Qualifications
        </div>
        <div class="apple-settings-group" style="padding: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Salary Package</label>
              <input type="text" name="salary" id="editJobSalary" class="apple-input" value="<?php echo htmlspecialchars($editingJob['salary'] ?? ''); ?>">
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Experience Required</label>
              <input type="text" name="experience" id="editJobExperience" class="apple-input" value="<?php echo htmlspecialchars($editingJob['experience'] ?? ''); ?>">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="apple-form-group">
              <label class="apple-form-label">Required Qualifications</label>
              <input type="text" name="qualification" id="editJobQualification" class="apple-input" value="<?php echo htmlspecialchars($editingJob['qualification'] ?? ''); ?>">
            </div>
            <div class="apple-form-group">
              <label class="apple-form-label">Medical Council / Regulatory Requirement</label>
              <input type="text" name="council_req" id="editJobCouncilReq" class="apple-input" value="<?php echo htmlspecialchars($editingJob['council_req'] ?? 'Council Registered'); ?>">
            </div>
          </div>

          <div class="apple-form-group" style="margin-bottom: 0;">
            <label class="apple-form-label">Clinical Overview & Requirements</label>
            <textarea name="description" id="editJobDescription" class="apple-textarea" rows="3"><?php echo htmlspecialchars($editingJob['description'] ?? ''); ?></textarea>
          </div>
        </div>

        <!-- Group 4: Status & Priority Badges -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <label class="apple-form-label" style="margin: 0;">Status:</label>
            <select name="status" id="editJobStatus" class="apple-select" style="width: auto; padding: 5px 12px; font-weight: 700;">
              <option value="Active" <?php echo (($editingJob['status'] ?? '') === 'Active') ? 'selected' : ''; ?>>🟢 Active</option>
              <option value="Pending" <?php echo (($editingJob['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>🟡 Pending</option>
              <option value="Closed" <?php echo (($editingJob['status'] ?? '') === 'Closed') ? 'selected' : ''; ?>>🔴 Closed</option>
            </select>
          </div>

          <div style="display: flex; gap: 18px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.86rem; font-weight: 600;">
              <input type="checkbox" name="urgent" id="editJobUrgent" value="1" <?php echo (!empty($editingJob['urgent'])) ? 'checked' : ''; ?> style="width: 17px; height: 17px; accent-color: #E11D48;">
              <span>Urgent</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.86rem; font-weight: 600;">
              <input type="checkbox" name="featured" id="editJobFeatured" value="1" <?php echo (!empty($editingJob['featured'])) ? 'checked' : ''; ?> style="width: 17px; height: 17px; accent-color: #0071E3;">
              <span>Featured</span>
            </label>
          </div>
        </div>

      </div>

      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeEditJobModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Update Job Listing</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Apple Level Pro Interactive JavaScript -->
<script>
function openAddJobModal() {
  document.getElementById('addJobModal').classList.add('active');
}

function closeAddJobModal() {
  document.getElementById('addJobModal').classList.remove('active');
}

function openInspectJobModalById(id) {
  const job = window.allJobsData ? window.allJobsData[id] : null;
  if (!job) return;

  document.getElementById('mdlJobTitle').textContent = job.title || 'Clinical Role';
  
  const statusEl = document.getElementById('mdlJobStatusBadge');
  if (job.status === 'Active') {
    statusEl.innerHTML = `<span class="apple-status-capsule active"><span class="pulse-dot emerald"></span>Active & Live Opening</span>`;
  } else if (job.status === 'Pending') {
    statusEl.innerHTML = `<span class="apple-status-capsule pending"><span class="pulse-dot amber"></span>Pending Review</span>`;
  } else {
    statusEl.innerHTML = `<span class="apple-status-capsule" style="background: #F1F5F9; color: #64748B;">● Closed</span>`;
  }

  document.getElementById('mdlJobCompany').textContent = job.company || '-';
  document.getElementById('mdlJobDepartment').textContent = job.department || '-';
  document.getElementById('mdlJobSpecialty').textContent = job.specialty || 'General Specialization';
  document.getElementById('mdlJobLocation').textContent = job.location || 'India';

  document.getElementById('mdlJobSalary').textContent = job.salary || 'Competitive Industry Standard';
  document.getElementById('mdlJobExperience').textContent = job.experience || 'Not Specified';
  document.getElementById('mdlJobQualification').textContent = job.qualification || 'MBBS / Specialist Certification';
  document.getElementById('mdlJobCouncilReq').textContent = job.council_req || 'State Medical Council / NMC';
  document.getElementById('mdlJobDescription').textContent = job.description || 'No detailed clinical description provided.';

  const actionDiv = document.getElementById('mdlInspectActions');
  actionDiv.innerHTML = `
    <a href="../job-details.php?id=${job.id}" target="_blank" class="apple-btn-secondary" style="color: var(--apple-blue);">
      <span>View Public Page &nearr;</span>
    </a>
    <button type="button" class="apple-btn-primary" onclick="closeInspectJobModal(); openEditJobModalById(${job.id});">
      <span>Edit Opening</span>
    </button>
  `;

  document.getElementById('inspectJobModal').classList.add('active');
}

function closeInspectJobModal() {
  document.getElementById('inspectJobModal').classList.remove('active');
}

function openEditJobModalById(id) {
  const job = window.allJobsData ? window.allJobsData[id] : null;
  if (!job) return;

  document.getElementById('editJobId').value = job.id;
  document.getElementById('editJobTitle').value = job.title || '';
  document.getElementById('editJobCompany').value = job.company || '';
  document.getElementById('editJobLocation').value = job.location || '';
  document.getElementById('editJobDepartment').value = job.department || '';
  document.getElementById('editJobSpecialty').value = job.specialty || '';
  document.getElementById('editJobSalary').value = job.salary || '';
  document.getElementById('editJobExperience').value = job.experience || '';
  document.getElementById('editJobQualification').value = job.qualification || '';
  document.getElementById('editJobCouncilReq').value = job.council_req || '';
  document.getElementById('editJobDescription').value = job.description || '';
  document.getElementById('editJobStatus').value = job.status || 'Active';
  document.getElementById('editJobUrgent').checked = (parseInt(job.urgent) === 1);
  document.getElementById('editJobFeatured').checked = (parseInt(job.featured) === 1);

  document.getElementById('editJobModal').classList.add('active');
}

function closeEditJobModal() {
  document.getElementById('editJobModal').classList.remove('active');
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAddJobModal();
    closeEditJobModal();
    closeInspectJobModal();
  }
});

// Apple Spotlight Live Filter
const searchInput = document.getElementById('appleJobSearchInput');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.apple-employer-card');
    let matchCount = 0;
    
    cards.forEach(card => {
      const text = card.getAttribute('data-search-target') || card.innerText.toLowerCase();
      if (text.includes(q)) {
        card.style.display = 'block';
        matchCount++;
      } else {
        card.style.display = 'none';
      }
    });

    const badge = document.getElementById('searchMatchCount');
    if (badge) badge.textContent = matchCount;
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
