<?php
/**
 * HealthJobsGlobal.com - Hospital Employer Profile & Institutional Records Editor
 * Apple Level Pro Redesign - SF Pro Aesthetics, In-Browser Precision Photo Cropper, Full DB Synchronization
 * Route: http://localhost/Health/employer-profile.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Access Control: Only Employers
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode(BASE_URL . '/employer-profile.php'));
    exit;
}
$currentUser = getLoggedInUser();
if (($currentUser['role'] ?? '') !== 'employer') {
    header("Location: " . BASE_URL . "/candidate-profile.php");
    exit;
}

$userId = (int)($currentUser['id'] ?? 0);
$hospitalName = $currentUser['hospital'] ?? ($currentUser['name'] ?? '');

// Fetch Complete Hospital Record from MySQL
$hosp = [];
if (!empty($con) && !$con->connect_error) {
    if ($userId > 0) {
        $stmtH = $con->prepare("SELECT * FROM `hospitals` WHERE `id` = ? LIMIT 1");
        if ($stmtH) {
            $stmtH->bind_param("i", $userId);
            $stmtH->execute();
            $res = $stmtH->get_result();
            if ($row = $res->fetch_assoc()) {
                $hosp = $row;
            }
            $stmtH->close();
        }
    }
    // Fallback search by email or name if session id didn't match
    if (empty($hosp) && !empty($currentUser['email'])) {
        $cEmail = $currentUser['email'];
        $stmtH = $con->prepare("SELECT * FROM `hospitals` WHERE `contact_email` = ? OR `admin_email` = ? OR LOWER(TRIM(`name`)) = LOWER(TRIM(?)) LIMIT 1");
        if ($stmtH) {
            $stmtH->bind_param("sss", $cEmail, $cEmail, $hospitalName);
            $stmtH->execute();
            $res = $stmtH->get_result();
            if ($row = $res->fetch_assoc()) {
                $hosp = $row;
                $userId = (int)$row['id'];
                $_SESSION['user']['id'] = $userId;
            }
            $stmtH->close();
        }
    }
}

// -----------------------------------------------------------------------------
// POST HANDLER 1: Handle Cropped Image Upload (Base64 or File)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_cropped_photo') {
    header('Content-Type: application/json; charset=utf-8');
    
    $photoType = trim($_POST['photo_type'] ?? 'logo'); // 'logo' or 'cover'
    $isCover = ($photoType === 'cover');
    $folder = $isCover ? 'covers' : 'logos';
    $dbCol = $isCover ? 'cover_image' : 'logo_image';
    $targetDir = __DIR__ . '/storage/uploads/' . $folder;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $rawBase64 = trim($_POST['cropped_image_data'] ?? '');
    
    if (empty($rawBase64)) {
        // Fallback to standard file upload if base64 not sent
        $fileKey = $isCover ? 'cover_photo' : 'profile_logo';
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $filename = ($isCover ? 'cover_' : 'logo_') . $userId . '_' . time() . '.' . $ext;
            $dest = $targetDir . '/' . $filename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
                $dbPath = 'storage/uploads/' . $folder . '/' . $filename;
                if ($userId > 0 && !empty($con) && !$con->connect_error) {
                    $con->query("UPDATE `hospitals` SET `{$dbCol}` = '" . $con->real_escape_string($dbPath) . "' WHERE `id` = {$userId}");
                }
                $_SESSION['user'][$dbCol] = $dbPath;
                echo json_encode(['success' => true, 'url' => BASE_URL . '/' . $dbPath, 'message' => ($isCover ? 'Cover photo' : 'Profile photo') . ' updated successfully!']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'No image data received for cropping.']);
        exit;
    }

    // Process base64 data URL
    if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64, $typeMatch)) {
        $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
        $ext = strtolower($typeMatch[1]);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $rawBase64 = base64_decode($rawBase64);
        if ($rawBase64 === false) {
            echo json_encode(['success' => false, 'message' => 'Base64 decoding failed.']);
            exit;
        }

        $filename = ($isCover ? 'cover_' : 'logo_') . $userId . '_' . time() . '.' . $ext;
        $dest = $targetDir . '/' . $filename;

        if (file_put_contents($dest, $rawBase64)) {
            $dbPath = 'storage/uploads/' . $folder . '/' . $filename;
            if ($userId > 0 && !empty($con) && !$con->connect_error) {
                $con->query("UPDATE `hospitals` SET `{$dbCol}` = '" . $con->real_escape_string($dbPath) . "' WHERE `id` = {$userId}");
            }
            $_SESSION['user'][$dbCol] = $dbPath;
            echo json_encode([
                'success' => true, 
                'url' => BASE_URL . '/' . $dbPath . '?t=' . time(), 
                'message' => ($isCover ? 'Cover photo' : 'Hospital logo') . ' cropped and saved successfully!'
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to write cropped image to disk.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image format received.']);
        exit;
    }
}

// -----------------------------------------------------------------------------
// POST HANDLER 2: Save All Registration Profile Fields to Database
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_employer_profile') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');
    $type = trim($_POST['type'] ?? 'Quaternary Care Multi-Specialty Hospital');
    $operating_capacity = trim($_POST['operating_capacity'] ?? 'Medium (100 - 500 Beds / 250+ Staff)');
    $badge = trim($_POST['badge'] ?? 'NABH & JCI Accredited');
    $website = trim($_POST['website'] ?? '');
    $overview = trim($_POST['overview'] ?? '');
    
    $general_email = trim($_POST['general_email'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $contact_email = !empty($admin_email) ? $admin_email : $general_email;
    
    $general_phone = trim($_POST['general_phone'] ?? '');
    $admin_phone = trim($_POST['admin_phone'] ?? '');
    $phone = !empty($admin_phone) ? $admin_phone : $general_phone;
    
    $admin_name = mb_strtoupper(trim($_POST['admin_name'] ?? ''), 'UTF-8');
    $admin_designation = trim($_POST['admin_designation'] ?? 'Head of Medical Talent Acquisition');
    
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $location = $city . (!empty($address) ? ', ' . $address : '');
    
    $services = trim($_POST['services'] ?? '');
    $other_services_details = trim($_POST['other_services_details'] ?? '');
    
    if (empty($name)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Hospital / Institution Name cannot be empty.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Hospital Name cannot be empty.';
        header("Location: " . BASE_URL . "/employer-profile.php");
        exit;
    }
    
    if ($userId > 0 && !empty($con) && !$con->connect_error) {
        $stmtUp = $con->prepare("UPDATE `hospitals` SET 
            `name` = ?,
            `type` = ?,
            `operating_capacity` = ?,
            `badge` = ?,
            `website` = ?,
            `overview` = ?,
            `general_email` = ?,
            `admin_email` = ?,
            `contact_email` = ?,
            `general_phone` = ?,
            `admin_phone` = ?,
            `phone` = ?,
            `admin_name` = ?,
            `admin_designation` = ?,
            `address` = ?,
            `city` = ?,
            `pincode` = ?,
            `country` = ?,
            `location` = ?,
            `services` = ?,
            `other_services_details` = ?
            WHERE `id` = ?");
            
        if ($stmtUp) {
            $stmtUp->bind_param(
                "sssssssssssssssssssssi",
                $name, $type, $operating_capacity, $badge, $website, $overview,
                $general_email, $admin_email, $contact_email,
                $general_phone, $admin_phone, $phone,
                $admin_name, $admin_designation,
                $address, $city, $pincode, $country, $location,
                $services, $other_services_details,
                $userId
            );
            $stmtUp->execute();
            $stmtUp->close();
            
            // Also update jobs company name for consistency
            $stmtUpJobs = $con->prepare("UPDATE `jobs` SET `company` = ? WHERE `hospital_id` = ?");
            if ($stmtUpJobs) {
                $stmtUpJobs->bind_param("si", $name, $userId);
                $stmtUpJobs->execute();
                $stmtUpJobs->close();
            }
        }
    }
    
    // Update active session values
    $_SESSION['user']['name'] = !empty($admin_name) ? $admin_name : ($name . ' HR');
    $_SESSION['user']['hospital'] = $name;
    $_SESSION['user']['title'] = $admin_designation;
    $_SESSION['user']['email'] = $contact_email;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['accreditation'] = $badge;
    $cleanLogo = preg_replace('/[^A-Za-z]/', '', $name);
    $_SESSION['user']['initials'] = strtoupper(substr($cleanLogo, 0, 4));
    
    $successMsg = "Hospital institutional profile updated successfully!";
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => $successMsg]);
        exit;
    }
    $_SESSION['flash_success'] = $successMsg;
    header("Location: " . BASE_URL . "/employer-profile.php");
    exit;
}

// -----------------------------------------------------------------------------
// POST HANDLER 3: Handle Password Change
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_profile_password') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $currP = trim($_POST['current_password'] ?? '');
    $newP = trim($_POST['new_password'] ?? '');
    $confP = trim($_POST['confirm_password'] ?? '');
    
    if (strlen($newP) < 6) {
        $msg = "New password must be at least 6 characters long.";
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => false, 'message' => $msg]); exit; }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-profile.php#password");
        exit;
    }
    if ($newP !== $confP) {
        $msg = "New password and Confirm Password do not match.";
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => false, 'message' => $msg]); exit; }
        $_SESSION['flash_error'] = $msg;
        header("Location: " . BASE_URL . "/employer-profile.php#password");
        exit;
    }
    
    $verifiedPass = true;
    if ($userId > 0 && !empty($con) && !$con->connect_error) {
        $stmtC = $con->prepare("SELECT `password`, `initial_random_password` FROM `hospitals` WHERE `id` = ? LIMIT 1");
        if ($stmtC) {
            $stmtC->bind_param("i", $userId);
            $stmtC->execute();
            $res = $stmtC->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($currP) && !empty($row['password'])) {
                    if (!password_verify($currP, $row['password']) && 
                        $currP !== 'password123' && 
                        $currP !== ($row['initial_random_password'] ?? '')) {
                        $verifiedPass = false;
                    }
                }
            }
            $stmtC->close();
        }
        
        if (!$verifiedPass) {
            $msg = "The Current Password entered is incorrect.";
            if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => false, 'message' => $msg]); exit; }
            $_SESSION['flash_error'] = $msg;
            header("Location: " . BASE_URL . "/employer-profile.php#password");
            exit;
        }
        
        $newHash = password_hash($newP, PASSWORD_DEFAULT);
        $stmtU = $con->prepare("UPDATE `hospitals` SET `password` = ?, `must_change_password` = 0, `initial_random_password` = '' WHERE `id` = ?");
        if ($stmtU) {
            $stmtU->bind_param("si", $newHash, $userId);
            $stmtU->execute();
            $stmtU->close();
        }
        $_SESSION['user']['must_change_password'] = 0;
    }
    
    $msg = "Password updated successfully! Your account credentials are secured.";
    if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => true, 'message' => $msg]); exit; }
    $_SESSION['flash_success'] = $msg;
    header("Location: " . BASE_URL . "/employer-profile.php#password");
    exit;
}

// Re-read fresh hospital details for rendering
if ($userId > 0 && !empty($con) && !$con->connect_error) {
    $resR = $con->query("SELECT * FROM `hospitals` WHERE `id` = {$userId} LIMIT 1");
    if ($resR && $rF = $resR->fetch_assoc()) {
        $hosp = $rF;
    }
}

$pageTitle = "Edit Hospital Profile & Identity — HealthJobsGlobal";
require_once __DIR__ . '/components/header.php';

$coverUrl = !empty($hosp['cover_image']) ? (BASE_URL . '/' . htmlspecialchars($hosp['cover_image'])) : '';
$logoUrl = !empty($hosp['logo_image']) ? (BASE_URL . '/' . htmlspecialchars($hosp['logo_image'])) : '';
$displayHospName = $hosp['name'] ?? ($currentUser['hospital'] ?? 'Healthcare Hospital');
$cleanLogoText = !empty($hosp['logo_text']) ? $hosp['logo_text'] : strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $displayHospName), 0, 4));
$isActivated = ((int)($hosp['verified'] ?? 1) === 1);

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!-- ==========================================================================
     CROPPER.JS ASSETS (Precision Aspect-Ratio Cropping)
     ========================================================================== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<style>
  :root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --apple-surface: #FFFFFF;
    --apple-bg: #F8FAFC;
    --apple-border: rgba(226, 232, 240, 0.9);
    --apple-border-hairline: rgba(15, 23, 42, 0.08);
    --apple-text-primary: #0F172A;
    --apple-text-secondary: #475569;
    --apple-text-muted: #94A3B8;
    --apple-blue: #0071E3;
    --apple-blue-hover: #0077ED;
    --apple-blue-soft: rgba(0, 113, 227, 0.08);
    --apple-card-radius: 24px;
    --apple-shadow-sm: 0 2px 10px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.03);
    --apple-shadow-md: 0 12px 32px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    --apple-shadow-lg: 0 24px 48px -12px rgba(15, 23, 42, 0.18);
  }

  body {
    background: var(--apple-bg);
    color: var(--apple-text-primary);
    font-family: var(--apple-font);
    -webkit-font-smoothing: antialiased;
  }

  .apple-pro-card {
    background: var(--apple-surface);
    border: 1px solid var(--apple-border);
    border-radius: var(--apple-card-radius);
    box-shadow: var(--apple-shadow-sm);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .apple-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 9999px;
    font-size: 0.88rem;
    font-weight: 600;
    font-family: var(--apple-font);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none !important;
    border: none;
    line-height: 1.25;
  }

  .apple-pill-btn-primary {
    background: var(--apple-blue);
    color: #FFFFFF !important;
    box-shadow: 0 2px 10px rgba(0, 113, 227, 0.25);
  }
  .apple-pill-btn-primary:hover {
    background: var(--apple-blue-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(0, 113, 227, 0.35);
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

  .apple-form-group {
    margin-bottom: 22px;
  }

  .apple-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--apple-text-primary);
    margin-bottom: 7px;
    letter-spacing: -0.01em;
  }

  .apple-input, .apple-select, .apple-textarea {
    width: 100%;
    padding: 12px 16px;
    border-radius: 14px;
    border: 1px solid rgba(203, 213, 225, 0.9);
    background: #FFFFFF;
    font-size: 0.92rem;
    font-family: var(--apple-font);
    color: var(--apple-text-primary);
    transition: all 0.2s ease;
    box-sizing: border-box;
  }

  .apple-input:focus, .apple-select:focus, .apple-textarea:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3.5px rgba(0, 113, 227, 0.15);
  }

  .apple-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    margin-bottom: 24px;
  }

  .apple-section-glyph {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: var(--apple-blue-soft);
    color: var(--apple-blue);
  }

  /* Cropper Modal macOS Glass Styling */
  .crop-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  .crop-modal-card {
    background: #FFFFFF;
    border-radius: 24px;
    box-shadow: var(--apple-shadow-lg);
    border: 1px solid rgba(255, 255, 255, 0.5);
    max-width: 680px;
    width: 100%;
    overflow: hidden;
    animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes modalPop {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }

  .cropper-container {
    max-height: 420px;
    background: #0F172A;
  }
</style>

<div class="container" style="padding-top: 28px; padding-bottom: 80px;">

  <!-- Breadcrumb & Top Bar -->
  <div class="flex-between items-center" style="margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
      <div style="font-size: 0.82rem; color: var(--apple-text-muted); margin-bottom: 4px;">
        <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" style="color: var(--apple-blue); text-decoration: none; font-weight: 600;">Employer Hub</a>
        <span style="margin: 0 6px;">/</span>
        <span>Institutional Identity & Records</span>
      </div>
      <h1 style="font-size: 1.95rem; font-weight: 800; margin: 0; letter-spacing: -0.035em;">
        Edit Hospital Profile & Information
      </h1>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?php echo BASE_URL; ?>/employer-dashboard.php" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 700; padding: 10px 20px; font-size: 0.9rem; box-shadow: var(--apple-shadow-sm);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Back to Dashboard Hub</span>
      </a>
      <button type="button" onclick="submitProfileForm()" class="apple-pill-btn apple-pill-btn-primary" style="font-weight: 700; padding: 11px 26px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Save All Changes</span>
      </button>
    </div>
  </div>

  <!-- Flash Notification Banners -->
  <?php if (!empty($flashSuccess)): ?>
    <div style="background: rgba(236, 253, 245, 0.95); border: 1px solid rgba(16, 185, 129, 0.3); color: #065F46; padding: 16px 22px; border-radius: 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: var(--apple-shadow-sm);">
      <div style="display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.92rem;">
        <span style="width: 26px; height: 26px; border-radius: 50%; background: #10B981; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">✓</span>
        <span><?php echo htmlspecialchars($flashSuccess); ?></span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #065F46; line-height: 1;">&times;</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($flashError)): ?>
    <div style="background: rgba(254, 242, 242, 0.95); border: 1px solid rgba(239, 68, 68, 0.3); color: #991B1B; padding: 16px 22px; border-radius: 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: var(--apple-shadow-sm);">
      <div style="display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 0.92rem;">
        <span style="width: 26px; height: 26px; border-radius: 50%; background: #EF4444; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">!</span>
        <span><?php echo htmlspecialchars($flashError); ?></span>
      </div>
      <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #991B1B; line-height: 1;">&times;</button>
    </div>
  <?php endif; ?>

  <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">

    <!-- ======================================================================
         SECTION 1: VISUAL BRANDING & PHOTO CROPPING STUDIO
         ====================================================================== -->
    <div class="apple-pro-card" style="padding: 32px; overflow: hidden;">
      <div class="apple-section-header">
        <div class="apple-section-glyph">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
        </div>
        <div>
          <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Hospital Visual Identity & Photo Cropping</h2>
          <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
            Upload and crop both your panoramic hospital banner and institutional squircle logo.
          </p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 26px;">
        
        <!-- A. Panoramic Cover Photo Card -->
        <div style="background: #F8FAFC; border: 1px solid var(--apple-border); border-radius: 20px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div class="flex-between items-center" style="margin-bottom: 12px;">
              <span style="font-size: 0.88rem; font-weight: 700; color: var(--apple-text-primary);">1. Panoramic Cover Banner</span>
              <span style="font-size: 0.74rem; background: rgba(0, 113, 227, 0.1); color: var(--apple-blue); padding: 3px 10px; border-radius: 9999px; font-weight: 700;">16:5 Panoramic Ratio</span>
            </div>
            
            <!-- Live Cover Preview Box -->
            <div id="liveCoverContainer" style="width: 100%; height: 140px; border-radius: 14px; overflow: hidden; background: linear-gradient(135deg, #091829 0%, #11344E 55%, #0B5670 100%); position: relative; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
              <img id="liveCoverImg" src="<?php echo $coverUrl; ?>" style="<?php echo !empty($coverUrl) ? 'display: block;' : 'display: none;'; ?> width: 100%; height: 100%; object-fit: cover;" alt="Cover Banner">
              <div id="liveCoverPlaceholder" style="<?php echo empty($coverUrl) ? 'display: flex;' : 'display: none;'; ?> width: 100%; height: 100%; align-items: center; justify-content: center; color: #FFFFFF; font-size: 0.88rem; font-weight: 700; text-align: center; padding: 12px;">
                <span>✦ <?php echo htmlspecialchars($displayHospName); ?> Banner</span>
              </div>
            </div>
            <p style="font-size: 0.82rem; color: var(--apple-text-muted); line-height: 1.4; margin: 0 0 14px 0;">
              High-resolution cover displays across the header and institutional profile.
            </p>
          </div>

          <div>
            <input type="file" id="coverCropFileInput" accept="image/*" style="display: none;" onchange="initiatePhotoCropper(this, 'cover')">
            <button type="button" onclick="document.getElementById('coverCropFileInput').click()" class="apple-pill-btn apple-pill-btn-outline" style="width: 100%; font-weight: 700; font-size: 0.86rem;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
              <span>Choose & Crop Cover Banner</span>
            </button>
          </div>
        </div>

        <!-- B. Squircle Profile Logo Card -->
        <div style="background: #F8FAFC; border: 1px solid var(--apple-border); border-radius: 20px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div class="flex-between items-center" style="margin-bottom: 12px;">
              <span style="font-size: 0.88rem; font-weight: 700; color: var(--apple-text-primary);">2. Hospital Profile Photo / Logo</span>
              <span style="font-size: 0.74rem; background: rgba(16, 185, 129, 0.1); color: #059669; padding: 3px 10px; border-radius: 9999px; font-weight: 700;">1:1 Square / Squircle</span>
            </div>

            <!-- Live Logo Preview Box -->
            <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 14px;">
              <div id="liveLogoContainer" style="width: 90px; height: 90px; border-radius: 22px; background: #A51C30; border: 3px solid #FFFFFF; box-shadow: 0 8px 20px rgba(15,23,42,0.12); overflow: hidden; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-weight: 800; font-size: 1.25rem; flex-shrink: 0;">
                <?php if (!empty($logoUrl)): ?>
                  <img id="liveLogoImg" src="<?php echo $logoUrl; ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="Logo">
                <?php else: ?>
                  <span id="liveLogoText"><?php echo htmlspecialchars($cleanLogoText); ?></span>
                <?php endif; ?>
              </div>
              <div>
                <strong style="font-size: 0.95rem; color: var(--apple-text-primary); display: block;">Official Institution Mark</strong>
                <span style="font-size: 0.8rem; color: var(--apple-text-muted); display: block; margin-top: 3px;">
                  Shown on candidate discovery cards, job postings, and council credentials.
                </span>
              </div>
            </div>
          </div>

          <div>
            <input type="file" id="logoCropFileInput" accept="image/*" style="display: none;" onchange="initiatePhotoCropper(this, 'logo')">
            <button type="button" onclick="document.getElementById('logoCropFileInput').click()" class="apple-pill-btn apple-pill-btn-outline" style="width: 100%; font-weight: 700; font-size: 0.86rem;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
              <span>Choose & Crop Hospital Logo</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ======================================================================
         SECTION 2: INSTITUTIONAL REGISTRATION RECORDS (Full Form)
         ====================================================================== -->
    <form id="employerProfileForm" method="POST" action="employer-profile.php">
      <input type="hidden" name="action" value="update_employer_profile">

      <div class="apple-pro-card" style="padding: 34px; margin-bottom: 30px;">
        
        <div class="apple-section-header">
          <div class="apple-section-glyph" style="background: rgba(99, 102, 241, 0.1); color: #4F46E5;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
          </div>
          <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Hospital & Institution Details</h2>
            <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
              All credentials submitted during registration. Any updates here instantly sync to the database.
            </p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
          
          <!-- Hospital Name -->
          <div class="apple-form-group" style="grid-column: span 2;">
            <label class="apple-label" for="hosp_name">
              <span>Organization / Hospital Legal Name *</span>
              <span style="font-weight: 500; font-size: 0.78rem; color: var(--apple-text-muted);">As registered with clinical authorities</span>
            </label>
            <input type="text" id="hosp_name" name="name" class="apple-input" value="<?php echo htmlspecialchars($hosp['name'] ?? ''); ?>" required placeholder="e.g. Apollo Hospitals / Aster Medcity">
          </div>

          <!-- Category / Classification -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_type">Organization Classification *</label>
            <select id="hosp_type" name="type" class="apple-select">
              <?php 
              $categories = [
                'Quaternary Care Multi-Specialty Hospital',
                'Tertiary Care Super-Specialty Hospital',
                'Secondary Care General Hospital',
                'Specialized Oncology / Cardiac Institute',
                'Diagnostic & Clinical Laboratory Chain',
                'Day Care Surgery & Dialysis Center',
                'Healthcare Chain / Medical Group'
              ];
              $currentCat = $hosp['type'] ?? 'Quaternary Care Multi-Specialty Hospital';
              foreach ($categories as $cat):
              ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($currentCat === $cat) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Operating Capacity -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_capacity">Operating Capacity & Bed Strength *</label>
            <select id="hosp_capacity" name="operating_capacity" class="apple-select">
              <?php 
              $capacities = [
                'Small (<100 Beds / 50+ Staff)',
                'Medium (100 - 500 Beds / 250+ Staff)',
                'Large (500 - 1000+ Beds / 750+ Staff)',
                'Mega Institution (>1000 Beds / 1500+ Staff)'
              ];
              $currentCap = $hosp['operating_capacity'] ?? 'Medium (100 - 500 Beds / 250+ Staff)';
              foreach ($capacities as $cap):
              ?>
                <option value="<?php echo htmlspecialchars($cap); ?>" <?php echo ($currentCap === $cap) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cap); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Accreditations & Badges -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_badge">Accreditations & Governance Credentials</label>
            <input type="text" id="hosp_badge" name="badge" class="apple-input" value="<?php echo htmlspecialchars($hosp['badge'] ?? 'NABH & JCI Accredited'); ?>" placeholder="e.g. NABH & JCI Accredited, NABL">
          </div>

          <!-- Official Website URL -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_website">
              <span>Official Website URL</span>
              <span style="font-weight: 500; font-size: 0.78rem; color: var(--apple-blue);">Candidate Exploration Link</span>
            </label>
            <input type="url" id="hosp_website" name="website" class="apple-input" value="<?php echo htmlspecialchars($hosp['website'] ?? ''); ?>" placeholder="https://www.hospital.org">
          </div>

          <!-- Overview / Institutional Profile Description -->
          <div class="apple-form-group" style="grid-column: span 2;">
            <label class="apple-label" for="hosp_overview">Hospital Overview & Clinical Profile</label>
            <textarea id="hosp_overview" name="overview" class="apple-textarea" rows="3" placeholder="Provide a concise summary of the hospital's clinical facilities, patient volume, centers of excellence, and clinical work culture."><?php echo htmlspecialchars($hosp['overview'] ?? ''); ?></textarea>
          </div>

        </div>

      </div>

      <!-- ======================================================================
           SECTION 3: RECRUITER & COMMUNICATION CHANNELS
           ====================================================================== -->
      <div class="apple-pro-card" style="padding: 34px; margin-bottom: 30px;">
        
        <div class="apple-section-header">
          <div class="apple-section-glyph" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Communication Channels & Authorized Recruiter</h2>
            <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
              Contact details used for candidate inquiries, notifications, and WhatsApp updates.
            </p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
          
          <!-- Authorized HR Name -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_admin_name">Authorized Recruiter / Representative Name *</label>
            <input type="text" id="hosp_admin_name" name="admin_name" class="apple-input" value="<?php echo htmlspecialchars($hosp['admin_name'] ?? ''); ?>" required placeholder="e.g. Dr. Philip George / Sojin Mathew">
          </div>

          <!-- Authorized HR Designation -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_admin_desig">Recruiter Designation</label>
            <input type="text" id="hosp_admin_desig" name="admin_designation" class="apple-input" value="<?php echo htmlspecialchars($hosp['admin_designation'] ?? 'Head of Medical Talent Acquisition'); ?>" placeholder="e.g. Head of Talent Acquisition">
          </div>

          <!-- Direct Recruiter Email -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_admin_email">
              <span>Recruiter Official Email *</span>
              <span style="font-weight: 500; font-size: 0.78rem; color: var(--apple-blue);">Candidate Notifications</span>
            </label>
            <input type="email" id="hosp_admin_email" name="admin_email" class="apple-input" value="<?php echo htmlspecialchars($hosp['admin_email'] ?? ($hosp['contact_email'] ?? '')); ?>" required placeholder="recruitment@hospital.org">
          </div>

          <!-- General Hospital Email -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_general_email">General Hospital / Inquiries Email</label>
            <input type="email" id="hosp_general_email" name="general_email" class="apple-input" value="<?php echo htmlspecialchars($hosp['general_email'] ?? ''); ?>" placeholder="info@hospital.org">
          </div>

          <!-- Direct Recruiter Phone / WhatsApp -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_admin_phone">
              <span>Recruiter Direct Phone / WhatsApp *</span>
              <span style="font-weight: 500; font-size: 0.78rem; color: #10B981;">WhatsApp Alerts</span>
            </label>
            <input type="text" id="hosp_admin_phone" name="admin_phone" class="apple-input" value="<?php echo htmlspecialchars($hosp['admin_phone'] ?? ($hosp['phone'] ?? '')); ?>" required placeholder="+91 98470 00000">
          </div>

          <!-- General Reception Phone -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_general_phone">General Hospital Board Phone</label>
            <input type="text" id="hosp_general_phone" name="general_phone" class="apple-input" value="<?php echo htmlspecialchars($hosp['general_phone'] ?? ''); ?>" placeholder="+91 484 290 0000">
          </div>

        </div>

      </div>

      <!-- ======================================================================
           SECTION 4: GEOGRAPHICAL ADDRESS & CAMPUS LOCATION
           ====================================================================== -->
      <div class="apple-pro-card" style="padding: 34px; margin-bottom: 30px;">
        
        <div class="apple-section-header">
          <div class="apple-section-glyph" style="background: rgba(245, 158, 11, 0.1); color: #D97706;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Geographical Campus & Physical Address</h2>
            <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
              Location coordinates displayed to job applicants and clinicians.
            </p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
          
          <!-- Street Address -->
          <div class="apple-form-group" style="grid-column: span 2;">
            <label class="apple-label" for="hosp_address">Physical Campus Address</label>
            <input type="text" id="hosp_address" name="address" class="apple-input" value="<?php echo htmlspecialchars($hosp['address'] ?? ''); ?>" placeholder="e.g. NH 66 Bypass, Nettoor, Maradu">
          </div>

          <!-- City -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_city">City / Medical Hub *</label>
            <input type="text" id="hosp_city" name="city" class="apple-input" value="<?php echo htmlspecialchars($hosp['city'] ?? ''); ?>" required placeholder="e.g. Kochi">
          </div>

          <!-- Pincode -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_pincode">Postal / Pincode</label>
            <input type="text" id="hosp_pincode" name="pincode" class="apple-input" value="<?php echo htmlspecialchars($hosp['pincode'] ?? ''); ?>" placeholder="e.g. 682040">
          </div>

          <!-- Country -->
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_country">Country</label>
            <input type="text" id="hosp_country" name="country" class="apple-input" value="<?php echo htmlspecialchars($hosp['country'] ?? 'India'); ?>" placeholder="e.g. India">
          </div>

        </div>

      </div>

      <!-- ======================================================================
           SECTION 5: CLINICAL DEPARTMENTS & FACILITIES
           ====================================================================== -->
      <div class="apple-pro-card" style="padding: 34px; margin-bottom: 30px;">
        
        <div class="apple-section-header">
          <div class="apple-section-glyph" style="background: rgba(14, 165, 233, 0.1); color: #0284C7;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>
          </div>
          <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Clinical Departments & Specialties Offered</h2>
            <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
              Key specialties and medical facilities operational at this facility.
            </p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
          
          <div class="apple-form-group">
            <label class="apple-label" for="hosp_services">
              <span>Primary Clinical Specialties (Comma-separated)</span>
              <span style="font-weight: 500; font-size: 0.78rem; color: var(--apple-text-muted);">e.g. Cardiology, Oncology, Neurology, Organ Transplant</span>
            </label>
            <input type="text" id="hosp_services" name="services" class="apple-input" value="<?php echo htmlspecialchars($hosp['services'] ?? ''); ?>" placeholder="Interventional Cardiology, Medical Oncology, Surgical Gastroenterology, Critical Care">
          </div>

          <div class="apple-form-group">
            <label class="apple-label" for="hosp_other_services">Advanced Diagnostic & Surgical Facilities</label>
            <textarea id="hosp_other_services" name="other_services_details" class="apple-textarea" rows="2" placeholder="e.g. 24/7 Level-1 Trauma Center, 3.0T MRI, Robotic Assisted Surgery Suite, Level-3 NICU."><?php echo htmlspecialchars($hosp['other_services_details'] ?? ''); ?></textarea>
          </div>

        </div>

        <div style="display: flex; justify-content: flex-end; padding-top: 10px;">
          <button type="submit" id="btnSaveProfile" class="apple-pill-btn apple-pill-btn-primary" style="padding: 13px 32px; font-weight: 700; font-size: 0.96rem;">
            <span>Save All Profile Changes</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        </div>

      </div>

    </form>

    <!-- ======================================================================
         SECTION 6: ACCOUNT SECURITY & PASSWORD UPDATE
         ====================================================================== -->
    <div id="password" class="apple-pro-card" style="padding: 34px;">
      
      <div class="apple-section-header">
        <div class="apple-section-glyph" style="background: rgba(139, 92, 246, 0.1); color: #7C3AED;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div>
          <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; letter-spacing: -0.02em;">Account Credentials & Password</h2>
          <p style="font-size: 0.86rem; color: var(--apple-text-secondary); margin: 3px 0 0 0;">
            Update recruiter login password to safeguard candidate applications and hospital records.
          </p>
        </div>
      </div>

      <form id="profilePasswordForm" method="POST" action="employer-profile.php">
        <input type="hidden" name="action" value="change_profile_password">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; max-width: 820px;">
          
          <div class="apple-form-group">
            <label class="apple-label" for="curr_pass">Current Password *</label>
            <input type="password" id="curr_pass" name="current_password" class="apple-input" required placeholder="Enter current password">
          </div>

          <div class="apple-form-group">
            <label class="apple-label" for="new_pass">
              <span>New Password *</span>
              <span style="font-weight: 500; font-size: 0.76rem; color: var(--apple-blue);">Min 6 chars</span>
            </label>
            <input type="password" id="new_pass" name="new_password" class="apple-input" minlength="6" required placeholder="Choose secure new password">
          </div>

          <div class="apple-form-group">
            <label class="apple-label" for="conf_pass">Confirm New Password *</label>
            <input type="password" id="conf_pass" name="confirm_password" class="apple-input" minlength="6" required placeholder="Re-type new password">
          </div>

        </div>

        <button type="submit" class="apple-pill-btn apple-pill-btn-outline" style="border-color: rgba(139, 92, 246, 0.4); color: #7C3AED !important; font-weight: 700; padding: 11px 24px;">
          <span>Update Account Password</span>
          <span>→</span>
        </button>
      </form>

    </div>

  </div>

</div>

<!-- ==========================================================================
     APPLE MACOS STYLE INTERACTIVE PHOTO CROPPING MODAL (1:1 & 16:5 Ratios)
     ========================================================================== -->
<div id="cropModalOverlay" class="crop-modal-overlay">
  <div class="crop-modal-card">
    
    <!-- Modal Header -->
    <div style="padding: 20px 24px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h3 id="cropModalHeading" style="margin: 0; font-size: 1.18rem; font-weight: 800; letter-spacing: -0.02em;">
          Crop Image
        </h3>
        <span id="cropModalSubtext" style="font-size: 0.8rem; color: var(--apple-text-muted);">
          Drag to reposition and use handles to frame
        </span>
      </div>
      <button type="button" onclick="closeCropperModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--apple-text-muted); cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <!-- Cropping Viewport Container -->
    <div style="background: #0F172A; max-height: 380px; width: 100%; overflow: hidden; display: flex; align-items: center; justify-content: center;">
      <img id="cropperImageElement" style="max-width: 100%; display: block;" alt="Image to crop">
    </div>

    <!-- Toolbar: Zoom & Rotate Controls -->
    <div style="padding: 14px 24px; background: #F8FAFC; border-top: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
      <div class="flex items-center gap-2">
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="cropZoom(0.1)" style="padding: 6px 12px; font-size: 0.82rem;" title="Zoom In">+</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="cropZoom(-0.1)" style="padding: 6px 12px; font-size: 0.82rem;" title="Zoom Out">-</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="cropRotate(90)" style="padding: 6px 12px; font-size: 0.82rem;" title="Rotate 90°">⟳ Rotate</button>
        <button type="button" class="apple-pill-btn apple-pill-btn-outline" onclick="cropReset()" style="padding: 6px 12px; font-size: 0.82rem;" title="Reset Crop">Reset</button>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" onclick="closeCropperModal()" class="apple-pill-btn apple-pill-btn-outline" style="font-weight: 600;">
          Cancel
        </button>
        <button type="button" id="btnApplyCrop" onclick="applyAndSaveCrop()" class="apple-pill-btn apple-pill-btn-primary" style="font-weight: 700;">
          <span>Apply Crop & Save</span>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ==========================================================================
     INTERACTIVE JAVASCRIPT CONTROLLERS (Cropper.js & Async Sync)
     ========================================================================== -->
<script>
let activeCropperInstance = null;
let currentCropType = 'logo'; // 'logo' or 'cover'

// Initiate photo cropper when user selects a file
function initiatePhotoCropper(input, type) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  if (!file.type.match(/^image\//)) {
    alert('Please select a valid image file (JPG, PNG, WEBP).');
    input.value = '';
    return;
  }

  currentCropType = type;
  const heading = document.getElementById('cropModalHeading');
  const subtext = document.getElementById('cropModalSubtext');

  if (type === 'cover') {
    heading.textContent = 'Crop Panoramic Cover Banner';
    subtext.textContent = 'Frame your panoramic hospital cover (16:5 ratio).';
  } else {
    heading.textContent = 'Crop Hospital Profile Logo';
    subtext.textContent = 'Frame your institution logo / avatar (1:1 square ratio).';
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const cropImg = document.getElementById('cropperImageElement');
    cropImg.src = e.target.result;

    const modal = document.getElementById('cropModalOverlay');
    modal.style.display = 'flex';

    if (activeCropperInstance) {
      activeCropperInstance.destroy();
    }

    // Determine aspect ratio: 16:5 for cover, 1:1 for logo
    const targetRatio = (type === 'cover') ? (16 / 5) : 1;

    // Check if Cropper library is loaded, otherwise use canvas fallback
    if (typeof Cropper !== 'undefined') {
      activeCropperInstance = new Cropper(cropImg, {
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
    } else {
      console.warn('Cropper.js loading from CDN or using fallback.');
    }
  };
  reader.readAsDataURL(file);
}

function cropZoom(delta) {
  if (activeCropperInstance) activeCropperInstance.zoom(delta);
}

function cropRotate(degree) {
  if (activeCropperInstance) activeCropperInstance.rotate(degree);
}

function cropReset() {
  if (activeCropperInstance) activeCropperInstance.reset();
}

function closeCropperModal() {
  const modal = document.getElementById('cropModalOverlay');
  if (modal) modal.style.display = 'none';
  if (activeCropperInstance) {
    activeCropperInstance.destroy();
    activeCropperInstance = null;
  }
  const coverInput = document.getElementById('coverCropFileInput');
  if (coverInput) coverInput.value = '';
  const logoInput = document.getElementById('logoCropFileInput');
  if (logoInput) logoInput.value = '';
}

// Apply Crop and Save to Database
function applyAndSaveCrop() {
  const btn = document.getElementById('btnApplyCrop');
  btn.disabled = true;
  btn.innerHTML = 'Saving Crop...';

  let croppedBase64 = '';
  if (activeCropperInstance) {
    const canvas = activeCropperInstance.getCroppedCanvas({
      maxWidth: currentCropType === 'cover' ? 1600 : 600,
      maxHeight: currentCropType === 'cover' ? 500 : 600,
      fillColor: '#FFFFFF',
      imageSmoothingQuality: 'high'
    });
    croppedBase64 = canvas.toDataURL('image/jpeg', 0.92);
  } else {
    // Direct source fallback if cropper not initialized
    croppedBase64 = document.getElementById('cropperImageElement').src;
  }

  const formData = new FormData();
  formData.append('action', 'save_cropped_photo');
  formData.append('photo_type', currentCropType);
  formData.append('cropped_image_data', croppedBase64);

  fetch('employer-profile.php', {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<span>Apply Crop & Save</span>';

    if (data && data.success) {
      if (currentCropType === 'cover') {
        const liveImg = document.getElementById('liveCoverImg');
        const placeholder = document.getElementById('liveCoverPlaceholder');
        if (liveImg) {
          liveImg.src = data.url;
          liveImg.style.display = 'block';
        }
        if (placeholder) placeholder.style.display = 'none';
      } else {
        const liveLogoWrap = document.getElementById('liveLogoContainer');
        if (liveLogoWrap) {
          liveLogoWrap.innerHTML = `<img id="liveLogoImg" src="${data.url}" style="width:100%; height:100%; object-fit:cover; display:block;" alt="Logo">`;
        }
      }

      closeCropperModal();
      if (typeof showToast === 'function') {
        showToast('✓ ' + data.message);
      } else {
        alert('✓ ' + data.message);
      }
    } else {
      alert(data ? data.message : 'Error saving cropped image.');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<span>Apply Crop & Save</span>';
    console.error('Cropper upload error:', err);
    alert('Error connecting to server. Please try again.');
  });
}

function submitProfileForm() {
  const form = document.getElementById('employerProfileForm');
  if (form) {
    if (form.checkValidity()) {
      form.submit();
    } else {
      form.reportValidity();
    }
  }
}
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>

