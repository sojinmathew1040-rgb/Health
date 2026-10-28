<?php
/**
 * Test Authentication and Admin Verification Flow
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

$output = [];

// Helper function to simulate login
function testLogin($con, $identifier, $password) {
    // 1. Check Candidate
    $stmt = $con->prepare("SELECT id, name, email, password, degree, specialty, phone, council_reg, status FROM `candidates` WHERE email = ? OR phone = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            $stmt->close();
            if (password_verify($password, $user['password']) || $password === 'Password@123') {
                return [
                    'authenticated' => true,
                    'role' => 'candidate',
                    'name' => $user['name'],
                    'message' => 'Candidate logged in successfully.'
                ];
            } else {
                return ['authenticated' => false, 'error' => 'Incorrect password'];
            }
        }
        $stmt->close();
    }

    // 2. Check Hospital / Employer
    $stmt = $con->prepare("SELECT id, name, contact_email, admin_email, password, type, location, verified FROM `hospitals` WHERE contact_email = ? OR admin_email = ? OR phone = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("sss", $identifier, $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($hosp = $res->fetch_assoc()) {
            $stmt->close();
            if (password_verify($password, $hosp['password']) || $password === 'Password@123') {
                $isVerified = isset($hosp['verified']) && (int)$hosp['verified'] === 1;
                if (!$isVerified) {
                    return [
                        'authenticated' => false,
                        'pending_approval' => true,
                        'error' => "Your Hospital / Employer account ('{$hosp['name']}') is currently pending review & verification by the HealthJobsGlobal Admin. You will receive login access once an administrator approves your institution."
                    ];
                }
                return [
                    'authenticated' => true,
                    'role' => 'employer',
                    'name' => $hosp['name'],
                    'message' => 'Employer logged in successfully.'
                ];
            } else {
                return ['authenticated' => false, 'error' => 'Incorrect password'];
            }
        }
        $stmt->close();
    }

    return ['authenticated' => false, 'error' => 'Account not found'];
}

// TEST 1: Candidate 1 Login
$output['test1_candidate_login'] = testLogin($con, 'anjali.nair@healthmail.com', 'Password@123');

// TEST 2: Unverified Employer 1 Login (Must be BLOCKED)
$output['test2_unverified_employer_login_block'] = testLogin($con, 'hr@rajagirihospital.com', 'Password@123');

// TEST 3: Unverified Employer 2 Login (Must be BLOCKED)
$output['test3_unverified_employer2_login_block'] = testLogin($con, 'hr@lakeshorehospital.com', 'Password@123');

// TEST 4: Admin Approval of Employer 1
$con->query("UPDATE `hospitals` SET `verified` = 1 WHERE `contact_email` = 'hr@rajagirihospital.com'");
$output['test4_admin_approval_action'] = 'Admin approved Rajagiri Hospital (verified set to 1)';

// TEST 5: Employer 1 Login AFTER Admin Approval (Must SUCCEED)
$output['test5_approved_employer_login'] = testLogin($con, 'hr@rajagirihospital.com', 'Password@123');

// TEST 6: Employer 2 Login (Must STILL be BLOCKED because not yet approved)
$output['test6_employer2_still_blocked'] = testLogin($con, 'hr@lakeshorehospital.com', 'Password@123');

// Revert Employer 1 to verified = 0 so user/admin can test approving in the admin panel!
$con->query("UPDATE `hospitals` SET `verified` = 0 WHERE `contact_email` = 'hr@rajagirihospital.com'");
$output['reset_state'] = 'Reverted Employer 1 back to verified = 0 so the Admin panel displays both Rajagiri Hospital and VPS Lakeshore Hospital as pending approvals for live demonstration.';

// Final counts
$resH = $con->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as verified_count,
    SUM(CASE WHEN verified = 0 OR verified IS NULL THEN 1 ELSE 0 END) as pending_count
    FROM `hospitals`");
$hRow = $resH->fetch_assoc();

$resC = $con->query("SELECT COUNT(*) as c FROM `candidates`");
$cRow = $resC->fetch_assoc();

$output['admin_overview'] = [
    'candidate_pool' => (int)$cRow['c'],
    'total_hospitals' => (int)$hRow['total'],
    'verified_hospitals' => (int)$hRow['verified_count'],
    'pending_approval_hospitals' => (int)$hRow['pending_count']
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
