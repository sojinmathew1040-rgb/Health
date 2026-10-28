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

// Rich Healthcare Hospitals (Employers)
$featuredCompanies = [
    [
        'id' => 1,
        'name' => 'Rajagiri Hospital',
        'slug' => 'rajagiri-hospital',
        'logo_text' => 'RAJAG',
        'badge' => 'NABH & JCI Accredited',
        'type' => 'Quaternary Care Multi-Specialty Hospital',
        'location' => 'Chunangamvely, Aluva, Kochi, Kerala',
        'openings' => 2,
        'rating' => '4.85',
        'verified' => true,
        'overview' => 'Rajagiri Hospital is an internationally accredited quaternary care hospital in Kochi with 500+ beds, advanced cardiac care, multi-organ transplants, and oncology.'
    ],
    [
        'id' => 2,
        'name' => 'VPS Lakeshore Hospital',
        'slug' => 'vps-lakeshore',
        'logo_text' => 'LAKES',
        'badge' => 'NABH & NABL Accredited',
        'type' => 'Multi-Super Speciality Quaternary Care',
        'location' => 'NH 66 Bypass, Nettoor, Maradu, Kochi, Kerala',
        'openings' => 2,
        'rating' => '4.8',
        'verified' => true,
        'overview' => 'VPS Lakeshore Hospital is one of the largest tertiary/quaternary care hospitals in South India specializing in gastroenterology, organ transplant, cardiology, and joint replacement.'
    ]
];

// Rich Healthcare Featured Jobs (Purely Hospital Medical & Nursing - 2 per Hospital)
$featuredJobs = [
    // 2 Jobs for Rajagiri Hospital (ID 1)
    [
        'id' => 101,
        'title' => 'Senior Consultant – Interventional Cardiology',
        'company' => 'Rajagiri Hospital',
        'company_id' => 1,
        'location' => 'Aluva, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Cardiology',
        'experience' => '5+ Years Post-DM/DNB',
        'employment_type' => 'Full Time · On-Site',
        'salary' => '₹45,00,000 - ₹55,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Active',
        'qualification' => 'MBBS, MD (Gen Med), DM / DNB (Cardiology)',
        'posted' => 'Just now',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Cath Lab', 'Primary PCI', 'TAVI / Structural Heart'],
        'description' => 'We are seeking an experienced Interventional Cardiologist to lead high-volume Cath Lab procedures, complex coronary interventions, and participate in clinical mentorship.'
    ],
    [
        'id' => 102,
        'title' => 'Nurse In-Charge – Intensive Care Unit (ICU)',
        'company' => 'Rajagiri Hospital',
        'company_id' => 1,
        'location' => 'Aluva, Kochi, Kerala, India',
        'department' => 'Nursing Specialties',
        'specialty' => 'Critical Care',
        'experience' => '3 - 6 Years in Level-3 ICU',
        'employment_type' => 'Full Time · Rotational Shifts',
        'salary' => '₹5,50,000 - ₹7,50,000 / year',
        'council_req' => 'Kerala Nursing Council (KNC) Registered',
        'qualification' => 'B.Sc Nursing / Post Basic B.Sc (Critical Care Certified)',
        'posted' => '1 day ago',
        'featured' => true,
        'urgent' => false,
        'tags' => ['Ventilator Management', 'Hemodynamic Monitoring', 'BLS & ACLS'],
        'description' => 'Lead a dedicated squad of critical care nurses in our 40-bed medical-surgical ICU. Ensure adherence to JCI clinical protocols and patient safety benchmarks.'
    ],

    // 2 Jobs for VPS Lakeshore Hospital (ID 2)
    [
        'id' => 103,
        'title' => 'Consultant Medical Gastroenterologist',
        'company' => 'VPS Lakeshore Hospital',
        'company_id' => 2,
        'location' => 'Nettoor, Maradu, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Gastroenterology',
        'experience' => '4+ Years Post-DM/DNB',
        'employment_type' => 'Full Time · Permanent Contract',
        'salary' => '₹40,00,000 - ₹50,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD (Medicine), DM / DNB (Medical Gastroenterology)',
        'posted' => 'Just now',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Therapeutic Endoscopy', 'ERCP', 'Liver Disease'],
        'description' => 'Direct outpatient & inpatient gastroenterology services, therapeutic endoscopies, ERCP, and lead liver transplant multidisciplinary clinical rounds.'
    ],
    [
        'id' => 104,
        'title' => 'Consultant Emergency Medicine & Trauma Care',
        'company' => 'VPS Lakeshore Hospital',
        'company_id' => 2,
        'location' => 'Nettoor, Maradu, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Emergency Medicine',
        'experience' => '3 - 6 Years in Level-1 Trauma Center',
        'employment_type' => 'Full Time · Rotational Emergency Roster',
        'salary' => '₹32,00,000 - ₹42,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD / DNB (Emergency Medicine) / MRCEM',
        'posted' => '1 day ago',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Polytrauma', 'POCUS Ultrasound', 'Emergency Resuscitation'],
        'description' => 'Manage acute polytrauma triage, resuscitation protocols, emergency ultrasound (POCUS), and critical airway procedures in a high-volume emergency department.'
    ]
];

// Helper functions for easy querying (PHP 8.4 array_find compatible)
function getFeaturedJobs(int $limit = 6): array {
    global $featuredJobs;
    return array_slice($featuredJobs, 0, $limit);
}

function getJobById(int|string $id): array {
    global $featuredJobs;
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
    return $featuredJobs[0] ?? []; // fallback
}

function getCompanyById(int|string $id): array {
    global $featuredCompanies;
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
    return $featuredCompanies[0] ?? [];
}
