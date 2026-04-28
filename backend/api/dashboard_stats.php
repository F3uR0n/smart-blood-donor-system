<?php
// Dashboard Stats
// GET ?userID=u001&role=donor|recipient|admin

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$userID = trim($_GET['userID'] ?? '');
$role   = trim($_GET['role']   ?? '');

if (!$userID || !$role) {
    echo json_encode(['error' => 'userID and role are required']);
    exit;
}

$userID = mysqli_real_escape_string($conn, $userID);

if ($role === 'donor') {
    $res = mysqli_query($conn,
        "SELECT d.bloodType, d.totalDonate, d.lastDonated,
                DATE_ADD(d.lastDonated, INTERVAL 90 DAY) AS nextEligible,
                (d.totalDonate * 50) AS points,
                (SELECT COUNT(*)+1 FROM donor d2 WHERE d2.totalDonate > d.totalDonate) AS globalRank,
                hp.weightKg, hp.haemoglobinLvl, hp.healthID,
                u.firstName, u.surName
         FROM donor d
         JOIN user u ON d.userID = u.userID
         LEFT JOIN health_profile hp ON hp.userID = d.userID
         WHERE d.userID = '$userID'");

    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['error' => 'Donor not found']);
        exit;
    }
    $donor = mysqli_fetch_assoc($res);

    // Fetch diseases
    $diseaseList = [];
    if ($donor['healthID']) {
        $dRes = mysqli_query($conn,
            "SELECT disease FROM diseases WHERE healthID = {$donor['healthID']}");
        while ($d = mysqli_fetch_assoc($dRes)) $diseaseList[] = $d['disease'];
    }

    $today    = date('Y-m-d');
    $daysLeft = 0;
    $nextEligible = $donor['nextEligible'];
    if ($nextEligible && $nextEligible > $today) {
        $daysLeft = (int) ceil((strtotime($nextEligible) - time()) / 86400);
    }

    $nextEligibleFmt = 'Now';
    if ($nextEligible && $nextEligible > $today) {
        $nextEligibleFmt = (new DateTime($nextEligible))->format('M d');
    }

    // Derive eligibility
    $eligible = true;
    if ($donor['haemoglobinLvl'] !== null && $donor['haemoglobinLvl'] < 12.0) $eligible = false;
    if ($donor['weightKg'] !== null && $donor['weightKg'] < 45) $eligible = false;
    if ($nextEligible && $nextEligible > $today) $eligible = false;

    // Derive healthStatus
    $healthStatus = null;
    $w = $donor['weightKg'];
    $h = $donor['haemoglobinLvl'];
    if ($w !== null && $h !== null) {
        if ($h >= 13.5 && $w >= 50)      $healthStatus = 'Perfect';
        elseif ($h >= 12.0 && $w >= 45)  $healthStatus = 'Good';
        else                              $healthStatus = 'Bad';
    }

    $donRes = mysqli_query($conn,
        "SELECT donationDate, hospitalCenter, unitsDonated, status
         FROM donation
         WHERE donorID = '$userID'
         ORDER BY donationDate DESC
         LIMIT 5");
    $donations = [];
    while ($row = mysqli_fetch_assoc($donRes)) {
        $donations[] = [
            'donationDate'   => $row['donationDate'],
            'hospitalCenter' => $row['hospitalCenter'],
            'unitsDonated'   => (int) $row['unitsDonated'],
            'status'         => $row['status'],
        ];
    }

    echo json_encode([
        'role'            => 'donor',
        'firstName'       => $donor['firstName'],
        'surName'         => $donor['surName'],
        'bloodType'       => $donor['bloodType'],
        'totalDonate'     => (int) $donor['totalDonate'],
        'points'          => (int) $donor['points'],
        'globalRank'      => (int) $donor['globalRank'],
        'lastDonated'     => $donor['lastDonated'],
        'nextEligible'    => $nextEligibleFmt,
        'nextEligibleRaw' => $nextEligible,
        'daysLeft'        => $daysLeft,
        'hemoglobinLvl'   => $donor['haemoglobinLvl'],
        'weightKG'        => $donor['weightKg'],
        'healthStatus'    => $healthStatus,
        'diseases'        => empty($diseaseList) ? 'None recorded' : implode(', ', $diseaseList),
        'eligible'        => $eligible,
        'recentDonations' => $donations,
    ]);

} elseif ($role === 'recipient') {
    $userRes = mysqli_query($conn,
        "SELECT u.firstName, u.surName, u.bloodType
         FROM user u WHERE u.userID = '$userID'");

    if (!$userRes || mysqli_num_rows($userRes) === 0) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    $user = mysqli_fetch_assoc($userRes);

    $statsRes = mysqli_query($conn,
        "SELECT currentStatus, COUNT(*) as cnt
         FROM emergency_request
         WHERE userID = '$userID'
         GROUP BY currentStatus");
    $sc = [];
    while ($row = mysqli_fetch_assoc($statsRes)) { $sc[$row['currentStatus']] = (int) $row['cnt']; }

    $activeRequests    = ($sc['pending'] ?? 0) + ($sc['matched'] ?? 0);
    $fulfilledRequests = $sc['fulfilled'] ?? 0;

    $matchRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(DISTINCT im.userID) as cnt
         FROM intelligent_match im
         JOIN emergency_request er ON im.requestID = er.requestID
         WHERE er.userID = '$userID' AND er.currentStatus IN ('pending','matched')"));
    $matchedDonors = (int) ($matchRow['cnt'] ?? 0);

    $reqRes = mysqli_query($conn,
        "SELECT requestID, bloodTypeNeeded, unitsNeeded,
                reqHospitalCenter, urgencyLvl, currentStatus, createdAt
         FROM emergency_request
         WHERE userID = '$userID'
         ORDER BY createdAt DESC
         LIMIT 5");
    $requests = [];
    while ($row = mysqli_fetch_assoc($reqRes)) {
        $requests[] = [
            'requestID'     => (int) $row['requestID'],
            'bloodType'     => $row['bloodTypeNeeded'],
            'unitsNeeded'   => (int) $row['unitsNeeded'],
            'hospital'      => $row['reqHospitalCenter'],
            'urgencyLevel'  => $row['urgencyLvl'],
            'currentStatus' => $row['currentStatus'],
            'createdAt'     => $row['createdAt'],
        ];
    }

    echo json_encode([
        'role'              => 'recipient',
        'firstName'         => $user['firstName'],
        'surName'           => $user['surName'],
        'bloodType'         => $user['bloodType'] ?: 'N/A',
        'activeRequests'    => $activeRequests,
        'matchedDonors'     => $matchedDonors,
        'fulfilledRequests' => $fulfilledRequests,
        'recentRequests'    => $requests,
    ]);

} elseif ($role === 'admin') {
    $totalUsers   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM user"))['n'];
    $openRequests = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM emergency_request WHERE currentStatus IN ('pending','matched')"))['n'];
    $criticalReqs = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM emergency_request WHERE urgencyLvl='critical' AND currentStatus IN ('pending','matched')"))['n'];
    $totalBanks   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM blood_bank"))['n'];
    $lowStockBanks= (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT bankID) as n FROM blood_inventory WHERE unitsAvailable < 5"))['n'];

    // Active campaigns: CURDATE() between startDate and endDate
    $activeCampaigns = (int) mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as n FROM campaign WHERE CURDATE() BETWEEN startDate AND endDate"))['n'];

    echo json_encode([
        'role'             => 'admin',
        'totalUsers'       => $totalUsers,
        'openRequests'     => $openRequests,
        'criticalRequests' => $criticalReqs,
        'totalBanks'       => $totalBanks,
        'lowStockBanks'    => $lowStockBanks,
        'activeCampaigns'  => $activeCampaigns,
    ]);

} else {
    echo json_encode(['error' => 'Invalid role']);
}
