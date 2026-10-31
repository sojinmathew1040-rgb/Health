<?php
/**
 * Admin Panel - Employer Verifications & Account Activation
 * Apple Level Pro Redesign - Zero Sideways Scroll, SF Pro Aesthetics, Responsive macOS Architecture
 * Route: http://localhost/Health/admin/employer-verifications.php
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

// Generate default random password with mix of uppercase, lowercase, numbers, and special characters
function generateRandomPassword($length = 9): string {
    $uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lowers = 'abcdefghjkmnpqrstuvwxyz';
    $numbers = '23456789';
    $specials = '!@#$%';
    
    $pass = '';
    $pass .= $uppers[random_int(0, strlen($uppers) - 1)];
    $pass .= $lowers[random_int(0, strlen($lowers) - 1)];
    $pass .= $numbers[random_int(0, strlen($numbers) - 1)];
    $pass .= $specials[random_int(0, strlen($specials) - 1)];
    
    $allChars = $uppers . $lowers . $numbers . $specials;
    for ($i = 4; $i < $length; $i++) {
        $pass .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    return str_shuffle($pass);
}

// Handle Admin POST Actions (Activate & Generate Random Password, Reject & Email, Delete, Configure Email) BEFORE ANY HTML OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 0. Update Common Company Sender Email & Optional SMTP Configuration
    if ($action === 'update_company_sender_email') {
        $newSenderEmail = trim($_POST['company_sender_email'] ?? '');
        $newCompanyName = trim($_POST['company_name'] ?? '');
        if (!empty($newSenderEmail) && filter_var($newSenderEmail, FILTER_VALIDATE_EMAIL)) {
            setSystemSetting($con, 'company_sender_email', $newSenderEmail);
            if (!empty($newCompanyName)) {
                setSystemSetting($con, 'company_name', $newCompanyName);
            }
            if (isset($_POST['smtp_host'])) {
                setSystemSetting($con, 'smtp_host', trim($_POST['smtp_host']));
                setSystemSetting($con, 'smtp_port', trim($_POST['smtp_port'] ?? '587'));
                setSystemSetting($con, 'smtp_user', trim($_POST['smtp_user'] ?? ''));
                if (!empty($_POST['smtp_pass'])) {
                    setSystemSetting($con, 'smtp_pass', trim($_POST['smtp_pass']));
                }
                setSystemSetting($con, 'smtp_secure', trim($_POST['smtp_secure'] ?? 'tls'));
            }
            $_SESSION['toast_msg'] = "Company dispatch configuration updated successfully.";
            $targetUrl = 'employer-verifications.php?email_saved=1';
            if (!headers_sent()) {
                header('Location: ' . $targetUrl);
            } else {
                echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
            }
            exit;
        }
    }

    // 0b. Live Test Email Dispatch (AJAX)
    if ($action === 'test_send_email') {
        header('Content-Type: application/json; charset=utf-8');
        $testRecipient = trim($_POST['test_recipient'] ?? '');
        if (empty($testRecipient) || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid recipient email address.']);
            exit;
        }

        $companySender = getCompanySenderEmail($con);
        $companyName = getCompanyDisplayName($con);
        $testSubject = "Live Test Email from {$companyName}";
        $testBody = "Hello!\n\nThis is a live test message sent from your HealthJobsGlobal Admin panel.\n\nFrom: {$companyName} <{$companySender}>\nTo: {$testRecipient}\nStatus: System Email Gateway Operational\n\nIf you see this in your inbox, your real email delivery is working perfectly!";

        $res = sendSystemMail(
            $con,
            $testRecipient,
            $testSubject,
            $testBody,
            '',
            'test',
            'Test Recipient'
        );

        $isDelivered = ($res['status'] && strpos($res['status'], 'Delivered') !== false);
        echo json_encode([
            'success' => $isDelivered || $res['success'],
            'status' => $res['status'],
            'note' => $res['delivery_note'],
            'message' => $isDelivered 
                ? "Test email delivered successfully to {$testRecipient} via SMTP!" 
                : ($res['status'] . ": " . $res['delivery_note'])
        ]);
        exit;
    }

    // 1. Verify & Activate Employer Account
    if ($action === 'activate_employer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !empty($con) && !$con->connect_error) {
            $stmtF = $con->prepare("SELECT * FROM `hospitals` WHERE `id` = ? LIMIT 1");
            $stmtF->bind_param("i", $id);
            $stmtF->execute();
            $resF = $stmtF->get_result();
            $hosp = $resF ? $resF->fetch_assoc() : null;
            $stmtF->close();

            if ($hosp) {
                // Generate a default random password (combination of numbers and characters)
                $randomPass = generateRandomPassword(9);
                $hashedPass = password_hash($randomPass, PASSWORD_DEFAULT);

                // Update database: verified = 1, must_change_password = 1, password = hashed, activated_at = NOW()
                $stmtU = $con->prepare("UPDATE `hospitals` SET `verified` = 1, `must_change_password` = 1, `password` = ?, `initial_random_password` = ?, `activated_at` = NOW() WHERE `id` = ?");
                $stmtU->bind_param("ssi", $hashedPass, $randomPass, $id);
                $stmtU->execute();
                $stmtU->close();

                // Send email to registered address(es) from configured company common email
                $companySender = getCompanySenderEmail($con);
                $companyName = getCompanyDisplayName($con);

                $adminEmail = !empty($hosp['admin_email']) ? trim($hosp['admin_email']) : '';
                $contactEmail = !empty($hosp['contact_email']) ? trim($hosp['contact_email']) : '';
                $generalEmail = !empty($hosp['general_email']) ? trim($hosp['general_email']) : '';
                $recipientEmail = $adminEmail ?: ($contactEmail ?: $generalEmail);
                $adminName = !empty($hosp['admin_name']) ? $hosp['admin_name'] : ($hosp['name'] . ' Administrator');
                $orgName = $hosp['name'];

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $portalLoginUrl = $protocol . $host . '/Health/login.php';
                $emailSubject = "Account Activated — {$companyName} Employer Portal Login Credentials";
                
                $emailBody = "Dear {$adminName},\n\n";
                $emailBody .= "We are pleased to inform you that your Healthcare Organization account for '{$orgName}' has been verified and activated by the {$companyName} Administration.\n\n";
                $emailBody .= "Your initial system-generated login credentials are as follows:\n";
                $emailBody .= "--------------------------------------------------------\n";
                $emailBody .= "Portal Sign-In URL: {$portalLoginUrl}\n";
                $emailBody .= "Registered Email / User ID: {$recipientEmail}\n";
                $emailBody .= "Default Temporary Password: {$randomPass}\n";
                $emailBody .= "--------------------------------------------------------\n\n";
                $emailBody .= "IMPORTANT SECURITY INSTRUCTION:\n";
                $emailBody .= "Upon signing into the portal for the first time with this default password, a prompt will appear asking you to replace this temporary password with your own secure password.\n\n";
                $emailBody .= "Welcome to {$companyName} — The Premier Healthcare Recruitment Exchange.\n";
                $emailBody .= "Corporate Dispatch Contact: {$companySender}\n";
                $emailBody .= "{$companyName} Credentialing Team\n";

                // Dispatch via Unified Mail Gateway (saves to outbox & HTML preview log and attempts SMTP)
                $mailResult = sendSystemMail(
                    $con,
                    $recipientEmail,
                    $emailSubject,
                    $emailBody,
                    $randomPass,
                    'activation',
                    $adminName
                );
                if (!empty($contactEmail) && $contactEmail !== $recipientEmail) {
                    sendSystemMail(
                        $con,
                        $contactEmail,
                        $emailSubject,
                        $emailBody,
                        $randomPass,
                        'activation',
                        $adminName
                    );
                }

                $_SESSION['activation_popup'] = [
                    'id' => $id,
                    'name' => $orgName,
                    'email' => $recipientEmail,
                    'admin_name' => $adminName,
                    'random_password' => $randomPass,
                    'email_subject' => $emailSubject,
                    'email_body' => $emailBody,
                    'status' => $mailResult['status'] ?? 'Dispatched',
                    'delivery_note' => $mailResult['delivery_note'] ?? 'Logged in Outbox'
                ];

                $targetUrl = 'employer-verifications.php?activated=1';
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // 2. Reject Employer Registration with Custom Reason & Feedback Email Dispatch
    if ($action === 'reject_employer_email') {
        $id = (int)($_POST['id'] ?? 0);
        $emailSubject = trim($_POST['email_subject'] ?? 'Update Regarding Healthcare Employer Registration — HealthJobsGlobal');
        $emailBody = trim($_POST['email_body'] ?? '');
        $rejectionReason = trim($_POST['rejection_reason'] ?? 'Requirements Not Met');
        $removeRecord = isset($_POST['remove_record']) ? 1 : 0;

        if ($id > 0 && !empty($con) && !$con->connect_error) {
            $stmtF = $con->prepare("SELECT * FROM `hospitals` WHERE `id` = ? LIMIT 1");
            $stmtF->bind_param("i", $id);
            $stmtF->execute();
            $resF = $stmtF->get_result();
            $hosp = $resF ? $resF->fetch_assoc() : null;
            $stmtF->close();

            if ($hosp) {
                $adminEmail = !empty($hosp['admin_email']) ? trim($hosp['admin_email']) : '';
                $contactEmail = !empty($hosp['contact_email']) ? trim($hosp['contact_email']) : '';
                $generalEmail = !empty($hosp['general_email']) ? trim($hosp['general_email']) : '';
                $recipientEmail = $adminEmail ?: ($contactEmail ?: $generalEmail);
                $adminName = !empty($hosp['admin_name']) ? $hosp['admin_name'] : ($hosp['name'] . ' Administrator');
                $orgName = $hosp['name'];

                // Dispatch Rejection / Feedback Notice Email via Unified Mail Gateway
                $mailResult = sendSystemMail(
                    $con,
                    $recipientEmail,
                    $emailSubject,
                    $emailBody,
                    '',
                    'rejection',
                    $adminName
                );
                if (!empty($contactEmail) && $contactEmail !== $recipientEmail) {
                    sendSystemMail(
                        $con,
                        $contactEmail,
                        $emailSubject,
                        $emailBody,
                        '',
                        'rejection',
                        $adminName
                    );
                }

                // Remove from pending queue (Cascade delete all associated clinical jobs & applications)
                if ($removeRecord) {
                    deleteHospitalCascade($con, $id);
                } else {
                    @$con->query("ALTER TABLE `hospitals` ADD `rejection_reason` TEXT DEFAULT NULL");
                    $stmtR = $con->prepare("UPDATE `hospitals` SET `verified` = -1, `rejection_reason` = ? WHERE `id` = ?");
                    if ($stmtR) {
                        $stmtR->bind_param("si", $emailBody, $id);
                        $stmtR->execute();
                        $stmtR->close();
                    }
                }

                // Flash session notification for Apple Modal sheet
                $_SESSION['rejection_sent_popup'] = [
                    'id' => $id,
                    'name' => $orgName,
                    'email' => $recipientEmail,
                    'admin_name' => $adminName,
                    'reason' => $rejectionReason,
                    'email_subject' => $emailSubject,
                    'email_body' => $emailBody
                ];

                $targetUrl = 'employer-verifications.php?rejected=1';
                if (!headers_sent()) {
                    header('Location: ' . $targetUrl);
                } else {
                    echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
                }
                exit;
            }
        }
    }

    // 3. Direct Delete Employer Registration (Cascade delete all associated clinical jobs & applications)
    if ($action === 'delete_registration') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !empty($con) && !$con->connect_error) {
            deleteHospitalCascade($con, $id);
            $targetUrl = 'employer-verifications.php?deleted=1';
            if (!headers_sent()) {
                header('Location: ' . $targetUrl);
            } else {
                echo "<script>window.location.href='" . addslashes($targetUrl) . "';</script>";
            }
            exit;
        }
    }
}

// Counts and Filter
$pendingCount = 0;
$verifiedCount = 0;
$totalCount = 0;

if (!empty($con) && !$con->connect_error) {
    $resCounts = $con->query("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as pending_c,
        SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_c 
        FROM `hospitals`");
    if ($resCounts && $cRow = $resCounts->fetch_assoc()) {
        $totalCount = (int)$cRow['total'];
        $pendingCount = (int)($cRow['pending_c'] ?? 0);
        $verifiedCount = (int)($cRow['verified_c'] ?? 0);
    }
}

$filter = 'pending';
$whereClause = "WHERE (h.verified = 0 OR h.verified IS NULL)";

// Fetch Registrations
$registrations = [];
if (!empty($con) && !$con->connect_error) {
    $sql = "SELECT h.*, 
            (SELECT COUNT(*) FROM `jobs` WHERE hospital_id = h.id OR company = h.name) as active_openings
            FROM `hospitals` h 
            {$whereClause} 
            ORDER BY (CASE WHEN h.verified = 0 OR h.verified IS NULL THEN 0 ELSE 1 END) ASC, h.id DESC";
    $res = $con->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $registrations[] = $row;
        }
    }
}

$companySenderEmail = getCompanySenderEmail($con);
$companyDisplayName = getCompanyDisplayName($con);
$currentSmtpHost = getSystemSetting($con, 'smtp_host', '');
$currentSmtpPort = getSystemSetting($con, 'smtp_port', '587');
$currentSmtpUser = getSystemSetting($con, 'smtp_user', '');
$currentSmtpPass = getSystemSetting($con, 'smtp_pass', '');
$currentSmtpSecure = getSystemSetting($con, 'smtp_secure', 'tls');

$pageTitle = "Employer Verifications & Approvals — HealthJobsGlobal Admin";
$pageHeading = "Employer Registrations & Verifications";

require_once __DIR__ . '/includes/header.php';

$quickActionBtn = '<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
  <button type="button" class="apple-btn-secondary" onclick="openCompanyEmailModal()" style="font-size: 0.82rem; padding: 7px 14px;" title="Configure common company email and outgoing SMTP settings">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
    <span>Sender: ' . htmlspecialchars($companySenderEmail) . '</span>
  </button>
  <a href="employers.php" class="apple-btn-secondary" style="font-size: 0.82rem; padding: 7px 14px; text-decoration: none;">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/></svg>
    <span>Hospitals Directory</span>
  </a>
</div>';

require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Apple Level Pro Custom Styles (Zero Sideways Scroll, SF Pro Design) -->
<style>
/* Apple Typography & Palette Tokens */
:root {
  --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", sans-serif;
  --apple-bg: #F5F7FA;
  --apple-card-bg: rgba(255, 255, 255, 0.95);
  --apple-border: rgba(226, 232, 240, 0.85);
  --apple-border-focus: #0071E3;
  --apple-blue: #0071E3;
  --apple-blue-hover: #0077ED;
  --apple-green: #34C759;
  --apple-green-hover: #2DAE4E;
  --apple-green-bg: #ECFDF5;
  --apple-green-text: #065F46;
  --apple-amber: #FF9500;
  --apple-amber-bg: #FFFBEB;
  --apple-amber-text: #92400E;
  --apple-red: #FF3B30;
  --apple-red-bg: #FEF2F2;
  --apple-text-main: #1D1D1F;
  --apple-text-muted: #6E6E73;
  --apple-text-light: #86868B;
  --apple-glass-shadow: 0 4px 24px -2px rgba(0, 0, 0, 0.04), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
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

/* Luminous Apple Pro Hero Card */
.apple-hero-card {
  position: relative;
  background: linear-gradient(135deg, #0B192C 0%, #1E3E62 50%, #000000 100%);
  border-radius: 20px;
  padding: 30px 36px;
  margin-bottom: 24px;
  color: #FFFFFF;
  box-shadow: 0 14px 34px -8px rgba(11, 25, 44, 0.35);
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
  width: 320px;
  max-width: 100%;
}

.apple-spotlight-input {
  width: 100%;
  height: 42px;
  padding: 0 40px 0 38px;
  border-radius: 9999px;
  background: #FFFFFF;
  border: 1px solid var(--apple-border);
  font-size: 0.88rem;
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

/* Apple Pro Zero-Scroll Employer Cards Feed */
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
  font-size: 1.1rem;
  letter-spacing: -0.02em;
  color: #FFFFFF;
  background: linear-gradient(135deg, #0284C7, #0369A1);
  box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
  flex-shrink: 0;
}

.apple-employer-card.is-pending .apple-squircle-avatar {
  background: linear-gradient(135deg, #F59E0B, #D97706);
  box-shadow: 0 4px 10px rgba(245, 158, 11, 0.25);
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
  transition: color 0.15s;
}

.apple-web-link:hover {
  text-decoration: underline;
  color: var(--apple-blue-hover);
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
  background: var(--apple-amber-bg);
  color: var(--apple-amber-text);
  border: 1px solid #FDE68A;
}

.apple-status-capsule.active {
  background: var(--apple-green-bg);
  color: var(--apple-green-text);
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

.apple-btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #E11D48;
  color: #FFFFFF;
  border: none;
  border-radius: 9999px;
  padding: 8px 18px;
  font-size: 0.84rem;
  font-weight: 700;
  font-family: var(--apple-font);
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(225, 29, 72, 0.35);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-decoration: none;
}

.apple-btn-danger:hover {
  background: #BE123C;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(225, 29, 72, 0.45);
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
  padding: 24px 28px;
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
  padding: 18px 28px;
  border-top: 1px solid #F1F5F9;
  background: #FAFAFA;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
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

<!-- Top Apple Pro Activation Success Notification Popup -->
<?php if (!empty($_SESSION['activation_popup'])): 
    $popupData = $_SESSION['activation_popup'];
    unset($_SESSION['activation_popup']);
?>
<div class="apple-modal-overlay active" id="activationResultModal">
  <div class="apple-modal-sheet" style="max-width: 600px;">
    <div class="apple-modal-sheet-header" style="background: linear-gradient(135deg, #059669, #047857); color: #FFFFFF;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">🎉</span>
        <div>
          <h3 style="color: #FFFFFF; margin: 0; font-size: 1.15rem; font-weight: 700;">Account Verified & Activated</h3>
          <div style="font-size: 0.78rem; color: #A7F3D0;">HealthJobsGlobal Administration</div>
        </div>
      </div>
      <button class="apple-close-circle" style="background: rgba(255,255,255,0.2); color: #FFFFFF;" onclick="document.getElementById('activationResultModal').remove()">&times;</button>
    </div>
    
    <div class="apple-modal-sheet-body">
      <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;">
        <div style="font-weight: 700; color: #065F46; font-size: 1rem; margin-bottom: 2px;">
          <?php echo htmlspecialchars($popupData['name']); ?>
        </div>
        <div style="font-size: 0.84rem; color: #047857;">
          Account status has been successfully updated to <strong>Active & Verified</strong>. Login credentials have been dispatched.
        </div>
      </div>

      <!-- Generated Default Random Password Display Pill -->
      <div style="background: #F8FAFC; border: 2px dashed #0284C7; border-radius: 14px; padding: 18px; margin-bottom: 20px; text-align: center;">
        <div style="font-size: 0.75rem; font-weight: 800; color: #0369A1; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
          ⚡ Generated Default Password (Alphanumeric & Symbols)
        </div>
        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; background: #FFFFFF; padding: 10px 16px; border-radius: 10px; border: 1.5px solid #BAE6FD; max-width: 380px; margin: 0 auto 10px auto;">
          <span id="genPassBox" style="font-family: monospace; font-size: 1.35rem; font-weight: 800; letter-spacing: 0.1em; color: #0F172A;">
            <?php echo htmlspecialchars($popupData['random_password']); ?>
          </span>
          <button type="button" class="apple-btn-secondary" onclick="copyPasswordToClipboard('<?php echo htmlspecialchars($popupData['random_password']); ?>')" style="padding: 5px 12px; font-size: 0.78rem;">
            Copy
          </button>
        </div>
        <div style="font-size: 0.82rem; color: #64748B;">
          Emailed to Administrator: <strong style="color: #0F172A;"><?php echo htmlspecialchars($popupData['email']); ?></strong>
        </div>
      </div>

      <!-- Mandatory Password Change Banner -->
      <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 14px 18px; font-size: 0.84rem; color: #1E40AF; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px;">
        <span style="font-size: 1.1rem; line-height: 1;">🔒</span>
        <div>
          <strong>Automatic First-Login Security Gate:</strong> When the employer logs in using this temporary password, the system will prompt them to replace it with their personal secure password before accessing the dashboard.
        </div>
      </div>

      <!-- Email Content Drawer -->
      <details style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; color: #475569;">
        <summary style="font-weight: 700; cursor: pointer; color: #0284C7;">View Dispatched Activation Email Body</summary>
        <pre style="white-space: pre-wrap; font-family: monospace; font-size: 0.78rem; margin-top: 10px; background: #FFFFFF; padding: 12px; border-radius: 8px; border: 1px solid #CBD5E1; color: #1E293B;"><?php echo htmlspecialchars($popupData['email_body']); ?></pre>
      </details>
    </div>
    
    <div class="apple-modal-sheet-footer">
      <button type="button" class="apple-btn-secondary" onclick="document.getElementById('activationResultModal').remove()">
        Done & Close
      </button>
      <a href="employers.php?filter=verified" class="apple-btn-primary" style="text-decoration: none;">
        <span>View in Hospital Networks &rarr;</span>
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Top Apple Pro Rejection Sent Notification Popup -->
<?php if (!empty($_SESSION['rejection_sent_popup'])): 
    $rejPopup = $_SESSION['rejection_sent_popup'];
    unset($_SESSION['rejection_sent_popup']);
?>
<div class="apple-modal-overlay active" id="rejectionResultModal">
  <div class="apple-modal-sheet" style="max-width: 660px;">
    <div class="apple-modal-sheet-header" style="background: linear-gradient(135deg, #E11D48, #BE123C); color: #FFFFFF;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">✉️</span>
        <div>
          <h3 style="color: #FFFFFF; margin: 0; font-size: 1.15rem; font-weight: 700;">Non-Approval Feedback Email Dispatched</h3>
          <div style="font-size: 0.78rem; color: #FECDD3;">HealthJobsGlobal Credentialing & Operations</div>
        </div>
      </div>
      <button class="apple-close-circle" style="background: rgba(255,255,255,0.2); color: #FFFFFF;" onclick="document.getElementById('rejectionResultModal').remove()">&times;</button>
    </div>
    
    <div class="apple-modal-sheet-body">
      <div style="background: #FFF1F2; border: 1px solid #FECDD3; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px;">
        <div style="font-weight: 700; color: #9F1239; font-size: 1rem; margin-bottom: 2px;">
          <?php echo htmlspecialchars($rejPopup['name']); ?>
        </div>
        <div style="font-size: 0.84rem; color: #BE123C;">
          A formal notification detailing the reasons for non-approval has been dispatched to <strong><?php echo htmlspecialchars($rejPopup['email']); ?></strong>.
        </div>
      </div>

      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;">
        <div style="font-size: 0.72rem; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 4px;">Primary Non-Approval Reason</div>
        <div style="font-size: 0.9rem; font-weight: 700; color: #0F172A;"><?php echo htmlspecialchars($rejPopup['reason']); ?></div>
      </div>

      <!-- Dispatched Email Full View (Directly Visible, Not Hidden) -->
      <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 0.75rem; font-weight: 800; color: #E11D48; text-transform: uppercase; letter-spacing: 0.04em;">
            ✉️ Dispatched Email Content
          </div>
          <button type="button" class="apple-btn-secondary" onclick="copyPasswordToClipboard(document.getElementById('sentEmailPre').innerText)" style="padding: 4px 10px; font-size: 0.74rem;">
            Copy Email
          </button>
        </div>
        <div style="font-size: 0.8rem; color: #475569; margin-bottom: 6px;">
          Subject: <strong style="color: #0F172A;"><?php echo htmlspecialchars($rejPopup['email_subject']); ?></strong>
        </div>
        <pre id="sentEmailPre" style="white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, 'SF Mono', monospace; font-size: 0.8rem; line-height: 1.55; background: #FFFFFF; padding: 14px; border-radius: 8px; border: 1px solid #E2E8F0; color: #1E293B; max-height: 260px; overflow-y: auto; margin: 0;"><?php echo htmlspecialchars($rejPopup['email_body']); ?></pre>
      </div>
    </div>
    
    <div class="apple-modal-sheet-footer">
      <div></div>
      <button type="button" class="apple-btn-primary" style="background: #E11D48;" onclick="document.getElementById('rejectionResultModal').remove()">
        Done & Close
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['email_saved'])): ?>
<div style="background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 14px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);">
  <div style="display: flex; align-items: center; gap: 10px;">
    <span style="font-size: 1.25rem;">✅</span>
    <div>
      <strong style="color: #065F46; font-size: 0.92rem;">Company Sender Email Updated Successfully</strong>
      <div style="color: #047857; font-size: 0.8rem; margin-top: 1px;">
        All subsequent approval credentials and non-approval explanation emails will be dispatched from: <strong><?php echo htmlspecialchars($companySenderEmail); ?></strong>
      </div>
    </div>
  </div>
  <a href="employer-verifications.php" style="color: #059669; font-weight: 700; font-size: 0.8rem; text-decoration: none; padding: 4px 10px; border-radius: 6px; background: #D1FAE5;">Dismiss</a>
</div>
<?php endif; ?>

<!-- Apple Pro Luminous Hero Header -->
<div class="apple-hero-card">
  <div class="apple-hero-left">
    <div class="apple-pill-tag">
      <span class="pulse-dot <?php echo $pendingCount > 0 ? 'amber' : 'emerald'; ?>"></span>
      <span>Multi-Vertical Healthcare Governance</span>
    </div>
    <h2 class="apple-hero-title">Employer Verification & Credentialing</h2>
    <p class="apple-hero-sub">
      Authorize registered hospitals, diagnostic networks, and clinics. Approving an account instantly provisions a secure temporary password and dispatches portal credentials.
    </p>
  </div>

  <div class="apple-metrics-group">
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #FFB020;">
        <span class="pulse-dot amber" style="margin-right: 2px;"></span><?php echo $pendingCount; ?>
      </div>
      <div class="apple-metric-lbl">Pending Review</div>
    </div>
    <div class="apple-metric-pill">
      <div class="apple-metric-val" style="color: #34C759;">
        <span class="pulse-dot emerald" style="margin-right: 2px;"></span><?php echo $verifiedCount; ?>
      </div>
      <div class="apple-metric-lbl">Active Partners</div>
    </div>
    <a href="employers.php" class="apple-metric-pill" style="text-decoration: none; cursor: pointer;" title="Open Hospital Networks">
      <div class="apple-metric-val" style="color: #38BDF8;">
        <?php echo $verifiedCount; ?> &nearr;
      </div>
      <div class="apple-metric-lbl">In Hospital Networks</div>
    </a>
  </div>
</div>

<!-- Apple Pro Company Dispatch Email Control Bar -->
<div class="apple-employer-card" style="margin-bottom: 24px; padding: 18px 24px; background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%); border-left: 5px solid var(--apple-blue);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    
    <div style="display: flex; align-items: center; gap: 14px;">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #0071E3, #0284C7); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; box-shadow: 0 4px 10px rgba(0, 113, 227, 0.25); flex-shrink: 0;">
        ✉️
      </div>
      <div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px;">
          <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--apple-blue); background: #E0F2FE; padding: 2px 8px; border-radius: 4px;">
            Central Outgoing Dispatch Gateway
          </span>
          <span class="apple-status-capsule active" style="padding: 2px 10px; font-size: 0.72rem;">
            <span class="pulse-dot emerald"></span>Active Gateway
          </span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <span style="font-size: 1.05rem; font-weight: 800; color: #0F172A; font-family: monospace; letter-spacing: -0.01em;">
            <?php echo htmlspecialchars($companySenderEmail); ?>
          </span>
          <span style="font-size: 0.82rem; color: #64748B;">
            · Display Sender: <strong><?php echo htmlspecialchars($companyDisplayName); ?></strong>
          </span>
        </div>
        <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">
          All verification approval passwords and non-approval explanation emails are dispatched from this common address directly to the user's registered email.
        </div>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 10px;">
      <button type="button" class="apple-btn-primary" onclick="openCompanyEmailModal()" style="font-size: 0.82rem; padding: 8px 18px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <span>Change Common Email ID</span>
      </button>
    </div>

  </div>
</div>

<!-- Apple Toolbar: Segmented Controls + Spotlight Search -->
<div class="apple-toolbar">
  <div class="apple-segmented-track">
    <span class="apple-segment-item active">
      <span class="pulse-dot amber"></span>
      <span>Awaiting Verification</span>
      <span class="apple-count-bubble"><?php echo $pendingCount; ?></span>
    </span>
    <a href="employers.php" class="apple-segment-item" title="Click to view all approved healthcare employers in Hospital Networks">
      <span class="pulse-dot emerald"></span>
      <span>Approved in Hospital Networks</span>
      <span class="apple-count-bubble"><?php echo $verifiedCount; ?> &nearr;</span>
    </a>
  </div>

  <div class="apple-spotlight-box">
    <svg class="apple-spotlight-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" class="apple-spotlight-input" placeholder="Search organization, admin, city..." id="appleSearchInput">
    <span class="apple-spotlight-badge" id="searchMatchCount"><?php echo count($registrations); ?></span>
  </div>
</div>

<!-- Zero-Scroll Apple Pro Employer Cards Feed -->
<div class="apple-cards-container" id="employerCardsContainer">
  <?php if (empty($registrations)): ?>
    <div class="apple-empty-state" style="padding: 56px 24px;">
      <div style="font-size: 3rem; margin-bottom: 14px;">🎉</div>
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--apple-text-main); margin-bottom: 8px;">All Registrations Verified</h3>
      <p style="font-size: 0.92rem; color: var(--apple-text-muted); max-width: 520px; margin: 0 auto 24px auto; line-height: 1.55;">
        There are currently no employer registrations awaiting admin verification. All approved healthcare organizations have been provisioned and are active in the <strong>Hospital Networks</strong> tab.
      </p>
      <a href="employers.php" class="apple-btn-primary" style="padding: 10px 24px; font-size: 0.9rem; text-decoration: none; display: inline-flex;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/></svg>
        <span>Open Hospital Networks & Medical Centers &rarr;</span>
      </a>
    </div>
  <?php else: ?>
    <script>
      window.allPendingHospitals = window.allPendingHospitals || {};
    </script>
    <?php foreach ($registrations as $r): 
      $isPending = empty($r['verified']) || (int)$r['verified'] === 0;
      $srvList = !empty($r['services']) ? explode(',', $r['services']) : [];
      $avatarText = !empty($r['logo_text']) ? $r['logo_text'] : substr($r['name'], 0, 3);
      $rId = (int)$r['id'];
    ?>
      <script>
        window.allPendingHospitals[<?php echo $rId; ?>] = <?php echo json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
      </script>
      <div class="apple-employer-card <?php echo $isPending ? 'is-pending' : 'is-verified'; ?>" data-search-target="<?php echo htmlspecialchars(strtolower($r['name'] . ' ' . $r['admin_name'] . ' ' . $r['admin_email'] . ' ' . $r['city'] . ' ' . $r['type'])); ?>">
        
        <!-- Header Strip -->
        <div class="apple-card-header">
          <div class="apple-org-identity">
            <div class="apple-squircle-avatar">
              <?php echo htmlspecialchars(strtoupper($avatarText)); ?>
            </div>
            <div>
              <h3 class="apple-org-title">
                <span><?php echo htmlspecialchars($r['name']); ?></span>
                <?php if ($isPending): ?>
                  <span class="apple-badge-pill" style="background: #FEF3C7; color: #92400E; font-weight: 800; font-size: 0.68rem;">NEW REGISTRATION</span>
                <?php endif; ?>
              </h3>
              <div class="apple-subtext">
                <span class="apple-badge-pill apple-service-tag"><?php echo htmlspecialchars($r['type'] ?: 'Hospital'); ?></span>
                <?php if (!empty($r['website'])): ?>
                  <span>·</span>
                  <a href="<?php echo htmlspecialchars($r['website']); ?>" target="_blank" class="apple-web-link">
                    <?php echo htmlspecialchars(parse_url($r['website'], PHP_URL_HOST) ?: $r['website']); ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
                  </a>
                <?php endif; ?>
                <span>·</span>
                <span>Ref: HOSP-<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?></span>
              </div>
            </div>
          </div>

          <!-- Status Indicator Pill -->
          <div>
            <?php if ($isPending): ?>
              <div class="apple-status-capsule pending">
                <span class="pulse-dot amber"></span>
                <span>Awaiting Admin Activation</span>
              </div>
            <?php else: ?>
              <div class="apple-status-capsule active">
                <span class="pulse-dot emerald"></span>
                <span>Active & Verified Partner</span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 3-Column Balanced Information Grid (Fits cleanly into all viewports) -->
        <div class="apple-info-grid">
          
          <!-- Column 1: Authorizing Administrator -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span>Authorizing Administrator</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars(!empty($r['admin_name']) ? $r['admin_name'] : 'N/A'); ?>
            </div>
            <div class="apple-cell-sub">
              <?php echo htmlspecialchars(!empty($r['admin_designation']) ? $r['admin_designation'] : 'Head of Medical Talent Acquisition'); ?>
            </div>
            <div style="margin-top: 6px; font-size: 0.8rem;">
              <a href="mailto:<?php echo htmlspecialchars($r['admin_email'] ?: ($r['contact_email'] ?? '')); ?>" style="color: var(--apple-blue); font-weight: 600; text-decoration: none;">
                ✉ <?php echo htmlspecialchars($r['admin_email'] ?: ($r['contact_email'] ?? '')); ?>
              </a>
              <?php if (!empty($r['admin_phone']) || !empty($r['phone'])): ?>
                <div style="color: var(--apple-text-muted); font-size: 0.76rem; margin-top: 2px;">
                  📞 <?php echo htmlspecialchars($r['admin_phone'] ?: ($r['phone'] ?? '')); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Column 2: Campus & Scale -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>Campus & Scale</span>
            </div>
            <div class="apple-cell-main">
              <?php echo htmlspecialchars($r['city'] ?: ($r['location'] ?? 'India')); ?>
            </div>
            <div class="apple-cell-sub" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" title="<?php echo htmlspecialchars($r['address'] ?: ($r['location'] ?? '')); ?>">
              <?php echo htmlspecialchars($r['address'] ?: ($r['location'] ?? '')); ?>
            </div>
            <div style="margin-top: 6px;">
              <span class="apple-badge-pill" style="background: #EEF2F6; color: #475569; font-weight: 600;">
                🏢 <?php echo htmlspecialchars($r['operating_capacity'] ?: 'Institutional Scale'); ?>
              </span>
            </div>
          </div>

          <!-- Column 3: Requested Solutions -->
          <div class="apple-info-cell">
            <div class="apple-cell-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <span>Requested Staffing Solutions</span>
            </div>
            <?php if (!empty($srvList)): ?>
              <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px;">
                <?php foreach (array_slice($srvList, 0, 3) as $s): ?>
                  <span class="apple-badge-pill apple-service-tag">
                    <?php echo htmlspecialchars(trim($s)); ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($srvList) > 3): ?>
                  <span class="apple-badge-pill" style="background: #F1F5F9; color: #64748B;">
                    +<?php echo count($srvList) - 3; ?> more
                  </span>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="apple-cell-sub" style="margin-top: 4px;">
                Standard Healthcare Staffing & Placement Portal Access
              </div>
            <?php endif; ?>
            <?php if (!empty($r['other_services_details'])): ?>
              <div style="font-size: 0.72rem; color: #0369A1; font-weight: 600; margin-top: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Note: <?php echo htmlspecialchars($r['other_services_details']); ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

        <!-- Footer Action Strip (Visible directly, NO horizontal scroll needed) -->
        <div class="apple-card-footer">
          <div class="apple-footer-meta">
            <span>Enrolled: <strong><?php echo !empty($r['created_at']) ? date('M d, Y', strtotime($r['created_at'])) : 'Recently'; ?></strong></span>
            <?php if (!empty($r['activated_at'])): ?>
              <span>·</span>
              <span>Activated: <strong><?php echo date('M d, Y', strtotime($r['activated_at'])); ?></strong></span>
            <?php endif; ?>
            <?php if (!$isPending && !empty($r['must_change_password'])): ?>
              <span>·</span>
              <span style="color: #D97706; font-weight: 700;">Password Update Pending</span>
            <?php endif; ?>
          </div>

          <div class="apple-action-btn-group">
            
            <!-- Details Inspection Button -->
            <button type="button" class="apple-btn-secondary" onclick="openDetailsModalById(<?php echo $rId; ?>)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <span>Inspect Details</span>
            </button>

            <!-- Activate Action -->
            <?php if ($isPending): ?>
              <form method="POST" action="employer-verifications.php" style="display:inline;" onsubmit="return confirm('Verify & Activate <?php echo addslashes($r['name']); ?>?\n\nA secure random temporary password will be automatically generated and emailed to <?php echo addslashes($r['admin_email'] ?: $r['contact_email']); ?>.');">
                <input type="hidden" name="action" value="activate_employer">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="apple-btn-primary">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Verify & Activate</span>
                </button>
              </form>
            <?php else: ?>
              <button type="button" class="apple-btn-secondary" onclick="openDetailsModalById(<?php echo $rId; ?>)" style="color: #0284C7;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Credentials</span>
              </button>
            <?php endif; ?>

            <!-- Decline & Send Feedback Email Action -->
            <button type="button" class="apple-btn-secondary" style="color: #E11D48; border-color: #FECDD3;" onclick="openDeclineModalById(<?php echo $rId; ?>)" title="Decline registration and send email with reason">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <span>Decline & Email</span>
            </button>

            <!-- Delete Registration Action -->
            <form method="POST" action="employer-verifications.php" style="display:inline;" onsubmit="return confirm('Permanently remove registration for <?php echo addslashes($r['name']); ?> without emailing?');">
              <input type="hidden" name="action" value="delete_registration">
              <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
              <button type="submit" class="apple-btn-icon-danger" title="Direct Delete without Email">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </button>
            </form>

          </div>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Apple Pro macOS Sheet Modal: Full Registration Details -->
<div class="apple-modal-overlay" id="fullDetailsModal">
  <div class="apple-modal-sheet">
    <div class="apple-modal-sheet-header">
      <div>
        <h3 id="mdlOrgName" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0F172A;">Healthcare Organization</h3>
        <div id="mdlStatusBadge" style="margin-top: 4px;"></div>
      </div>
      <button class="apple-close-circle" onclick="closeDetailsModal()">&times;</button>
    </div>

    <div class="apple-modal-sheet-body">
      
      <!-- Group 1: Profile & Scale -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        1. Organization Profile & Domain
      </div>
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label">Healthcare Vertical / Category</span>
          <span class="apple-settings-val" id="mdlOrgCategory">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Operating Capacity / Bed Count</span>
          <span class="apple-settings-val" id="mdlOperatingScale">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Official Website</span>
          <span class="apple-settings-val" id="mdlWebsite">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Enrolled On</span>
          <span class="apple-settings-val" id="mdlCreatedAt">-</span>
        </div>
      </div>

      <!-- Group 2: Authorizing Administrator -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        2. Authorizing Administrator
      </div>
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label">Full Name</span>
          <span class="apple-settings-val" id="mdlAdminName">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Official Designation</span>
          <span class="apple-settings-val" id="mdlAdminDesig">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Direct Email ID</span>
          <span class="apple-settings-val" id="mdlAdminEmail" style="color: var(--apple-blue);">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Direct Mobile / WhatsApp</span>
          <span class="apple-settings-val" id="mdlAdminPhone">-</span>
        </div>
      </div>

      <!-- Group 3: Campus & Contacts -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        3. Campus Location & Contacts
      </div>
      <div class="apple-settings-group">
        <div class="apple-settings-row">
          <span class="apple-settings-label">Campus / Street Address</span>
          <span class="apple-settings-val" id="mdlAddress" style="max-width: 380px;">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">City, State & Postal PIN</span>
          <span class="apple-settings-val" id="mdlCityPincode">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Country</span>
          <span class="apple-settings-val" id="mdlCountry">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Institutional General Email</span>
          <span class="apple-settings-val" id="mdlGeneralEmail">-</span>
        </div>
        <div class="apple-settings-row">
          <span class="apple-settings-label">Boardline Telephone</span>
          <span class="apple-settings-val" id="mdlGeneralPhone">-</span>
        </div>
      </div>

      <!-- Group 4: Requested Services -->
      <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--apple-text-light); margin-bottom: 8px;">
        4. Staffing Solutions Requested
      </div>
      <div class="apple-settings-group" style="padding: 14px 18px;">
        <div id="mdlServicesList" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
        <div id="mdlOtherServicesRow" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #E2E8F0;">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--apple-text-muted); margin-bottom: 4px;">ADDITIONAL REMARKS</div>
          <div id="mdlOtherServicesText" style="font-size: 0.84rem; color: #334155;"></div>
        </div>
      </div>

      <!-- Group 5: Credential State -->
      <div id="mdlCredentialsBox" style="border-radius: 14px; padding: 16px 18px; border: 1.5px solid #FDE68A; background: #FFFBEB;">
        <div style="font-weight: 700; color: #92400E; font-size: 0.9rem; margin-bottom: 4px;">
          Credential Governance & Activation State:
        </div>
        <div id="mdlCredentialsText" style="font-size: 0.84rem; color: #78350F; line-height: 1.5;"></div>
      </div>

    </div>

    <div class="apple-modal-sheet-footer">
      <button type="button" class="apple-btn-secondary" onclick="closeDetailsModal()">Close</button>
      <div id="mdlActionButtons" style="display: flex; gap: 8px; align-items: center;"></div>
    </div>
  </div>
</div>

<!-- Apple Pro Modal Sheet: Decline Registration & Generate Draft Feedback Email -->
<div class="apple-modal-overlay" id="declineEmployerModal">
  <div class="apple-modal-sheet" style="max-width: 720px;">
    <div class="apple-modal-sheet-header" style="background: linear-gradient(135deg, #FFF1F2, #FFE4E6); border-bottom: 1px solid #FECDD3;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 12px; background: #E11D48; color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
          ✉️
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #9F1239;">Decline Employer & Generate Reason Email</h3>
          <div style="font-size: 0.78rem; color: #BE123C; margin-top: 2px;">
            A formal advisory email will be dispatched explaining why the account cannot be approved.
          </div>
        </div>
      </div>
      <button type="button" class="apple-close-circle" onclick="closeDeclineModal()">&times;</button>
    </div>

    <form method="POST" action="employer-verifications.php" id="declineEmployerForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
      <input type="hidden" name="action" value="reject_employer_email">
      <input type="hidden" name="id" id="declineHospId" value="">
      
      <div class="apple-modal-sheet-body" style="padding: 20px 24px; overflow-y: auto; flex: 1 1 auto; min-height: 0; max-height: calc(85vh - 140px);">
        
        <!-- Outgoing Company Dispatch Email Banner -->
        <div style="background: #F0F9FF; border: 1.5px solid #BAE6FD; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #0284C7; letter-spacing: 0.05em;">Dispatching From (Company Common Sender Email)</div>
            <div style="font-size: 0.95rem; font-weight: 800; color: #0369A1; font-family: monospace;" id="declineFromEmailDisplay">
              <?php echo htmlspecialchars($companySenderEmail); ?>
            </div>
            <div style="font-size: 0.76rem; color: #0284C7;">
              Sender: <?php echo htmlspecialchars($companyDisplayName); ?>
            </div>
          </div>
          <div>
            <input type="hidden" name="custom_from_email" id="declineCustomFromEmail" value="<?php echo htmlspecialchars($companySenderEmail); ?>">
            <button type="button" class="apple-btn-secondary" onclick="openCompanyEmailModal()" style="font-size: 0.78rem; padding: 5px 12px; background: #FFF;">
              Change Email
            </button>
          </div>
        </div>

        <!-- Recipient Information Strip -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div>
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--apple-text-muted);">Organization & Recipient</div>
            <div style="font-size: 0.92rem; font-weight: 700; color: #0F172A;" id="declineOrgNameDisplay">Organization</div>
            <div style="font-size: 0.8rem; color: var(--apple-blue);" id="declineRecipientEmailDisplay">email@domain.com</div>
          </div>
          <div>
            <span class="apple-badge-pill" style="background: #FFF1F2; color: #E11D48; font-weight: 700;">Status: Non-Approved</span>
          </div>
        </div>

        <!-- Non-Approval Reason Selector -->
        <div class="apple-form-group">
          <label class="apple-form-label" for="declineReasonSelect">Primary Reason for Non-Approval *</label>
          <select name="rejection_reason" id="declineReasonSelect" class="apple-select" onchange="generateDraftEmail()">
            <option value="Incomplete or Unverifiable NABH / JCI / Clinical Operating License">Incomplete or Unverifiable NABH / JCI / Clinical Operating License</option>
            <option value="Authorized Administrator Verification Failed">Authorized Administrator Verification Failed</option>
            <option value="Institutional Domain Email Required (Public webmail not accepted)">Institutional Domain Email Required (Public webmail not accepted)</option>
            <option value="Duplicate Organization Profile / Campus Already Registered">Duplicate Organization Profile / Campus Already Registered</option>
            <option value="Incomplete Institutional Scale & Clinical Profile">Incomplete Institutional Scale & Clinical Profile</option>
            <option value="Regulatory & Compliance Standard Notice">Custom Regulatory & Compliance Notice</option>
          </select>
        </div>

        <!-- Custom Remarks / Additional Notes -->
        <div class="apple-form-group">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label class="apple-form-label" for="declineCustomNote" style="margin-bottom: 0;">Specific Findings / Custom Notes (Optional)</label>
            <span style="font-size: 0.74rem; color: var(--apple-text-muted);">Auto-inserted into draft email</span>
          </div>
          <input type="text" id="declineCustomNote" class="apple-input" placeholder="e.g. Please provide NABH certificate valid for current calendar year..." oninput="generateDraftEmail()">
        </div>

        <!-- Draft Email Subject -->
        <div class="apple-form-group">
          <label class="apple-form-label" for="declineEmailSubject">Email Subject Line *</label>
          <input type="text" name="email_subject" id="declineEmailSubject" class="apple-input" required>
        </div>

        <!-- Editable Draft Email Body -->
        <div class="apple-form-group" style="margin-bottom: 14px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label class="apple-form-label" for="declineEmailBody" style="margin-bottom: 0;">Generated Draft Email (Fully Editable) *</label>
            <button type="button" class="apple-btn-secondary" onclick="generateDraftEmail(true)" style="padding: 3px 10px; font-size: 0.74rem; height: auto;">
              ⚡ Reset to Template
            </button>
          </div>
          <textarea name="email_body" id="declineEmailBody" class="apple-textarea" rows="6" style="font-family: monospace; font-size: 0.82rem; line-height: 1.5; min-height: 110px; max-height: 240px; resize: vertical;" required></textarea>
          <div style="font-size: 0.74rem; color: #64748B; margin-top: 4px;">
            You can review, modify, or add any custom instructions to this email draft before sending.
          </div>
        </div>

        <!-- Queue Removal Checkbox -->
        <div style="background: #FFF1F2; border: 1px solid #FECDD3; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
          <input type="checkbox" name="remove_record" id="declineRemoveRecord" value="1" checked style="width: 17px; height: 17px; accent-color: #E11D48; cursor: pointer;">
          <label for="declineRemoveRecord" style="font-size: 0.82rem; color: #9F1239; font-weight: 600; cursor: pointer; margin: 0;">
            Remove this registration permanently from the Pending Verification queue once email is dispatched.
          </label>
        </div>

      </div>

      <div class="apple-modal-sheet-footer" style="flex-shrink: 0; position: sticky; bottom: 0; z-index: 20; background: #FAFAFA; border-top: 1px solid #F1F5F9; padding: 16px 24px;">
        <button type="button" class="apple-btn-secondary" onclick="closeDeclineModal()">Cancel</button>
        <button type="submit" class="apple-btn-danger" style="padding: 10px 22px; font-size: 0.86rem; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4); cursor: pointer;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <span>Send Reason Email & Decline</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Apple macOS Sheet Modal: Configure Company Common Sender Email -->
<div class="apple-modal-overlay" id="companyEmailModal">
  <div class="apple-modal-sheet" style="max-width: 580px;">
    <div class="apple-modal-sheet-header">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #0071E3, #0284C7); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(0, 113, 227, 0.3);">
          📬
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0F172A;">Company Common Email Address</h3>
          <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">Central Outgoing Email Dispatch Configuration</div>
        </div>
      </div>
      <button type="button" class="apple-close-circle" onclick="closeCompanyEmailModal()">&times;</button>
    </div>

    <form method="POST" action="employer-verifications.php">
      <input type="hidden" name="action" value="update_company_sender_email">
      
      <div class="apple-modal-sheet-body" style="padding: 22px 26px;">
        <div style="background: #F0FDF4; border: 1.5px solid #BBF7D0; border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.84rem; color: #166534; line-height: 1.5;">
          <strong>Official Outgoing Common Email:</strong> All employer activation passwords and non-approval explanation emails are dispatched from this address directly to the email address entered by the user during registration.
        </div>

        <div class="apple-form-group">
          <label class="apple-form-label" for="inpCompanySenderEmail">Company Common Email Address *</label>
          <input type="email" name="company_sender_email" id="inpCompanySenderEmail" class="apple-input" value="<?php echo htmlspecialchars($companySenderEmail); ?>" placeholder="e.g. support@healthjobsglobal.com or yourname@gmail.com" required style="font-size: 0.96rem; font-weight: 700; color: #0071E3;">
          <div style="font-size: 0.74rem; color: #64748B; margin-top: 5px;">
            This email appears in the recipient's <code>From:</code> and <code>Reply-To:</code> headers.
          </div>
        </div>

        <div class="apple-form-group" style="margin-bottom: 16px;">
          <label class="apple-form-label" for="inpCompanyName">Company / Sender Display Name *</label>
          <input type="text" name="company_name" id="inpCompanyName" class="apple-input" value="<?php echo htmlspecialchars($companyDisplayName); ?>" placeholder="e.g. HealthJobsGlobal Administration" required>
          <div style="font-size: 0.74rem; color: #64748B; margin-top: 5px;">
            The friendly sender name that appears next to the email address in user inboxes.
          </div>
        </div>

        <!-- Advanced SMTP Settings (Optional - Collapsed by Default) -->
        <details style="margin-top: 18px; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 16px; background: #F8FAFC;">
          <summary style="font-size: 0.82rem; font-weight: 700; color: #475569; cursor: pointer; user-select: none;">
            ⚙️ Optional: Advanced SMTP Server Settings (Click to expand)
          </summary>
          <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #CBD5E1;">
            <p style="font-size: 0.76rem; color: #64748B; margin: 0 0 12px 0; line-height: 1.45;">
              If you have custom SMTP relay credentials (e.g. Google Workspace, SendGrid, or custom mail server), you can enter them here. Otherwise, the system uses standard server mail dispatch.
            </p>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 10px;">
              <div class="apple-form-group" style="margin: 0;">
                <label class="apple-form-label">SMTP Host</label>
                <input type="text" name="smtp_host" id="inpSmtpHost" class="apple-input" value="<?php echo htmlspecialchars($currentSmtpHost); ?>" placeholder="e.g. smtp.gmail.com">
              </div>
              <div class="apple-form-group" style="margin: 0;">
                <label class="apple-form-label">Port</label>
                <input type="text" name="smtp_port" id="inpSmtpPort" class="apple-input" value="<?php echo htmlspecialchars($currentSmtpPort); ?>" placeholder="587">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
              <div class="apple-form-group" style="margin: 0;">
                <label class="apple-form-label">SMTP Username / Email</label>
                <input type="text" name="smtp_user" id="inpSmtpUser" class="apple-input" value="<?php echo htmlspecialchars($currentSmtpUser); ?>" placeholder="your-email@gmail.com">
              </div>
              <div class="apple-form-group" style="margin: 0;">
                <label class="apple-form-label">Password / App Password</label>
                <input type="password" name="smtp_pass" id="inpSmtpPass" class="apple-input" value="<?php echo htmlspecialchars($currentSmtpPass); ?>" placeholder="••••••••">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
              <div class="apple-form-group" style="margin: 0;">
                <label class="apple-form-label">Encryption</label>
                <select name="smtp_secure" id="inpSmtpSecure" class="apple-select">
                  <option value="tls" <?php echo ($currentSmtpSecure === 'tls') ? 'selected' : ''; ?>>TLS / STARTTLS (Port 587)</option>
                  <option value="ssl" <?php echo ($currentSmtpSecure === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                  <option value="none" <?php echo ($currentSmtpSecure === 'none') ? 'selected' : ''; ?>>None / Plain</option>
                </select>
              </div>
              <div class="apple-form-group" style="margin: 0; display: flex; flex-direction: column; justify-content: flex-end;">
                <button type="button" class="apple-btn-secondary" onclick="presetGmailSmtp()" style="font-size: 0.78rem; padding: 9px 12px; text-align: center; justify-content: center;">
                  Use Gmail Defaults
                </button>
              </div>
            </div>

            <!-- Optional Quick Test Button inside settings -->
            <div style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed #E2E8F0; display: flex; gap: 8px; align-items: center;">
              <input type="email" id="testEmailRecipient" class="apple-input" placeholder="Test recipient (e.g. your-email@gmail.com)" value="<?php echo htmlspecialchars($companySenderEmail); ?>" style="font-size: 0.8rem; flex: 1;">
              <button type="button" id="btnTestEmail" class="apple-btn-secondary" onclick="sendLiveTestEmail()" style="font-size: 0.8rem; padding: 6px 14px; white-space: nowrap; font-weight: 700;">
                🧪 Test Connection
              </button>
            </div>
            <div id="testEmailFeedback" style="font-size: 0.78rem; margin-top: 6px; display: none; line-height: 1.4;"></div>
          </div>
        </details>
      </div>

      <div class="apple-modal-sheet-footer">
        <button type="button" class="apple-btn-secondary" onclick="closeCompanyEmailModal()">Cancel</button>
        <button type="submit" class="apple-btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Save & Apply Company Email</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Apple Spotlight Search & Modal Interactions -->
<script>
function openDetailsModal(hosp) {
  if (!hosp) return;

  document.getElementById('mdlOrgName').textContent = hosp.name || 'Organization';
  
  const isPending = !hosp.verified || parseInt(hosp.verified) === 0;
  const badgeEl = document.getElementById('mdlStatusBadge');
  if (isPending) {
    badgeEl.innerHTML = `<span class="apple-status-capsule pending"><span class="pulse-dot amber"></span>Awaiting Admin Verification — Account will be active after verification</span>`;
  } else {
    badgeEl.innerHTML = `<span class="apple-status-capsule active"><span class="pulse-dot emerald"></span>Active & Verified Healthcare Partner</span>`;
  }

  document.getElementById('mdlOrgCategory').textContent = hosp.type || hosp.badge || 'Quaternary Care Multi-Specialty Hospital';
  document.getElementById('mdlOperatingScale').textContent = hosp.operating_capacity || 'Medium Scale (100 - 500 Staff)';
  
  const webEl = document.getElementById('mdlWebsite');
  if (hosp.website) {
    webEl.innerHTML = `<a href="${hosp.website}" target="_blank" style="color: var(--apple-blue); text-decoration: underline; font-weight: 600;">${hosp.website} &nearr;</a>`;
  } else {
    webEl.textContent = 'Not Provided';
  }
  document.getElementById('mdlCreatedAt').textContent = hosp.created_at || 'Recently';

  document.getElementById('mdlAdminName').textContent = hosp.admin_name || 'N/A';
  document.getElementById('mdlAdminDesig').textContent = hosp.admin_designation || 'Head of Medical Talent Acquisition';
  document.getElementById('mdlAdminEmail').textContent = hosp.admin_email || hosp.contact_email || 'N/A';
  document.getElementById('mdlAdminPhone').textContent = hosp.admin_phone || hosp.phone || 'N/A';

  document.getElementById('mdlAddress').textContent = hosp.address || hosp.location || 'N/A';
  document.getElementById('mdlCityPincode').textContent = `${hosp.city || ''} ${hosp.pincode ? '· PIN ' + hosp.pincode : ''}`.trim() || hosp.location || 'N/A';
  document.getElementById('mdlCountry').textContent = hosp.country || 'India';
  document.getElementById('mdlGeneralEmail').textContent = hosp.general_email || hosp.contact_email || 'N/A';
  document.getElementById('mdlGeneralPhone').textContent = hosp.general_phone || hosp.phone || 'N/A';

  // Services
  const srvContainer = document.getElementById('mdlServicesList');
  srvContainer.innerHTML = '';
  if (hosp.services) {
    const list = hosp.services.split(',');
    list.forEach(s => {
      if (s.trim()) {
        const span = document.createElement('span');
        span.className = 'apple-badge-pill apple-service-tag';
        span.textContent = s.trim();
        srvContainer.appendChild(span);
      }
    });
  } else {
    srvContainer.innerHTML = '<span style="font-size: 0.84rem; color: #64748B;">Standard Healthcare Staffing & Placement Portal Access</span>';
  }

  const otherBox = document.getElementById('mdlOtherServicesRow');
  const otherText = document.getElementById('mdlOtherServicesText');
  if (hosp.other_services_details && hosp.other_services_details.trim()) {
    otherBox.style.display = 'block';
    otherText.textContent = hosp.other_services_details;
  } else {
    otherBox.style.display = 'none';
  }

  // Credentials State
  const credBox = document.getElementById('mdlCredentialsBox');
  const credText = document.getElementById('mdlCredentialsText');
  const actionDiv = document.getElementById('mdlActionButtons');
  actionDiv.innerHTML = '';

  if (isPending) {
    credBox.style.background = '#FFFBEB';
    credBox.style.borderColor = '#FDE68A';
    credText.innerHTML = `<strong>Account Status: Inactive (Awaiting Verification).</strong><br>Clicking <strong>'Verify & Activate'</strong> below will immediately generate an alphanumeric default temporary password, update database verification flags, and dispatch an activation email to <strong>${hosp.admin_email || hosp.contact_email}</strong>.`;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'employer-verifications.php';
    form.onsubmit = function() {
      return confirm(`Activate ${hosp.name}? A random default password will be generated and emailed to ${hosp.admin_email || hosp.contact_email}.`);
    };
    form.innerHTML = `
      <input type="hidden" name="action" value="activate_employer">
      <input type="hidden" name="id" value="${hosp.id}">
      <button type="submit" class="apple-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Verify & Activate Account Now</span>
      </button>
    `;
    actionDiv.appendChild(form);

    const declineBtn = document.createElement('button');
    declineBtn.type = 'button';
    declineBtn.className = 'apple-btn-secondary';
    declineBtn.style.color = '#E11D48';
    declineBtn.style.borderColor = '#FECDD3';
    declineBtn.innerHTML = `
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      <span>Decline & Email</span>
    `;
    declineBtn.onclick = function() {
      closeDetailsModal();
      openDeclineModal(hosp);
    };
    actionDiv.appendChild(declineBtn);
  } else {
    credBox.style.background = '#ECFDF5';
    credBox.style.borderColor = '#A7F3D0';
    credText.innerHTML = `<strong>Account Status: Active & Verified Partner.</strong><br>Activated on: <strong>${hosp.activated_at || 'Approved'}</strong><br>` +
      (hosp.initial_random_password ? `Default Generated Password: <code style="font-size: 0.95rem; font-weight: 700; background: #FFF; padding: 2px 8px; border-radius: 6px; border: 1px solid #CBD5E1; color: #0F172A;">${hosp.initial_random_password}</code><br>` : '') +
      `First login password update: <strong>${hosp.must_change_password == 1 ? 'Pending Employer Action' : 'Completed by Employer'}</strong>`;

    actionDiv.innerHTML = `<span style="font-size: 0.84rem; color: #059669; font-weight: 700;">✓ Account Active</span>`;
  }

  const modal = document.getElementById('fullDetailsModal');
  modal.classList.add('active');
}

function closeDetailsModal() {
  const modal = document.getElementById('fullDetailsModal');
  if (modal) modal.classList.remove('active');
}

function openDetailsModalById(id) {
  const hosp = window.allPendingHospitals ? window.allPendingHospitals[id] : null;
  if (hosp) openDetailsModal(hosp);
}

function openDeclineModalById(id) {
  const hosp = window.allPendingHospitals ? window.allPendingHospitals[id] : null;
  if (hosp) openDeclineModal(hosp);
}

// Apple Decline Registration Modal & Draft Email Generator
let currentDeclineHosp = null;

function openDeclineModal(hosp) {
  if (!hosp) return;
  currentDeclineHosp = hosp;

  document.getElementById('declineHospId').value = hosp.id;
  document.getElementById('declineOrgNameDisplay').textContent = hosp.name || 'Healthcare Organization';
  
  const recipientEmail = (hosp.admin_email || hosp.contact_email || '').trim();
  document.getElementById('declineRecipientEmailDisplay').textContent = recipientEmail ? `Recipient: ${recipientEmail}` : 'No Email Found on Record';

  document.getElementById('declineReasonSelect').selectedIndex = 0;
  document.getElementById('declineCustomNote').value = '';

  generateDraftEmail(true);

  const modal = document.getElementById('declineEmployerModal');
  if (modal) modal.classList.add('active');
}

function closeDeclineModal() {
  const modal = document.getElementById('declineEmployerModal');
  if (modal) modal.classList.remove('active');
  currentDeclineHosp = null;
}

function generateDraftEmail(forceReset = false) {
  if (!currentDeclineHosp) return;

  const currentSenderEmail = (document.getElementById('declineCustomFromEmail') ? document.getElementById('declineCustomFromEmail').value : '<?php echo addslashes($companySenderEmail); ?>') || '<?php echo addslashes($companySenderEmail); ?>';
  const currentCompanyName = '<?php echo addslashes($companyDisplayName); ?>';

  const hosp = currentDeclineHosp;
  const orgName = hosp.name || 'Healthcare Organization';
  const adminName = hosp.admin_name || 'Administrator';
  const reason = document.getElementById('declineReasonSelect').value;
  const customNote = (document.getElementById('declineCustomNote').value || '').trim();

  let subject = `Important Update Regarding Your Registration on ${currentCompanyName} — ${orgName}`;
  let body = '';

  const customBlock = customNote ? `\n\nSpecific Findings / Clarification Required:\n• ${customNote}\n` : '';

  if (reason.includes('NABH') || reason.includes('License')) {
    subject = `Action Required: Clinical Accreditation Verification for ${orgName} — ${currentCompanyName}`;
    body = `Dear ${adminName},

Thank you for your interest in partnering with ${currentCompanyName} on behalf of ${orgName}.

During our standard institutional compliance review, our credentialing committee was unable to verify the active healthcare operating license, clinical establishment registration, or NABH/JCI accreditation for ${orgName}.

To complete your organization's onboarding and activate your institutional recruiting account, please reply directly to this email or send the following documentation to ${currentSenderEmail}:

1. Copy of current State Clinical Establishment License or Hospital Operating Certificate
2. NABH / NABL / JCI Accreditation Certificate (if applicable)
3. Formal Letter of Authorization on official hospital letterhead${customBlock}
Your account registration has been placed on hold pending receipt of these credentials. We appreciate your partnership in upholding healthcare hiring and regulatory compliance.

Sincerely,
${currentCompanyName} Institutional Credentialing Committee
${currentSenderEmail}`;
  } else if (reason.includes('Administrator Verification')) {
    subject = `Verification Notice: Authorizing Administrator Identity for ${orgName}`;
    body = `Dear ${adminName},

Thank you for registering ${orgName} on ${currentCompanyName}.

As part of our healthcare security protocol, we verify the institutional authorization of every administrator before provisioning portal access. We were unable to confirm official authorization for this registration to act on behalf of ${orgName}.

To activate your account, please have the Hospital Director, Chief Human Resources Officer, or Medical Superintendent send an email confirmation of your appointment to ${currentSenderEmail}.${customBlock}
Once confirmed, portal credentials will be dispatched promptly.

Sincerely,
${currentCompanyName} Institutional Credentialing Committee
Dispatch Contact: ${currentSenderEmail}`;
  } else if (reason.includes('Domain Email')) {
    subject = `Domain Email Notice: Healthcare Organization Registration for ${orgName}`;
    body = `Dear ${adminName},

Thank you for registering ${orgName} on ${currentCompanyName}.

To safeguard candidate privacy and institutional data security, ${currentCompanyName} requires all authorizing administrators to register using an official domain-based email address (e.g. name@hospitaldomain.com or hr@hospitaldomain.com) rather than public webmail providers (such as Gmail, Yahoo, or Outlook).${customBlock}
Please re-register your organization using your institutional email address or reply to this message from your official healthcare email address (${currentSenderEmail}) so that our team can provision your portal credentials.

Sincerely,
${currentCompanyName} Institutional Credentialing Committee`;
  } else if (reason.includes('Duplicate')) {
    subject = `Duplicate Registration Notice: ${orgName} on ${currentCompanyName}`;
    body = `Dear ${adminName},

We have received a new registration for ${orgName} on ${currentCompanyName}.

Our database indicates that an active institutional account for ${orgName} is already provisioned in our Hospital Networks directory. To avoid duplicate profiles and preserve existing applicant pipelines, secondary duplicate accounts are not activated.${customBlock}
If your campus, branch, or clinical department requires separate recruitment sub-accounts, or if you need access restored to your hospital's existing primary account, please contact our support desk:
Support Desk: ${currentSenderEmail}

Sincerely,
${currentCompanyName} Onboarding Team`;
  } else {
    subject = `Update Regarding Healthcare Employer Registration for ${orgName} — ${currentCompanyName}`;
    body = `Dear ${adminName},

Thank you for registering ${orgName} on ${currentCompanyName}.

After reviewing your submission, our credentialing committee is unable to approve this employer account at this time for the following reason:
• ${reason}${customBlock}
If you believe this determination was made in error or if your institutional status has changed, please contact our credentialing desk with supporting documentation at ${currentSenderEmail}.

Sincerely,
${currentCompanyName} Credentialing & Operations`;
  }

  document.getElementById('declineEmailSubject').value = subject;
  document.getElementById('declineEmailBody').value = body;
}

// Helper preset for Gmail SMTP
function presetGmailSmtp() {
  document.getElementById('inpSmtpHost').value = 'smtp.gmail.com';
  document.getElementById('inpSmtpPort').value = '587';
  document.getElementById('inpSmtpSecure').value = 'tls';
  const senderEmail = document.getElementById('inpCompanySenderEmail').value.trim();
  if (senderEmail && senderEmail.includes('@gmail.com')) {
    document.getElementById('inpSmtpUser').value = senderEmail;
  }
}

// Live Test Email Dispatcher
async function sendLiveTestEmail() {
  const emailInp = document.getElementById('testEmailRecipient');
  const btn = document.getElementById('btnTestEmail');
  const feedback = document.getElementById('testEmailFeedback');
  const recipient = (emailInp ? emailInp.value : '').trim();

  if (!recipient) {
    alert('Please enter an email address to send the test message to.');
    return;
  }

  const oldText = btn.innerHTML;
  btn.innerHTML = 'Sending...';
  btn.disabled = true;
  feedback.style.display = 'none';

  const formData = new FormData();
  formData.append('action', 'test_send_email');
  formData.append('test_recipient', recipient);

  try {
    const res = await fetch('employer-verifications.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    feedback.style.display = 'block';
    if (data.success) {
      feedback.style.color = '#059669';
      feedback.innerHTML = '<strong>✓ ' + (data.message || 'Test email dispatched successfully! Check your inbox.') + '</strong>';
    } else {
      feedback.style.color = '#DC2626';
      feedback.innerHTML = '<strong>⚠️ ' + (data.message || 'Delivery error. Please verify your SMTP settings.') + '</strong>';
    }
  } catch (err) {
    feedback.style.display = 'block';
    feedback.style.color = '#DC2626';
    feedback.innerHTML = '<strong>⚠️ Network error: ' + err.message + '</strong>';
  } finally {
    btn.innerHTML = oldText;
    btn.disabled = false;
  }
}

// Apple Company Sender Email Modal
function openCompanyEmailModal() {
  const modal = document.getElementById('companyEmailModal');
  if (modal) modal.classList.add('active');
}

function closeCompanyEmailModal() {
  const modal = document.getElementById('companyEmailModal');
  if (modal) modal.classList.remove('active');
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeDetailsModal();
    closeDeclineModal();
    closeCompanyEmailModal();
    const actModal = document.getElementById('activationResultModal');
    if (actModal) actModal.remove();
    const rejModal = document.getElementById('rejectionResultModal');
    if (rejModal) rejModal.remove();
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

// Apple Spotlight Live Filter
const searchInput = document.getElementById('appleSearchInput');
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
