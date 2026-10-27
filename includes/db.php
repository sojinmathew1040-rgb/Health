<?php
/**
 * HealthJobsGlobal - MySQL Database Connection
 * Single centralized database connection for entire application
 */

//local server

$servername = "localhost";
$username = "root";
$password ="";
$dbname = "healthjobs";


// Create connection
$con = @new mysqli($servername, $username, $password, $dbname);

// If database does not exist, automatically create it and reconnect
if ($con->connect_error) {
    $rootConn = @new mysqli($servername, $username, $password);
    if ($rootConn && !$rootConn->connect_error) {
        $rootConn->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $rootConn->close();
        $con = @new mysqli($servername, $username, $password, $dbname);
    }
}

if (!$con->connect_error) {
    $con->set_charset("utf8");
}

/**
 * Initialize Schema and Seed Data if tables are missing
 */
function initAdminDatabase($con) {
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

    // Seed Jobs
    $jobs = [
        [101, 'Senior Consultant – Interventional Cardiology', 1, 'Aster Medcity', 'Kochi, Kerala, India', 'Doctors & Clinical Specialists', 'Cardiology', '8+ Years Post-DM/DNB', 'Full Time · On-Site', '₹42,00,000 - ₹55,00,000 / year', 'Travancore-Cochin Medical Council / NMC Active', 'MBBS, MD (Gen Med), DM / DNB (Cardiology)', 'We are seeking an experienced Interventional Cardiologist to lead high-volume Cath Lab procedures, complex coronary interventions, and participate in clinical mentorship.', 'Cath Lab,Primary PCI,TAVI', 'Active', 1, 1, 1420, 18],
        [102, 'Nurse In-Charge – Intensive Care Unit (ICU)', 2, 'Apollo Hospitals Enterprise', 'Hyderabad, Telangana, India', 'Nursing Specialties', 'Critical Care', '4 - 7 Years in Level-3 ICU', 'Full Time · Rotational Shifts', '₹6,50,000 - ₹8,50,000 / year', 'Telangana State Nursing Council Registered', 'B.Sc Nursing / Post Basic B.Sc (Critical Care Certified)', 'Lead a dedicated squad of critical care nurses in our 40-bed medical-surgical ICU. Ensure adherence to JCI clinical protocols and patient safety benchmarks.', 'Ventilator Management,Hemodynamic Monitoring,BLS & ACLS', 'Active', 1, 0, 980, 34],
        [103, 'Consultant Radiologist (MRI / Cross-Sectional Imaging)', 3, 'Cleveland Clinic Abu Dhabi', 'Abu Dhabi, United Arab Emirates', 'Diagnostics, Pathology & Pharmacy', 'Radiology', '5+ Years Specialist Experience', 'Full Time · Permanent Contract', 'AED 65,000 - 85,000 / month (Tax Free)', 'DOH / HAAD Consultant License Eligible', 'MD / DNB / FRCR / American Board in Radiodiagnosis', 'Exceptional opportunity for a fellowship-trained Radiologist to join a state-of-the-art imaging institute equipped with cutting-edge 3T MR and photon-counting CT technology.', '3T MRI,Dual-Source CT,Cardiac Imaging', 'Active', 1, 1, 2140, 26],
        [104, 'Staff Nurse – Operation Theatre & Surgical Scrub', 6, 'Lisie Hospital', 'Kochi, Kerala, India', 'Nursing Specialties', 'OT Nursing', '3 - 6 Years in Cardiac / Ortho OT', 'Full Time · Rotational Shifts', '₹5,20,000 - ₹7,00,000 / year', 'Kerala Nursing Council (KNC) Registered', 'B.Sc Nursing / GNM with OT Specialization', 'Join our high-volume cardiothoracic surgical suites. Assist in open heart surgeries, valve replacements, and complex vascular repairs under renowned surgical faculty.', 'Aseptic Protocol,Cardiac Surgery Assist,CSSD Coordination', 'Active', 1, 1, 890, 19],
        [105, 'Consultant Medical Oncologist', 4, 'KIMSHEALTH Multi-Specialty Hospital', 'Thiruvananthapuram, Kerala, India', 'Doctors & Clinical Specialists', 'Oncology', '4+ Years Post-DM/DNB', 'Full Time', '₹36,00,000 - ₹48,00,000 / year', 'Travancore-Cochin Medical Council / NMC Registered', 'MBBS, MD (Medicine), DM / DNB (Medical Oncology)', 'Direct outpatient oncology consultations, clinical chemotherapy regimens, immunotherapy trials, and lead weekly multidisciplinary tumor board rounds.', 'Chemotherapy Protocol,Immunotherapy,Tumor Board Review', 'Active', 1, 0, 1150, 12],
        [106, 'Senior Physiotherapist – Neuro-Rehabilitation', 5, 'Amrita Institute of Medical Sciences (AIMS)', 'Kochi, Kerala, India', 'Allied Health & Paramedical', 'Physiotherapy', '3 - 6 Years Experience', 'Full Time · Day Shifts', '₹5,50,000 - ₹7,50,000 / year', 'Kerala State Physiotherapy Council Registered', 'BPT + MPT (Neurology / Neuro-sciences)', 'Provide advanced neuro-rehabilitative therapy to inpatients and outpatients suffering from traumatic brain injuries, stroke, and progressive neurological conditions.', 'Stroke Rehab,Robotic Gait Training,Spinal Cord Injury', 'Active', 0, 0, 640, 15],
        [107, 'Chief Perfusionist – Cardiothoracic Surgery', 6, 'Lisie Hospital', 'Kochi, Kerala, India', 'Allied Health & Paramedical', 'Perfusion Technology', '6 - 10 Years in Heart Surgery Unit', 'Full Time', '₹12,00,000 - ₹18,00,000 / year', 'Board of Cardiovascular Perfusion India Certified', 'B.Sc / M.Sc in Perfusion Technology', 'Manage extra-corporeal circulation, ECMO circuits, and autotransfusion machines during complex CABG, pediatric cardiac corrections, and heart transplants.', 'Heart-Lung Machine,ECMO Management,IABP Monitoring', 'Active', 1, 1, 720, 8],
        [108, 'Staff Nurse – Neonatal Intensive Care Unit (NICU)', 1, 'Aster Medcity', 'Kochi, Kerala, India', 'Nursing Specialties', 'NICU Nursing', '2 - 5 Years in Level-3 NICU', 'Full Time · Rotational Shifts', '₹4,80,000 - ₹6,50,000 / year', 'Kerala Nursing Council (KNC) Registered', 'B.Sc Nursing with Neonatal Care Fellowship', 'Provide comprehensive neonatal nursing to extreme pre-term infants and critically ill newborns in our 20-bed tertiary NICU with advanced developmental care protocols.', 'Premature Neonate Care,CPAP,Phototherapy', 'Active', 0, 0, 580, 22],
        [109, 'Clinical Pharmacist – Antibiotic Stewardship & ICU', 2, 'Apollo Hospitals Enterprise', 'Chennai, Tamil Nadu, India', 'Diagnostics, Pathology & Pharmacy', 'Clinical Pharmacy', '2 - 5 Years in JCI Hospital', 'Full Time', '₹6,00,000 - ₹8,20,000 / year', 'Pharmacy Council of India (PCI) Registered', 'Pharm.D (Doctor of Pharmacy)', 'Collaborate with ICU intensivists and infectious disease consultants to optimize antimicrobial therapy, therapeutic drug monitoring, and patient discharge counseling.', 'Drug-Drug Interaction,Dose TDM,Antibiotic Stewardship', 'Active', 0, 0, 490, 14],
        [110, 'Assistant Medical Administrator / Operations Manager', 4, 'KIMSHEALTH Multi-Specialty Hospital', 'Thiruvananthapuram, Kerala, India', 'Hospital Operations & Leadership', 'Hospital Administration', '5 - 8 Years in Quaternary Hospital', 'Full Time', '₹14,00,000 - ₹18,50,000 / year', 'MHA / MBA in Hospital Management', 'MBBS/BAMS + MHA from recognized medical university', 'Oversee day-to-day inpatient bed allotment, emergency triage flow, clinical compliance, and coordinate medical superintendent audits across 40 clinical departments.', 'NABH Standards,Patient Throughput,Clinical Audit', 'Active', 0, 0, 810, 21],
        [111, 'Consultant Emergency Medicine (MD / DNB / MRCEM)', 2, 'Apollo Hospitals Enterprise', 'Bengaluru, Karnataka, India', 'Doctors & Clinical Specialists', 'Emergency Medicine', '3 - 6 Years in Trauma Center', 'Full Time · Rotational Emergency Roster', '₹28,00,000 - ₹38,00,000 / year', 'Karnataka Medical Council / NMC Registered', 'MBBS, MD / DNB (Emergency Medicine) / MRCEM (UK)', 'Manage acute resuscitations, polytrauma triage, point-of-care ultrasound (POCUS), and disaster response in a Level-1 comprehensive emergency department.', 'Polytrauma,POCUS Ultrasound,Airway Management', 'Active', 1, 1, 1310, 29]
    ];

    $stmtJ = $con->prepare("INSERT IGNORE INTO `jobs` (`id`, `title`, `hospital_id`, `company`, `location`, `department`, `specialty`, `experience`, `employment_type`, `salary`, `council_req`, `qualification`, `description`, `tags`, `status`, `featured`, `urgent`, `views`, `applications_count`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmtJ) {
        foreach ($jobs as $j) {
            $stmtJ->bind_param("isisssssssssssiiiii", $j[0], $j[1], $j[2], $j[3], $j[4], $j[5], $j[6], $j[7], $j[8], $j[9], $j[10], $j[11], $j[12], $j[13], $j[14], $j[15], $j[16], $j[17], $j[18]);
            $stmtJ->execute();
        }
        $stmtJ->close();
    }

    // Seed Candidates
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

    // Seed Inbound Applications
    $applications = [
        [1, 101, 'Senior Consultant – Interventional Cardiology', 'Aster Medcity', 'Dr. Arjun Menon', 'dr.arjun@hospital.com', '+91 98470 12345', 'MD, DM (Cardiology)', '8+ Years', 'TCMC 45892', 'Looking forward to leading cath lab clinical operations and transcatheter therapies.', 'Shortlisted'],
        [2, 102, 'Nurse In-Charge – Intensive Care Unit (ICU)', 'Apollo Hospitals Enterprise', 'Sister Preethi Thomas', 'preethi.thomas@nursemail.com', '+91 97455 67890', 'B.Sc Nursing', '5 Years', 'KNC 88214', 'Experienced in mechanical ventilation weaning and hemodynamic surveillance.', 'Interview Scheduled'],
        [3, 111, 'Consultant Emergency Medicine (MD / DNB / MRCEM)', 'Apollo Hospitals Enterprise', 'Dr. Rajesh Nair', 'dr.rajesh.nair@healthmail.com', '+91 94471 23456', 'MD (Emergency Med)', '6 Years', 'KMC 67341', 'Level-1 trauma center team lead with extensive airway management record.', 'Reviewing'],
        [4, 107, 'Chief Perfusionist – Cardiothoracic Surgery', 'Lisie Hospital', 'Naveen Kurian', 'naveen.perfusion@lisiealumni.org', '+91 99460 98765', 'M.Sc Perfusion', '7 Years', 'BCPI 1290', 'Extensive experience with ECMO and pediatric open-heart perfusion procedures.', 'Applied'],
        [5, 109, 'Clinical Pharmacist – Antibiotic Stewardship & ICU', 'Apollo Hospitals Enterprise', 'Ananya Sharma', 'ananya.sharma@pharmacycare.com', '+91 98112 34567', 'Pharm.D', '3 Years', 'PCI 55421', 'Focused on TDM calculations and antimicrobial resistance reduction.', 'Offered']
    ];

    $stmtA = $con->prepare("INSERT IGNORE INTO `applications` (`id`, `job_id`, `job_title`, `hospital_name`, `candidate_name`, `candidate_email`, `candidate_phone`, `qualification`, `experience`, `council_reg`, `cover_letter`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmtA) {
        foreach ($applications as $a) {
            $stmtA->bind_param("iissssssssss", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9], $a[10], $a[11]);
            $stmtA->execute();
        }
        $stmtA->close();
    }

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
}
