<?php
/**
 * Setup Fresh Data:
 * 1. Resets hospitals table to ONLY the 2 testing hospitals (Rajagiri & VPS Lakeshore)
 * 2. Posts 2 clinical job vacancies for each hospital (Total 4 jobs)
 * 3. Keeps the 2 fresh candidate registrations (Dr. Anjali Nair & Sister Preethi Thomas)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'actions' => [],
    'errors' => []
];

if (!$con || $con->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $con->connect_error]);
    exit;
}

// 1. Clear All Existing Hospitals & Reset
$con->query("DELETE FROM `hospitals`");
$con->query("ALTER TABLE `hospitals` AUTO_INCREMENT = 1");
$results['actions'][] = 'Cleared old 6 dummy hospitals from `hospitals` table.';

// 2. Clear All Existing Jobs & Reset
$con->query("DELETE FROM `jobs`");
$con->query("ALTER TABLE `jobs` AUTO_INCREMENT = 101");
$results['actions'][] = 'Cleared old dummy jobs from `jobs` table.';

// 3. Clear Candidates & Insert 2 Fresh Candidates
$con->query("DELETE FROM `candidates`");
$con->query("ALTER TABLE `candidates` AUTO_INCREMENT = 1");
$passHash = password_hash('Password@123', PASSWORD_DEFAULT);

$cand1 = [
    'name' => 'Dr. Anjali Nair',
    'email' => 'anjali.nair@healthmail.com',
    'password' => $passHash,
    'phone' => '+91 98470 12345',
    'degree' => 'MBBS, MD (General Medicine)',
    'specialty' => 'General Medicine',
    'experience' => '5+ Years Post-MD',
    'council_reg' => 'TCMC-2023-45892',
    'location' => 'Kochi, Kerala',
    'status' => 'Verified'
];

$cand2 = [
    'name' => 'Sister Preethi Thomas',
    'email' => 'preethi.thomas@nursemail.com',
    'password' => $passHash,
    'phone' => '+91 97455 67890',
    'degree' => 'B.Sc Nursing (Critical Care Certified)',
    'specialty' => 'Critical Care Nursing',
    'experience' => '4 Years in Level-3 ICU',
    'council_reg' => 'KNC-2022-88214',
    'location' => 'Ernakulam, Kerala',
    'status' => 'Verified'
];

$stmtC = $con->prepare("INSERT INTO `candidates` (`name`, `email`, `password`, `phone`, `degree`, `specialty`, `experience`, `council_reg`, `location`, `status`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if ($stmtC) {
    $stmtC->bind_param("ssssssssss", $cand1['name'], $cand1['email'], $cand1['password'], $cand1['phone'], $cand1['degree'], $cand1['specialty'], $cand1['experience'], $cand1['council_reg'], $cand1['location'], $cand1['status']);
    $stmtC->execute();
    $stmtC->bind_param("ssssssssss", $cand2['name'], $cand2['email'], $cand2['password'], $cand2['phone'], $cand2['degree'], $cand2['specialty'], $cand2['experience'], $cand2['council_reg'], $cand2['location'], $cand2['status']);
    $stmtC->execute();
    $stmtC->close();
    $results['actions'][] = 'Registered 2 candidates: Dr. Anjali Nair and Sister Preethi Thomas.';
}

// 4. Insert ONLY the 2 Hospitals (Rajagiri & VPS Lakeshore)
$emp1 = [
    'id' => 1,
    'name' => 'Rajagiri Hospital',
    'slug' => 'rajagiri-hospital',
    'logo_text' => 'RAJAG',
    'badge' => 'NABH & JCI Accredited',
    'type' => 'Quaternary Care Multi-Specialty Hospital',
    'location' => 'Chunangamvely, Aluva, Kochi, Kerala',
    'openings' => 2,
    'rating' => '4.85',
    'verified' => 1,
    'contact_email' => 'hr@rajagirihospital.com',
    'admin_email' => 'careers@rajagirihospital.com',
    'admin_name' => 'Fr. Johnson Palappillil (Director)',
    'phone' => '+91 484 290 5000',
    'password' => $passHash,
    'website' => 'https://www.rajagirihospital.com',
    'overview' => 'Rajagiri Hospital is an internationally accredited quaternary care hospital in Kochi with 500+ beds, advanced cardiac care, multi-organ transplants, and oncology.'
];

$emp2 = [
    'id' => 2,
    'name' => 'VPS Lakeshore Hospital',
    'slug' => 'vps-lakeshore',
    'logo_text' => 'LAKES',
    'badge' => 'NABH & NABL Accredited',
    'type' => 'Multi-Super Speciality Quaternary Care',
    'location' => 'NH 66 Bypass, Nettoor, Maradu, Kochi, Kerala',
    'openings' => 2,
    'rating' => '4.8',
    'verified' => 1,
    'contact_email' => 'hr@lakeshorehospital.com',
    'admin_email' => 'recruitment@lakeshorehospital.com',
    'admin_name' => 'Dr. Philip George (Head of Medical Talent)',
    'phone' => '+91 484 270 1011',
    'password' => $passHash,
    'website' => 'https://www.lakeshorehospital.com',
    'overview' => 'VPS Lakeshore Hospital is one of the largest tertiary/quaternary care hospitals in South India specializing in gastroenterology, organ transplant, cardiology, and joint replacement.'
];

$stmtH = $con->prepare("INSERT INTO `hospitals` (`id`, `name`, `slug`, `logo_text`, `badge`, `type`, `location`, `openings`, `rating`, `verified`, `contact_email`, `admin_email`, `admin_name`, `phone`, `password`, `website`, `overview`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if ($stmtH) {
    // Insert Emp 1
    $stmtH->bind_param("issssssisisssssss", $emp1['id'], $emp1['name'], $emp1['slug'], $emp1['logo_text'], $emp1['badge'], $emp1['type'], $emp1['location'], $emp1['openings'], $emp1['rating'], $emp1['verified'], $emp1['contact_email'], $emp1['admin_email'], $emp1['admin_name'], $emp1['phone'], $emp1['password'], $emp1['website'], $emp1['overview']);
    $stmtH->execute();
    $results['actions'][] = "Saved Hospital 1: {$emp1['name']} (ID: {$emp1['id']})";

    // Insert Emp 2
    $stmtH->bind_param("issssssisisssssss", $emp2['id'], $emp2['name'], $emp2['slug'], $emp2['logo_text'], $emp2['badge'], $emp2['type'], $emp2['location'], $emp2['openings'], $emp2['rating'], $emp2['verified'], $emp2['contact_email'], $emp2['admin_email'], $emp2['admin_name'], $emp2['phone'], $emp2['password'], $emp2['website'], $emp2['overview']);
    $stmtH->execute();
    $results['actions'][] = "Saved Hospital 2: {$emp2['name']} (ID: {$emp2['id']})";

    $stmtH->close();
} else {
    $results['errors'][] = 'Hospital statement prep failed: ' . $con->error;
}

// 5. Post 2 Job Vacancies for EACH Hospital (Total 4 Jobs)
$jobs = [
    // 2 Vacancies for Rajagiri Hospital (ID 1)
    [
        'id' => 101,
        'title' => 'Senior Consultant – Interventional Cardiology',
        'hospital_id' => 1,
        'company' => 'Rajagiri Hospital',
        'location' => 'Aluva, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Cardiology',
        'experience' => '5+ Years Post-DM/DNB',
        'employment_type' => 'Full Time · On-Site',
        'salary' => '₹45,00,000 - ₹55,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Active',
        'qualification' => 'MBBS, MD (Gen Med), DM / DNB (Cardiology)',
        'description' => 'We are seeking an experienced Interventional Cardiologist to lead high-volume Cath Lab procedures, complex coronary interventions, and participate in clinical mentorship.',
        'tags' => 'Cath Lab,Primary PCI,TAVI',
        'status' => 'Active',
        'featured' => 1,
        'urgent' => 1,
        'views' => 140,
        'applications_count' => 5
    ],
    [
        'id' => 102,
        'title' => 'Nurse In-Charge – Intensive Care Unit (ICU)',
        'hospital_id' => 1,
        'company' => 'Rajagiri Hospital',
        'location' => 'Aluva, Kochi, Kerala, India',
        'department' => 'Nursing Specialties',
        'specialty' => 'Critical Care',
        'experience' => '3 - 6 Years in Level-3 ICU',
        'employment_type' => 'Full Time · Rotational Shifts',
        'salary' => '₹5,50,000 - ₹7,50,000 / year',
        'council_req' => 'Kerala Nursing Council (KNC) Registered',
        'qualification' => 'B.Sc Nursing / Post Basic B.Sc (Critical Care Certified)',
        'description' => 'Lead a dedicated squad of critical care nurses in our 40-bed medical-surgical ICU. Ensure adherence to JCI clinical protocols and patient safety benchmarks.',
        'tags' => 'Ventilator Management,Hemodynamic Monitoring,BLS & ACLS',
        'status' => 'Active',
        'featured' => 1,
        'urgent' => 0,
        'views' => 95,
        'applications_count' => 8
    ],

    // 2 Vacancies for VPS Lakeshore Hospital (ID 2)
    [
        'id' => 103,
        'title' => 'Consultant Medical Gastroenterologist',
        'hospital_id' => 2,
        'company' => 'VPS Lakeshore Hospital',
        'location' => 'Nettoor, Maradu, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Gastroenterology',
        'experience' => '4+ Years Post-DM/DNB',
        'employment_type' => 'Full Time · Permanent Contract',
        'salary' => '₹40,00,000 - ₹50,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD (Medicine), DM / DNB (Medical Gastroenterology)',
        'description' => 'Direct outpatient & inpatient gastroenterology services, therapeutic endoscopies, ERCP, and lead liver transplant multidisciplinary clinical rounds.',
        'tags' => 'Therapeutic Endoscopy,ERCP,Liver Disease',
        'status' => 'Active',
        'featured' => 1,
        'urgent' => 1,
        'views' => 210,
        'applications_count' => 6
    ],
    [
        'id' => 104,
        'title' => 'Consultant Emergency Medicine & Trauma Care',
        'hospital_id' => 2,
        'company' => 'VPS Lakeshore Hospital',
        'location' => 'Nettoor, Maradu, Kochi, Kerala, India',
        'department' => 'Doctors & Clinical Specialists',
        'specialty' => 'Emergency Medicine',
        'experience' => '3 - 6 Years in Level-1 Trauma Center',
        'employment_type' => 'Full Time · Rotational Emergency Roster',
        'salary' => '₹32,00,000 - ₹42,00,000 / year',
        'council_req' => 'Travancore-Cochin Medical Council / NMC Registered',
        'qualification' => 'MBBS, MD / DNB (Emergency Medicine) / MRCEM',
        'description' => 'Manage acute polytrauma triage, resuscitation protocols, emergency ultrasound (POCUS), and critical airway procedures in a high-volume emergency department.',
        'tags' => 'Polytrauma,POCUS Ultrasound,Airway Management',
        'status' => 'Active',
        'featured' => 1,
        'urgent' => 1,
        'views' => 165,
        'applications_count' => 4
    ]
];

$stmtJ = $con->prepare("INSERT INTO `jobs` (`id`, `title`, `hospital_id`, `company`, `location`, `department`, `specialty`, `experience`, `employment_type`, `salary`, `council_req`, `qualification`, `description`, `tags`, `status`, `featured`, `urgent`, `views`, `applications_count`, `posted_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if ($stmtJ) {
    foreach ($jobs as $j) {
        $stmtJ->bind_param("isisssssssssssiiiii", $j['id'], $j['title'], $j['hospital_id'], $j['company'], $j['location'], $j['department'], $j['specialty'], $j['experience'], $j['employment_type'], $j['salary'], $j['council_req'], $j['qualification'], $j['description'], $j['tags'], $j['status'], $j['featured'], $j['urgent'], $j['views'], $j['applications_count']);
        $stmtJ->execute();
        $results['actions'][] = "Posted Job {$j['id']}: '{$j['title']}' for {$j['company']}";
    }
    $stmtJ->close();
} else {
    $results['errors'][] = 'Job statement prep failed: ' . $con->error;
}

// 6. Summary check
$resH = $con->query("SELECT id, name, openings, verified FROM `hospitals`");
$hList = [];
while ($row = $resH->fetch_assoc()) {
    $hList[] = $row;
}

$resJ = $con->query("SELECT id, title, company FROM `jobs`");
$jList = [];
while ($row = $resJ->fetch_assoc()) {
    $jList[] = $row;
}

$resC = $con->query("SELECT id, name, email FROM `candidates`");
$cList = [];
while ($row = $resC->fetch_assoc()) {
    $cList[] = $row;
}

$results['summary'] = [
    'hospitals_count' => count($hList),
    'hospitals' => $hList,
    'jobs_count' => count($jList),
    'jobs' => $jList,
    'candidates_count' => count($cList),
    'candidates' => $cList
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
