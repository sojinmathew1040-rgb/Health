<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain');
echo "=== HOSPITALS ===\n";
$resH = $con->query("SELECT id, name, admin_name, contact_email, admin_email FROM hospitals");
while ($r = $resH->fetch_assoc()) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Admin: {$r['admin_name']} | Contact: {$r['contact_email']} | AdminEmail: {$r['admin_email']}\n";
}

echo "\n=== JOBS ===\n";
$resJ = $con->query("SELECT id, title, hospital_id, company, status FROM jobs");
while ($r = $resJ->fetch_assoc()) {
    echo "ID: {$r['id']} | Title: {$r['title']} | HospID: {$r['hospital_id']} | Company: {$r['company']} | Status: {$r['status']}\n";
}

echo "\n=== APPLICATIONS ===\n";
$resA = $con->query("SELECT id, job_id, hospital_id, job_title, hospital_name, candidate_name, candidate_email, status, applied_at FROM applications");
while ($r = $resA->fetch_assoc()) {
    echo "ID: {$r['id']} | JobID: {$r['job_id']} | HospID: {$r['hospital_id']} | JobTitle: {$r['job_title']} | HospName: {$r['hospital_name']} | Cand: {$r['candidate_name']} ({$r['candidate_email']}) | Status: {$r['status']}\n";
}

