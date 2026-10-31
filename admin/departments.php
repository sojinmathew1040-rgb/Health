<?php
/**
 * Admin Panel - Clinical Departments Management
 * Apple Level Pro Redesign - Zero Sideways Scroll, SF Pro Aesthetics, Responsive macOS Architecture
 * Route: http://localhost/Health/admin/departments.php
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

// Handle Actions (Add, Edit, Delete) BEFORE sending any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Department
    if ($action === 'create_dept') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($_POST['slug'] ?? $name))));
        $openings = (int)($_POST['openings'] ?? 0);
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            $stmt = $con->prepare("INSERT INTO `departments` (`name`, `slug`, `openings`, `description`) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssis", $name, $slug, $openings, $desc);
                $stmt->execute();
                $stmt->close();
                header('Location: departments.php?success=' . urlencode("Healthcare Department '{$name}' created successfully!"));
                exit;
            }
        }
    }

    // Edit Department
    if ($action === 'edit_dept') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($_POST['slug'] ?? $name))));
        $openings = (int)($_POST['openings'] ?? 0);
        $desc = trim($_POST['description'] ?? '');

        if ($id > 0 && !empty($name)) {
            $stmt = $con->prepare("UPDATE `departments` SET `name` = ?, `slug` = ?, `openings` = ?, `description` = ? WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("ssisi", $name, $slug, $openings, $desc, $id);
                $stmt->execute();
                $stmt->close();
                header('Location: departments.php?success=' . urlencode("Healthcare Department '{$name}' updated successfully!"));
                exit;
            }
        }
    }

    // Delete Department
    if ($action === 'delete_dept') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $con->prepare("DELETE FROM `departments` WHERE `id` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header('Location: departments.php?success=' . urlencode("Healthcare Department removed."));
                exit;
            }
        }
    }
}

$pageTitle = "Clinical Departments — HealthJobsGlobal Admin";
$pageHeading = "Healthcare Specialization Departments";

require_once __DIR__ . '/includes/header.php';

// Fetch Departments with Live Jobs Count
$departments = [];
$totalJobs = 0;
$departmentsWithJobs = 0;

if (!empty($con) && !$con->connect_error) {
    $res = $con->query("SELECT d.*, (SELECT COUNT(*) FROM jobs WHERE department = d.name) as live_jobs FROM `departments` d ORDER BY id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['live_jobs'] = (int)($r['live_jobs'] ?? 0);
            $totalJobs += $r['live_jobs'];
            if ($r['live_jobs'] > 0) {
                $departmentsWithJobs++;
            }
            $departments[] = $r;
        }
    }
}

$totalDepts = count($departments);

// Filter: all vs active_only
$filter = $_GET['filter'] ?? 'all';
$filteredDepartments = array_filter($departments, function($d) use ($filter) {
    if ($filter === 'active') {
        return $d['live_jobs'] > 0;
    }
    return true;
});

// Helper for dynamic department icon
function getDeptIconMeta($name, $slug): array {
    $str = strtolower($name . ' ' . $slug);
    if (strpos($str, 'doctor') !== false || strpos($str, 'physician') !== false || strpos($str, 'specialist') !== false || strpos($str, 'clinical') !== false) {
        return ['icon' => '🩺', 'grad' => 'linear-gradient(135deg, #0071E3, #0284C7)', 'color' => '#0284C7'];
    }
    if (strpos($str, 'nurs') !== false) {
        return ['icon' => '💉', 'grad' => 'linear-gradient(135deg, #10B981, #059669)', 'color' => '#059669'];
    }
    if (strpos($str, 'diagnost') !== false || strpos($str, 'pathol') !== false || strpos($str, 'pharm') !== false || strpos($str, 'lab') !== false) {
        return ['icon' => '🔬', 'grad' => 'linear-gradient(135deg, #8B5CF6, #6D28D9)', 'color' => '#7C3AED'];
    }
    if (strpos($str, 'allied') !== false || strpos($str, 'paramed') !== false || strpos($str, 'physio') !== false || strpos($str, 'rehab') !== false) {
        return ['icon' => '🩹', 'grad' => 'linear-gradient(135deg, #F59E0B, #D97706)', 'color' => '#D97706'];
    }
    if (strpos($str, 'operat') !== false || strpos($str, 'leader') !== false || strpos($str, 'admin') !== false || strpos($str, 'mgmt') !== false) {
        return ['icon' => '🏥', 'grad' => 'linear-gradient(135deg, #0EA5E9, #0284C7)', 'color' => '#0284C7'];
    }
    if (strpos($str, 'ayush') !== false || strpos($str, 'ayurved') !== false) {
        return ['icon' => '🌿', 'grad' => 'linear-gradient(135deg, #10B981, #047857)', 'color' => '#047857'];
    }
    if (strpos($str, 'dental') !== false) {
        return ['icon' => '🦷', 'grad' => 'linear-gradient(135deg, #6366F1, #4F46E5)', 'color' => '#4F46E5'];
    }
    return ['icon' => '🩺', 'grad' => 'linear-gradient(135deg, #0071E3, #0284C7)', 'color' => '#0071E3'];
}

$quickActionBtn = '<button class="apple-btn-primary" onclick="openAddDeptModal()" style="font-size: 0.82rem; padding: 7px 16px;">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  <span>Add Healthcare Department</span>
</button>';

require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Apple Level Pro Styles for Clinical Departments -->
<style>
:root {
  --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", sans-serif;
  --apple-bg: #F5F7FA;
  --apple-blue: #0071E3;
  --apple-blue-hover: #0077ED;
  --apple-green: #34C759;
  --apple-green-hover: #2DAE4E;
  --apple-amber: #FF9500;
  --apple-rose: #E11D48;
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
  background: radial-gradient(circle, rgba(0, 113, 227, 0.35) 0%, rgba(52, 199, 89, 0) 70%);
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

/* Apple Toolbar & Spotlight Search */
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
  padding: 2px 8px;
  border-radius: 9999px;
  background: #F1F5F9;
  color: #475569;
}

.apple-segment-item.active .apple-count-bubble {
  background: #0071E3;
  color: #FFFFFF;
}

/* Spotlight Search */
.apple-spotlight-box {
  position: relative;
  min-width: 280px;
  flex: 1;
  max-width: 440px;
}

.apple-spotlight-input {
  width: 100%;
  box-sizing: border-box;
  background: #FFFFFF;
  border: 1px solid #CBD5E1;
  border-radius: 9999px;
  padding: 9px 38px 9px 40px;
  font-size: 0.88rem;
  font-family: var(--apple-font);
  color: #0F172A;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
  outline: none;
}

.apple-spotlight-input:focus {
  border-color: #0071E3;
  box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
}

.apple-spotlight-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94A3B8;
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

/* Apple Pro Buttons */
.apple-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--apple-blue);
  color: #FFFFFF;
  font-family: var(--apple-font);
  font-size: 0.86rem;
  font-weight: 600;
  padding: 9px 20px;
  border-radius: 9999px;
  border: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 2px 8px rgba(0, 113, 227, 0.25);
  text-decoration: none;
}

.apple-btn-primary:hover {
  background: var(--apple-blue-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0, 113, 227, 0.35);
  color: #FFFFFF;
}

.apple-btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #FFFFFF;
  color: #334155;
  font-family: var(--apple-font);
  font-size: 0.82rem;
  font-weight: 600;
  padding: 7px 14px;
  border-radius: 9999px;
  border: 1px solid #E2E8F0;
  cursor: pointer;
  transition: all 0.18s ease;
  text-decoration: none;
}

.apple-btn-secondary:hover {
  background: #F8FAFC;
  border-color: #CBD5E1;
  color: #0F172A;
}

.apple-btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #FFF1F2;
  color: var(--apple-rose);
  font-family: var(--apple-font);
  font-size: 0.82rem;
  font-weight: 600;
  padding: 7px 12px;
  border-radius: 9999px;
  border: 1px solid #FECDD3;
  cursor: pointer;
  transition: all 0.18s ease;
}

.apple-btn-danger:hover {
  background: #FFE4E6;
  border-color: #FDA4AF;
}

/* Apple Pro Departments Directory Feed (Zero Horizontal Scroll) */
.apple-cards-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  box-sizing: border-box;
}

.apple-dept-card {
  width: 100%;
  box-sizing: border-box;
  background: #FFFFFF;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.85);
  box-shadow: var(--apple-card-shadow);
  padding: 22px 26px;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  border-left: 5px solid var(--apple-blue);
  position: relative;
  overflow: hidden;
}

.apple-dept-card:hover {
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
}

.apple-dept-title-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.apple-dept-icon-box {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.55rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  flex-shrink: 0;
  color: #FFFFFF;
}

.apple-dept-title {
  font-size: 1.18rem;
  font-weight: 700;
  color: #0F172A;
  margin: 0 0 4px 0;
  letter-spacing: -0.015em;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.apple-slug-badge {
  font-family: -apple-system, BlinkMacSystemFont, "SF Mono", Menlo, monospace;
  font-size: 0.74rem;
  background: #F1F5F9;
  color: #475569;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid #E2E8F0;
  font-weight: 600;
}

/* Status Capsules */
.apple-status-capsule {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.apple-status-capsule.active {
  background: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

.apple-status-capsule.neutral {
  background: #F8FAFC;
  color: #64748B;
  border: 1px solid #E2E8F0;
}

/* Card Body */
.apple-card-body {
  padding: 16px 0;
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: 20px;
  align-items: center;
}

@media (max-width: 820px) {
  .apple-card-body {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

.apple-dept-desc {
  font-size: 0.88rem;
  color: #475569;
  line-height: 1.55;
  margin: 0;
}

.apple-dept-scope-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  padding: 8px 14px;
  border-radius: 12px;
  font-size: 0.8rem;
  color: #334155;
}

/* Card Footer */
.apple-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 14px;
  border-top: 1px solid #F1F5F9;
}

.apple-action-btn-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Apple macOS Sheet Modal */
.apple-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
}

.apple-modal-overlay.active {
  display: flex;
}

.apple-modal-sheet {
  background: #FFFFFF;
  width: 100%;
  max-width: 600px;
  border-radius: 20px;
  box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.4);
  overflow: hidden;
  animation: appleModalPop 0.26s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
}

@keyframes appleModalPop {
  0% { transform: scale(0.92); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

.apple-modal-sheet-header {
  padding: 20px 26px;
  background: #FAFAFA;
  border-bottom: 1px solid #F1F5F9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.apple-modal-sheet-body {
  padding: 24px 26px;
  max-height: 70vh;
  overflow-y: auto;
}

.apple-modal-sheet-footer {
  padding: 16px 26px;
  background: #FAFAFA;
  border-top: 1px solid #F1F5F9;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.apple-close-circle {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: none;
  background: #F1F5F9;
  color: #64748B;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.apple-close-circle:hover {
  background: #E2E8F0;
  color: #0F172A;
}

/* Empty State */
.apple-empty-state {
  text-align: center;
  padding: 64px 24px;
  background: #FFFFFF;
  border-radius: 20px;
  border: 1px dashed #CBD5E1;
}
</style>

<!-- Apple Pro Luminous Hero Header -->
<div class="apple-hero-card">
  <div class="apple-hero-left">
    <div class="apple-pill-tag">
      <span class="pulse-dot emerald"></span>
      <span>Clinical Governance & Taxonomy</span>
    </div>
    <h2 class="apple-hero-title">Healthcare Specialization Departments</h2>
    <p class="apple-hero-sub">
      Coordinate medical disciplines, physician specialties, nursing faculties, and allied health departments across the healthcare exchange.
    </p>
  </div>

  <div class="apple-metrics-group">
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #38BDF8;">
        <?php echo $totalDepts; ?>
      </div>
      <div class="apple-metric-lbl">Total Departments</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #34C759;">
        <span class="pulse-dot emerald" style="margin-right: 2px;"></span><?php echo $totalJobs; ?>
      </div>
      <div class="apple-metric-lbl">Live Clinical Jobs</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #FFB020;">
        <?php echo $departmentsWithJobs; ?>
      </div>
      <div class="apple-metric-lbl">Active Faculties</div>
    </div>
  </div>
</div>

<!-- Apple Toolbar: Segmented Controls + Spotlight Search -->
<div class="apple-toolbar">
  <div class="apple-segmented-track">
    <a href="departments.php?filter=all" class="apple-segment-item <?php echo $filter === 'all' ? 'active' : ''; ?>">
      <span>All Departments</span>
      <span class="apple-count-bubble"><?php echo $totalDepts; ?></span>
    </a>
    <a href="departments.php?filter=active" class="apple-segment-item <?php echo $filter === 'active' ? 'active' : ''; ?>">
      <span class="pulse-dot emerald"></span>
      <span>With Live Postings</span>
      <span class="apple-count-bubble"><?php echo $departmentsWithJobs; ?></span>
    </a>
  </div>

  <div class="apple-spotlight-box">
    <svg class="apple-spotlight-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" class="apple-spotlight-input" placeholder="Search departments, clinical faculties, slugs..." id="appleSearchInput">
    <span class="apple-spotlight-badge" id="searchMatchCount"><?php echo count($filteredDepartments); ?></span>
  </div>

  <button type="button" class="apple-btn-primary" onclick="openAddDeptModal()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    <span>Add Department</span>
  </button>
</div>

<!-- Apple Pro Zero-Scroll Department Cards Directory Feed -->
<div class="apple-cards-container" id="deptCardsContainer">
  <?php if (empty($filteredDepartments)): ?>
    <div class="apple-empty-state">
      <div style="font-size: 3rem; margin-bottom: 14px;">🏥</div>
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--apple-text-main); margin-bottom: 8px;">No Healthcare Departments Found</h3>
      <p style="font-size: 0.92rem; color: var(--apple-text-muted); max-width: 500px; margin: 0 auto 24px auto; line-height: 1.55;">
        <?php echo $filter === 'active' ? 'No departments currently have active job openings posted.' : 'Start structuring clinical specialties by registering your first healthcare department.'; ?>
      </p>
      <button type="button" class="apple-btn-primary" onclick="openAddDeptModal()">
        <span>+ Add Healthcare Department</span>
      </button>
    </div>
  <?php else: ?>
    <?php foreach ($filteredDepartments as $dept): 
      $meta = getDeptIconMeta($dept['name'], $dept['slug']);
      $searchString = strtolower($dept['name'] . ' ' . $dept['slug'] . ' ' . $dept['description']);
    ?>
      <div class="apple-dept-card" data-search-target="<?php echo htmlspecialchars($searchString); ?>" style="border-left-color: <?php echo $meta['color']; ?>;">
        
        <!-- Header -->
        <div class="apple-card-header">
          <div class="apple-dept-title-group">
            <div class="apple-dept-icon-box" style="background: <?php echo $meta['grad']; ?>;">
              <?php echo $meta['icon']; ?>
            </div>
            <div>
              <h3 class="apple-dept-title">
                <span><?php echo htmlspecialchars($dept['name']); ?></span>
                <span class="apple-slug-badge">slug: <?php echo htmlspecialchars($dept['slug']); ?></span>
              </h3>
              <div style="font-size: 0.8rem; color: #64748B;">
                Estimated Market Openings: <strong><?php echo number_format((int)$dept['openings']); ?></strong>
              </div>
            </div>
          </div>

          <div>
            <?php if ($dept['live_jobs'] > 0): ?>
              <span class="apple-status-capsule active">
                <span class="pulse-dot emerald"></span>
                <span><?php echo $dept['live_jobs']; ?> Live Clinical Opening<?php echo $dept['live_jobs'] > 1 ? 's' : ''; ?></span>
              </span>
            <?php else: ?>
              <span class="apple-status-capsule neutral">
                <span>○ 0 Live Openings</span>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Body -->
        <div class="apple-card-body">
          <div>
            <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94A3B8; margin-bottom: 4px;">
              Clinical Scope & Faculties
            </div>
            <p class="apple-dept-desc">
              <?php echo !empty($dept['description']) ? htmlspecialchars($dept['description']) : 'Comprehensive healthcare discipline encompassing medical consultants, clinical practitioners, nursing and allied care staff.'; ?>
            </p>
          </div>

          <div style="display: flex; justify-content: flex-end;">
            <div class="apple-dept-scope-pill">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span>Market Demand: <strong><?php echo (int)$dept['openings']; ?> Positions</strong></span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="apple-card-footer">
          <div>
            <?php if ($dept['live_jobs'] > 0): ?>
              <a href="jobs.php?department=<?php echo urlencode($dept['name']); ?>" class="apple-btn-secondary" style="color: var(--apple-blue); border-color: #BAE6FD;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <span>Browse <?php echo $dept['live_jobs']; ?> Job<?php echo $dept['live_jobs'] > 1 ? 's' : ''; ?> in Directory &rarr;</span>
              </a>
            <?php else: ?>
              <span style="font-size: 0.78rem; color: #94A3B8;">No active postings assigned yet</span>
            <?php endif; ?>
          </div>

          <div class="apple-action-btn-group">
            <button type="button" class="apple-btn-secondary" onclick='openEditDeptModal(<?php echo json_encode($dept, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span>Edit Department</span>
            </button>

            <form method="POST" action="departments.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove the department \'<?php echo addslashes($dept['name']); ?>\'?');">
              <input type="hidden" name="action" value="delete_dept">
              <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
              <button type="submit" class="apple-btn-danger" title="Delete Department">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <span>Delete</span>
              </button>
            </form>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Apple Pro macOS Sheet Modal: Add Healthcare Department -->
<div class="apple-modal-overlay" id="addDeptModal">
  <div class="apple-modal-sheet">
    <div class="apple-modal-sheet-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">🏥</span>
        <div>
          <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Add Healthcare Department</h3>
          <div style="font-size: 0.78rem; color: var(--apple-text-muted);">Clinical Specialization & Taxonomy Structure</div>
        </div>
      </div>
      <button type="button" class="apple-close-circle" onclick="closeAddDeptModal()">&times;</button>
    </div>

    <form method="POST" action="departments.php">
      <input type="hidden" name="action" value="create_dept">
      <div class="apple-modal-sheet-body">
        
        <div style="margin-bottom: 16px;">
          <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Department Title *</label>
          <input type="text" name="name" id="inpDeptName" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" placeholder="e.g. AYUSH & Integrative Medicine" oninput="autoGenerateSlug(this.value, 'inpDeptSlug')" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">URL Identifier Slug *</label>
            <input type="text" name="slug" id="inpDeptSlug" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; font-family: monospace;" placeholder="e.g. ayush" required>
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Estimated Openings Count</label>
            <input type="number" name="openings" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" value="50" min="0">
          </div>
        </div>

        <div>
          <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Clinical Scope & Discipline Description</label>
          <textarea name="description" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; padding: 10px 14px; font-family: var(--apple-font); font-size: 0.85rem;" rows="3" placeholder="Scope of medical faculties, doctors, surgeons, specialists, and nursing cadres..."></textarea>
        </div>

      </div>
      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeAddDeptModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Save Department</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Apple Pro macOS Sheet Modal: Edit Healthcare Department -->
<div class="apple-modal-overlay" id="editDeptModal">
  <div class="apple-modal-sheet">
    <div class="apple-modal-sheet-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">✏️</span>
        <div>
          <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Edit Healthcare Department</h3>
          <div style="font-size: 0.78rem; color: var(--apple-text-muted);">Modify Taxonomy, Slug & Scope</div>
        </div>
      </div>
      <button type="button" class="apple-close-circle" onclick="closeEditDeptModal()">&times;</button>
    </div>

    <form method="POST" action="departments.php">
      <input type="hidden" name="action" value="edit_dept">
      <input type="hidden" name="id" id="editDeptId" value="">
      
      <div class="apple-modal-sheet-body">
        
        <div style="margin-bottom: 16px;">
          <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Department Title *</label>
          <input type="text" name="name" id="editDeptName" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">URL Identifier Slug *</label>
            <input type="text" name="slug" id="editDeptSlug" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; font-family: monospace;" required>
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Estimated Openings Count</label>
            <input type="number" name="openings" id="editDeptOpenings" class="apple-spotlight-input" style="border-radius: 10px; width: 100%;" min="0">
          </div>
        </div>

        <div>
          <label style="font-size: 0.8rem; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Clinical Scope & Discipline Description</label>
          <textarea name="description" id="editDeptDesc" class="apple-spotlight-input" style="border-radius: 10px; width: 100%; padding: 10px 14px; font-family: var(--apple-font); font-size: 0.85rem;" rows="3"></textarea>
        </div>

      </div>
      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeEditDeptModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Update Department</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal Controls
function openAddDeptModal() {
  const m = document.getElementById('addDeptModal');
  if (m) m.classList.add('active');
  const inp = document.getElementById('inpDeptName');
  if (inp) inp.focus();
}

function closeAddDeptModal() {
  const m = document.getElementById('addDeptModal');
  if (m) m.classList.remove('active');
}

function openEditDeptModal(dept) {
  if (!dept) return;
  document.getElementById('editDeptId').value = dept.id || '';
  document.getElementById('editDeptName').value = dept.name || '';
  document.getElementById('editDeptSlug').value = dept.slug || '';
  document.getElementById('editDeptOpenings').value = dept.openings || '0';
  document.getElementById('editDeptDesc').value = dept.description || '';
  
  const m = document.getElementById('editDeptModal');
  if (m) m.classList.add('active');
}

function closeEditDeptModal() {
  const m = document.getElementById('editDeptModal');
  if (m) m.classList.remove('active');
}

// Auto slug generator helper
function autoGenerateSlug(name, targetId) {
  const target = document.getElementById(targetId);
  if (target) {
    target.value = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAddDeptModal();
    closeEditDeptModal();
  }
});

// Apple Spotlight Live Filter
const searchInput = document.getElementById('appleSearchInput');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.apple-dept-card');
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
