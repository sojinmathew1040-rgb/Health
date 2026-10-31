<?php
/**
 * HealthJobsGlobal.com - Configuration & Hospital Mock Dataset
 * Exclusively designed for Hospital Healthcare & Medical Recruitment:
 * Doctors, Specialists, Nursing, Allied Health, Diagnostics, and Hospital Management.
 */

// Prevent multiple definitions
if (!defined('APP_NAME')) {
    define('APP_NAME', 'HealthJobsGlobal');
    define('APP_TAGLINE', 'Healthcare Careers. Global Opportunities.');
    define('APP_DOMAIN', 'HealthJobsGlobal.com');
    
    // Auto-detect base URL for XAMPP or custom virtual hosts
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim($scriptDir, '/');
    define('BASE_URL', $basePath);
}

// Session Management & Output Buffering (prevents 'headers already sent' in PHP 8.4)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/db.php';

// Handle Demo Login / Logout testing triggers
if (isset($_GET['demo_login'])) {
    if ($_GET['demo_login'] === 'candidate') {
        $_SESSION['user'] = [
            'role' => 'candidate',
            'name' => 'Dr. Arjun Menon',
            'degree' => 'MD, DM (Cardiology)',
            'title' => 'Senior Consultant – Interventional Cardiology',
            'email' => 'dr.arjun@hospital.com',
            'initials' => 'AM',
            'council' => 'TCMC 45892'
        ];
    } elseif ($_GET['demo_login'] === 'employer') {
        $_SESSION['user'] = [
            'role' => 'employer',
            'name' => 'Aster Medcity HR',
            'hospital' => 'Aster Medcity Hospital',
            'title' => 'Head of Medical Talent Acquisition',
            'email' => 'careers@astermedcity.com',
            'initials' => 'AS',
            'accreditation' => 'JCI & NABH Accredited'
        ];
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function isGuest(): bool {
    return empty($_SESSION['user']);
}

function getLoggedInUser(): ?array {
    return $_SESSION['user'] ?? null;
}

// Navigation structure
$navLinks = [
    ['title' => 'Jobs', 'url' => BASE_URL . '/jobs.php'],
    ['title' => 'Hospitals', 'url' => BASE_URL . '/companies.php'],
    ['title' => 'Clinical Departments', 'url' => BASE_URL . '/departments.php'],
    ['title' => 'Services', 'url' => BASE_URL . '/services.php'],
    ['title' => 'About', 'url' => BASE_URL . '/about.php'],
    ['title' => 'Contact', 'url' => BASE_URL . '/contact.php'],
];

// Pure Healthcare & Hospital Departments (No general engineering)
$healthcareDepartments = [
    'clinical' => [
        'name' => 'Doctors & Clinical Specialists',
        'icon' => 'stethoscope',
        'count' => '940+ Openings',
        'specialties' => [
            ['name' => 'General Medicine / Internal Medicine', 'slug' => 'general-medicine', 'jobs' => 164],
            ['name' => 'Interventional Cardiology & CTVS', 'slug' => 'cardiology', 'jobs' => 96],
            ['name' => 'General & Laparoscopic Surgery', 'slug' => 'general-surgery', 'jobs' => 112],
            ['name' => 'Pediatrics & Neonatology', 'slug' => 'pediatrics', 'jobs' => 128],
            ['name' => 'Obstetrics & Gynecology (OBGYN)', 'slug' => 'obgyn', 'jobs' => 105],
            ['name' => 'Orthopedics & Joint Replacement', 'slug' => 'orthopedics', 'jobs' => 88],
            ['name' => 'Neurology & Neurosurgery', 'slug' => 'neurology', 'jobs' => 64],
            ['name' => 'Medical & Surgical Oncology', 'slug' => 'oncology', 'jobs' => 76],
            ['name' => 'Gastroenterology & Hepatology', 'slug' => 'gastroenterology', 'jobs' => 58],
            ['name' => 'ENT / Otorhinolaryngology', 'slug' => 'ent', 'jobs' => 49],
        ]
    ],
    'nursing' => [
        'name' => 'Nursing Specialties',
        'icon' => 'heart-pulse',
        'count' => '780+ Openings',
        'specialties' => [
            ['name' => 'Critical Care & ICU Staff Nurse', 'slug' => 'icu-nursing', 'jobs' => 220],
            ['name' => 'Operation Theatre (OT) & Scrub Nurse', 'slug' => 'ot-nursing', 'jobs' => 145],
            ['name' => 'Emergency & Trauma Triage Nurse', 'slug' => 'er-nursing', 'jobs' => 98],
            ['name' => 'Neonatal & Pediatric ICU (NICU/PICU)', 'slug' => 'nicu-nursing', 'jobs' => 84],
            ['name' => 'Medical & Surgical Ward Nurse', 'slug' => 'med-surg-nursing', 'jobs' => 135],
            ['name' => 'Dialysis & Renal Care Nurse', 'slug' => 'dialysis-nursing', 'jobs' => 58],
            ['name' => 'Nursing Supervisors & Matrons', 'slug' => 'nursing-admin', 'jobs' => 40],
        ]
    ],
    'allied' => [
        'name' => 'Allied Health & Paramedical',
        'icon' => 'activity',
        'count' => '460+ Openings',
        'specialties' => [
            ['name' => 'Physiotherapy & Neuro-Rehabilitation', 'slug' => 'physiotherapy', 'jobs' => 125],
            ['name' => 'Cardiac Cath Lab Technologists', 'slug' => 'cardiology-tech', 'jobs' => 74],
            ['name' => 'Operation Theatre (OT) Technicians', 'slug' => 'ot-technicians', 'jobs' => 86],
            ['name' => 'Dialysis Technologists', 'slug' => 'dialysis-tech', 'jobs' => 54],
            ['name' => 'Perfusionists (Cardiothoracic Surgery)', 'slug' => 'perfusionist', 'jobs' => 32],
            ['name' => 'Occupational & Speech Therapists', 'slug' => 'rehab-therapy', 'jobs' => 48],
            ['name' => 'Emergency Medical Technicians (EMT)', 'slug' => 'emt', 'jobs' => 41],
        ]
    ],
    'diagnostics' => [
        'name' => 'Diagnostics, Pathology & Pharmacy',
        'icon' => 'microscope',
        'count' => '390+ Openings',
        'specialties' => [
            ['name' => 'Radiology & Medical Imaging (MRI/CT/USG)', 'slug' => 'radiology', 'jobs' => 110],
            ['name' => 'Pathology & Clinical Laboratory', 'slug' => 'pathology-lab', 'jobs' => 95],
            ['name' => 'Hospital & Clinical Pharmacy (Pharm.D)', 'slug' => 'pharmacy', 'jobs' => 118],
            ['name' => 'Clinical Biochemistry & Hematology', 'slug' => 'biochemistry', 'jobs' => 42],
            ['name' => 'Medical Records (MRD) & Clinical Coding', 'slug' => 'medical-records', 'jobs' => 25],
        ]
    ],
    'hospital_admin' => [
        'name' => 'Hospital Operations & Leadership',
        'icon' => 'briefcase',
        'count' => '240+ Openings',
        'specialties' => [
            ['name' => 'Hospital Operations & Administration (MHA)', 'slug' => 'hospital-admin', 'jobs' => 82],
            ['name' => 'Patient Care Coordinators & Front Office', 'slug' => 'patient-experience', 'jobs' => 65],
            ['name' => 'Infection Control Officers (JCI/NABH)', 'slug' => 'infection-control', 'jobs' => 38],
            ['name' => 'Dietetics & Clinical Nutrition', 'slug' => 'clinical-nutrition', 'jobs' => 55],
        ]
    ],
    'ayush' => [
        'name' => 'AYUSH & Integrative Medicine',
        'icon' => 'leaf',
        'count' => '170+ Openings',
        'specialties' => [
            ['name' => 'Ayurveda Physicians (BAMS/MD)', 'slug' => 'ayurveda', 'jobs' => 64],
            ['name' => 'Yoga & Naturopathy Consultants (BNYS)', 'slug' => 'yoga-naturopathy', 'jobs' => 46],
            ['name' => 'Homeopathy Doctors (BHMS/MD)', 'slug' => 'homeopathy', 'jobs' => 38],
            ['name' => 'Unani Medicine Specialists (BUMS)', 'slug' => 'unani', 'jobs' => 22],
        ]
    ]
];

// Dynamic Healthcare Hospitals (Verified Employers from Database)
$featuredCompanies = [];
if (!empty($con) && !$con->connect_error) {
    // Only verified hospitals (verified = 1)
    $resComp = $con->query("SELECT h.*, 
        (SELECT COUNT(*) FROM `jobs` WHERE (`hospital_id` = h.id OR `company` = h.name) AND `status` = 'Active') as openings_count
        FROM `hospitals` h 
        WHERE h.verified = 1 
        ORDER BY h.id DESC");
    if ($resComp) {
        while ($cRow = $resComp->fetch_assoc()) {
            $cRow['id'] = (int)$cRow['id'];
            $cRow['openings'] = (int)($cRow['openings_count'] ?? 0);
            $cRow['verified'] = true;
            if (empty($cRow['logo_text'])) {
                $cRow['logo_text'] = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cRow['name']), 0, 4));
            }
            if (empty($cRow['badge'])) {
                $cRow['badge'] = 'NABH & JCI Accredited';
            }
            if (empty($cRow['type'])) {
                $cRow['type'] = 'Quaternary Care Multi-Specialty Hospital';
            }
            if (empty($cRow['overview'])) {
                $cRow['overview'] = $cRow['name'] . ' is a premier verified healthcare establishment registered on HealthJobsGlobal.';
            }
            $featuredCompanies[] = $cRow;
        }
    }
}

// Dynamic Healthcare Jobs (Active Jobs from Verified Hospitals in Database)
$featuredJobs = [];
if (!empty($con) && !$con->connect_error) {
    // Clean up any lingering jobs from deleted hospitals
    cleanupOrphanJobs($con);

    // Only Active jobs belonging to active, verified hospitals
    $resJobs = $con->query("SELECT j.*, h.name as hospital_name, h.slug as hospital_slug, h.verified as hospital_verified, h.logo_text as hospital_logo
        FROM `jobs` j
        INNER JOIN `hospitals` h ON (j.hospital_id = h.id OR j.company = h.name)
        WHERE h.verified = 1 AND j.status = 'Active'
        ORDER BY j.featured DESC, j.id DESC");
    if ($resJobs) {
        while ($jRow = $resJobs->fetch_assoc()) {
            $jRow['id'] = (int)$jRow['id'];
            $jRow['company_id'] = (int)($jRow['hospital_id'] ?? 0);
            $jRow['company'] = $jRow['hospital_name'] ?: $jRow['company'];
            if (!empty($jRow['tags']) && is_string($jRow['tags'])) {
                $jRow['tags'] = array_map('trim', explode(',', $jRow['tags']));
            } elseif (empty($jRow['tags'])) {
                $jRow['tags'] = [];
            }
            $jRow['posted'] = !empty($jRow['created_at']) ? date('d M Y', strtotime($jRow['created_at'])) : (!empty($jRow['posted_at']) ? date('d M Y', strtotime($jRow['posted_at'])) : 'Recently');
            $jRow['featured'] = !empty($jRow['featured']);
            $jRow['urgent'] = !empty($jRow['urgent']);
            $featuredJobs[] = $jRow;
        }
    }
}

// Helper functions for easy querying (PHP 8.4 array_find compatible)
function getFeaturedJobs(int $limit = 6): array {
    global $featuredJobs;
    return array_slice($featuredJobs, 0, $limit);
}

function getJobById(int|string $id): array {
    global $con, $featuredJobs;
    if (!empty($con) && !$con->connect_error) {
        $stmt = $con->prepare("SELECT j.*, h.name as hospital_name, h.slug as hospital_slug, h.verified as hospital_verified, h.logo_text as hospital_logo
            FROM `jobs` j
            INNER JOIN `hospitals` h ON (j.hospital_id = h.id OR j.company = h.name)
            WHERE j.id = ? AND h.verified = 1 AND j.status = 'Active' LIMIT 1");
        if ($stmt) {
            $jId = (int)$id;
            $stmt->bind_param("i", $jId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $j = $res->fetch_assoc()) {
                $stmt->close();
                $j['id'] = (int)$j['id'];
                $j['company_id'] = (int)($j['hospital_id'] ?? 0);
                $j['company'] = $j['hospital_name'] ?: $j['company'];
                if (!empty($j['tags']) && is_string($j['tags'])) {
                    $j['tags'] = array_map('trim', explode(',', $j['tags']));
                } elseif (empty($j['tags'])) {
                    $j['tags'] = [];
                }
                $j['posted'] = !empty($j['created_at']) ? date('d M Y', strtotime($j['created_at'])) : (!empty($j['posted_at']) ? date('d M Y', strtotime($j['posted_at'])) : 'Recently');
                $j['featured'] = !empty($j['featured']);
                $j['urgent'] = !empty($j['urgent']);
                return $j;
            }
            $stmt->close();
        }
    }

    if (function_exists('array_find')) {
        $found = array_find($featuredJobs, fn(array $job): bool => (string)$job['id'] === (string)$id);
        if ($found !== null) {
            return $found;
        }
    } else {
        foreach ($featuredJobs as $job) {
            if ((string)$job['id'] === (string)$id) return $job;
        }
    }
    return [];
}

function getCompanyById(int|string $id): array {
    global $con, $featuredCompanies;
    if (!empty($con) && !$con->connect_error) {
        $stmt = $con->prepare("SELECT h.*, 
            (SELECT COUNT(*) FROM `jobs` WHERE (`hospital_id` = h.id OR `company` = h.name) AND `status` = 'Active') as openings_count
            FROM `hospitals` h WHERE h.id = ? AND h.verified = 1 LIMIT 1");
        if ($stmt) {
            $cId = (int)$id;
            $stmt->bind_param("i", $cId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $c = $res->fetch_assoc()) {
                $stmt->close();
                $c['id'] = (int)$c['id'];
                $c['openings'] = (int)($c['openings_count'] ?? 0);
                $c['verified'] = true;
                if (empty($c['logo_text'])) {
                    $c['logo_text'] = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $c['name']), 0, 4));
                }
                if (empty($c['badge'])) {
                    $c['badge'] = 'NABH & JCI Accredited';
                }
                if (empty($c['type'])) {
                    $c['type'] = 'Quaternary Care Multi-Specialty Hospital';
                }
                return $c;
            }
            $stmt->close();
        }
    }

    if (function_exists('array_find')) {
        $found = array_find($featuredCompanies, fn(array $c): bool => (string)$c['id'] === (string)$id);
        if ($found !== null) {
            return $found;
        }
    } else {
        foreach ($featuredCompanies as $c) {
            if ((string)$c['id'] === (string)$id) return $c;
        }
    }
    return [];
}
