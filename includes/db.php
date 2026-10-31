<?php
/**
 * HealthJobsGlobal - MySQL Database Connection
 * Single centralized database connection for entire application
 */

// MySQL connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthjobs";

// Ensure BASE_URL is always defined across entire system
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Health');
}

// PHP 8.1 - 8.4 compatibility: Prevent uncaught mysqli_sql_exception on initial connection probe
mysqli_report(MYSQLI_REPORT_OFF);

// Create connection with fallback database auto-creation
try {
    $con = @new mysqli($servername, $username, $password, $dbname);
} catch (Throwable $e) {
    $con = false;
}

// If database does not exist, automatically create it and reconnect
if (!$con || $con->connect_error) {
    try {
        $rootConn = @new mysqli($servername, $username, $password);
        if ($rootConn && !$rootConn->connect_error) {
            $rootConn->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $rootConn->close();
            $con = @new mysqli($servername, $username, $password, $dbname);
        }
    } catch (Throwable $e) {
        // Keep $con as is
    }
}

if ($con && !$con->connect_error) {
    $con->set_charset("utf8mb4");

    // Ensure columns exist on applications table
    @$con->query("ALTER TABLE `applications` ADD COLUMN `hospital_id` INT DEFAULT 0");
    @$con->query("ALTER TABLE `applications` ADD COLUMN `resume` VARCHAR(255) DEFAULT ''");
    @$con->query("ALTER TABLE `applications` ADD COLUMN `notice_period` VARCHAR(50) DEFAULT ''");

    // Ensure columns exist on hospitals table for cover banner and profile photo
    @$con->query("ALTER TABLE `hospitals` ADD COLUMN `cover_image` VARCHAR(255) DEFAULT ''");
    @$con->query("ALTER TABLE `hospitals` ADD COLUMN `logo_image` VARCHAR(255) DEFAULT ''");

    // Ensure columns exist on jobs table
    @$con->query("ALTER TABLE `jobs` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP");

    // Clear legacy dummy/seeded jobs and applications once
    $lockDir = __DIR__ . '/../storage';
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0777, true);
    }
    $lockFile = $lockDir . '/dummy_cleared_v2.lock';
    if (!file_exists($lockFile)) {
        // Clear all mock applications and mock jobs
        @$con->query("TRUNCATE TABLE `applications`");
        @$con->query("TRUNCATE TABLE `jobs`");
        @$con->query("UPDATE `hospitals` SET `openings` = 0");
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));
    }
}

/**
 * Initialize Schema and Seed Data if tables are missing
 * (PHP 8.4 typed and explicitly nullable parameter)
 */
function initAdminDatabase(?mysqli $con): bool {
    if (!$con || $con->connect_error) {
        return false;
    }

    // Check if jobs table exists
    $check = $con->query("SHOW TABLES LIKE 'jobs'");
    if ($check && $check->num_rows > 0) {
        return true; // Already initialized
    }

    // 1. Admins Table
    $con->query("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL,
        `email` VARCHAR(191) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'Super Administrator',
        `avatar` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default admin: admin@healthjobs.com / admin123
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $con->query("INSERT IGNORE INTO `admins` (`id`, `username`, `email`, `password`, `name`, `role`) 
        VALUES (1, 'admin', 'admin@healthjobs.com', '{$defaultPass}', 'Chief Medical Director', 'Super Administrator')");

    // 2. Hospitals / Employers Table
    $con->query("CREATE TABLE IF NOT EXISTS `hospitals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `logo_text` VARCHAR(50) DEFAULT '',
        `badge` VARCHAR(150) DEFAULT 'NABH / JCI Accredited',
        `type` VARCHAR(150) DEFAULT 'Quaternary Hospital Network',
        `location` VARCHAR(255) NOT NULL,
        `openings` INT DEFAULT 0,
        `rating` VARCHAR(10) DEFAULT '4.8',
        `verified` TINYINT(1) DEFAULT 1,
        `contact_email` VARCHAR(191) DEFAULT '',
        `admin_email` VARCHAR(191) DEFAULT '',
        `admin_name` VARCHAR(150) DEFAULT '',
        `password` VARCHAR(255) DEFAULT '',
        `phone` VARCHAR(50) DEFAULT '',
        `website` VARCHAR(255) DEFAULT '',
        `overview` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Clinical Jobs Table
    $con->query("CREATE TABLE IF NOT EXISTS `jobs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `hospital_id` INT DEFAULT 1,
        `company` VARCHAR(255) NOT NULL,
        `location` VARCHAR(255) NOT NULL,
        `department` VARCHAR(150) NOT NULL,
        `specialty` VARCHAR(150) NOT NULL,
        `experience` VARCHAR(100) NOT NULL,
        `employment_type` VARCHAR(100) DEFAULT 'Full Time · On-Site',
        `salary` VARCHAR(100) NOT NULL,
        `council_req` VARCHAR(255) DEFAULT 'Medical / Nursing Council Active',
        `qualification` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `tags` VARCHAR(255) DEFAULT '',
        `status` ENUM('Active', 'Pending', 'Closed') DEFAULT 'Active',
        `featured` TINYINT(1) DEFAULT 0,
        `urgent` TINYINT(1) DEFAULT 0,
        `views` INT DEFAULT 0,
        `applications_count` INT DEFAULT 0,
        `posted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. Candidates Table
    $con->query("CREATE TABLE IF NOT EXISTS `candidates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(191) NOT NULL UNIQUE,
        `password` VARCHAR(255) DEFAULT '',
        `phone` VARCHAR(50) DEFAULT '',
        `degree` VARCHAR(150) NOT NULL,
        `specialty` VARCHAR(150) NOT NULL,
        `experience` VARCHAR(100) DEFAULT '',
        `council_reg` VARCHAR(100) DEFAULT '',
        `location` VARCHAR(255) DEFAULT '',
        `status` ENUM('Verified', 'Pending', 'Suspended') DEFAULT 'Verified',
        `resume` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. Inbound Applications Table
    $con->query("CREATE TABLE IF NOT EXISTS `applications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `job_id` INT NOT NULL,
        `job_title` VARCHAR(255) NOT NULL,
        `hospital_name` VARCHAR(255) NOT NULL,
        `candidate_name` VARCHAR(255) NOT NULL,
        `candidate_email` VARCHAR(191) NOT NULL,
        `candidate_phone` VARCHAR(50) DEFAULT '',
        `qualification` VARCHAR(150) DEFAULT '',
        `experience` VARCHAR(100) DEFAULT '',
        `council_reg` VARCHAR(100) DEFAULT '',
        `cover_letter` TEXT,
        `status` ENUM('Applied', 'Reviewing', 'Shortlisted', 'Interview Scheduled', 'Offered', 'Rejected') DEFAULT 'Applied',
        `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 6. Departments Table
    $con->query("CREATE TABLE IF NOT EXISTS `departments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `slug` VARCHAR(150) NOT NULL,
        `icon` VARCHAR(50) DEFAULT 'stethoscope',
        `openings` INT DEFAULT 0,
        `description` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed Hospitals
    $hospitals = [
        [1, 'Aster Medcity', 'aster-medcity', 'ASTER', 'JCI & NABH Accredited Quaternary Care', 'Quaternary Care Multi-Specialty Hospital', 'Kochi, Kerala & Dubai, UAE', 54, '4.8', 1, 'careers@astermedcity.com', '+91 484 669 9999', 'https://www.astermedcity.com', 'Aster DM Healthcare operates premier quaternary hospitals across South India and the GCC with world-class clinical governance and robotic surgery centers.'],
        [2, 'Apollo Hospitals Enterprise', 'apollo-hospitals', 'APOLLO', 'JCI Accredited Flagship', 'Quaternary Hospital Network', 'Chennai, Hyderabad, Bengaluru, Delhi', 138, '4.9', 1, 'jobs@apollohospitals.com', '+91 44 2829 0200', 'https://www.apollohospitals.com', 'Recognized globally for clinical excellence, cardiac transplants, robotic da Vinci surgeries, and cutting-edge medical oncology.'],
        [3, 'Cleveland Clinic Abu Dhabi', 'cleveland-clinic-abudhabi', 'CCAD', 'International Academic Medical Center', 'Quaternary Hospital', 'Al Maryah Island, Abu Dhabi, UAE', 42, '4.95', 1, 'recruitment@clevelandclinicabudhabi.ae', '+971 2 659 0200', 'https://www.clevelandclinicabudhabi.ae', 'Extension of US Cleveland Clinic model providing access to the highest clinical protocols and research-driven healthcare.'],
        [4, 'KIMSHEALTH Multi-Specialty Hospital', 'kimshealth', 'KIMS', 'ACHSI & NABH Accredited', 'Quaternary Care Teaching Hospital', 'Thiruvananthapuram, Kerala & Bahrain', 46, '4.8', 1, 'hr@kimshealth.org', '+91 471 294 1400', 'https://www.kimshealth.org', 'One of South India\'s leading quaternary hospitals with over 900 beds, specializing in organ transplants, neurosciences, and critical care.'],
        [5, 'Amrita Institute of Medical Sciences (AIMS)', 'amrita-hospital', 'AIMS', 'NABH & NABL Quaternary Center', 'Super-Specialty Medical Institute', 'Kochi, Kerala & Faridabad, Delhi NCR', 68, '4.85', 1, 'careers@aims.amrita.edu', '+91 484 285 1234', 'https://www.amrita.edu/hospital', 'Premier 1,350-bed quaternary care teaching hospital renowned globally for pediatric cardiology, bone marrow transplants, and complex surgery.'],
        [6, 'Lisie Hospital', 'lisie-hospital', 'LISIE', 'NABH Certified Center of Excellence', 'Cardiac & Multi-Specialty Hospital', 'Kochi, Kerala', 38, '4.8', 1, 'hr@lisiehospital.org', '+91 484 240 2044', 'https://www.lisiehospital.org', 'Pioneers in cardiothoracic surgery, interventional cardiology, and comprehensive medical care with over six decades of medical heritage.']
    ];

    $stmtH = $con->prepare("INSERT IGNORE INTO `hospitals` (`id`, `name`, `slug`, `logo_text`, `badge`, `type`, `location`, `openings`, `rating`, `verified`, `contact_email`, `phone`, `website`, `overview`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmtH) {
        foreach ($hospitals as $h) {
            $stmtH->bind_param("issssssisissss", $h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $h[6], $h[7], $h[8], $h[9], $h[10], $h[11], $h[12], $h[13]);
            $stmtH->execute();
        }
        $stmtH->close();
    }

    // Jobs and Applications tables start clean (no mock seeding)
    // Only real vacancies posted by employers and real candidate applications exist in the platform.

    // Seed Candidates (Sample candidate logins for testing if needed)
    $candidates = [
        [1, 'Dr. Arjun Menon', 'dr.arjun@hospital.com', '+91 98470 12345', 'MD, DM (Cardiology)', 'Interventional Cardiology', '8+ Years', 'TCMC 45892', 'Kochi, Kerala', 'Verified'],
        [2, 'Sister Preethi Thomas', 'preethi.thomas@nursemail.com', '+91 97455 67890', 'B.Sc Nursing, Post-Basic ICU', 'Critical Care Nursing', '5 Years', 'KNC 88214', 'Ernakulam, Kerala', 'Verified'],
        [3, 'Dr. Rajesh Nair', 'dr.rajesh.nair@healthmail.com', '+91 94471 23456', 'MBBS, MD (Emergency Med), MRCEM', 'Emergency Medicine', '6 Years', 'KMC 67341', 'Bengaluru, Karnataka', 'Verified'],
        [4, 'Ananya Sharma', 'ananya.sharma@pharmacycare.com', '+91 98112 34567', 'Pharm.D (Doctor of Pharmacy)', 'Clinical Pharmacy', '3 Years', 'PCI 55421', 'Chennai, Tamil Nadu', 'Verified'],
        [5, 'Naveen Kurian', 'naveen.perfusion@lisiealumni.org', '+91 99460 98765', 'M.Sc Perfusion Technology', 'Cardiovascular Perfusion', '7 Years', 'BCPI 1290', 'Kochi, Kerala', 'Verified']
    ];

    $stmtC = $con->prepare("INSERT IGNORE INTO `candidates` (`id`, `name`, `email`, `phone`, `degree`, `specialty`, `experience`, `council_reg`, `location`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmtC) {
        foreach ($candidates as $c) {
            $stmtC->bind_param("isssssssss", $c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $c[8], $c[9]);
            $stmtC->execute();
        }
        $stmtC->close();
    }

    // Inbound Applications table starts empty (only real candidate applications)

    // Seed Departments
    $depts = [
        [1, 'Doctors & Clinical Specialists', 'clinical', 'stethoscope', 940, 'Specialist physicians, surgeons, super-specialists across all medical faculties.'],
        [2, 'Nursing Specialties', 'nursing', 'heart-pulse', 780, 'Registered critical care, OT scrub, NICU, ER, and ward nurses.'],
        [3, 'Diagnostics, Pathology & Pharmacy', 'diagnostics', 'microscope', 410, 'Radiologists, pathologists, biochemists, and clinical pharmacists.'],
        [4, 'Allied Health & Paramedical', 'allied', 'activity', 350, 'Physiotherapists, perfusionists, radiographers, and emergency technicians.'],
        [5, 'Hospital Operations & Leadership', 'operations', 'settings', 190, 'Medical superintendents, operations managers, and NABH coordinators.']
    ];

    $stmtD = $con->prepare("INSERT IGNORE INTO `departments` (`id`, `name`, `slug`, `icon`, `openings`, `description`) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmtD) {
        foreach ($depts as $d) {
            $stmtD->bind_param("isssis", $d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
            $stmtD->execute();
        }
        $stmtD->close();
    }

    return true;
}

// Auto-run schema check and initialization
if (!$con->connect_error) {
    initAdminDatabase($con);
    @$con->query("UPDATE `jobs` SET `status` = 'Active' WHERE `status` = '' OR `status` IS NULL");

    // Auto-migrate missing columns for unified authentication
    $colCheckC = @$con->query("SHOW COLUMNS FROM `candidates` LIKE 'password'");
    if ($colCheckC && $colCheckC->num_rows === 0) {
        @$con->query("ALTER TABLE `candidates` ADD `password` VARCHAR(255) DEFAULT '' AFTER `email`");
    }

    $colCheckH1 = @$con->query("SHOW COLUMNS FROM `hospitals` LIKE 'password'");
    if ($colCheckH1 && $colCheckH1->num_rows === 0) {
        @$con->query("ALTER TABLE `hospitals` ADD `password` VARCHAR(255) DEFAULT '' AFTER `contact_email`");
    }
    $colCheckH2 = @$con->query("SHOW COLUMNS FROM `hospitals` LIKE 'admin_email'");
    if ($colCheckH2 && $colCheckH2->num_rows === 0) {
        @$con->query("ALTER TABLE `hospitals` ADD `admin_email` VARCHAR(191) DEFAULT '' AFTER `contact_email`");
    }
    $colCheckH3 = @$con->query("SHOW COLUMNS FROM `hospitals` LIKE 'admin_name'");
    if ($colCheckH3 && $colCheckH3->num_rows === 0) {
        @$con->query("ALTER TABLE `hospitals` ADD `admin_name` VARCHAR(150) DEFAULT '' AFTER `admin_email`");
    }

    // Auto-migrate employer registration details and activation password management
    $hospNewCols = [
        'must_change_password' => "ALTER TABLE `hospitals` ADD `must_change_password` TINYINT(1) DEFAULT 0",
        'admin_designation' => "ALTER TABLE `hospitals` ADD `admin_designation` VARCHAR(150) DEFAULT '' AFTER `admin_name`",
        'general_email' => "ALTER TABLE `hospitals` ADD `general_email` VARCHAR(191) DEFAULT ''",
        'general_phone' => "ALTER TABLE `hospitals` ADD `general_phone` VARCHAR(50) DEFAULT ''",
        'admin_phone' => "ALTER TABLE `hospitals` ADD `admin_phone` VARCHAR(50) DEFAULT ''",
        'address' => "ALTER TABLE `hospitals` ADD `address` TEXT DEFAULT NULL",
        'city' => "ALTER TABLE `hospitals` ADD `city` VARCHAR(150) DEFAULT ''",
        'pincode' => "ALTER TABLE `hospitals` ADD `pincode` VARCHAR(20) DEFAULT ''",
        'country' => "ALTER TABLE `hospitals` ADD `country` VARCHAR(100) DEFAULT 'India'",
        'operating_capacity' => "ALTER TABLE `hospitals` ADD `operating_capacity` VARCHAR(100) DEFAULT ''",
        'services' => "ALTER TABLE `hospitals` ADD `services` TEXT DEFAULT NULL",
        'other_services_details' => "ALTER TABLE `hospitals` ADD `other_services_details` TEXT DEFAULT NULL",
        'activated_at' => "ALTER TABLE `hospitals` ADD `activated_at` DATETIME DEFAULT NULL",
        'initial_random_password' => "ALTER TABLE `hospitals` ADD `initial_random_password` VARCHAR(100) DEFAULT ''"
    ];
    foreach ($hospNewCols as $cName => $sqlAlter) {
        $chk = @$con->query("SHOW COLUMNS FROM `hospitals` LIKE '{$cName}'");
        if ($chk && $chk->num_rows === 0) {
            @$con->query($sqlAlter);
        }
    }

    // Ensure default demo accounts have hashed password 'password123'
    $defaultPassHash = password_hash('password123', PASSWORD_DEFAULT);
    @$con->query("UPDATE `candidates` SET `password` = '{$defaultPassHash}' WHERE `email` = 'dr.arjun@hospital.com' AND (`password` = '' OR `password` IS NULL)");
    @$con->query("UPDATE `hospitals` SET `password` = '{$defaultPassHash}' WHERE `contact_email` = 'careers@astermedcity.com' AND (`password` = '' OR `password` IS NULL)");

    // System Settings Table Auto-Migration
    @$con->query("CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Initialize default company sender email if not set
    $chkSet = @$con->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'company_sender_email' LIMIT 1");
    if ($chkSet && $chkSet->num_rows === 0) {
        @$con->query("INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES 
            ('company_sender_email', 'careers@healthjobsglobal.com'),
            ('company_name', 'HealthJobsGlobal Administration'),
            ('company_reply_to', 'careers@healthjobsglobal.com')");
    }

    // Email Outbox Table Auto-Migration
    @$con->query("CREATE TABLE IF NOT EXISTS `email_outbox` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `recipient` VARCHAR(191) NOT NULL,
        `recipient_name` VARCHAR(191) DEFAULT '',
        `sender` VARCHAR(191) NOT NULL,
        `sender_name` VARCHAR(191) DEFAULT '',
        `subject` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL,
        `temp_password` VARCHAR(100) DEFAULT '',
        `type` VARCHAR(50) DEFAULT 'activation',
        `status` VARCHAR(50) DEFAULT 'Dispatched',
        `delivery_note` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Retrieve system setting by key with fallback
 */
function getSystemSetting(?mysqli $con, string $key, string $default = ''): string {
    if (!$con || $con->connect_error) {
        return $default;
    }
    $stmt = $con->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $stmt->close();
            return ($row['setting_value'] !== null && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
        }
        $stmt->close();
    }
    return $default;
}

/**
 * Save or update system setting
 */
function setSystemSetting(?mysqli $con, string $key, string $value): bool {
    if (!$con || $con->connect_error) {
        return false;
    }
    $stmt = $con->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    if ($stmt) {
        $stmt->bind_param("ss", $key, $value);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
    return false;
}

/**
 * Get configured company sender email for all admin dispatches
 */
function getCompanySenderEmail(?mysqli $con): string {
    $email = getSystemSetting($con, 'company_sender_email', 'careers@healthjobsglobal.com');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'careers@healthjobsglobal.com';
    }
    return $email;
}

/**
 * Get configured company display name for outgoing emails
 */
function getCompanyDisplayName(?mysqli $con): string {
    return getSystemSetting($con, 'company_name', 'HealthJobsGlobal Operations');
}

/**
 * Cascade delete hospital and all its related clinical jobs, applications, and assets
 */
function deleteHospitalCascade(?mysqli $con, int $hospitalId): bool {
    if (!$con || $con->connect_error || $hospitalId <= 0) {
        return false;
    }

    // 1. Fetch Hospital Info
    $hospName = '';
    $stmtH = $con->prepare("SELECT `name` FROM `hospitals` WHERE `id` = ? LIMIT 1");
    if ($stmtH) {
        $stmtH->bind_param("i", $hospitalId);
        $stmtH->execute();
        $resH = $stmtH->get_result();
        if ($resH && $hRow = $resH->fetch_assoc()) {
            $hospName = trim($hRow['name']);
        }
        $stmtH->close();
    }

    // 2. Identify all clinical jobs linked by hospital_id OR company name
    $jobIds = [];
    $hospNameEsc = $con->real_escape_string($hospName);
    $whereJobs = "`hospital_id` = {$hospitalId}";
    if (!empty($hospName)) {
        $whereJobs .= " OR `company` = '{$hospNameEsc}'";
    }

    $resJ = $con->query("SELECT `id` FROM `jobs` WHERE {$whereJobs}");
    if ($resJ) {
        while ($j = $resJ->fetch_assoc()) {
            $jobIds[] = (int)$j['id'];
        }
    }

    // 3. Delete all inbound applications linked to these jobs or hospital name
    if (!empty($jobIds)) {
        $idList = implode(',', $jobIds);
        @$con->query("DELETE FROM `applications` WHERE `job_id` IN ({$idList})");
    }
    if (!empty($hospName)) {
        @$con->query("DELETE FROM `applications` WHERE `hospital_name` = '{$hospNameEsc}'");
    }

    // 4. Delete all clinical jobs linked to this hospital
    if (!empty($jobIds)) {
        $idList = implode(',', $jobIds);
        @$con->query("DELETE FROM `jobs` WHERE `id` IN ({$idList})");
    }
    if (!empty($hospName)) {
        @$con->query("DELETE FROM `jobs` WHERE `company` = '{$hospNameEsc}'");
    }

    // 5. Delete the hospital record itself
    $stmtD = $con->prepare("DELETE FROM `hospitals` WHERE `id` = ?");
    if ($stmtD) {
        $stmtD->bind_param("i", $hospitalId);
        $resD = $stmtD->execute();
        $stmtD->close();
        return $resD;
    }
    return false;
}

/**
 * Clean up orphan clinical jobs whose parent hospital has already been deleted
 */
function cleanupOrphanJobs(?mysqli $con): int {
    if (!$con || $con->connect_error) {
        return 0;
    }

    // Clean up jobs that reference a deleted hospital
    $deletedCount = 0;
    $resOrphans = $con->query("SELECT j.id, j.hospital_id, j.company FROM `jobs` j 
        LEFT JOIN `hospitals` h1 ON j.hospital_id = h1.id 
        LEFT JOIN `hospitals` h2 ON j.company = h2.name 
        WHERE h1.id IS NULL AND h2.id IS NULL");
    
    if ($resOrphans && $resOrphans->num_rows > 0) {
        $orphanIds = [];
        while ($row = $resOrphans->fetch_assoc()) {
            $orphanIds[] = (int)$row['id'];
        }
        if (!empty($orphanIds)) {
            $idList = implode(',', $orphanIds);
            @$con->query("DELETE FROM `applications` WHERE `job_id` IN ({$idList})");
            @$con->query("DELETE FROM `jobs` WHERE `id` IN ({$idList})");
            $deletedCount = count($orphanIds);
        }
    }
    return $deletedCount;
}



