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

// Session Management & Authentication State
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

function isLoggedIn() {
    return !empty($_SESSION['user']);
}

function isGuest() {
    return empty($_SESSION['user']);
}

function getLoggedInUser() {
    return $_SESSION['user'] ?? null;
}

// Navigation structure
$navLinks = [
    ['title' => 'Jobs', 'url' => BASE_URL . '/jobs.php', 'badge' => '2.5k+'],
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
        'name' => 'Aster Medcity & Aster Hospitals',
        'slug' => 'aster-dm-healthcare',
        'logo_text' => 'ASTER',
        'badge' => 'JCI & NABH Accredited',
        'type' => 'Quaternary Care Multi-Specialty Hospital',
        'location' => 'Kochi, Kerala & Dubai, UAE',
        'openings' => 54,
        'rating' => '4.8',
        'verified' => true,
        'overview' => 'Aster DM Healthcare operates premier quaternary hospitals across South India and the GCC with world-class clinical governance and robotic surgery centers.'
    ],
    [
        'id' => 2,
        'name' => 'Apollo Hospitals Enterprise',
        'slug' => 'apollo-hospitals',
        'logo_text' => 'APOLLO',
        'badge' => 'JCI Accredited Flagship',
        'type' => 'Quaternary Hospital Network',
        'location' => 'Chennai, Hyderabad, Bengaluru, Delhi',
        'openings' => 138,
        'rating' => '4.9',
        'verified' => true,
        'overview' => 'Recognized globally for clinical excellence, cardiac transplants, robotic da Vinci surgeries, and cutting-edge medical oncology.'
    ],
    [
        'id' => 3,
        'name' => 'Cleveland Clinic Abu Dhabi',
        'slug' => 'cleveland-clinic-abudhabi',
        'logo_text' => 'CCAD',
        'badge' => 'International Academic Medical Center',
        'type' => 'Quaternary Hospital',
        'location' => 'Al Maryah Island, Abu Dhabi, UAE',
        'openings' => 42,
        'rating' => '4.95',
        'verified' => true,
        'overview' => 'Extension of US Cleveland Clinic model providing access to the world\'s highest clinical protocols and research-driven healthcare.'
    ],
    [
        'id' => 4,
        'name' => 'KIMSHEALTH Multi-Specialty Hospital',
        'slug' => 'kimshealth',
        'logo_text' => 'KIMS',
        'badge' => 'ACHSI & NABH Accredited',
        'type' => 'Quaternary Care Teaching Hospital',
        'location' => 'Thiruvananthapuram, Kerala & Bahrain',
        'openings' => 46,
        'rating' => '4.8',
        'verified' => true,
        'overview' => 'One of South India\'s leading quaternary hospitals with over 900 beds, specializing in organ transplants, neurosciences, and critical care.'
    ],
    [
        'id' => 5,
        'name' => 'Amrita Institute of Medical Sciences (AIMS)',
        'slug' => 'amrita-hospital',
        'logo_text' => 'AIMS',
        'badge' => 'NABH & NABL Quaternary Center',
        'type' => 'Super-Specialty Medical Institute',
        'location' => 'Kochi, Kerala & Faridabad, Delhi NCR',
        'openings' => 68,
        'rating' => '4.85',
        'verified' => true,
        'overview' => 'Premier 1,350-bed quaternary care teaching hospital renowned globally for pediatric cardiology, bone marrow transplants, and complex surgery.'
    ],
    [
        'id' => 6,
        'name' => 'Lisie Hospital',
        'slug' => 'lisie-hospital',
        'logo_text' => 'LISIE',
        'badge' => 'NABH Certified Center of Excellence',
        'type' => 'Cardiac & Multi-Specialty Hospital',
        'location' => 'Kochi, Kerala',
        'openings' => 38,
        'rating' => '4.8',
        'verified' => true,
        'overview' => 'Pioneers in cardiothoracic surgery, interventional cardiology, and comprehensive medical care with over six decades of medical heritage.'
    ]
];

// Rich Healthcare Featured Jobs (Purely Hospital Medical & Nursing)
$featuredJobs = [
    [
        'id' => 101,
        'title' => 'Senior Consultant – Interventional Cardiology',
        'company' => 'Aster Medcity',
        'company_id' => 1,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Cardiology',
        'experience' => '8+ Years Post-DM/DNB',
        'employment_type' => 'Full Time · On-Site',
        'salary' => '₹42,00,000 - ₹55,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Active',
        'qualification' => 'MBBS, MD (Gen Med), DM / DNB (Cardiology)',
        'posted' => '1 day ago',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Cath Lab', 'Primary PCI', 'TAVI / Structural Heart'],
        'description' => 'We are seeking an experienced Interventional Cardiologist to lead high-volume Cath Lab procedures, complex coronary interventions, and participate in clinical mentorship.'
    ],
    [
        'id' => 102,
        'title' => 'Nurse In-Charge – Intensive Care Unit (ICU)',
        'company' => 'Apollo Hospitals Enterprise',
        'company_id' => 2,
        'location' => 'Hyderabad, Telangana, India',
        'department' => 'Nursing Specialties',
        'specialty' => 'Critical Care',
        'experience' => '4 - 7 Years in Level-3 ICU',
        'employment_type' => 'Full Time · Rotational Shifts',
        'salary' => '₹6,50,000 - ₹8,50,000 / year',
        'council_req' => 'Telangana State Nursing Council Registered',
        'qualification' => 'B.Sc Nursing / Post Basic B.Sc (Critical Care Certified)',
        'posted' => '2 days ago',
        'featured' => true,
        'urgent' => false,
        'tags' => ['Ventilator Management', 'Hemodynamic Monitoring', 'BLS & ACLS'],
        'description' => 'Lead a dedicated squad of critical care nurses in our 40-bed medical-surgical ICU. Ensure adherence to JCI clinical protocols and patient safety benchmarks.'
    ],
    [
        'id' => 103,
        'title' => 'Consultant Radiologist (MRI / Cross-Sectional Imaging)',
        'company' => 'Cleveland Clinic Abu Dhabi',
        'company_id' => 3,
        'location' => 'Abu Dhabi, United Arab Emirates',
        'department' => 'Diagnostics, Pathology & Pharmacy',
        'specialty' => 'Radiology',
        'experience' => '5+ Years Specialist Experience',
        'employment_type' => 'Full Time · Permanent Contract',
        'salary' => 'AED 65,000 - 85,000 / month (Tax Free)',
        'council_req' => 'DOH / HAAD Consultant License Eligible',
        'qualification' => 'MD / DNB / FRCR / American Board in Radiodiagnosis',
        'posted' => 'Just now',
        'featured' => true,
        'urgent' => true,
        'tags' => ['3T MRI', 'Dual-Source CT', 'Cardiac Imaging'],
        'description' => 'Exceptional opportunity for a fellowship-trained Radiologist to join a state-of-the-art imaging institute equipped with cutting-edge 3T MR and photon-counting CT technology.'
    ],
    [
        'id' => 104,
        'title' => 'Staff Nurse – Operation Theatre & Surgical Scrub',
        'company' => 'Lisie Hospital',
        'company_id' => 6,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Nursing Specialties',
        'specialty' => 'OT Nursing',
        'experience' => '3 - 6 Years in Cardiac / Ortho OT',
        'employment_type' => 'Full Time · Rotational Shifts',
        'salary' => '₹5,20,000 - ₹7,00,000 / year',
        'council_req' => 'Kerala Nursing Council (KNC) Registered',
        'qualification' => 'B.Sc Nursing / GNM with OT Specialization',
        'posted' => '2 days ago',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Aseptic Protocol', 'Cardiac Surgery Assist', 'CSSD Coordination'],
        'description' => 'Join our high-volume cardiothoracic surgical suites. Assist in open heart surgeries, valve replacements, and complex vascular repairs under renowned surgical faculty.'
    ],
    [
        'id' => 105,
        'title' => 'Consultant Medical Oncologist',
        'company' => 'KIMSHEALTH Multi-Specialty Hospital',
        'company_id' => 4,
        'location' => 'Thiruvananthapuram, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Oncology',
        'experience' => '4+ Years Post-DM/DNB',
        'employment_type' => 'Full Time',
        'salary' => '₹36,00,000 - ₹48,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD (Medicine), DM / DNB (Medical Oncology)',
        'posted' => '3 days ago',
        'featured' => true,
        'urgent' => false,
        'tags' => ['Chemotherapy Protocol', 'Immunotherapy', 'Tumor Board Review'],
        'description' => 'Direct outpatient oncology consultations, clinical chemotherapy regimens, immunotherapy trials, and lead weekly multidisciplinary tumor board rounds.'
    ],
    [
        'id' => 106,
        'title' => 'Senior Physiotherapist – Neuro-Rehabilitation',
        'company' => 'Amrita Institute of Medical Sciences (AIMS)',
        'company_id' => 5,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Allied Health & Paramedical',
        'specialty' => 'Physiotherapy',
        'experience' => '3 - 6 Years Experience',
        'employment_type' => 'Full Time · Day Shifts',
        'salary' => '₹5,50,000 - ₹7,50,000 / year',
        'council_req' => 'Kerala State Physiotherapy Council Registered',
        'qualification' => 'BPT + MPT (Neurology / Neuro-sciences)',
        'posted' => '4 days ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['Stroke Rehab', 'Robotic Gait Training', 'Spinal Cord Injury'],
        'description' => 'Provide advanced neuro-rehabilitative therapy to inpatients and outpatients suffering from traumatic brain injuries, stroke, and progressive neurological conditions.'
    ],
    [
        'id' => 107,
        'title' => 'Chief Perfusionist – Cardiothoracic Surgery',
        'company' => 'Lisie Hospital',
        'company_id' => 6,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Allied Health & Paramedical',
        'specialty' => 'Perfusion Technology',
        'experience' => '6 - 10 Years in Heart Surgery Unit',
        'employment_type' => 'Full Time',
        'salary' => '₹12,00,000 - ₹18,00,000 / year',
        'council_req' => 'Board of Cardiovascular Perfusion India Certified',
        'qualification' => 'B.Sc / M.Sc in Perfusion Technology',
        'posted' => '5 days ago',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Heart-Lung Machine', 'ECMO Management', 'IABP Monitoring'],
        'description' => 'Manage extra-corporeal circulation, ECMO circuits, and autotransfusion machines during complex CABG, pediatric cardiac corrections, and heart transplants.'
    ],
    [
        'id' => 108,
        'title' => 'Staff Nurse – Neonatal Intensive Care Unit (NICU)',
        'company' => 'Aster Medcity',
        'company_id' => 1,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Nursing Specialties',
        'specialty' => 'NICU Nursing',
        'experience' => '2 - 5 Years in Level-3 NICU',
        'employment_type' => 'Full Time · Rotational Shifts',
        'salary' => '₹4,80,000 - ₹6,50,000 / year',
        'council_req' => 'Kerala Nursing Council (KNC) Registered',
        'qualification' => 'B.Sc Nursing with Neonatal Care Fellowship / Certificate',
        'posted' => '6 days ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['Premature Neonate Care', 'CPAP / Surfactant', 'Phototherapy'],
        'description' => 'Provide comprehensive neonatal nursing to extreme pre-term infants and critically ill newborns in our 20-bed tertiary NICU with advanced developmental care protocols.'
    ],
    [
        'id' => 109,
        'title' => 'Clinical Pharmacist – Antibiotic Stewardship & ICU',
        'company' => 'Apollo Hospitals Enterprise',
        'company_id' => 2,
        'location' => 'Chennai, Tamil Nadu, India',
        'department' => 'Diagnostics, Pathology & Pharmacy',
        'specialty' => 'Clinical Pharmacy',
        'experience' => '2 - 5 Years in JCI Hospital',
        'employment_type' => 'Full Time',
        'salary' => '₹6,00,000 - ₹8,20,000 / year',
        'council_req' => 'Pharmacy Council of India (PCI) Registered',
        'qualification' => 'Pharm.D (Doctor of Pharmacy)',
        'posted' => '1 week ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['Drug-Drug Interaction', 'Dose TDM Calculation', 'Antibiotic Stewardship'],
        'description' => 'Collaborate with ICU intensivists and infectious disease consultants to optimize antimicrobial therapy, therapeutic drug monitoring, and patient discharge counseling.'
    ],
    [
        'id' => 110,
        'title' => 'Assistant Medical Administrator / Operations Manager',
        'company' => 'KIMSHEALTH Multi-Specialty Hospital',
        'company_id' => 4,
        'location' => 'Thiruvananthapuram, Kerala, India',
        'department' => 'Hospital Operations & Leadership',
        'specialty' => 'Hospital Administration',
        'experience' => '5 - 8 Years in Quaternary Hospital',
        'employment_type' => 'Full Time',
        'salary' => '₹14,00,000 - ₹18,50,000 / year',
        'council_req' => 'MHA / MBA in Hospital Management',
        'qualification' => 'MBBS/BAMS + MHA from recognized medical university',
        'posted' => '1 week ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['NABH Standards', 'Patient Throughput', 'Clinical Audit'],
        'description' => 'Oversee day-to-day inpatient bed allotment, emergency triage flow, clinical compliance, and coordinate medical superintendent audits across 40 clinical departments.'
    ],
    [
        'id' => 111,
        'title' => 'Consultant Emergency Medicine (MD / DNB / MRCEM)',
        'company' => 'Apollo Hospitals Enterprise',
        'company_id' => 2,
        'location' => 'Bengaluru, Karnataka, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Emergency Medicine',
        'experience' => '3 - 6 Years in Trauma Center',
        'employment_type' => 'Full Time · Rotational Emergency Roster',
        'salary' => '₹28,00,000 - ₹38,00,000 / year',
        'council_req' => 'Karnataka Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD / DNB (Emergency Medicine) / MRCEM (UK)',
        'posted' => '2 days ago',
        'featured' => true,
        'urgent' => true,
        'tags' => ['Polytrauma Care', 'Airway Management', 'Disaster Triage'],
        'description' => 'Lead emergency room resuscitative protocols, rapid sequence intubation, polytrauma resuscitation, and mass casualty emergency coordination.'
    ],
    [
        'id' => 112,
        'title' => 'Dialysis Technologist & Hemodialysis Coordinator',
        'company' => 'Aster Medcity',
        'company_id' => 1,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Allied Health & Paramedical',
        'specialty' => 'Dialysis Technology',
        'experience' => '2 - 5 Years in Nephrology Unit',
        'employment_type' => 'Full Time',
        'salary' => '₹4,20,000 - ₹5,80,000 / year',
        'council_req' => 'Kerala Allied Health Council Registered',
        'qualification' => 'B.Sc in Renal Dialysis Technology (RDT)',
        'posted' => '3 days ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['Hemodialysis', 'CRRT in ICU', 'RO Plant Maintenance'],
        'description' => 'Conduct maintenance hemodialysis, acute bedside CRRT in intensive care units, cannulate AV fistulas, and monitor dialysis water treatment systems.'
    ],
    [
        'id' => 113,
        'title' => 'Consultant Anesthesiologist & Neuro-Anesthesia Lead',
        'company' => 'Lisie Hospital',
        'company_id' => 6,
        'location' => 'Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Anesthesiology',
        'experience' => '5+ Years Post-MD/DNB',
        'employment_type' => 'Full Time',
        'salary' => '₹32,00,000 - ₹44,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD / DNB (Anesthesiology) with Neuro/Cardiac Fellowship',
        'posted' => '4 days ago',
        'featured' => true,
        'urgent' => false,
        'tags' => ['Ultrasound Guided Blocks', 'Cardiac Anesthesia', 'Difficult Airway'],
        'description' => 'Manage anesthetic depth during complex cardiothoracic, vascular, and robotic surgeries. Lead post-operative acute pain services and difficult airway carts.'
    ],
    [
        'id' => 114,
        'title' => 'Ayurveda Physician & Panchakarma Specialist (BAMS/MD)',
        'company' => 'Amrita Institute of Medical Sciences (AIMS)',
        'company_id' => 5,
        'location' => 'Faridabad, Delhi NCR, India',
        'department' => 'AYUSH & Integrative Medicine',
        'specialty' => 'Ayurveda & Integrative Medicine',
        'experience' => '3 - 7 Years Clinical Practice',
        'employment_type' => 'Full Time',
        'salary' => '₹8,50,000 - ₹12,00,000 / year',
        'council_req' => 'National Commission for Indian System of Medicine (NCISM)',
        'qualification' => 'BAMS, MD (Ayurveda Panchakarma / Kaya Chikitsa)',
        'posted' => '5 days ago',
        'featured' => false,
        'urgent' => false,
        'tags' => ['Integrative Medicine', 'Panchakarma Protocol', 'Lifestyle Disorders'],
        'description' => 'Practice holistic integrative consultations in conjunction with modern medical specialists for chronic arthritis, metabolic syndrome, and autoimmune recovery.'
    ]
];

// Helper functions for easy querying
function getFeaturedJobs($limit = 6) {
    global $featuredJobs;
    return array_slice($featuredJobs, 0, $limit);
}

function getJobById($id) {
    global $featuredJobs;
    foreach ($featuredJobs as $job) {
        if ($job['id'] == $id) return $job;
    }
    return $featuredJobs[0]; // fallback
}

function getCompanyById($id) {
    global $featuredCompanies;
    foreach ($featuredCompanies as $c) {
        if ($c['id'] == $id) return $c;
    }
    return $featuredCompanies[0];
}
