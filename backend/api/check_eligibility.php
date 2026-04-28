<?php
// Feature 2: Donor Eligibility Checker
// GET ?userID=u001

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$userID = $_GET['userID'] ?? '';

if (!$userID) {
    echo json_encode(['error' => 'userID is required']);
    exit;
}

$userID = mysqli_real_escape_string($conn, $userID);

$result = mysqli_query($conn,
    "SELECT d.bloodType, d.totalDonate, d.lastDonated,
            DATE_ADD(d.lastDonated, INTERVAL 90 DAY) AS nextEligible,
            hp.weightKg, hp.haemoglobinLvl, hp.healthID,
            u.firstName, u.surName
     FROM donor d
     JOIN user u ON d.userID = u.userID
     LEFT JOIN health_profile hp ON hp.userID = d.userID
     WHERE d.userID = '$userID'");

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['error' => 'Donor not found']);
    exit;
}
$donor = mysqli_fetch_assoc($result);
$today = date('Y-m-d');

// Fetch diseases list
$diseaseList = [];
if ($donor['healthID']) {
    $dRes = mysqli_query($conn,
        "SELECT disease FROM diseases WHERE healthID = {$donor['healthID']}");
    while ($d = mysqli_fetch_assoc($dRes)) {
        $diseaseList[] = $d['disease'];
    }
}

$eligible = true;
$reasons  = [];

// Rule 1: Hemoglobin must be >= 12 g/dL
if ($donor['haemoglobinLvl'] !== null && $donor['haemoglobinLvl'] < 12.0) {
    $eligible  = false;
    $reasons[] = 'Hemoglobin too low (' . $donor['haemoglobinLvl'] . ' g/dL, minimum 12.0)';
}

// Rule 2: Weight must be >= 45 kg
if ($donor['weightKg'] !== null && $donor['weightKg'] < 45) {
    $eligible  = false;
    $reasons[] = 'Weight below minimum (' . $donor['weightKg'] . ' kg, minimum 45 kg)';
}

// Rule 3: No critical diseases
if (!empty($diseaseList)) {
    $critical = ['HIV', 'Hepatitis', 'Cancer', 'Malaria', 'Tuberculosis', 'Diabetes'];
    foreach ($diseaseList as $disease) {
        foreach ($critical as $c) {
            if (stripos($disease, $c) !== false) {
                $eligible  = false;
                $reasons[] = 'Critical condition on record: ' . $disease;
                break 2;
            }
        }
    }
}

// Rule 4: Must wait 90 days between donations
$nextEligible = $donor['nextEligible'];
if ($nextEligible && $nextEligible > $today) {
    $eligible  = false;
    $reasons[] = 'Next eligible date: ' . $nextEligible . ' (must wait 90 days after last donation)';
}

echo json_encode([
    'userID'        => $userID,
    'name'          => $donor['firstName'] . ' ' . $donor['surName'],
    'bloodType'     => $donor['bloodType'],
    'hemoglobinLvl' => $donor['haemoglobinLvl'],
    'weightKG'      => $donor['weightKg'],
    'diseases'      => empty($diseaseList) ? 'None recorded' : implode(', ', $diseaseList),
    'lastDonated'   => $donor['lastDonated'],
    'nextEligible'  => $nextEligible ?: 'Now',
    'totalDonate'   => (int) $donor['totalDonate'],
    'eligible'      => $eligible,
    'reasons'       => $reasons,
]);
