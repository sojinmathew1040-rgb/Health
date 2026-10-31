<?php
/**
 * HealthJobsGlobal.com - Candidate Job Application API Handler
 * Saves candidate application to MySQL database and updates job applicant counter
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

function sendApiResponse(bool $success, string $message, int $jobId = 0, bool $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        $_SESSION['flash_apply_result'] = ['success' => $success, 'message' => $message];
        $dest = ($jobId > 0) ? (BASE_URL . '/job-details.php?id=' . $jobId) : (BASE_URL . '/jobs.php');
        header("Location: " . $dest);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendApiResponse(false, 'Invalid request method.', 0, $isAjax);
}

$jobId = intval($_POST['job_id'] ?? 0);
$name = trim($_POST['name'] ?? ($_POST['applicant_name'] ?? ''));
$email = trim($_POST['email'] ?? ($_POST['applicant_email'] ?? ''));
$phone = trim($_POST['phone'] ?? ($_POST['applicant_phone'] ?? ''));
$council = trim($_POST['council_reg'] ?? ($_POST['applicant_council'] ?? ''));
$experience = trim($_POST['experience'] ?? ($_POST['applicant_exp'] ?? ''));
$notice = trim($_POST['notice_period'] ?? ($_POST['applicant_notice'] ?? '30 Days'));
$qualification = trim($_POST['qualification'] ?? '');
$coverLetter = trim($_POST['cover_letter'] ?? '');

// If user is logged in as candidate, pull defaults if missing
if (isLoggedIn()) {
    $logged = getLoggedInUser();
    if (($logged['role'] ?? '') === 'candidate') {
        if (empty($name)) $name = $logged['name'] ?? '';
        if (empty($email)) $email = $logged['email'] ?? '';
        if (empty($phone)) $phone = $logged['phone'] ?? '';
        if (empty($council)) $council = $logged['council'] ?? '';
        if (empty($qualification)) $qualification = $logged['degree'] ?? ($logged['title'] ?? '');
    }
}

if ($jobId <= 0) {
    sendApiResponse(false, 'Invalid or missing Job ID.', 0, $isAjax);
}

if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendApiResponse(false, 'Please provide a valid full name and email address.', $jobId, $isAjax);
}

if (empty($con) || $con->connect_error) {
    sendApiResponse(false, 'Database connection unavailable. Please try again.', $jobId, $isAjax);
}

// 1. Verify Job exists and get Hospital details
$stmtJ = $con->prepare("SELECT id, title, company, hospital_id FROM `jobs` WHERE id = ? LIMIT 1");
if (!$stmtJ) {
    sendApiResponse(false, 'Failed to prepare job verification statement.', $jobId, $isAjax);
}
$stmtJ->bind_param("i", $jobId);
$stmtJ->execute();
$resJ = $stmtJ->get_result();
if (!$resJ || !($job = $resJ->fetch_assoc())) {
    $stmtJ->close();
    sendApiResponse(false, 'The selected healthcare vacancy was not found or has been closed.', $jobId, $isAjax);
}
$stmtJ->close();

$jobTitle = $job['title'];
$hospitalName = $job['company'];
$hospitalId = (int)($job['hospital_id'] ?? 0);

// If hospital_id is 0 or unlinked, lookup hospital record by name or admin name
if ($hospitalId <= 0 && !empty($hospitalName)) {
    $cleanHospName = trim($hospitalName);
    $stmtH = $con->prepare("SELECT id, name FROM `hospitals` WHERE LOWER(TRIM(name)) = LOWER(?) OR LOWER(TRIM(admin_name)) = LOWER(?) LIMIT 1");
    if ($stmtH) {
        $stmtH->bind_param("ss", $cleanHospName, $cleanHospName);
        $stmtH->execute();
        $rH = $stmtH->get_result();
        if ($rH && $hRow = $rH->fetch_assoc()) {
            $hospitalId = (int)$hRow['id'];
            $hospitalName = $hRow['name'];
            @$con->query("UPDATE `jobs` SET `hospital_id` = {$hospitalId} WHERE `id` = {$jobId}");
        }
        $stmtH->close();
    }
}

if (empty($qualification)) {
    $qualification = 'Healthcare Professional';
}

// Ensure schema columns exist
@$con->query("ALTER TABLE `applications` ADD COLUMN `hospital_id` INT DEFAULT 0");
@$con->query("ALTER TABLE `applications` ADD COLUMN `resume` VARCHAR(255) DEFAULT ''");
@$con->query("ALTER TABLE `applications` ADD COLUMN `notice_period` VARCHAR(50) DEFAULT ''");

// 2. Handle CV / Resume file upload
$resumePath = '';
if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['cv_file']['tmp_name'];
    $fileName = $_FILES['cv_file']['name'];
    $fileSize = $_FILES['cv_file']['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExts = ['pdf', 'doc', 'docx'];
    if (in_array($fileExt, $allowedExts) && $fileSize <= 10 * 1024 * 1024) {
        $storageDir = __DIR__ . '/../storage/resumes';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }
        $safeName = 'resume_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', substr($name, 0, 15)) . '.' . $fileExt;
        $destPath = $storageDir . '/' . $safeName;
        if (move_uploaded_file($fileTmp, $destPath)) {
            $resumePath = 'storage/resumes/' . $safeName;
        }
    }
}

// 3. Check if application already exists for this candidate & job
$existingAppId = 0;
$stmtCheck = $con->prepare("SELECT id, resume FROM `applications` WHERE job_id = ? AND candidate_email = ? LIMIT 1");
if ($stmtCheck) {
    $stmtCheck->bind_param("is", $jobId, $email);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    if ($resCheck && $rowC = $resCheck->fetch_assoc()) {
        $existingAppId = (int)$rowC['id'];
        if (empty($resumePath) && !empty($rowC['resume'])) {
            $resumePath = $rowC['resume'];
        }
    }
    $stmtCheck->close();
}

if ($existingAppId > 0) {
    // Update existing application record
    $stmtUp = $con->prepare("UPDATE `applications` SET 
        `hospital_id` = ?, 
        `hospital_name` = ?, 
        `job_title` = ?, 
        `candidate_name` = ?, 
        `candidate_phone` = ?, 
        `qualification` = ?, 
        `experience` = ?, 
        `council_reg` = ?, 
        `notice_period` = ?, 
        `resume` = ?, 
        `cover_letter` = ?, 
        `status` = 'Applied', 
        `applied_at` = NOW() 
        WHERE `id` = ?");
    if ($stmtUp) {
        $stmtUp->bind_param("issssssssssi", 
            $hospitalId, 
            $hospitalName, 
            $jobTitle, 
            $name, 
            $phone, 
            $qualification, 
            $experience, 
            $council, 
            $notice, 
            $resumePath, 
            $coverLetter, 
            $existingAppId
        );
        $stmtUp->execute();
        $stmtUp->close();
    }
} else {
    // Insert new application record
    $stmtIns = $con->prepare("INSERT INTO `applications` 
        (`job_id`, `hospital_id`, `job_title`, `hospital_name`, `candidate_name`, `candidate_email`, `candidate_phone`, `qualification`, `experience`, `council_reg`, `notice_period`, `resume`, `cover_letter`, `status`, `applied_at`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Applied', NOW())");

    if (!$stmtIns) {
        sendApiResponse(false, 'Failed to prepare application insert: ' . $con->error, $jobId, $isAjax);
    }

    $stmtIns->bind_param("iisssssssssss", 
        $jobId, 
        $hospitalId, 
        $jobTitle, 
        $hospitalName, 
        $name, 
        $email, 
        $phone, 
        $qualification, 
        $experience, 
        $council, 
        $notice, 
        $resumePath, 
        $coverLetter
    );

    if (!$stmtIns->execute()) {
        $err = $stmtIns->error;
        $stmtIns->close();
        sendApiResponse(false, 'Database error submitting application: ' . $err, $jobId, $isAjax);
    }
    $stmtIns->close();
}

// 4. Increment job application count
$con->query("UPDATE `jobs` SET `applications_count` = (SELECT COUNT(*) FROM `applications` WHERE `job_id` = {$jobId}) WHERE `id` = {$jobId}");

sendApiResponse(true, "Application submitted successfully! Recruiter notified via WhatsApp & Email.", $jobId, $isAjax);
