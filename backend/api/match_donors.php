<?php
// Feature 1: Intelligent Donor Matching
// GET ?requestID=1

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$requestID = intval($_GET['requestID'] ?? 0);

if (!$requestID) {
    echo json_encode(['error' => 'requestID is required']);
    exit;
}

$res = mysqli_query($conn, "SELECT bloodTypeNeeded, requestCity, unitsNeeded
                             FROM emergency_request
                             WHERE requestID = $requestID");

if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}
$request = mysqli_fetch_assoc($res);
$bloodNeeded = $request['bloodTypeNeeded'];
$city        = $request['requestCity'];

$compatMap = [
    'A+'  => ['A+', 'A-', 'O+', 'O-'],
    'A-'  => ['A-', 'O-'],
    'B+'  => ['B+', 'B-', 'O+', 'O-'],
    'B-'  => ['B-', 'O-'],
    'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
    'AB-' => ['A-', 'B-', 'AB-', 'O-'],
    'O+'  => ['O+', 'O-'],
    'O-'  => ['O-'],
];

$compatTypes = $compatMap[$bloodNeeded] ?? [];

if (empty($compatTypes)) {
    echo json_encode(['donors' => [], 'bloodNeeded' => $bloodNeeded, 'city' => $city]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($compatTypes), '?'));
$cityLike     = '%' . $city . '%';
$today        = date('Y-m-d');

// Derived eligibility: lastDonated is NULL or lastDonated + 90 days <= today
// Also check weight >= 45 and haemoglobin >= 12 via health_profile
$sql = "SELECT u.userID, u.firstName, u.surName, u.phone,
               d.bloodType, d.totalDonate, d.isAvailable, d.lastDonated,
               ul.location,
               hp.weightKg, hp.haemoglobinLvl
        FROM donor d
        JOIN user u ON d.userID = u.userID
        JOIN user_location ul ON u.userID = ul.userID
        LEFT JOIN health_profile hp ON hp.userID = d.userID
        WHERE d.isAvailable = 1
          AND (d.lastDonated IS NULL OR DATE_ADD(d.lastDonated, INTERVAL 90 DAY) <= ?)
          AND d.bloodType IN ($placeholders)
          AND ul.location LIKE ?
        ORDER BY d.totalDonate DESC";

$stmt  = mysqli_prepare($conn, $sql);
$types = 's' . str_repeat('s', count($compatTypes)) . 's';
$args  = array_merge([$today], $compatTypes, [$cityLike]);
mysqli_stmt_bind_param($stmt, $types, ...$args);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$donors = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Additional health checks
    if ($row['haemoglobinLvl'] !== null && $row['haemoglobinLvl'] < 12.0) continue;
    if ($row['weightKg'] !== null && $row['weightKg'] < 45) continue;

    $donors[] = [
        'userID'      => $row['userID'],
        'name'        => $row['firstName'] . ' ' . $row['surName'],
        'bloodType'   => $row['bloodType'],
        'phone'       => $row['phone'],
        'location'    => $row['location'],
        'totalDonate' => (int) $row['totalDonate'],
    ];
}

echo json_encode([
    'requestID'   => $requestID,
    'bloodNeeded' => $bloodNeeded,
    'unitsNeeded' => (int) $request['unitsNeeded'],
    'city'        => $city,
    'count'       => count($donors),
    'donors'      => $donors,
]);
