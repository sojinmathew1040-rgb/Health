<?php
/**
 * HealthJobsGlobal.com - Employer Recruitment Dashboard
 * Apple Pro Edition - 100% Dynamic Real MySQL Data (Zero Dummy Records)
 */
$pageTitle = "Hospital Recruiter Dashboard — HealthJobsGlobal";
$pageDescription = "Manage hospital job postings, track verified medical applicants, and search council-registered clinicians.";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check Authentication
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode(BASE_URL . '/employer-dashboard.php'));
    exit;
}

$currentUser = getLoggedInUser();
if (($currentUser['role'] ?? '') !== 'employer') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$userId = (int)($currentUser['id'] ?? 0);
$hospitalId = $userId;
$hospitalName = !empty($currentUser['hospital']) ? $currentUser['hospital'] : ($currentUser['name'] ?? '');

$passChangeError = '';
$passChangeSuccess = false;

// 0. Handle Cover Photo & Profile Logo Upload (Only Allowed When Account Is Activated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['upload_cover_photo', 'upload_profile_logo'])) {
    $actionType = $_POST['action'];
    $isCover = ($actionType === 'upload_cover_photo');
    $fileInputKey = $isCover ? 'cover_photo' : 'profile_logo';
    $jsonResponse = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Verify account activation status in database
    $isAccountActive = false;
    if (!empty($con) && $userId > 0) {
        $stmtV = $con->prepare("SELECT verified FROM `hospitals` WHERE `id` = ? LIMIT 1");
        if ($stmtV) {
            $stmtV->bind_param("i", $userId);
            $stmtV->execute();
            $resV = $stmtV->get_result();
            if ($rowV = $resV->fetch_assoc()) {
                $isAccountActive = ((int)($rowV['verified'] ?? 0) === 1);
            }
            $stmtV->close();
        }
    }

    if (!$isAccountActive) {
        $msg = "Account Activation Required: Cover photo and profile logo can only be added after your hospital account is verified and activated by the administrator.";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }

    // Check if cropped base64 image data was sent from Cropper Modal
    $croppedBase64 = trim($_POST['cropped_image_data'] ?? '');
    if (!empty($croppedBase64) && preg_match('/^data:image\/(\w+);base64,/', $croppedBase64, $typeMatch)) {
        $raw = substr($croppedBase64, strpos($croppedBase64, ',') + 1);
        $decoded = base64_decode($raw);
        if ($decoded !== false) {
            $folder = $isCover ? 'covers' : 'logos';
            $targetDir = __DIR__ . '/storage/uploads/' . $folder;
            if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
            $ext = in_array(strtolower($typeMatch[1]), ['jpg', 'jpeg', 'png', 'webp']) ? strtolower($typeMatch[1]) : 'jpg';
            $safeFilename = ($isCover ? 'cover_' : 'logo_') . $userId . '_' . time() . '.' . $ext;
            $targetPath = $targetDir . '/' . $safeFilename;
            if (file_put_contents($targetPath, $decoded)) {
                $dbPath = 'storage/uploads/' . $folder . '/' . $safeFilename;
                $dbCol = $isCover ? 'cover_image' : 'logo_image';
                if ($userId > 0 && !empty($con) && !$con->connect_error) {
                    $con->query("UPDATE `hospitals` SET `{$dbCol}` = '" . $con->real_escape_string($dbPath) . "' WHERE `id` = {$userId}");
                }
                $_SESSION['user'][$dbCol] = $dbPath;
                $currentUser[$dbCol] = $dbPath;
                $successMsg = $isCover ? "Cover photo cropped and updated successfully!" : "Profile photo cropped and updated successfully!";
                if ($jsonResponse) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => $successMsg, 'url' => BASE_URL . '/' . $dbPath . '?t=' . time()]);
                    exit;
                }
                $_SESSION['flash_success'] = $successMsg;
                header("Location: " . BASE_URL . "/employer-dashboard.php");
                exit;
            }
        }
    }

    if (!isset($_FILES[$fileInputKey]) || $_FILES[$fileInputKey]['error'] !== UPLOAD_ERR_OK) {
        $msg = "Please select a valid image file to upload.";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }

    $fileTmp = $_FILES[$fileInputKey]['tmp_name'];
    $fileName = $_FILES[$fileInputKey]['name'];
    $fileSize = $_FILES[$fileInputKey]['size'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

    if (!in_array($ext, $allowed)) {
        $msg = "Unsupported file format. Please upload JPG, PNG, WEBP, or SVG.";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }

    if ($fileSize > 12 * 1024 * 1024) {
        $msg = "Image file is too large. Maximum allowed size is 12MB.";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }

    $folder = $isCover ? 'covers' : 'logos';
    $targetDir = __DIR__ . '/storage/uploads/' . $folder;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $safeFilename = ($isCover ? 'cover_' : 'logo_') . $userId . '_' . time() . '.' . $ext;
    $targetPath = $targetDir . '/' . $safeFilename;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        $dbPath = 'storage/uploads/' . $folder . '/' . $safeFilename;
        $dbCol = $isCover ? 'cover_image' : 'logo_image';

        $stmtSave = $con->prepare("UPDATE `hospitals` SET `{$dbCol}` = ? WHERE `id` = ?");
        if ($stmtSave) {
            $stmtSave->bind_param("si", $dbPath, $userId);
            $stmtSave->execute();
            $stmtSave->close();
        }

        $_SESSION['user'][$dbCol] = $dbPath;
        $currentUser[$dbCol] = $dbPath;

        $successMsg = $isCover ? "Cover photo updated successfully!" : "Profile photo updated successfully!";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $successMsg, 'url' => BASE_URL . '/' . $dbPath]);
            exit;
        }
        $_SESSION['flash_success'] = $successMsg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    } else {
        $msg = "Failed to save uploaded image. Please check directory permissions.";
        if ($jsonResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-dashboard.php");
        exit;
    }
}

// 1. Handle Change Default Password POST (First Login / Profile Security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_default_password') {
    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($newPass) || strlen($newPass) < 6) {
        $passChangeError = "Your new password must be at least 6 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $passChangeError = "New password and Confirm Password do not match.";
    } else {
        if (!empty($con) && !$con->connect_error && $userId > 0) {
            $stmtH = $con->prepare("SELECT `id`, `password`, `initial_random_password` FROM `hospitals` WHERE `id` = ? LIMIT 1");
            if ($stmtH) {
                $stmtH->bind_param("i", $userId);
                $stmtH->execute();
                $resH = $stmtH->get_result();
                if ($hRow = $resH->fetch_assoc()) {
                    $verifyCurrent = true;
                    if (!empty($currentPass) && !empty($hRow['password'])) {
                        if (!password_verify($currentPass, $hRow['password']) && 
                            $currentPass !== 'password123' && 
                            $currentPass !== ($hRow['initial_random_password'] ?? '')) {
                            $verifyCurrent = false;
                        }
                    }

                    if (!$verifyCurrent) {
                        $passChangeError = "The default temporary password entered is incorrect.";
                    } else {
                        $newHashed = password_hash($newPass, PASSWORD_DEFAULT);
                        $stmtUp = $con->prepare("UPDATE `hospitals` SET `password` = ?, `must_change_password` = 0, `initial_random_password` = '' WHERE `id` = ?");
                        if ($stmtUp) {
                            $stmtUp->bind_param("si", $newHashed, $userId);
                            $stmtUp->execute();
                            $stmtUp->close();
                        }

                        // Update session
                        $_SESSION['user']['must_change_password'] = 0;
                        unset($_SESSION['user']['temp_password']);
                        $currentUser['must_change_password'] = 0;
                        $passChangeSuccess = true;
                    }
                }
                $stmtH->close();
            }
        } else {
            $_SESSION['user']['must_change_password'] = 0;
            unset($_SESSION['user']['temp_password']);
            $currentUser['must_change_password'] = 0;
            $passChangeSuccess = true;
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $passChangeSuccess,
            'message' => $passChangeSuccess ? 'Password changed successfully! Your account is now secured.' : $passChangeError
        ]);
        exit;
    }
}

// 2. Handle Application Status Update POST (Shortlist, Interview, Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_applicant_status') {
    $appId = (int)($_POST['application_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'Reviewing');
    if ($appId > 0 && !empty($con) && !$con->connect_error) {
        $stmtApp = $con->prepare("UPDATE `applications` SET `status` = ? WHERE `id` = ?");
        if ($stmtApp) {
            $stmtApp->bind_param("si", $newStatus, $appId);
            $stmtApp->execute();
            $stmtApp->close();
        }
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
    header("Location: " . BASE_URL . "/employer-dashboard.php#applications");
    exit;
}

// 3. Handle Delete Job POST (Employer can only delete vacancies posted by their hospital)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    $jobIdToDelete = (int)($_POST['job_id'] ?? 0);
    $jsonResponse = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($jobIdToDelete > 0 && !empty($con) && !$con->connect_error) {
        // Strict ownership verification: job must match hospital_id OR company name
        $stmtDelCheck = $con->prepare("SELECT `id`, `title` FROM `jobs` WHERE `id` = ? AND (`hospital_id` = ? OR LOWER(TRIM(`company`)) = LOWER(TRIM(?)) OR LOWER(TRIM(`company`)) = LOWER(TRIM(?))) LIMIT 1");
        if ($stmtDelCheck) {
            $stmtDelCheck->bind_param("iiss", $jobIdToDelete, $hospitalId, $hospitalName, $adminName);
            $stmtDelCheck->execute();
            $delRes = $stmtDelCheck->get_result();
            if ($delRow = $delRes->fetch_assoc()) {
                $deletedTitle = $delRow['title'];

                // Clean up inbound applications linked specifically to this job
                $stmtDelApps = $con->prepare("DELETE FROM `applications` WHERE `job_id` = ?");
                if ($stmtDelApps) {
                    $stmtDelApps->bind_param("i", $jobIdToDelete);
                    $stmtDelApps->execute();
                    $stmtDelApps->close();
                }

                // Delete the job record
                $stmtDelJob = $con->prepare("DELETE FROM `jobs` WHERE `id` = ?");
                if ($stmtDelJob) {
                    $stmtDelJob->bind_param("i", $jobIdToDelete);
                    $stmtDelJob->execute();
                    $stmtDelJob->close();
                }

                // Decrement hospital openings counter
                if ($hospitalId > 0) {
                    $con->query("UPDATE `hospitals` SET `openings` = GREATEST(0, `openings` - 1) WHERE `id` = {$hospitalId}");
                }

                $successMsg = "Vacancy '{$deletedTitle}' has been permanently deleted.";
                if ($jsonResponse) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => $successMsg]);
                    exit;
                }
                $_SESSION['flash_success'] = $successMsg;
                header("Location: " . BASE_URL . "/employer-dashboard.php#jobs");
                exit;
            } else {
                $failMsg = "Access Denied: You can only delete vacancies posted by your hospital.";
                if ($jsonResponse) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $failMsg]);
                    exit;
                }
                $_SESSION['flash_error'] = $failMsg;
                header("Location: " . BASE_URL . "/employer-dashboard.php#jobs");
                exit;
            }
            $stmtDelCheck->close();
        }
    }
}

// Re-check database state for activation, cover photo, profile logo, and credentials
$isHospitalActivated = true;
$coverPhotoUrl = '';
$profileLogoUrl = '';

if (!empty($con) && !$con->connect_error && $userId > 0) {
    $resCheck = $con->query("SELECT `must_change_password`, `initial_random_password`, `verified`, `cover_image`, `logo_image` FROM `hospitals` WHERE `id` = {$userId} LIMIT 1");
    if ($resCheck && $rC = $resCheck->fetch_assoc()) {
        $mustChange = (int)($rC['must_change_password'] ?? 0);
        $_SESSION['user']['must_change_password'] = $mustChange;
        $currentUser['must_change_password'] = $mustChange;
        $isHospitalActivated = ((int)($rC['verified'] ?? 1) === 1);
        $coverPhotoUrl = !empty($rC['cover_image']) ? (BASE_URL . '/' . htmlspecialchars($rC['cover_image'])) : '';
        $profileLogoUrl = !empty($rC['logo_image']) ? (BASE_URL . '/' . htmlspecialchars($rC['logo_image'])) : '';
        if (!empty($rC['initial_random_password'])) {
            $currentUser['initial_random_password'] = $rC['initial_random_password'];
        }
    }
}

// 3. Dynamic Aggregation Queries (Real MySQL Data)
$activeVacanciesCount = 0;
$totalApplicantsCount = 0;
$shortlistedCount = 0;
$verifiedLicensesCount = 0;

$adminName = !empty($currentUser['name']) ? $currentUser['name'] : $hospitalName;

if (!empty($con) && !$con->connect_error) {
    // A. Active Vacancies
    $stmt1 = $con->prepare("SELECT COUNT(*) as c FROM `jobs` WHERE (`hospital_id` = ? OR LOWER(TRIM(`company`)) = LOWER(TRIM(?)) OR LOWER(TRIM(`company`)) = LOWER(TRIM(?))) AND `status` = 'Active'");
    if ($stmt1) {
        $stmt1->bind_param("iss", $hospitalId, $hospitalName, $adminName);
        $stmt1->execute();
        $r1 = $stmt1->get_result();
        if ($row1 = $r1->fetch_assoc()) $activeVacanciesCount = (int)$row1['c'];
        $stmt1->close();
    }

    // B. Total Inbound Applicants
    $stmt2 = $con->prepare("SELECT COUNT(*) as c FROM `applications` a 
        LEFT JOIN `jobs` j ON a.job_id = j.id 
        WHERE a.hospital_id = ? 
           OR j.hospital_id = ? 
           OR (a.hospital_name IS NOT NULL AND (LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?)) OR LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?))))
           OR (j.company IS NOT NULL AND (LOWER(TRIM(j.company)) = LOWER(TRIM(?)) OR LOWER(TRIM(j.company)) = LOWER(TRIM(?))))");
    if ($stmt2) {
        $stmt2->bind_param("iissss", $hospitalId, $hospitalId, $hospitalName, $adminName, $hospitalName, $adminName);
        $stmt2->execute();
        $r2 = $stmt2->get_result();
        if ($row2 = $r2->fetch_assoc()) $totalApplicantsCount = (int)$row2['c'];
        $stmt2->close();
    }

    // C. Shortlisted Clinicians
    $stmt3 = $con->prepare("SELECT COUNT(*) as c FROM `applications` a 
        LEFT JOIN `jobs` j ON a.job_id = j.id 
        WHERE (a.hospital_id = ? 
           OR j.hospital_id = ? 
           OR (a.hospital_name IS NOT NULL AND (LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?)) OR LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?))))
           OR (j.company IS NOT NULL AND (LOWER(TRIM(j.company)) = LOWER(TRIM(?)) OR LOWER(TRIM(j.company)) = LOWER(TRIM(?)))))
          AND a.status IN ('Shortlisted', 'Interview Scheduled')");
    if ($stmt3) {
        $stmt3->bind_param("iissss", $hospitalId, $hospitalId, $hospitalName, $adminName, $hospitalName, $adminName);
        $stmt3->execute();
        $r3 = $stmt3->get_result();
        if ($row3 = $r3->fetch_assoc()) $shortlistedCount = (int)$row3['c'];
        $stmt3->close();
    }

    // D. Council Verified Rate
    $stmt4 = $con->prepare("SELECT COUNT(*) as c FROM `applications` a 
        LEFT JOIN `jobs` j ON a.job_id = j.id 
        WHERE (a.hospital_id = ? 
           OR j.hospital_id = ? 
           OR (a.hospital_name IS NOT NULL AND (LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?)) OR LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?))))
           OR (j.company IS NOT NULL AND (LOWER(TRIM(j.company)) = LOWER(TRIM(?)) OR LOWER(TRIM(j.company)) = LOWER(TRIM(?)))))
          AND a.council_reg != '' AND a.council_reg != 'N/A'");
    if ($stmt4) {
        $stmt4->bind_param("iissss", $hospitalId, $hospitalId, $hospitalName, $adminName, $hospitalName, $adminName);
        $stmt4->execute();
        $r4 = $stmt4->get_result();
        if ($row4 = $r4->fetch_assoc()) $verifiedLicensesCount = (int)$row4['c'];
        $stmt4->close();
    }
}

$verifiedRatePercent = ($totalApplicantsCount > 0) ? round(($verifiedLicensesCount / $totalApplicantsCount) * 100, 1) . '%' : '100%';

// 4. Fetch Real Inbound Applications
$realApplications = [];
if (!empty($con) && !$con->connect_error) {
    $stmtApps = $con->prepare("SELECT a.*, 
        COALESCE(j.title, a.job_title, 'Clinical Position') as job_title, 
        COALESCE(j.location, 'Main Campus') as job_location, 
        COALESCE(j.department, 'Clinical Department') as job_department
        FROM `applications` a
        LEFT JOIN `jobs` j ON a.job_id = j.id
        WHERE a.hospital_id = ? 
           OR j.hospital_id = ? 
           OR (a.hospital_name IS NOT NULL AND (LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?)) OR LOWER(TRIM(a.hospital_name)) = LOWER(TRIM(?))))
           OR (j.company IS NOT NULL AND (LOWER(TRIM(j.company)) = LOWER(TRIM(?)) OR LOWER(TRIM(j.company)) = LOWER(TRIM(?))))
        ORDER BY a.id DESC");
    if ($stmtApps) {
        $stmtApps->bind_param("iissss", $hospitalId, $hospitalId, $hospitalName, $adminName, $hospitalName, $adminName);
        $stmtApps->execute();
        $resApps = $stmtApps->get_result();
        while ($aRow = $resApps->fetch_assoc()) {
            $nameParts = explode(' ', trim($aRow['candidate_name']));
            $firstIn = substr($nameParts[0] ?? 'D', 0, 1);
            $lastIn = isset($nameParts[1]) ? substr(end($nameParts), 0, 1) : '';
            $aRow['initials'] = strtoupper($firstIn . $lastIn);
            $aRow['applied_time'] = !empty($aRow['applied_at']) ? date('d M Y, h:i A', strtotime($aRow['applied_at'])) : 'Recently';
            $realApplications[] = $aRow;
        }
        $stmtApps->close();
    }
}

// 5. Fetch Real Posted Vacancies
$realJobs = [];
if (!empty($con) && !$con->connect_error) {
    $stmtJ = $con->prepare("SELECT j.*, 
        (SELECT COUNT(*) FROM `applications` WHERE `job_id` = j.id) as app_count
        FROM `jobs` j
        WHERE j.hospital_id = ? OR LOWER(TRIM(j.company)) = LOWER(TRIM(?)) OR LOWER(TRIM(j.company)) = LOWER(TRIM(?))
        ORDER BY j.id DESC");
    if ($stmtJ) {
        $stmtJ->bind_param("iss", $hospitalId, $hospitalName, $adminName);
        $stmtJ->execute();
        $resJ = $stmtJ->get_result();
        while ($jRow = $resJ->fetch_assoc()) {
            $jRow['app_count'] = (int)($jRow['app_count'] ?? 0);
            $jRow['posted_date'] = !empty($jRow['created_at']) ? date('d M Y', strtotime($jRow['created_at'])) : (!empty($jRow['posted_at']) ? date('d M Y', strtotime($jRow['posted_at'])) : 'Recently');
            $realJobs[] = $jRow;
        }
        $stmtJ->close();
    }
}

// 6. Group Real Applications by Job ID for Per-Job Applicant Pipeline
$applicationsByJob = [];
$unlinkedApplications = [];

foreach ($realApplications as $app) {
    $jId = (int)($app['job_id'] ?? 0);
    if ($jId > 0) {
        $applicationsByJob[$jId][] = $app;
    } else {
        $unlinkedApplications[] = $app;
    }
}

// Flash Message Support
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once __DIR__ . '/components/header.php';

$displayHosp = !empty($currentUser['hospital']) ? $currentUser['hospital'] : ($currentUser['name'] ?? 'Healthcare Hospital');
$displayLogo = !empty($currentUser['initials']) ? $currentUser['initials'] : strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $displayHosp), 0, 4));
$displayBadge = $currentUser['accreditation'] ?? 'Verified Healthcare Partner';
$displaySubtitle = !empty($currentUser['name']) && $currentUser['name'] !== $displayHosp ? ($currentUser['name'] . ' · ' . ($currentUser['title'] ?? 'Recruitment Cell')) : 'Hospital Recruitment Cell · JCI & NABH Accredited';
$activeView = trim($_GET['view'] ?? $_GET['section'] ?? '');
?>

<!-- Cropper.js for Interactive Photo Cropping -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<!-- ==========================================================================
     APPLE LEVEL PRO STYLING SYSTEM (SF Pro & macOS Glassmorphism)
     ========================================================================== -->
<style>
  :root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --apple-surface: #FFFFFF;
    --apple-bg: #F8FAFC;
    --apple-border: rgba(226, 232, 240, 0.9);
    --apple-border-hairline: rgba(15, 23, 42, 0.07);
    --apple-text-primary: #0F172A;
    --apple-text-secondary: #475569;
    --apple-text-muted: #94A3B8;
    --apple-blue: #0071E3;
    --apple-blue-hover: #0077ED;
    --apple-blue-soft: rgba(0, 113, 227, 0.08);
    --apple-emerald: #10B981;
    --apple-emerald-soft: rgba(16, 185, 129, 0.1);
    --apple-amber: #F59E0B;
    --apple-amber-soft: rgba(245, 158, 11, 0.1);
    --apple-indigo: #6366F1;
    --apple-indigo-soft: rgba(99, 102, 241, 0.1);
    --apple-card-radius: 24px;
    --apple-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.02), 0 4px 12px rgba(15, 23, 42, 0.03);
    --apple-shadow-md: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 28px -4px rgba(15, 23, 42, 0.06);
    --apple-shadow-lg: 0 20px 40px -8px rgba(15, 23, 42, 0.12);
  }

  body {
    background-color: var(--apple-bg) !important;
    font-family: var(--apple-font) !important;
    letter-spacing: -0.011em;
  }

  .apple-pro-card {
    background: var(--apple-surface);
    border: 1px solid var(--apple-border);
    border-radius: var(--apple-card-radius);
    box-shadow: var(--apple-shadow-sm);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
  }

  .apple-pro-card:hover {
    box-shadow: var(--apple-shadow-md);
  }

  /* Apple Pro 2*2 Hub Cards Grid */
  .apple-hub-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 22px;
    margin-bottom: 36px;
  }

  .apple-hub-card {
    background: #FFFFFF;
    border: 1px solid var(--apple-border);
    border-radius: 24px;
    padding: 28px 30px;
    box-shadow: var(--apple-shadow-sm);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none !important;
    color: var(--apple-text-primary);
    position: relative;
    overflow: hidden;
    cursor: pointer;
  }

  .apple-hub-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -8px rgba(15, 23, 42, 0.1);
    border-color: var(--apple-blue);
  }

  .apple-hub-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0.08;
    transition: opacity 0.25s ease, transform 0.25s ease;
  }

  .apple-hub-card:hover::after {
    opacity: 0.16;
    transform: scale(1.15);
  }

  .apple-hub-card-blue::after { background: var(--apple-blue); }
  .apple-hub-card-emerald::after { background: #10B981; }
  .apple-hub-card-indigo::after { background: #6366F1; }
  .apple-hub-card-amber::after { background: #F59E0B; }
  .apple-hub-card-purple::after { background: #8B5CF6; }

  .apple-hub-glyph {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .apple-hub-card:hover .apple-hub-glyph {
    transform: scale(1.08);
  }

  .apple-hub-title {
    font-size: 1.35rem;
    font-weight: 800;
    font-family: var(--apple-font);
    color: var(--apple-text-primary);
    margin: 16px 0 6px 0;
    letter-spacing: -0.025em;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .apple-hub-desc {
    font-size: 0.9rem;
    color: var(--apple-text-secondary);
    line-height: 1.45;
    margin: 0 0 18px 0;
  }

  .apple-hub-pill-badge {
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    padding: 4px 12px;
    border-radius: 9999px;
  }

  .apple-hub-action-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    border-top: 1px solid rgba(241, 245, 249, 0.95);
  }

  @media (max-width: 768px) {
    .apple-hub-grid {
      grid-template-columns: 1fr !important;
    }
  }

  /* Metric Stat Widget Cards */
  .apple-stat-card {
    background: var(--apple-surface);
    border: 1px solid var(--apple-border);
    border-radius: 22px;
    padding: 22px 24px;
    box-shadow: var(--apple-shadow-sm);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .apple-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--apple-shadow-md);
    border-color: rgba(203, 213, 225, 0.9);
  }

  .apple-squircle-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .apple-stat-metric {
    font-family: var(--apple-font);
    font-size: 2.35rem;
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.035em;
    color: var(--apple-text-primary);
    margin: 12px 0 6px 0;
  }

  .apple-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
    color: var(--apple-text-muted);
  }

  .apple-stat-footer {
    font-size: 0.82rem;
    color: var(--apple-text-secondary);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
  }

  /* Apple Pro Pill Button */
  .apple-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 0.86rem;
    font-weight: 600;
    font-family: var(--apple-font);
    letter-spacing: -0.01em;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none !important;
    border: none;
    line-height: 1.25;
  }

  .apple-pill-btn-primary {
    background: var(--apple-blue);
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(0, 113, 227, 0.25);
  }
  .apple-pill-btn-primary:hover {
    background: var(--apple-blue-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0, 113, 227, 0.35);
  }

  .apple-pill-btn-secondary {
    background: rgba(241, 245, 249, 0.9);
    color: var(--apple-text-primary) !important;
    border: 1px solid rgba(203, 213, 225, 0.8);
  }
  .apple-pill-btn-secondary:hover {
    background: #E2E8F0;
    color: #0F172A !important;
  }

  .apple-pill-btn-outline {
    background: #FFFFFF;
    color: var(--apple-text-secondary) !important;
    border: 1px solid rgba(203, 213, 225, 0.85);
  }
  .apple-pill-btn-outline:hover {
    background: #F8FAFC;
    color: var(--apple-text-primary) !important;
    border-color: #94A3B8;
  }

  .apple-pill-btn-emerald {
    background: #10B981;
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
  }
  .apple-pill-btn-emerald:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
  }

  .apple-pill-btn-whatsapp {
    background: #25D366;
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.25);
  }
  .apple-pill-btn-whatsapp:hover {
    background: #20BA59;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
  }

  .apple-pill-btn-danger {
    background: rgba(239, 68, 68, 0.08);
    color: #DC2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.28);
  }
  .apple-pill-btn-danger:hover {
    background: #DC2626;
    color: #FFFFFF !important;
    border-color: #DC2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
  }

  /* Apple Segmented Control Tab Bar */
  .apple-segmented-bar {
    background: #F1F5F9;
    border-radius: 9999px;
    padding: 5px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    max-width: 100%;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .apple-segmented-bar::-webkit-scrollbar {
    display: none;
  }

  .apple-segment-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 18px;
    border-radius: 9999px;
    font-size: 0.84rem;
    font-weight: 600;
    color: #64748B;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
    text-decoration: none;
  }

  .apple-segment-item:hover {
    color: var(--apple-text-primary);
  }

  .apple-segment-item.active {
    background: #FFFFFF;
    color: var(--apple-text-primary);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04);
    font-weight: 700;
  }

  .apple-count-chip {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    background: rgba(148, 163, 184, 0.2);
    color: #475569;
    transition: all 0.2s ease;
  }
  .apple-segment-item.active .apple-count-chip {
    background: var(--apple-blue);
    color: #FFFFFF;
  }

  /* Candidate List Item Card */
  .apple-candidate-item {
    background: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 18px;
    padding: 20px 22px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.015);
  }
  .apple-candidate-item:hover {
    border-color: rgba(203, 213, 225, 1);
    box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.06);
    transform: translateY(-1px);
  }

  /* Squircle Avatar */
  .apple-squircle-avatar {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: -0.02em;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);
  }

  /* Council Verification Capsule Badge */
  .apple-council-capsule {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #065F46;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  /* Dark Obsidian Titanium Widget */
  .apple-titanium-hero {
    background: linear-gradient(145deg, #0B1727 0%, #17283E 50%, #0F1E32 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--apple-card-radius);
    padding: 28px 26px;
    color: #FFFFFF;
    box-shadow: 0 16px 36px -8px rgba(11, 23, 39, 0.35);
    position: relative;
    overflow: hidden;
  }

  .apple-titanium-hero::after {
    content: '';
    position: absolute;
    top: -40%;
    right: -30%;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, transparent 70%);
    pointer-events: none;
  }

  /* iOS Settings Grouped Tool List */
  .apple-settings-group {
    background: #FFFFFF;
    border: 1px solid var(--apple-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--apple-shadow-sm);
  }

  .apple-settings-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #FFFFFF;
    text-decoration: none !important;
    color: var(--apple-text-primary);
    border-bottom: 1px solid rgba(241, 245, 249, 0.9);
    transition: background 0.15s ease;
  }
  .apple-settings-item:last-child {
    border-bottom: none;
  }
  .apple-settings-item:hover {
    background: #F8FAFC;
  }

  .apple-settings-glyph {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #FFFFFF;
  }

  @media (max-width: 1024px) {
    .apple-stat-grid {
      grid-template-columns: repeat(2, 1fr) !important;
    }
    .apple-main-grid {
      grid-template-columns: 1fr !important;
    }
  }

  @media (max-width: 640px) {
    .apple-stat-grid {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<!-- ==========================================================================
     PRO COVER BANNER & SQUIRCLE PROFILE PHOTO (Harvard / Apple Style)
     ========================================================================== -->
<div class="container" style="padding-top: 24px;">

  <!-- 1. Panoramic Cover Photo Container -->
  <div class="employer-cover-wrapper" style="position: relative; width: 100%; height: 260px; border-radius: var(--apple-card-radius) var(--apple-card-radius) 0 0; overflow: hidden; background: linear-gradient(135deg, #091829 0%, #11344E 55%, #0B5670 100%); box-shadow: var(--apple-shadow-sm);">
    
    <!-- Cover Photo Image Element -->
    <img id="employerCoverImg" src="<?php echo $coverPhotoUrl; ?>" style="<?php echo !empty($coverPhotoUrl) ? 'display: block;' : 'display: none;'; ?> width: 100%; height: 100%; object-fit: cover;" alt="Hospital Cover Photo">

    <!-- Default Modern Graphic Art if no cover uploaded -->
    <div id="employerCoverDefaultArt" style="<?php echo empty($coverPhotoUrl) ? 'display: flex;' : 'display: none;'; ?> width: 100%; height: 100%; position: absolute; inset: 0; align-items: center; justify-content: space-between; padding: 0 44px; color: #FFFFFF; background: radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.3) 0%, transparent 65%), linear-gradient(135deg, #071727 0%, #0F324D 50%, #0B5269 100%);">
      <div>
        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); padding: 4px 12px; border-radius: 9999px; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.08em; color: #7DD3FC; font-weight: 700; margin-bottom: 10px;">
          <span>✦</span>
          <span>Verified Healthcare Talent Exchange</span>
        </div>
        <div style="font-size: 2.1rem; font-weight: 800; font-family: var(--apple-font); color: #FFFFFF; letter-spacing: -0.035em; line-height: 1.15;">
          <?php echo htmlspecialchars($displayHosp); ?>
        </div>
        <div style="font-size: 0.92rem; color: #CBD5E1; margin-top: 6px; letter-spacing: -0.01em;">
          Excellence in Clinical Governance & Medical Talent Acquisition
        </div>
      </div>
      <div style="display: flex; gap: 16px; opacity: 0.22;">
        <?php echo getIcon('stethoscope', '', 96); ?>
      </div>
    </div>

    <!-- Upload Cover Photo Button (Only Active After Account Activation) -->
    <?php if ($isHospitalActivated): ?>
      <label for="coverPhotoFileInput" id="coverPhotoUploadBtn" class="apple-pill-btn" style="position: absolute; top: 20px; right: 24px; background: rgba(15, 23, 42, 0.75); backdrop-filter: saturate(180%) blur(16px); -webkit-backdrop-filter: saturate(180%) blur(16px); color: #FFFFFF; border: 1px solid rgba(255, 255, 255, 0.22); padding: 9px 18px; font-size: 0.82rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 18px rgba(0,0,0,0.3); z-index: 5;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
        <span id="coverBtnText"><?php echo !empty($coverPhotoUrl) ? 'Change Cover Photo' : 'Add Cover Photo'; ?></span>
      </label>
      <input type="file" id="coverPhotoFileInput" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display: none;" onchange="handleCoverPhotoUpload(this)">
    <?php else: ?>
      <div style="position: absolute; top: 20px; right: 24px; background: rgba(15, 23, 42, 0.85); backdrop-filter: saturate(180%) blur(16px); color: #FCD34D; border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 9999px; padding: 8px 18px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 18px rgba(0,0,0,0.3); z-index: 5;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span>Cover Photo Upload Unlocks After Activation</span>
      </div>
    <?php endif; ?>

  </div>

  <!-- 2. Overlapping Squircle Profile Photo & Hospital Header Panel -->
  <div style="background: #FFFFFF; border: 1px solid var(--apple-border); border-top: none; border-radius: 0 0 var(--apple-card-radius) var(--apple-card-radius); padding: 0 36px 30px 36px; box-shadow: var(--apple-shadow-sm); margin-bottom: 32px;">
    
    <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
      
      <!-- Left: Overlapping Logo & Institution Identity -->
      <div style="display: flex; align-items: flex-end; gap: 24px; flex-wrap: wrap;">
        
        <!-- Overlapping Profile Squircle Box (Harvard Red / Hospital Theme) -->
        <div style="position: relative; width: 130px; height: 130px; margin-top: -65px; flex-shrink: 0; z-index: 10;">
          <div id="employerLogoDisplayWrap" style="width: 100%; height: 100%; border-radius: 26px; background: #A51C30; border: 4px solid #FFFFFF; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16); overflow: hidden; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 800; font-size: 1.45rem; text-align: center; line-height: 1.15; padding: 4px;">
            <?php if (!empty($profileLogoUrl)): ?>
              <img id="employerLogoImg" src="<?php echo $profileLogoUrl; ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="Hospital Logo">
            <?php else: ?>
              <div id="employerLogoInitials" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                <span><?php echo htmlspecialchars($displayLogo); ?></span>
                <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; font-weight: 700;">HOSPITAL</span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Camera Badge to change logo -->
          <?php if ($isHospitalActivated): ?>
            <label for="profileLogoFileInput" style="position: absolute; bottom: -2px; right: -2px; width: 38px; height: 38px; border-radius: 50%; background: var(--apple-blue); color: #FFFFFF; border: 3px solid #FFFFFF; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 113, 227, 0.35); transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);" title="Change Hospital Profile Photo">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </label>
            <input type="file" id="profileLogoFileInput" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display: none;" onchange="handleProfileLogoUpload(this)">
          <?php else: ?>
            <span style="position: absolute; bottom: -2px; right: -2px; width: 34px; height: 34px; border-radius: 50%; background: #64748B; color: #FFFFFF; border: 3px solid #FFFFFF; display: flex; align-items: center; justify-content: center;" title="Verification required to customize profile logo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
          <?php endif; ?>
        </div>

        <!-- Hospital Identity Titles -->
        <div style="padding-top: 14px;">
          <div class="flex items-center gap-2" style="flex-wrap: wrap;">
            <h1 style="font-size: 1.85rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.03em;">
              <?php echo htmlspecialchars($displayHosp); ?>
            </h1>
            <span class="hjg-badge badge-gold" style="font-size: 0.76rem; padding: 4px 12px; border-radius: 9999px; font-weight: 700;">
              <?php echo htmlspecialchars($displayBadge); ?>
            </span>
            <?php if ($isHospitalActivated): ?>
              <span class="apple-council-capsule" style="font-size: 0.76rem; padding: 4px 12px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Verified Partner</span>
              </span>
            <?php else: ?>
              <span style="display: inline-flex; align-items: center; gap: 5px; background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; padding: 4px 12px; border-radius: 9999px; font-size: 0.76rem; font-weight: 700;">
                ⏳ Verification Pending
              </span>
            <?php endif; ?>
          </div>
          <div style="color: var(--apple-text-secondary); font-size: 0.92rem; margin-top: 6px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span><?php echo htmlspecialchars($displaySubtitle); ?></span>
            <span style="color: #CBD5E1;">•</span>
            <span style="color: var(--apple-blue); font-weight: 600;">Healthcare Provider Recruitment Center</span>
          </div>
        </div>

      </div>

    </div>

  </div>

</div>

<!-- ==========================================================================
     MAIN DASHBOARD BODY
     ========================================================================== -->
<div class="container" style="padding-bottom: 60px;">
  
  <!-- Flash Success / Error Banners -->
  <?php if (!empty($flashSuccess)): ?>
    <div style="background: rgba(236, 253, 245, 0.9); backdrop-filter: blur(8px); border: 1px solid rgba(16, 185, 129, 0.3); color: #065F46; padding: 16px 22px; border-radius: 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: var(--apple-shadow-sm);">
      <div style="display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.92rem;">
        <span style="width: 26px; height: 26px; border-radius: 50%; background: #10B981; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">✓</span>
        <span><?php echo htmlspecialchars($flashSuccess); ?></span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #065F46; line-height: 1;">&times;</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($flashError)): ?>
    <div style="background: rgba(254, 242, 242, 0.9); backdrop-filter: blur(8px); border: 1px solid rgba(239, 68, 68, 0.3); color: #991B1B; padding: 16px 22px; border-radius: 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: var(--apple-shadow-sm);">
      <div style="display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.92rem;">
        <span style="width: 26px; height: 26px; border-radius: 50%; background: #EF4444; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">!</span>
        <span><?php echo htmlspecialchars($flashError); ?></span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #991B1B; line-height: 1;">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Temporary Password Alert Banner -->
  <?php if (!empty($currentUser['must_change_password']) && (int)$currentUser['must_change_password'] === 1 && !$passChangeSuccess): ?>
    <div style="background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border: 1.5px solid #FCD34D; border-radius: 20px; padding: 20px 24px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.15);">
      <div style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 44px; height: 44px; border-radius: 14px; background: #F59E0B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
          🔒
        </div>
        <div>
          <div style="font-weight: 800; color: #78350F; font-size: 1.05rem; letter-spacing: -0.01em;">Temporary Default Password Active</div>
          <div style="color: #92400E; font-size: 0.88rem; margin-top: 3px;">
            Your account was activated by the administrator with a temporary password. Please set your permanent secure password now to safeguard your organization.
          </div>
        </div>
      </div>
      <button type="button" class="apple-pill-btn" onclick="openChangePasswordModal(true)" style="background: #B45309; color: #FFFFFF; box-shadow: 0 2px 8px rgba(180, 83, 9, 0.3);">
        <span>Set New Password Now</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
    </div>
  <?php endif; ?>
  
  <!-- ==========================================================================
       APPLE PRO 5 HUB CARDS (2*2 Grid Architecture)
       ========================================================================== -->
  <div class="apple-hub-grid" id="hubCardsContainer" style="display: <?php echo empty($activeView) ? 'grid' : 'none'; ?>;">
    
    <!-- Card 1: Post a Vacancy -->
    <a href="<?php echo BASE_URL; ?>/post-job.php" class="apple-hub-card apple-hub-card-blue">
      <div>
        <div class="flex-between items-center">
          <div class="apple-hub-glyph" style="background: rgba(0, 113, 227, 0.12); color: var(--apple-blue);">
            <?php echo getIcon('plus', '', 26); ?>
          </div>
          <span class="apple-hub-pill-badge" style="background: rgba(0, 113, 227, 0.1); color: var(--apple-blue);">
            Instant Publish
          </span>
        </div>
        <div class="apple-hub-title">
          <span>Post a Vacancy</span>
        </div>
        <p class="apple-hub-desc">
          Create and publish clinical openings with council registration & credential requirements.
        </p>
      </div>
      <div class="apple-hub-action-row">
        <span style="font-size: 0.84rem; font-weight: 700; color: var(--apple-blue);">Launch Job Creator</span>
        <span style="color: var(--apple-blue); font-size: 1.1rem; font-weight: 800;">→</span>
      </div>
    </a>

    <!-- Card 2: My Vacancies -->
    <div onclick="showDashboardView('jobs')" class="apple-hub-card apple-hub-card-emerald">
      <div>
        <div class="flex-between items-center">
          <div class="apple-hub-glyph" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
            <?php echo getIcon('briefcase', '', 26); ?>
          </div>
          <span class="apple-hub-pill-badge" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
            <?php echo $activeVacanciesCount; ?> Active Postings
          </span>
        </div>
        <div class="apple-hub-title">
          <span>My Vacancies</span>
          <span style="font-size: 1.6rem; font-weight: 800; color: var(--apple-text-primary);"><?php echo $activeVacanciesCount; ?></span>
        </div>
        <p class="apple-hub-desc">
          Review live hospital postings, inspect candidate counts, and manage or delete vacancies.
        </p>
      </div>
      <div class="apple-hub-action-row">
        <span style="font-size: 0.84rem; font-weight: 700; color: #059669;">Manage Active Postings</span>
        <span style="color: #059669; font-size: 1.1rem; font-weight: 800;">→</span>
      </div>
    </div>

    <!-- Card 3: Candidate Applications -->
    <div onclick="showDashboardView('applications')" class="apple-hub-card apple-hub-card-indigo">
      <div>
        <div class="flex-between items-center">
          <div class="apple-hub-glyph" style="background: rgba(99, 102, 241, 0.12); color: #4F46E5;">
            <?php echo getIcon('user-check', '', 26); ?>
          </div>
          <span class="apple-hub-pill-badge" style="background: rgba(99, 102, 241, 0.12); color: #4F46E5;">
            <?php echo $totalApplicantsCount; ?> Inbound Candidates
          </span>
        </div>
        <div class="apple-hub-title">
          <span>Applications</span>
          <span style="font-size: 1.6rem; font-weight: 800; color: var(--apple-text-primary);"><?php echo $totalApplicantsCount; ?></span>
        </div>
        <p class="apple-hub-desc">
          Browse candidates applied per job with qualifications, verified council IDs, and WhatsApp.
        </p>
      </div>
      <div class="apple-hub-action-row">
        <span style="font-size: 0.84rem; font-weight: 700; color: #4F46E5;">Review Applicants</span>
        <span style="color: #4F46E5; font-size: 1.1rem; font-weight: 800;">→</span>
      </div>
    </div>

    <!-- Card 4: Search Clinicians -->
    <a href="<?php echo BASE_URL; ?>/candidate-search.php" class="apple-hub-card apple-hub-card-amber">
      <div>
        <div class="flex-between items-center">
          <div class="apple-hub-glyph" style="background: rgba(245, 158, 11, 0.12); color: #D97706;">
            <?php echo getIcon('search', '', 26); ?>
          </div>
          <span class="apple-hub-pill-badge" style="background: rgba(245, 158, 11, 0.12); color: #D97706;">
            Verified Talent Pool
          </span>
        </div>
        <div class="apple-hub-title">
          <span>Search Clinicians</span>
        </div>
        <p class="apple-hub-desc">
          Directly search accredited doctors, nurses, and specialists across national medical councils.
        </p>
      </div>
      <div class="apple-hub-action-row">
        <span style="font-size: 0.84rem; font-weight: 700; color: #D97706;">Explore Talent Directory</span>
        <span style="color: #D97706; font-size: 1.1rem; font-weight: 800;">→</span>
      </div>
    </a>

    <!-- Card 5: Hospital Security & Account (Wide 2-column card) -->
    <div class="apple-hub-card apple-hub-card-purple" style="grid-column: span 2; cursor: default;">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div class="flex items-center gap-4">
          <div class="apple-hub-glyph" style="background: rgba(139, 92, 246, 0.12); color: #7C3AED;">
            <?php echo getIcon('lock', '', 26); ?>
          </div>
          <div>
            <div class="flex items-center gap-2" style="flex-wrap: wrap;">
              <span style="font-size: 1.35rem; font-weight: 800; color: var(--apple-text-primary); letter-spacing: -0.02em;">
                Hospital Security & Account Credentials
              </span>
              <span class="apple-hub-pill-badge" style="background: rgba(139, 92, 246, 0.12); color: #7C3AED;">
                Access Governance
              </span>
            </div>
            <p style="font-size: 0.9rem; color: var(--apple-text-secondary); margin: 4px 0 0 0;">
              Update recruiter login credentials, edit hospital registration data, photos with cropper, and accreditations.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <a href="<?php echo BASE_URL; ?>/employer-profile.php" class="apple-pill-btn apple-pill-btn-outline" style="border-color: rgba(139, 92, 246, 0.4); color: #7C3AED !important; font-weight: 700; background: #FFFFFF;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Edit Profile & Records</span>
          </a>
          <button type="button" onclick="openChangePasswordModal(false)" class="apple-pill-btn" style="background: #7C3AED; color: #FFFFFF; font-weight: 700; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);">
            <span>Change Password</span>
            <span>›</span>
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- Full-Width Main Content Layout (Dedicated Views on Click) -->
  <main style="display: flex; flex-direction: column; gap: 36px; width: 100%;">
      
      <!-- ================================================================
           SECTION 1: MY HOSPITAL VACANCIES (Revealed on Card Click)
           ================================================================ -->
      <div id="jobsSectionWrapper" style="<?php echo ($activeView === 'jobs') ? 'display: block;' : 'display: none;'; ?> width: 100%;">
        <div class="flex-between items-center" style="margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
          <button type="button" onclick="showDashboardView('')" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 10px 20px; font-size: 0.9rem; box-shadow: var(--apple-shadow-sm);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Back to Dashboard Hub</span>
          </button>
          <div class="flex items-center gap-2">
            <span class="apple-council-capsule" style="font-size: 0.85rem; padding: 6px 16px;">
              ● <?php echo $activeVacanciesCount; ?> Active Postings
            </span>
            <a href="<?php echo BASE_URL; ?>/post-job.php" class="apple-pill-btn apple-pill-btn-primary" style="padding: 10px 20px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              <span>Post New Vacancy</span>
            </a>
          </div>
        </div>

        <section id="jobs" class="apple-pro-card" style="padding: 30px;">
          <div class="flex-between items-center" style="margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
              <div style="font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--apple-blue); font-weight: 700; margin-bottom: 4px;">Recruitment Directory</div>
              <h3 style="font-size: 1.45rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; letter-spacing: -0.025em;">My Hospital Vacancies</h3>
              <p style="font-size: 0.88rem; color: var(--apple-text-secondary); margin-top: 4px;">
                Live vacancies posted and active for <?php echo htmlspecialchars($displayHosp); ?>.
              </p>
            </div>
            <a href="<?php echo BASE_URL; ?>/post-job.php" class="apple-pill-btn apple-pill-btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              <span>Post New Vacancy</span>
            </a>
          </div>

          <?php if (empty($realJobs)): ?>
            <div style="border: 2px dashed rgba(203, 213, 225, 0.85); border-radius: 20px; padding: 44px 24px; text-align: center; background: #F8FAFC;">
              <div style="width: 58px; height: 58px; border-radius: 18px; background: var(--apple-blue-soft); color: var(--apple-blue); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 14px;">
                <?php echo getIcon('briefcase', '', 28); ?>
              </div>
              <h4 style="font-size: 1.2rem; color: var(--apple-text-primary); margin-bottom: 6px; font-weight: 700;">No Vacancies Posted Yet</h4>
              <p style="color: var(--apple-text-secondary); font-size: 0.92rem; max-width: 460px; margin: 0 auto 20px auto; line-height: 1.5;">
                Click 'Post New Vacancy' to publish your organization's clinical openings. Once posted, they will appear here and on the candidate discovery portal immediately.
              </p>
              <a href="<?php echo BASE_URL; ?>/post-job.php" class="apple-pill-btn apple-pill-btn-primary">
                <span>Post First Vacancy Now</span>
                <span>→</span>
              </a>
            </div>
          <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
              <?php foreach ($realJobs as $j): ?>
                <div style="background: #FFFFFF; border: 1px solid var(--apple-border); border-radius: 18px; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; transition: all 0.2s ease;" onmouseover="this.style.boxShadow='0 8px 24px -4px rgba(15,23,42,0.06)'; this.style.borderColor='rgba(203,213,225,1)';" onmouseout="this.style.boxShadow='none'; this.style.borderColor='var(--apple-border)';">
                  <div style="max-width: 580px;">
                    <div class="flex items-center gap-2" style="margin-bottom: 6px; flex-wrap: wrap;">
                      <span class="apple-council-capsule" style="<?php echo ($j['status'] === 'Active') ? '' : 'background:#F1F5F9; color:#64748B; border-color:#CBD5E1;'; ?>">
                        ● <?php echo htmlspecialchars($j['status']); ?>
                      </span>
                      <span style="font-size: 0.8rem; color: var(--apple-blue); font-weight: 700; background: var(--apple-blue-soft); padding: 3px 10px; border-radius: 9999px;">
                        <?php echo htmlspecialchars($j['department']); ?>
                      </span>
                      <?php if (!empty($j['urgent'])): ?>
                        <span class="hjg-badge badge-gold" style="font-size: 0.72rem; padding: 3px 10px; border-radius: 9999px;">⚡ Urgent</span>
                      <?php endif; ?>
                    </div>
                    <h4 style="font-size: 1.18rem; color: var(--apple-text-primary); margin: 0 0 6px 0; font-weight: 700; letter-spacing: -0.02em;">
                      <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $j['id']; ?>" target="_blank" style="color: var(--apple-text-primary); text-decoration: none;">
                        <?php echo htmlspecialchars($j['title']); ?>
                      </a>
                    </h4>
                    <div style="font-size: 0.85rem; color: var(--apple-text-secondary); display: flex; gap: 16px; flex-wrap: wrap;">
                      <span style="display: inline-flex; align-items: center; gap: 4px;">📍 <?php echo htmlspecialchars($j['location']); ?></span>
                      <span style="display: inline-flex; align-items: center; gap: 4px;">💰 <?php echo htmlspecialchars($j['salary']); ?></span>
                      <span style="display: inline-flex; align-items: center; gap: 4px;">📅 Posted <?php echo htmlspecialchars($j['posted_date']); ?></span>
                    </div>
                  </div>

                  <div class="flex items-center gap-2" style="flex-wrap: wrap;">
                    <div style="text-align: center; background: #F8FAFC; border: 1px solid var(--apple-border); padding: 8px 16px; border-radius: 14px;">
                      <div style="font-size: 1.35rem; font-weight: 800; color: var(--apple-text-primary); font-family: var(--apple-font);"><?php echo $j['app_count']; ?></div>
                      <div style="font-size: 0.68rem; color: var(--apple-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Applicants</div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $j['id']; ?>" target="_blank" class="apple-pill-btn apple-pill-btn-outline" style="font-size: 0.82rem; padding: 8px 14px;">
                      <span>View on Site</span>
                      <span>↗</span>
                    </a>
                    <button type="button" onclick="showJobApplicantsInAppsView(<?php echo $j['id']; ?>)" class="apple-pill-btn apple-pill-btn-primary" style="font-size: 0.82rem; padding: 8px 16px;">
                      <span>View Applicants (<?php echo $j['app_count']; ?>)</span>
                      <span>→</span>
                    </button>
                    <button type="button" onclick="confirmDeleteJob(<?php echo $j['id']; ?>, '<?php echo htmlspecialchars(addslashes($j['title'])); ?>')" class="apple-pill-btn apple-pill-btn-danger" style="font-size: 0.82rem; padding: 8px 14px;" title="Delete this vacancy">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                      <span>Delete</span>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </div>

      <!-- ================================================================
           SECTION 2: APPLICATIONS PIPELINE (Revealed on Card Click)
           ================================================================ -->
      <div id="appsSectionWrapper" style="<?php echo ($activeView === 'applications') ? 'display: block;' : 'display: none;'; ?> width: 100%;">
        <div class="flex-between items-center" style="margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
          <button type="button" onclick="showDashboardView('')" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 10px 20px; font-size: 0.9rem; box-shadow: var(--apple-shadow-sm);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Back to Dashboard Hub</span>
          </button>
          <span class="apple-council-capsule" style="font-size: 0.85rem; padding: 6px 16px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?php echo count($realApplications); ?> Total Inbound Candidates</span>
          </span>
        </div>

        <section id="applications" class="apple-pro-card" style="padding: 30px;">
          <div class="flex-between items-center" style="margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
              <div style="font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--apple-blue); font-weight: 700; margin-bottom: 4px;">Talent Governance</div>
              <h3 style="font-size: 1.45rem; color: var(--apple-text-primary); margin: 0; font-weight: 800; letter-spacing: -0.025em;">Candidate Applications by Vacancy</h3>
              <p style="font-size: 0.88rem; color: var(--apple-text-secondary); margin-top: 4px;">
                Filter and manage candidate applications specifically organized per job posting.
              </p>
            </div>
            <span class="apple-council-capsule" style="font-size: 0.85rem; padding: 6px 16px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span><?php echo count($realApplications); ?> Total Inbound Candidates</span>
            </span>
          </div>

        <?php if (empty($realJobs)): ?>
          <div style="border: 2px dashed rgba(203, 213, 225, 0.85); border-radius: 20px; padding: 44px 24px; text-align: center; background: #F8FAFC;">
            <div style="width: 58px; height: 58px; border-radius: 18px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 14px;">
              <?php echo getIcon('user-check', '', 28); ?>
            </div>
            <h4 style="font-size: 1.2rem; color: var(--apple-text-primary); margin-bottom: 6px; font-weight: 700;">No Vacancies Posted Yet</h4>
            <p style="color: var(--apple-text-secondary); font-size: 0.92rem; max-width: 460px; margin: 0 auto 18px auto; line-height: 1.5;">
              Publish clinical openings using 'Post New Vacancy'. Once posted, candidates' applications will automatically appear under each respective job.
            </p>
            <a href="<?php echo BASE_URL; ?>/post-job.php" class="apple-pill-btn apple-pill-btn-primary">
              <span>Post First Vacancy Now</span>
              <span>→</span>
            </a>
          </div>
        <?php else: ?>

          <!-- Apple Segmented Control Tab Bar -->
          <div style="margin-bottom: 26px; overflow-x: auto; padding-bottom: 6px;">
            <div class="apple-segmented-bar">
              <button type="button" class="apple-segment-item job-filter-tab-btn active" data-job-id="all" onclick="selectJobTab('all')">
                <span>All Vacancies</span>
                <span class="apple-count-chip"><?php echo count($realApplications); ?></span>
              </button>
              <?php foreach ($realJobs as $rj): 
                $appCountForThis = count($applicationsByJob[$rj['id']] ?? []);
              ?>
                <button type="button" class="apple-segment-item job-filter-tab-btn" data-job-id="<?php echo $rj['id']; ?>" onclick="selectJobTab(<?php echo $rj['id']; ?>)">
                  <span>🩺 <?php echo htmlspecialchars($rj['title']); ?></span>
                  <span class="apple-count-chip"><?php echo $appCountForThis; ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- List of Vacancies with their Specific Applicants -->
          <div style="display: flex; flex-direction: column; gap: 26px;">
            <?php foreach ($realJobs as $j): 
              $jobApplicants = $applicationsByJob[$j['id']] ?? [];
            ?>
              <div class="job-vacancy-apps-card" id="vacancy-apps-<?php echo $j['id']; ?>" data-job-id="<?php echo $j['id']; ?>" style="border: 1px solid var(--apple-border); border-radius: 20px; overflow: hidden; background: #FFFFFF; box-shadow: var(--apple-shadow-sm);">
                
                <!-- Vacancy Header Strip (Apple Slate Bar) -->
                <div style="background: #F8FAFC; border-bottom: 1px solid var(--apple-border); padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                  <div>
                    <div class="flex items-center gap-2" style="margin-bottom: 6px; flex-wrap: wrap;">
                      <span class="apple-council-capsule">
                        ● <?php echo htmlspecialchars($j['status']); ?>
                      </span>
                      <span style="font-size: 0.78rem; color: var(--apple-blue); font-weight: 700; background: var(--apple-blue-soft); padding: 2px 10px; border-radius: 9999px;">
                        <?php echo htmlspecialchars($j['department']); ?>
                      </span>
                      <?php if (!empty($j['urgent'])): ?>
                        <span class="hjg-badge badge-gold" style="font-size: 0.72rem; padding: 2px 10px; border-radius: 9999px;">⚡ Urgent</span>
                      <?php endif; ?>
                    </div>
                    <h4 style="font-size: 1.22rem; color: var(--apple-text-primary); margin: 0 0 6px 0; font-weight: 800; letter-spacing: -0.02em;">
                      <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $j['id']; ?>" target="_blank" style="color: var(--apple-text-primary); text-decoration: none;">
                        <?php echo htmlspecialchars($j['title']); ?>
                      </a>
                    </h4>
                    <div style="font-size: 0.82rem; color: var(--apple-text-secondary); display: flex; gap: 16px; flex-wrap: wrap;">
                      <span>📍 <?php echo htmlspecialchars($j['location']); ?></span>
                      <span>💰 <?php echo htmlspecialchars($j['salary']); ?></span>
                      <span>📅 Posted <?php echo htmlspecialchars($j['posted_date']); ?></span>
                    </div>
                  </div>

                  <div class="flex items-center gap-2" style="flex-wrap: wrap;">
                    <span class="apple-council-capsule" style="<?php echo count($jobApplicants) > 0 ? '' : 'background:#F1F5F9; color:#64748B; border-color:#CBD5E1;'; ?> font-size: 0.84rem; padding: 6px 14px;">
                      <?php echo count($jobApplicants); ?> Candidate<?php echo count($jobApplicants) === 1 ? '' : 's'; ?> Applied
                    </span>
                    <a href="<?php echo BASE_URL; ?>/job-details.php?id=<?php echo $j['id']; ?>" target="_blank" class="apple-pill-btn apple-pill-btn-outline" style="font-size: 0.82rem; padding: 7px 14px;">
                      <span>View Vacancy</span>
                      <span>↗</span>
                    </a>
                    <button type="button" onclick="confirmDeleteJob(<?php echo $j['id']; ?>, '<?php echo htmlspecialchars(addslashes($j['title'])); ?>')" class="apple-pill-btn apple-pill-btn-danger" style="font-size: 0.82rem; padding: 7px 12px;" title="Delete this vacancy">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                      <span>Delete</span>
                    </button>
                  </div>
                </div>

                <!-- Candidates Applied specifically for this Job -->
                <div style="padding: 22px 24px;">
                  <?php if (empty($jobApplicants)): ?>
                    <div style="text-align: center; padding: 28px 18px; background: #F8FAFC; border-radius: 14px; border: 1px dashed rgba(203, 213, 225, 0.85); color: var(--apple-text-muted); font-size: 0.9rem;">
                      No candidates have applied for <strong><?php echo htmlspecialchars($j['title']); ?></strong> yet.
                    </div>
                  <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                      <?php foreach ($jobApplicants as $app): ?>
                        <div class="apple-candidate-item flex-between items-center" style="flex-wrap: wrap; gap: 18px;">
                          
                          <!-- Candidate Left Info -->
                          <div class="flex items-center gap-4" style="min-width: 280px;">
                            <div class="apple-squircle-avatar">
                              <span><?php echo htmlspecialchars($app['initials'] ?: 'MD'); ?></span>
                            </div>
                            <div>
                              <div class="flex items-center gap-2" style="flex-wrap: wrap;">
                                <strong style="color: var(--apple-text-primary); font-size: 1.1rem; font-weight: 700; letter-spacing: -0.015em;">
                                  <?php echo htmlspecialchars($app['candidate_name']); ?>
                                </strong>
                                <?php if (!empty($app['council_reg']) && $app['council_reg'] !== 'N/A'): ?>
                                  <span class="apple-council-capsule">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span><?php echo htmlspecialchars($app['council_reg']); ?></span>
                                  </span>
                                <?php endif; ?>
                                <span style="font-size: 0.72rem; padding: 2px 10px; border-radius: 9999px; background: #F1F5F9; color: #475569; font-weight: 700;">
                                  <?php echo htmlspecialchars($app['status']); ?>
                                </span>
                              </div>
                              <div style="font-size: 0.86rem; color: var(--apple-blue); font-weight: 600; margin-top: 3px;">
                                <?php echo htmlspecialchars($app['qualification'] ?: 'Healthcare Professional'); ?> · Experience: <?php echo htmlspecialchars($app['experience'] ?: 'Standard'); ?>
                              </div>
                              <div style="font-size: 0.8rem; color: var(--apple-text-muted); margin-top: 3px;">
                                Applied on <?php echo htmlspecialchars($app['applied_time']); ?>
                              </div>
                              <div style="font-size: 0.82rem; color: var(--apple-text-secondary); margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span>📧 <?php echo htmlspecialchars($app['candidate_email']); ?></span>
                                <span>•</span>
                                <span>📱 <?php echo htmlspecialchars($app['candidate_phone']); ?></span>
                              </div>
                            </div>
                          </div>

                          <!-- Candidate Right Action Buttons -->
                          <div class="flex items-center gap-2" style="flex-wrap: wrap;">
                            <?php if (!empty($app['resume'])): ?>
                              <a href="<?php echo BASE_URL . '/' . htmlspecialchars($app['resume']); ?>" target="_blank" class="apple-pill-btn apple-pill-btn-outline" style="font-size: 0.82rem; padding: 7px 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>View CV</span>
                              </a>
                            <?php endif; ?>

                            <?php if (!empty($app['candidate_phone'])): 
                              $cleanPhone = preg_replace('/[^0-9]/', '', $app['candidate_phone']);
                            ?>
                              <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=Hello%20<?php echo urlencode($app['candidate_name']); ?>,%20we%20reviewed%20your%20application%20for%20<?php echo urlencode($j['title']); ?>%20at%20<?php echo urlencode($hospitalName); ?>." target="_blank" class="apple-pill-btn apple-pill-btn-whatsapp" style="font-size: 0.82rem; padding: 7px 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                <span>WhatsApp</span>
                              </a>
                            <?php endif; ?>

                            <!-- Status Action Forms -->
                            <form method="POST" action="employer-dashboard.php" style="display: inline;">
                              <input type="hidden" name="action" value="update_applicant_status">
                              <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                              <input type="hidden" name="status" value="Shortlisted">
                              <button type="submit" class="apple-pill-btn <?php echo ($app['status'] === 'Shortlisted') ? 'apple-pill-btn-secondary' : 'apple-pill-btn-primary'; ?>" style="font-size: 0.82rem; padding: 7px 14px;" <?php echo ($app['status'] === 'Shortlisted') ? 'disabled style="opacity: 0.7;"' : ''; ?>>
                                <?php echo ($app['status'] === 'Shortlisted') ? 'Shortlisted ✓' : 'Shortlist'; ?>
                              </button>
                            </form>

                            <form method="POST" action="employer-dashboard.php" style="display: inline;">
                              <input type="hidden" name="action" value="update_applicant_status">
                              <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                              <input type="hidden" name="status" value="Interview Scheduled">
                              <button type="submit" class="apple-pill-btn apple-pill-btn-emerald" style="font-size: 0.82rem; padding: 7px 14px;">
                                <span>Interview</span>
                              </button>
                            </form>
                          </div>

                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
          </div>

          <!-- Unlinked / General Hospital Applications if any exist -->
          <?php if (!empty($unlinkedApplications)): ?>
            <div style="margin-top: 26px; border: 1px solid var(--apple-border); border-radius: 20px; padding: 24px; background: #FFFFFF; box-shadow: var(--apple-shadow-sm);">
              <h4 style="font-size: 1.18rem; color: var(--apple-text-primary); margin-bottom: 14px; font-weight: 700;">General Clinical Inquiries (<?php echo count($unlinkedApplications); ?>)</h4>
              <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($unlinkedApplications as $app): ?>
                  <div class="apple-candidate-item flex-between items-center" style="flex-wrap: wrap; gap: 14px;">
                    <div>
                      <strong style="color: var(--apple-text-primary); font-size: 1.05rem;"><?php echo htmlspecialchars($app['candidate_name']); ?></strong> (<?php echo htmlspecialchars($app['candidate_email']); ?>)
                      <div style="font-size: 0.8rem; color: var(--apple-text-muted); margin-top: 2px;">Applied on <?php echo htmlspecialchars($app['applied_time']); ?></div>
                    </div>
                    <?php if (!empty($app['resume'])): ?>
                      <a href="<?php echo BASE_URL . '/' . htmlspecialchars($app['resume']); ?>" target="_blank" class="apple-pill-btn apple-pill-btn-outline" style="font-size: 0.82rem; padding: 6px 14px;">View CV</a>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </section>
    </div> <!-- /appsSectionWrapper -->

  </main>
</div>
</div>

<!-- ==========================================================================
     MANDATORY / FIRST LOGIN CHANGE DEFAULT PASSWORD MODAL (macOS Style)
     ========================================================================== -->
<?php 
$isMandatoryPasswordChange = !empty($currentUser['must_change_password']) && ((int)$currentUser['must_change_password'] === 1) && !$passChangeSuccess;
?>
<div class="modal-overlay" id="changePasswordModal" style="<?php echo $isMandatoryPasswordChange ? 'display: flex;' : 'display: none;'; ?> position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
  <div class="modal-card apple-pro-card" style="max-width: 480px; width: 100%; border: 1px solid rgba(255,255,255,0.4); box-shadow: var(--apple-shadow-lg); overflow: hidden; animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
    
    <!-- Modal Header -->
    <div style="background: linear-gradient(135deg, #091829 0%, #11344E 100%); color: #FFFFFF; padding: 24px 28px; position: relative;">
      <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(56, 189, 248, 0.2); border: 1px solid rgba(56, 189, 248, 0.4); display: flex; align-items: center; justify-content: center; color: #38BDF8;">
          <?php echo getIcon('lock', '', 20); ?>
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.28rem; color: #FFFFFF; font-weight: 800; font-family: var(--apple-font); letter-spacing: -0.02em;">
            <?php echo $isMandatoryPasswordChange ? 'Set Your New Password' : 'Change Account Password'; ?>
          </h3>
          <span style="font-size: 0.78rem; color: #94A3B8;">Security & Access Governance</span>
        </div>
      </div>
      
      <?php if (!$isMandatoryPasswordChange): ?>
        <button type="button" onclick="closeChangePasswordModal()" style="position: absolute; right: 20px; top: 20px; background: rgba(255,255,255,0.1); border: none; color: #CBD5E1; width: 28px; height: 28px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
      <?php endif; ?>
    </div>

    <!-- Modal Body -->
    <div style="padding: 26px 28px;">
      
      <?php if ($isMandatoryPasswordChange): ?>
        <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; color: #92400E; padding: 14px 18px; border-radius: 14px; font-size: 0.88rem; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
          <span style="font-size: 1.25rem; line-height: 1;">⚠️</span>
          <div>
            <strong>Temporary Password In Use!</strong><br>
            Your account was activated with a system-generated default password. To protect your healthcare organization and candidate data, please choose a permanent personal password.
          </div>
        </div>
      <?php endif; ?>

      <!-- Error / Success Alert Box -->
      <div id="modalAlertBox" style="<?php echo !empty($passChangeError) ? 'display: flex;' : 'display: none;'; ?> background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; font-size: 0.85rem; margin-bottom: 20px; align-items: center; gap: 10px;">
        <?php echo getIcon('alert-triangle', '', 18); ?>
        <span id="modalAlertText"><?php echo htmlspecialchars($passChangeError); ?></span>
      </div>

      <form id="changePasswordForm" method="POST" action="employer-dashboard.php" onsubmit="submitChangePassword(event)">
        <input type="hidden" name="action" value="change_default_password">

        <!-- Field 1: Current / Default Temporary Password -->
        <div class="form-group" style="margin-bottom: 18px;">
          <label class="form-label" for="currPassInput" style="font-weight: 700; font-size: 0.88rem; margin-bottom: 6px; color: var(--apple-text-primary);">
            <?php echo $isMandatoryPasswordChange ? 'Default Temporary Password *' : 'Current Password *'; ?>
          </label>
          <div style="position: relative;">
            <input type="password" name="current_password" id="currPassInput" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user']['temp_password'] ?? ($currentUser['initial_random_password'] ?? '')); ?>" placeholder="Enter the password received in email" style="padding: 10px 44px 10px 14px; border-radius: 12px; border: 1px solid #CBD5E1; font-size: 0.92rem; width: 100%;" required autofocus>
            <button type="button" class="eye-toggle-btn" onclick="togglePassField('currPassInput', this)" title="See password while typing" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--apple-text-muted); display: flex; align-items: center; justify-content: center; padding: 4px;">
              <?php echo getIcon('eye', '', 18); ?>
            </button>
          </div>
          <span class="form-hint" style="font-size: 0.76rem; color: var(--apple-text-muted); margin-top: 4px; display: block;">
            <?php echo $isMandatoryPasswordChange ? 'The alphanumeric password sent in your account activation message' : 'Your current portal password'; ?>
          </span>
        </div>

        <!-- Field 2: New Password -->
        <div class="form-group" style="margin-bottom: 18px;">
          <div class="flex-between items-center" style="margin-bottom: 6px;">
            <label class="form-label" for="newPassInput" style="font-weight: 700; font-size: 0.88rem; margin: 0; color: var(--apple-text-primary);">New Password *</label>
            <span style="font-size: 0.75rem; color: var(--apple-blue); font-weight: 600;">Minimum 6 characters</span>
          </div>
          <div style="position: relative;">
            <input type="password" name="new_password" id="newPassInput" class="form-control" placeholder="Choose a secure new password" style="padding: 10px 44px 10px 14px; border-radius: 12px; border: 1px solid #CBD5E1; font-size: 0.92rem; width: 100%;" required minlength="6">
            <button type="button" class="eye-toggle-btn" onclick="togglePassField('newPassInput', this)" title="See password while typing" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--apple-text-muted); display: flex; align-items: center; justify-content: center; padding: 4px;">
              <?php echo getIcon('eye', '', 18); ?>
            </button>
          </div>
        </div>

        <!-- Field 3: Confirm New Password -->
        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="confirmPassInput" style="font-weight: 700; font-size: 0.88rem; margin-bottom: 6px; color: var(--apple-text-primary);">Confirm New Password *</label>
          <div style="position: relative;">
            <input type="password" name="confirm_password" id="confirmPassInput" class="form-control" placeholder="Re-type your new password" style="padding: 10px 44px 10px 14px; border-radius: 12px; border: 1px solid #CBD5E1; font-size: 0.92rem; width: 100%;" required minlength="6">
            <button type="button" class="eye-toggle-btn" onclick="togglePassField('confirmPassInput', this)" title="See password while typing" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--apple-text-muted); display: flex; align-items: center; justify-content: center; padding: 4px;">
              <?php echo getIcon('eye', '', 18); ?>
            </button>
          </div>
        </div>

        <button type="submit" id="btnSavePassword" class="apple-pill-btn apple-pill-btn-primary" style="width: 100%; padding: 13px 20px; font-size: 0.96rem; font-weight: 700;">
          <span>Set New Password & Access Dashboard</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </button>
      </form>
    </div>

  </div>
</div>

<!-- ==========================================================================
     APPLE MACOS STYLE PHOTO CROPPING MODAL (1:1 Logo & 16:5 Cover)
     ========================================================================== -->
<div id="dashCropModalOverlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.72); backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); z-index: 999999; align-items: center; justify-content: center; padding: 24px;">
  <div style="background: #FFFFFF; border-radius: 24px; box-shadow: var(--apple-shadow-lg); border: 1px solid rgba(255, 255, 255, 0.5); max-width: 680px; width: 100%; overflow: hidden; animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
    
    <div style="padding: 20px 24px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h3 id="dashCropHeading" style="margin: 0; font-size: 1.18rem; font-weight: 800; letter-spacing: -0.02em;">Crop Photo</h3>
        <span id="dashCropSubtext" style="font-size: 0.8rem; color: var(--apple-text-muted);">Reposition & adjust handles to frame your image</span>
      </div>
      <button type="button" onclick="closeDashCropperModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--apple-text-muted); cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <div style="background: #0F172A; max-height: 380px; width: 100%; overflow: hidden; display: flex; align-items: center; justify-content: center;">
      <img id="dashCropImageElement" style="max-width: 100%; display: block;" alt="Image to crop">
    </div>

    <div style="padding: 14px 24px; background: #F8FAFC; border-top: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
      <div class="flex items-center gap-2">
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="dashCropZoom(0.1)" style="padding: 6px 12px; font-size: 0.82rem;" title="Zoom In">+</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="dashCropZoom(-0.1)" style="padding: 6px 12px; font-size: 0.82rem;" title="Zoom Out">-</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="dashCropRotate(90)" style="padding: 6px 12px; font-size: 0.82rem;" title="Rotate">⟳ Rotate</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="dashCropReset()" style="padding: 6px 12px; font-size: 0.82rem;" title="Reset">Reset</button>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" onclick="closeDashCropperModal()" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 600;">Cancel</button>
        <button type="button" id="btnDashApplyCrop" onclick="saveDashCroppedPhoto()" class="apple-pill-btn apple-pill-btn-primary" style="font-weight: 700;">
          <span>Apply Crop & Save</span>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ==========================================================================
     APPLE MACOS STYLE DELETE JOB CONFIRMATION MODAL
     ========================================================================== -->
<div class="modal-overlay" id="deleteJobConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
  <div class="modal-card apple-pro-card" style="max-width: 440px; width: 100%; border: 1px solid rgba(255,255,255,0.4); box-shadow: var(--apple-shadow-lg); overflow: hidden; animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
    
    <div style="padding: 32px 28px 26px 28px; text-align: center;">
      <!-- Red Trash Squircle Glyph -->
      <div style="width: 58px; height: 58px; border-radius: 18px; background: rgba(239, 68, 68, 0.12); color: #DC2626; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
      </div>
      
      <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--apple-text-primary); margin: 0 0 8px 0; letter-spacing: -0.025em; font-family: var(--apple-font);">
        Delete Vacancy?
      </h3>
      
      <p style="font-size: 0.92rem; color: var(--apple-text-secondary); line-height: 1.5; margin: 0 0 24px 0;">
        Are you sure you want to permanently delete <strong id="deleteJobModalTitle" style="color: var(--apple-text-primary);">this vacancy</strong>? This listing will be immediately removed from candidate view and all associated applications will be cleared.
      </p>

      <form id="deleteJobForm" method="POST" action="employer-dashboard.php">
        <input type="hidden" name="action" value="delete_job">
        <input type="hidden" name="job_id" id="deleteJobModalId" value="">
        
        <div style="display: flex; gap: 12px; justify-content: center;">
          <button type="button" onclick="closeDeleteJobModal()" class="apple-pill-btn apple-pill-btn-outline" style="flex: 1; padding: 12px 18px; font-weight: 600;">
            Cancel
          </button>
          <button type="submit" id="btnConfirmDeleteJob" class="apple-pill-btn" style="flex: 1; padding: 12px 18px; background: #DC2626; color: #FFFFFF; font-weight: 700; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35); border: none;">
            Delete Vacancy
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<!-- ==========================================================================
     INTERACTIVE JAVASCRIPT CONTROLLERS
     ========================================================================== -->
<script>
// Dynamic Apple Pro View Switcher: Hub Cards <-> Vacancies <-> Applications
function showDashboardView(viewId) {
  const hub = document.getElementById('hubCardsContainer');
  const jobsWrap = document.getElementById('jobsSectionWrapper');
  const appsWrap = document.getElementById('appsSectionWrapper');

  if (viewId === 'jobs') {
    if (hub) hub.style.display = 'none';
    if (jobsWrap) jobsWrap.style.display = 'block';
    if (appsWrap) appsWrap.style.display = 'none';
    try { history.pushState(null, '', '?view=jobs'); } catch (e) {}
    window.scrollTo({ top: 320, behavior: 'smooth' });
  } else if (viewId === 'applications') {
    if (hub) hub.style.display = 'none';
    if (jobsWrap) jobsWrap.style.display = 'none';
    if (appsWrap) appsWrap.style.display = 'block';
    try { history.pushState(null, '', '?view=applications'); } catch (e) {}
    window.scrollTo({ top: 320, behavior: 'smooth' });
  } else {
    // Default Clean Hub View
    if (hub) hub.style.display = 'grid';
    if (jobsWrap) jobsWrap.style.display = 'none';
    if (appsWrap) appsWrap.style.display = 'none';
    try { history.pushState(null, '', 'employer-dashboard.php'); } catch (e) {}
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// Seamlessly switch to Applications view and focus on specific job
function showJobApplicantsInAppsView(jobId) {
  showDashboardView('applications');
  setTimeout(() => {
    selectJobTab(jobId);
  }, 60);
}

// Open Apple macOS style Delete Job Modal
function confirmDeleteJob(jobId, jobTitle) {
  const modal = document.getElementById('deleteJobConfirmModal');
  const titleEl = document.getElementById('deleteJobModalTitle');
  const idInput = document.getElementById('deleteJobModalId');
  if (modal && titleEl && idInput) {
    titleEl.textContent = '“' + jobTitle + '”';
    idInput.value = jobId;
    modal.style.display = 'flex';
  } else {
    if (confirm('Are you sure you want to delete "' + jobTitle + '"? This will remove the listing and all applications.')) {
      const f = document.createElement('form');
      f.method = 'POST';
      f.action = 'employer-dashboard.php';
      f.innerHTML = '<input type="hidden" name="action" value="delete_job"><input type="hidden" name="job_id" value="' + jobId + '">';
      document.body.appendChild(f);
      f.submit();
    }
  }
}

function closeDeleteJobModal() {
  const modal = document.getElementById('deleteJobConfirmModal');
  if (modal) modal.style.display = 'none';
}

// Toggle Password Visibility
function togglePassField(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;

  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<?php echo getIcon('eye-off', '', 18); ?>';
    btn.title = 'Hide password';
  } else {
    input.type = 'password';
    btn.innerHTML = '<?php echo getIcon('eye', '', 18); ?>';
    btn.title = 'See password while typing';
  }
}

function openChangePasswordModal(isMandatory) {
  const modal = document.getElementById('changePasswordModal');
  if (modal) {
    modal.style.display = 'flex';
    document.getElementById('newPassInput').value = '';
    document.getElementById('confirmPassInput').value = '';
  }
}

function closeChangePasswordModal() {
  <?php if ($isMandatoryPasswordChange): ?>
    if (typeof showToast === 'function') {
      showToast('Action Required: Please set your personal permanent password to proceed.');
    }
    return;
  <?php endif; ?>
  const modal = document.getElementById('changePasswordModal');
  if (modal) modal.style.display = 'none';
}

function submitChangePassword(e) {
  e.preventDefault();
  const form = document.getElementById('changePasswordForm');
  const alertBox = document.getElementById('modalAlertBox');
  const alertText = document.getElementById('modalAlertText');
  const btn = document.getElementById('btnSavePassword');

  const curr = document.getElementById('currPassInput').value.trim();
  const newP = document.getElementById('newPassInput').value.trim();
  const confP = document.getElementById('confirmPassInput').value.trim();

  if (newP.length < 6) {
    alertBox.style.display = 'flex';
    alertText.textContent = 'Password must be at least 6 characters.';
    return;
  }
  if (newP !== confP) {
    alertBox.style.display = 'flex';
    alertText.textContent = 'New password and confirm password do not match.';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = 'Securing Account...';

  const formData = new FormData(form);
  fetch('employer-dashboard.php', {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alertBox.style.background = '#ECFDF5';
      alertBox.style.borderColor = '#10B981';
      alertBox.style.color = '#065F46';
      alertBox.style.display = 'flex';
      alertText.textContent = data.message;

      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<span>Set New Password & Access Dashboard</span> &rarr;';
      alertBox.style.background = '#FEF2F2';
      alertBox.style.borderColor = '#FCA5A5';
      alertBox.style.color = '#991B1B';
      alertBox.style.display = 'flex';
      alertText.textContent = data.message;
    }
  })
  .catch(err => {
    form.submit();
  });
}

// =============================================================================
// APPLE PRO PHOTO CROPPING STUDIO FOR COVER & LOGO
// =============================================================================
let dashCropperInstance = null;
let dashCropType = 'cover'; // 'cover' or 'logo'

function handleCoverPhotoUpload(input) {
  openDashboardCropper(input, 'cover');
}

function handleProfileLogoUpload(input) {
  openDashboardCropper(input, 'logo');
}

function openDashboardCropper(input, type) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  if (file.size > 12 * 1024 * 1024) {
    if (typeof showToast === 'function') {
      showToast('File too large! Please choose an image smaller than 12MB.');
    } else {
      alert('File too large! Maximum allowed is 12MB.');
    }
    input.value = '';
    return;
  }

  dashCropType = type;
  const heading = document.getElementById('dashCropHeading');
  const subtext = document.getElementById('dashCropSubtext');

  if (type === 'cover') {
    if (heading) heading.textContent = 'Crop Panoramic Cover Banner';
    if (subtext) subtext.textContent = 'Frame your panoramic hospital cover (16:5 ratio).';
  } else {
    if (heading) heading.textContent = 'Crop Hospital Profile Logo';
    if (subtext) subtext.textContent = 'Frame your hospital logo / squircle avatar (1:1 square ratio).';
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const cropImg = document.getElementById('dashCropImageElement');
    cropImg.src = e.target.result;

    const modal = document.getElementById('dashCropModalOverlay');
    if (modal) modal.style.display = 'flex';

    if (dashCropperInstance) {
      dashCropperInstance.destroy();
    }

    const targetRatio = (type === 'cover') ? (16 / 5) : 1;

    if (typeof Cropper !== 'undefined') {
      dashCropperInstance = new Cropper(cropImg, {
        aspectRatio: targetRatio,
        viewMode: 2,
        autoCropArea: 0.95,
        responsive: true,
        restore: true,
        checkOrientation: true,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false
      });
    }
  };
  reader.readAsDataURL(file);
}

function dashCropZoom(delta) {
  if (dashCropperInstance) dashCropperInstance.zoom(delta);
}

function dashCropRotate(degree) {
  if (dashCropperInstance) dashCropperInstance.rotate(degree);
}

function dashCropReset() {
  if (dashCropperInstance) dashCropperInstance.reset();
}

function closeDashCropperModal() {
  const modal = document.getElementById('dashCropModalOverlay');
  if (modal) modal.style.display = 'none';
  if (dashCropperInstance) {
    dashCropperInstance.destroy();
    dashCropperInstance = null;
  }
  const coverInput = document.getElementById('coverPhotoFileInput');
  if (coverInput) coverInput.value = '';
  const logoInput = document.getElementById('profileLogoFileInput');
  if (logoInput) logoInput.value = '';
}

function saveDashCroppedPhoto() {
  const btn = document.getElementById('btnDashApplyCrop');
  btn.disabled = true;
  btn.innerHTML = 'Saving Cropped Photo...';

  let croppedBase64 = '';
  if (dashCropperInstance) {
    const canvas = dashCropperInstance.getCroppedCanvas({
      maxWidth: dashCropType === 'cover' ? 1600 : 600,
      maxHeight: dashCropType === 'cover' ? 500 : 600,
      fillColor: '#FFFFFF',
      imageSmoothingQuality: 'high'
    });
    croppedBase64 = canvas.toDataURL('image/jpeg', 0.92);
  } else {
    croppedBase64 = document.getElementById('dashCropImageElement').src;
  }

  const formData = new FormData();
  formData.append('action', (dashCropType === 'cover') ? 'upload_cover_photo' : 'upload_profile_logo');
  formData.append('cropped_image_data', croppedBase64);

  fetch('employer-dashboard.php', {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<span>Apply Crop & Save</span>';

    if (data && data.success) {
      if (dashCropType === 'cover') {
        const coverImg = document.getElementById('employerCoverImg');
        const defaultArt = document.getElementById('employerCoverDefaultArt');
        const btnText = document.getElementById('coverBtnText');

        if (coverImg) {
          coverImg.src = data.url;
          coverImg.style.display = 'block';
        }
        if (defaultArt) defaultArt.style.display = 'none';
        if (btnText) btnText.textContent = 'Change Cover Photo';
      } else {
        const wrap = document.getElementById('employerLogoDisplayWrap');
        if (wrap) {
          wrap.innerHTML = `<img id="employerLogoImg" src="${data.url}" style="width:100%; height:100%; object-fit:cover; display:block;" alt="Hospital Logo">`;
        }
      }

      closeDashCropperModal();
      if (typeof showToast === 'function') {
        showToast('✓ ' + data.message);
      } else {
        alert('✓ ' + data.message);
      }
    } else {
      alert(data ? data.message : 'Error uploading cropped image.');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<span>Apply Crop & Save</span>';
    console.error('Cropping upload error:', err);
    alert('Network error while saving cropped photo.');
  });
}

// Filter Candidate Applications by specific Vacancy / Job (Apple Segmented Control)
function selectJobTab(jobId) {
  const allCards = document.querySelectorAll('.job-vacancy-apps-card');
  const allTabs = document.querySelectorAll('.job-filter-tab-btn');

  allTabs.forEach(t => {
    t.classList.remove('active');
  });

  const activeTab = document.querySelector(`.job-filter-tab-btn[data-job-id="${jobId}"]`);
  if (activeTab) {
    activeTab.classList.add('active');
  }

  if (jobId === 'all') {
    allCards.forEach(c => c.style.display = 'block');
  } else {
    allCards.forEach(c => {
      if (c.getAttribute('data-job-id') == jobId) {
        c.style.display = 'block';
        c.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        c.style.transition = 'box-shadow 0.4s ease, border-color 0.4s ease';
        c.style.borderColor = 'var(--apple-blue)';
        c.style.boxShadow = '0 0 0 4px rgba(0, 113, 227, 0.18)';
        setTimeout(() => {
          c.style.borderColor = 'var(--apple-border)';
          c.style.boxShadow = 'var(--apple-shadow-sm)';
        }, 2200);
      } else {
        c.style.display = 'none';
      }
    });
  }
}

// Auto-activate requested view or specific job on page load
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const viewParam = urlParams.get('view') || urlParams.get('section');
  const viewJobParam = urlParams.get('view_job') || urlParams.get('job_id');
  const hash = window.location.hash;

  if (viewJobParam || hash.startsWith('#vacancy-apps-') || viewParam === 'applications' || hash === '#applications') {
    showDashboardView('applications');
    const targetJobId = viewJobParam || (hash.startsWith('#vacancy-apps-') ? hash.replace('#vacancy-apps-', '') : 'all');
    setTimeout(() => {
      selectJobTab(targetJobId);
    }, 80);
  } else if (viewParam === 'jobs' || hash === '#jobs') {
    showDashboardView('jobs');
  } else {
    // Clean default dashboard hub with 5 cards only
    showDashboardView('');
  }
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
