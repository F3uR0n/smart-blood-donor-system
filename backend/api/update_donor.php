<?php
// POST { userID, lastDonated }
// Saves lastDonated to donor table; returns computed nextEligible.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$data        = json_decode(file_get_contents('php://input'), true);
$userID      = trim($data['userID']      ?? '');
$lastDonated = trim($data['lastDonated'] ?? '');

if (!$userID || !$lastDonated) {
    echo json_encode(['success' => false, 'error' => 'userID and lastDonated are required']);
    exit;
}

// Validate date format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDonated) || !strtotime($lastDonated)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format (expected YYYY-MM-DD)']);
    exit;
}

$userID      = mysqli_real_escape_string($conn, $userID);
$lastDonated = mysqli_real_escape_string($conn, $lastDonated);

$check = mysqli_query($conn, "SELECT userID FROM donor WHERE userID = '$userID'");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'error' => 'Donor not found']);
    exit;
}

if (!mysqli_query($conn, "UPDATE donor SET lastDonated = '$lastDonated' WHERE userID = '$userID'")) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

// Compute nextEligible
$nextRes = mysqli_query($conn,
    "SELECT DATE_ADD('$lastDonated', INTERVAL 90 DAY) AS nextEligible");
$next = mysqli_fetch_assoc($nextRes);

echo json_encode([
    'success'      => true,
    'lastDonated'  => $lastDonated,
    'nextEligible' => $next['nextEligible'],
]);
