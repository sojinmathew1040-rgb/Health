<?php
/**
 * Admin Panel - Hospital Networks & Healthcare Employers Management
 * Apple Level Pro Design - Zero Sideways Scroll, SF Pro Aesthetics, Responsive macOS Architecture
 * Route: http://localhost/Health/admin/employers.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/auth.php';
requireAdminAuth();

// Handle Actions (Add, Edit, Delete, Toggle Verified) BEFORE sending any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Hospital
    if ($action === 'create_hospital') {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $badge = trim($_POST['badge'] ?? 'NABH & JCI Accredited');
        $type = trim($_POST['type'] ?? 'Quaternary Care Multi-Specialty Hospital');
        $location = trim($_POST['location'] ?? '');
        $city = trim($_POST['city'] ?? $location);
        $address = trim($_POST['address'] ?? $location);
        $email = trim($_POST['contact_email'] ?? '');
        $adminName = mb_strtoupper(trim($_POST['admin_name'] ?? ''), 'UTF-8');
        $adminDesig = mb_strtoupper(trim($_POST['admin_designation'] ?? 'Medical Talent Director'), 'UTF-8');
        $phone = trim($_POST['phone'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $capacity = trim($_POST['operating_capacity'] ?? 'Medium (100 - 500 Beds)');
        $overview = trim($_POST['overview'] ?? '');
        $logo_text = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));

        if (!empty($name)) {
            $stmt = $con->prepare("INSERT INTO `hospitals` (
                `name`, `slug`, `logo_text`, `badge`, `type`, `location`, `city`, `address`,
                `contact_email`, `admin_email`, `admin_name`, `admin_designation`, `phone`, 
                `website`, `operating_capacity`, `overview`, `verified`, `rating`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '4.8', NOW())");
            if ($stmt) {
                $stmt->bind_param(
                    "ssssssssssssssss", 
                    $name, $slug, $logo_text, $badge, $type, $location, $city, $address,
                    $email, $email, $adminName, $adminDesig, $phone, $website, $capacity, $overview
                );
                $stmt->execute();
                $stmt->close();
                $targetUrl = 'employers.php?success=' . urlencode("Healthcare Facility '{$name}' registered and verified successfully!");
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // Toggle Verified Status
    if ($action === 'toggle_verified') {
        $id = (int)($_POST['id'] ?? 0);
        $newVal = (int)($_POST['verified'] ?? 1);
        if ($id > 0 && !empty($con) && !$con->connect_error) {
            $stmt = $con->prepare("UPDATE `hospitals` SET `verified` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $newVal, $id);
                $stmt->execute();
                $stmt->close();
                $msg = $newVal ? "Hospital verified and active!" : "Hospital access set to unverified.";
                $targetUrl = 'employers.php?success=' . urlencode($msg);
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // Delete Hospital (Cascade delete all associated clinical jobs and applications)
    if ($action === 'delete_hospital') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !empty($con) && !$con->connect_error) {
            deleteHospitalCascade($con, $id);
            $targetUrl = 'employers.php?success=' . urlencode("Hospital and all related clinical jobs & applications removed successfully.");
            if (!headers_sent()) {
                header('Location: ' . $targetUrl);
            } else {
                echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
            }
            exit;
        }
    }
}

$pageTitle = "Hospital Networks & Medical Centers — HealthJobsGlobal Admin";
$pageHeading = "Hospital Networks & Healthcare Facilities";

require_once __DIR__ . '/includes/header.php';

// Counts and Statistics
$totalHospCount = 0;
$pendingHospCount = 0;
$verifiedHospCount = 0;
$totalJobsInNetworks = 0;

if (!empty($con) && !$con->connect_error) {
    $resCounts = $con->query("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as pending_c,
        SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_c 
        FROM `hospitals`");
    if ($resCounts && $cRow = $resCounts->fetch_assoc()) {
        $totalHospCount = (int)$cRow['total'];
        $pendingHospCount = (int)($cRow['pending_c'] ?? 0);
        $verifiedHospCount = (int)($cRow['verified_c'] ?? 0);
    }

    $resJ = $con->query("SELECT COUNT(*) as j_count FROM `jobs`");
    if ($resJ && $jRow = $resJ->fetch_assoc()) {
        $totalJobsInNetworks = (int)$jRow['j_count'];
    }
}

// Filter: Approved & Verified accounts are shown here in Hospital Networks tab!
$filter = $_GET['filter'] ?? 'verified';
$whereClause = "";
if ($filter === 'verified') {
    $whereClause = "WHERE h.verified = 1";
} elseif ($filter === 'all') {
    $whereClause = "";
}

// Fetch Hospitals
$hospitals = [];
if (!empty($con) && !$con->connect_error) {
    $query = "SELECT h.*, 
              (SELECT COUNT(*) FROM jobs WHERE hospital_id = h.id OR company = h.name) as live_openings 
              FROM `hospitals` h 
              {$whereClause} 
              ORDER BY h.id DESC";
    $res = $con->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $hospitals[] = $r;
        }
    }
}

$quickActionBtn = '<button class="apple-btn-primary" onclick="openAddHospitalModal()" style="font-size: 0.82rem; padding: 7px 16px;">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Add Healthcare Facility</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Apple Level Pro Styles for Hospital Networks -->
<style>
:root {
  --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", sans-serif;
  --apple-bg: #F5F7FA;
  --apple-blue: #0071E3;
  --apple-blue-hover: #0077ED;
  --apple-green: #34C759;
  --apple-green-hover: #2DAE4E;
  --apple-amber: #FF9500;
  --apple-text-main: #1D1D1F;
  --apple-text-muted: #6E6E73;
  --apple-text-light: #86868B;
  --apple-card-shadow: 0 2px 14px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
  --apple-card-hover: 0 10px 30px -4px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.03);
}

.admin-body {
  font-family: var(--apple-font);
  overflow-x: hidden !important;
  max-width: 100% !important;
  padding: 24px !important;
  box-sizing: border-box !important;
}

/* Luminous Apple Pro Hero Banner */
.apple-hero-card {
  position: relative;
  background: linear-gradient(135deg, #07192F 0%, #0F3460 55%, #16213E 100%);
  border-radius: 20px;
  padding: 30px 36px;
  margin-bottom: 24px;
  color: #FFFFFF;
  box-shadow: 0 14px 34px -8px rgba(7, 25, 47, 0.35);
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
  top: -70px;
  right: -70px;
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, rgba(52, 199, 89, 0.3) 0%, rgba(0, 113, 227, 0) 70%);
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

/* Indicator Pills */
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
  min-width: 120px;
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

/* Pulsing Status Dot */
.pulse-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  position: relative;
  margin-right: 6px;
}

.pulse-dot.emerald {
  background: #34C759;
  box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7);
  animation: pulse-emerald 2s infinite;
}

.pulse-dot.amber {
  background: #FF9500;
  box-shadow: 0 0 0 0 rgba(255, 149, 0, 0.7);
  animation: pulse-amber 2s infinite;
}

@keyframes pulse-emerald {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(52, 199, 89, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 199, 89, 0); }
}

@keyframes pulse-amber {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 149, 0, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(255, 149, 0, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 149, 0, 0); }
}

/* Toolbar & Spotlight Search */
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

.apple-spotlight-box {
  position: relative;
  width: 320px;
  max-width: 100%;
}

.apple-spotlight-input {
  width: 100%;
  height: 42px;
  padding: 0 40px 0 38px;
  border-radius: 9999px;
  background: #FFFFFF;
  border: 1px solid rgba(226, 232, 240, 0.85);
  font-size: 0.88rem;
  font-family: var(--apple-font);
  color: var(--apple-text-main);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  outline: none;
  transition: all 0.2s;
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

/* Apple Pro Hospital Cards Feed */
.apple-cards-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  box-sizing: border-box;
}

.apple-hospital-card {
  width: 100%;
  box-sizing: border-box;
  background: #FFFFFF;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.85);
  box-shadow: var(--apple-card-shadow);
  padding: 22px 26px;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  border-left: 5px solid var(--apple-green);
  position: relative;
  overflow: hidden;
}

.apple-hospital-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--apple-card-hover);
  border-color: rgba(203, 213, 225, 0.95);
}

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
  font-size: 1.1rem;
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

.apple-web-link {
  color: var(--apple-blue);
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.apple-web-link:hover {
  text-decoration: underline;
}

.apple-status-capsule {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 700;
  background: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

/* Info Grid (3 columns, zero sideways scroll) */
.apple-info-grid {
  display: grid;
  grid-template-columns: 1.2fr 1.1fr 1.1fr;
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

/* Footer Action Strip */
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
  transition: all 0.2s;
  text-decoration: none;
}

.apple-btn-primary:hover {
  background: var(--apple-green-hover);
  transform: translateY(-1px);
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
  transition: all 0.2s;
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
  transition: all 0.2s;
}

.apple-btn-icon-danger:hover {
  background: #FFE4E6;
  color: #BE123C;
  transform: scale(1.05);
}

/* Apple macOS Sheet Modal */
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
  max-width: 720px;
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

.apple-modal-sheet-body {
  padding: 24px 28px;
  overflow-y: auto;
  flex: 1;
}

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

.apple-modal-sheet-footer {
  padding: 18px 28px;
  border-top: 1px solid #F1F5F9;
  background: #FAFAFA;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

<!-- Pending Verifications Alert Ribbon if any exist -->
<?php if ($pendingHospCount > 0): ?>
<div style="background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border: 1.5px solid #FCD34D; border-radius: 16px; padding: 14px 22px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.08);">
  <div style="display: flex; align-items: center; gap: 12px;">
    <span class="pulse-dot amber" style="width: 10px; height: 10px;"></span>
    <span style="font-weight: 700; color: #92400E; font-size: 0.92rem;">
      <?php echo $pendingHospCount; ?> Healthcare Employer Registration<?php echo $pendingHospCount > 1 ? 's' : ''; ?> Awaiting Review in Verification Center
    </span>
  </div>
  <a href="employer-verifications.php" class="apple-btn-secondary" style="background: #FFFFFF; color: #B45309; border-color: #FCD34D; font-weight: 700; font-size: 0.8rem; padding: 6px 14px;">
    <span>Verify Registrations &rarr;</span>
  </a>
</div>
<?php endif; ?>

<!-- Apple Pro Luminous Hero Banner -->
<div class="apple-hero-card">
  <div class="apple-hero-left">
    <div class="apple-pill-tag">
      <span class="pulse-dot emerald"></span>
      <span>Accredited Healthcare Infrastructure</span>
    </div>
    <h2 class="apple-hero-title">Hospital Networks & Medical Centers</h2>
    <p class="apple-hero-sub">
      Central directory of verified healthcare institutions, multi-specialty hospitals, teaching colleges, and authorized partner clinics across the recruitment exchange.
    </p>
  </div>

  <div class="apple-metrics-group">
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #34C759;">
        <span class="pulse-dot emerald" style="margin-right: 2px;"></span><?php echo $verifiedHospCount; ?>
      </div>
      <div class="apple-metric-lbl">Verified Hospitals</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #0071E3;">
        <?php echo $totalJobsInNetworks; ?>
      </div>
      <div class="apple-metric-lbl">Live Job Openings</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #FFFFFF;">
        <?php echo $totalHospCount; ?>
      </div>
      <div class="apple-metric-lbl">Total Enrolled</div>
    </div>
  </div>
</div>

<!-- Apple Toolbar: Segmented Controls + Spotlight Search -->
<div class="apple-toolbar">
  <div class="apple-segmented-track">
    <a href="employers.php?filter=verified" class="apple-segment-item <?php echo $filter === 'verified' ? 'active' : ''; ?>">
      <span class="pulse-dot emerald"></span>
      <span>Verified Hospital Partners</span>
      <span class="apple-count-bubble"><?php echo $verifiedHospCount; ?></span>
    </a>
    <a href="employers.php?filter=all" class="apple-segment-item <?php echo $filter === 'all' ? 'active' : ''; ?>">
      <span>All Hospital Facilities</span>
      <span class="apple-count-bubble"><?php echo $totalHospCount; ?></span>
    </a>
    <?php if ($pendingHospCount > 0): ?>
      <a href="employer-verifications.php" class="apple-segment-item" title="Click to review pending verifications">
        <span class="pulse-dot amber"></span>
        <span>Awaiting Verification</span>
        <span class="apple-count-bubble"><?php echo $pendingHospCount; ?> &nearr;</span>
      </a>
    <?php endif; ?>
  </div>

  <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
    <div class="apple-spotlight-box">
      <svg class="apple-spotlight-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" class="apple-spotlight-input" placeholder="Search hospital name, city, admin..." id="appleSearchInput">
      <span class="apple-spotlight-badge" id="searchMatchCount"><?php echo count($hospitals); ?></span>
    </div>

    <button type="button" class="apple-btn-primary" onclick="openAddHospitalModal()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>Register Facility</span>
    </button>
  </div>
</div>

<!-- Apple Pro Zero-Scroll Hospital Cards Feed -->
<div class="apple-cards-container" id="hospitalCardsContainer">
  <?php if (empty($hospitals)): ?>
    <div style="text-align: center; padding: 60px 24px; background: #FFFFFF; border-radius: 20px; border: 1px dashed #CBD5E1;">
      <div style="font-size: 2.8rem; margin-bottom: 12px;">🏥</div>
      <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--apple-text-main); margin-bottom: 6px;">No Healthcare Facilities Found</h3>
      <p style="font-size: 0.88rem; color: var(--apple-text-muted); max-width: 460px; margin: 0 auto;">
        No healthcare establishments match the active filter.
      </p>
    </div>
  <?php else: ?>
    <script>
      window.allHospitalRecords = window.allHospitalRecords || {};
    </script>
    <?php foreach ($hospitals as $hosp): 
      $isVerified = !empty($hosp['verified']) && (int)$hosp['verified'] === 1;
      $avatarText = !empty($hosp['logo_text']) ? $hosp['logo_text'] : substr($hosp['name'], 0, 3);
      $hId = (int)$hosp['id'];
    ?>
      <script>
        window.allHospitalRecords[<?php echo $hId; ?>] = <?php echo json_encode($hosp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
      </script>
      <div class="apple-hospital-card" data-search-target="<?php echo htmlspecialchars(strtolower($hosp['name'] . ' ' . $hosp['city'] . ' ' . $hosp['location'] . ' ' . $hosp['admin_name'] . ' ' . $hosp['badge'])); ?>">
        
        <!-- Header Strip -->
        <div class="apple-card-header">
          <div class="apple-org-identity">
            <div class="apple-squircle-avatar">
              <?php echo htmlspecialchars(strtoupper($avatarText)); ?>
            </div>
            <div>
              <h3 class="apple-org-title">
                <span><?php echo htmlspecialchars($hosp['name']); ?></span>
                <span class="apple-badge-pill" style="background: #E0F2FE; color: #0369A1; font-weight: 700; font-size: 0.72rem;">
                  <?php echo htmlspecialchars($hosp['badge'] ?: 'Accredited Facility'); ?>
                </span>
              </h3>
              <div class="apple-subtext">
                <span class="apple-badge-pill apple-service-tag"><?php echo htmlspecialchars($hosp['type'] ?: 'Quaternary Hospital'); ?></span>
                <?php if (!empty($hosp['website'])): ?>
                  <span>·</span>
                  <a href="<?php echo htmlspecialchars($hosp['website']); ?>" target="_blank" class="apple-web-link">
                    <?php echo htmlspecialchars(parse_url($hosp['website'], PHP_URL_HOST) ?: $hosp['website']); ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                  </a>
                <?php endif; ?>
                <span>·</span>
                <span>Ref: HOSP-<?php echo str_pad($hosp['id'], 5, '0', STR_PAD_LEFT); ?></span>
              </div>
            </div>
          </div>

          <!-- Status Capsule -->
          <div>
            <?php if ($isVerified): ?>
              <div class="apple-status-capsule">
                <span class="pulse-dot emerald"></span>
                <span>Verified Healthcare Partner</span>
              </div>
            <?php else: ?>
              <div class="apple-status-capsule" style="background: #FFFBEB; color: #92400E; border-color: #FCD34D;">
                <span class="pulse-dot amber"></span>
                <span>Pending Verification</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 3-Column Info Grid (Fits cleanly into all viewports, ZERO sideways scroll) -->
        <div class="apple-info-grid">
          
          <!-- Column 1: Administrator & Contacts -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Authorized Administrator</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars(!empty($hosp['admin_name']) ? $hosp['admin_name'] : ($hosp['name'] . ' Administrator')); ?>
            </div>
            <div class="apple-cell-sub">
              <?php echo htmlspecialchars(!empty($hosp['admin_designation']) ? $hosp['admin_designation'] : 'Medical Talent Acquisition'); ?>
            </div>
            <div style="margin-top: 6px; font-size: 0.8rem;">
              <a href="mailto:<?php echo htmlspecialchars($hosp['admin_email'] ?: ($hosp['contact_email'] ?? '')); ?>" style="color: var(--apple-blue); font-weight: 600; text-decoration: none;">
                ✉ <?php echo htmlspecialchars($hosp['admin_email'] ?: ($hosp['contact_email'] ?? '')); ?>
              </a>
              <?php if (!empty($hosp['admin_phone']) || !empty($hosp['phone'])): ?>
                <div style="color: var(--apple-text-muted); font-size: 0.76rem; margin-top: 2px;">
                  📞 <?php echo htmlspecialchars($hosp['admin_phone'] ?: ($hosp['phone'] ?? '')); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Column 2: Campus & Capacity -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>Campus & Scale</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars($hosp['city'] ?: ($hosp['location'] ?? 'India')); ?>
            </div>
            <div class="apple-cell-sub" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($hosp['address'] ?: ($hosp['location'] ?? '')); ?>">
              <?php echo htmlspecialchars($hosp['address'] ?: ($hosp['location'] ?? '')); ?>
            </div>
            <div style="margin-top: 6px;">
              <span class="apple-badge-pill" style="background: #EEF2F6; color: #475569; font-weight: 600;">
                🏢 <?php echo htmlspecialchars($hosp['operating_capacity'] ?: 'Institutional Scale'); ?>
              </span>
            </div>
          </div>

          <!-- Column 3: Live Clinical Jobs -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
              <span>Clinical Recruitment</span>
            </div>
            <div class="apple-cell-main" style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 1.25rem; font-weight: 800; color: #0284C7;">
                <?php echo $hosp['live_openings'] ?: $hosp['openings']; ?>
              </span>
              <span style="font-size: 0.82rem; color: var(--apple-text-muted); font-weight: 600;">Active Job Postings</span>
            </div>
            <div class="apple-cell-sub" style="margin-top: 6px;">
              <a href="jobs.php" style="color: var(--apple-blue); font-weight: 600; text-decoration: none; font-size: 0.8rem;">
                Manage Clinical Openings &rarr;
              </a>
            </div>
          </div>

        </div>

        <!-- Footer Action Strip -->
        <div class="apple-card-footer">
          <div class="apple-footer-meta">
            <span>Rating: <strong>★ <?php echo htmlspecialchars($hosp['rating'] ?? '4.8'); ?></strong></span>
            <?php if (!empty($hosp['created_at'])): ?>
              <span>·</span>
              <span>Enrolled: <strong><?php echo date('M d, Y', strtotime($hosp['created_at'])); ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($hosp['initial_random_password'])): ?>
              <span>·</span>
              <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span>⚡ Default Pass:</span>
                <code style="font-family: monospace; font-size: 0.88rem; font-weight: 800; background: #EFF6FF; border: 1px solid #BAE6FD; padding: 2px 7px; border-radius: 6px; color: #0284C7;">
                  <?php echo htmlspecialchars($hosp['initial_random_password']); ?>
                </code>
                <button type="button" class="apple-btn-secondary" style="padding: 2px 8px; font-size: 0.72rem;" onclick="copyPasswordToClipboard('<?php echo htmlspecialchars($hosp['initial_random_password']); ?>')">
                  Copy
                </button>
              </span>
            <?php endif; ?>
            <?php if (isset($hosp['must_change_password'])): ?>
              <span>·</span>
              <?php if ((int)$hosp['must_change_password'] === 1): ?>
                <span class="apple-badge-pill" style="background: #FEF3C7; color: #92400E; font-weight: 700;">⚠️ Temp Password Active</span>
              <?php else: ?>
                <span class="apple-badge-pill" style="background: #ECFDF5; color: #065F46; font-weight: 700;">✓ Personal Password Set</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="apple-action-btn-group">
            
            <!-- Public Showcase Link -->
            <?php if (!empty($hosp['slug'])): ?>
              <a href="../company-details.php?slug=<?php echo htmlspecialchars($hosp['slug']); ?>" target="_blank" class="apple-btn-secondary" title="View Public Employer Profile">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span>Public Page</span>
              </a>
            <?php endif; ?>

            <!-- Credentials & Details Button -->
            <button type="button" class="apple-btn-secondary" onclick="openHospitalDetailsById(<?php echo $hId; ?>)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <span>Inspect & Credentials</span>
            </button>

            <!-- Delete Record -->
            <form method="POST" action="employers.php" style="display:inline;" onsubmit="return confirm('Permanently remove <?php echo addslashes($hosp['name']); ?> from Hospital Networks?');">
              <input type="hidden" name="action" value="delete_hospital">
              <input type="hidden" name="id" value="<?php echo $hosp['id']; ?>">
              <button type="submit" class="apple-btn-icon-danger" title="Delete Hospital Facility">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </form>

          </div>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Apple Pro macOS Sheet Modal: Register Healthcare Facility -->
<div class="apple-modal-overlay" id="addHospitalModal">
  <div class="apple-modal-sheet" style="max-width: 680px;">
    <div class="apple-modal-sheet-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">🏥</span>
        <div>
          <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Register Healthcare Facility</h3>
          <div style="font-size: 0.78rem; color: var(--apple-text-muted);">HealthJobsGlobal Hospital Directory</div>
        </div>
      </div>
      <button class="apple-close-circle" onclick="closeAddHospitalModal()">&times;</button>
    </div>

    <form method="POST" action="employers.php">
      <input type="hidden" name="action" value="create_hospital">
      <div class="apple-modal-sheet-body">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div style="grid-column: span 2;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Facility / Hospital Name *</label>
            <input type="text" name="name" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. LISIE HEART INSTITUTE & RESEARCH CENTRE" required>
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Authorizing Administrator Name</label>
            <input type="text" name="admin_name" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. DR. RADHAKRISHNAN">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Administrator Designation</label>
            <input type="text" name="admin_designation" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" placeholder="e.g. MEDICAL SUPERINTENDENT" value="MEDICAL TALENT DIRECTOR">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Accreditation Badge</label>
            <input type="text" name="badge" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" value="NABH & JCI Accredited">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Facility Classification</label>
            <input type="text" name="type" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" value="Quaternary Care Multi-Specialty Hospital">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">City & State *</label>
            <input type="text" name="city" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="e.g. Kochi, Kerala" required>
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Operating Scale / Capacity</label>
            <input type="text" name="operating_capacity" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" value="Medium (100 - 500 Beds)">
          </div>
          <div style="grid-column: span 2;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Street / Campus Address</label>
            <input type="text" name="address" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="e.g. Lisie Hospital Rd, Kaloor">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Official Careers Email</label>
            <input type="email" name="contact_email" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="careers@lisiehospital.org">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Boardline / Contact Phone</label>
            <input type="text" name="phone" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="+91 484 240 2044">
          </div>
          <div style="grid-column: span 2;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Website URL</label>
            <input type="url" name="website" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="https://www.lisiehospital.org">
          </div>
          <div style="grid-column: span 2;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Facility Overview</label>
            <textarea name="overview" style="width: 100%; border-radius: 10px; border: 1px solid #E2E8F0; padding: 10px 14px; font-family: var(--apple-font); font-size: 0.85rem;" rows="2" placeholder="Brief clinical background, bed capacity, centers of excellence..."></textarea>
          </div>
        </div>

      </div>
      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeAddHospitalModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">Save & Register Facility</button>
      </div>
    </form>
  </div>
</div>

<!-- Apple Pro macOS Sheet Modal: Inspect Hospital Details -->
<div class="apple-modal-overlay" id="hospitalDetailsModal">
  <div class="apple-modal-sheet" style="max-width: 680px;">
    <div class="apple-modal-sheet-header">
      <div>
        <h3 id="mdlHospName" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Hospital Name</h3>
        <span class="apple-status-capsule" style="margin-top: 4px; display: inline-flex;">
          <span class="pulse-dot emerald"></span>
          <span>Verified Healthcare Facility</span>
        </span>
      </div>
      <button class="apple-close-circle" onclick="closeHospitalDetails()">&times;</button>
    </div>

    <div class="apple-modal-sheet-body">
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Accreditation</span>
          <span class="apple-settings-val" id="mdlBadge" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Classification</span>
          <span class="apple-settings-val" id="mdlType" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Operating Capacity</span>
          <span class="apple-settings-val" id="mdlCapacity" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Campus Location</span>
          <span class="apple-settings-val" id="mdlLocation" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Authorizing Admin</span>
          <span class="apple-settings-val" id="mdlAdmin" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Official Email</span>
          <span class="apple-settings-val" id="mdlEmail" style="color: var(--apple-blue); font-weight: 700;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Contact Phone</span>
          <span class="apple-settings-val" id="mdlPhone" style="font-weight: 700; color: #0F172A;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label" style="font-size: 0.82rem; font-weight: 600; color: var(--apple-text-muted);">Website</span>
          <span class="apple-settings-val" id="mdlWebsite">-</span>
        </div>
      </div>

      <div id="mdlCredStateBox" style="background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 14px; padding: 14px 18px;">
        <div style="font-weight: 700; color: #065F46; font-size: 0.88rem; margin-bottom: 2px;">
          ✓ Portal Login Credentials Status
        </div>
        <div id="mdlCredStateText" style="font-size: 0.82rem; color: #047857;">
          Account is verified and authorized to log into the HealthJobsGlobal employer portal.
        </div>
      </div>
    </div>

    <div class="apple-modal-sheet-footer">
      <button type="button" class="apple-btn-secondary" onclick="closeHospitalDetails()">Close</button>
      <div id="mdlPublicLink"></div>
    </div>
  </div>
</div>

<script>
function openAddHospitalModal() {
  document.getElementById('addHospitalModal').classList.add('active');
}

function closeAddHospitalModal() {
  document.getElementById('addHospitalModal').classList.remove('active');
}

function openHospitalDetailsById(id) {
  const hosp = window.allHospitalRecords ? window.allHospitalRecords[id] : null;
  if (hosp) openHospitalDetails(hosp);
}

function openHospitalDetails(hosp) {
  if (!hosp) return;
  document.getElementById('mdlHospName').textContent = hosp.name || 'Hospital';
  document.getElementById('mdlBadge').textContent = hosp.badge || 'Accredited Facility';
  document.getElementById('mdlType').textContent = hosp.type || 'Hospital';
  document.getElementById('mdlCapacity').textContent = hosp.operating_capacity || 'Standard Scale';
  document.getElementById('mdlLocation').textContent = (hosp.address || hosp.location || hosp.city || 'India');
  document.getElementById('mdlAdmin').textContent = (hosp.admin_name || 'Hospital Administrator') + (hosp.admin_designation ? ' (' + hosp.admin_designation + ')' : '');
  document.getElementById('mdlEmail').textContent = hosp.contact_email || hosp.admin_email || 'N/A';
  document.getElementById('mdlPhone').textContent = hosp.phone || hosp.admin_phone || 'N/A';

  const webEl = document.getElementById('mdlWebsite');
  if (hosp.website) {
    webEl.innerHTML = `<a href="${hosp.website}" target="_blank" style="color: var(--apple-blue); text-decoration: underline; font-weight: 600;">${hosp.website} &nearr;</a>`;
  } else {
    webEl.textContent = 'Not Provided';
  }

  const credText = document.getElementById('mdlCredStateText');
  if (hosp.initial_random_password) {
    credText.innerHTML = `Default temporary password generated: <code style="font-family: monospace; font-size: 0.95rem; font-weight: 700; background: #FFF; padding: 2px 6px; border-radius: 4px; border: 1px solid #CBD5E1;">${hosp.initial_random_password}</code><br>First login password update: <strong>${hosp.must_change_password == 1 ? 'Pending Employer Action' : 'Completed'}</strong>`;
  } else {
    credText.textContent = 'Account active and authorized. Recruiter can manage jobs and candidate applicants on employer dashboard.';
  }

  const pubEl = document.getElementById('mdlPublicLink');
  if (hosp.slug) {
    pubEl.innerHTML = `<a href="../company-details.php?slug=${hosp.slug}" target="_blank" class="apple-btn-secondary" style="color: var(--apple-blue);"><span>View Public Showcase &nearr;</span></a>`;
  } else {
    pubEl.innerHTML = '';
  }

  document.getElementById('hospitalDetailsModal').classList.add('active');
}

function closeHospitalDetails() {
  document.getElementById('hospitalDetailsModal').classList.remove('active');
}

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAddHospitalModal();
    closeHospitalDetails();
  }
});

// Copy Password to Clipboard with visual feedback
function copyPasswordToClipboard(pass) {
  navigator.clipboard.writeText(pass).then(() => {
    alert('Temporary Default Password copied to clipboard: ' + pass);
  }).catch(() => {
    prompt('Copy Password:', pass);
  });
}

// Spotlight Live Search
const searchInput = document.getElementById('appleSearchInput');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.apple-hospital-card');
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
