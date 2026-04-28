<?php
// POST { userID, weightKg?, haemoglobinLvl? }
// Upserts health_profile; only updates the fields that are sent.
// Derives and returns healthStatus.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$data           = json_decode(file_get_contents('php://input'), true);
$userID         = trim($data['userID']         ?? '');
$hasWeight      = isset($data['weightKg'])      && $data['weightKg'] !== '';
$hasHaemo       = isset($data['haemoglobinLvl'])&& $data['haemoglobinLvl'] !== '';
$weightKg       = $hasWeight ? (float) $data['weightKg']       : null;
$haemoglobinLvl = $hasHaemo  ? (float) $data['haemoglobinLvl'] : null;

if (!$userID) {
    echo json_encode(['success' => false, 'error' => 'userID is required']);
    exit;
}

$userID = mysqli_real_escape_string($conn, $userID);

$check = mysqli_query($conn, "SELECT userID FROM donor WHERE userID = '$userID'");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'error' => 'Donor not found']);
    exit;
}

// Load current values so we don't overwrite with NULL
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT weightKg, haemoglobinLvl FROM health_profile WHERE userID = '$userID'"));

$finalWeight = $hasWeight ? $weightKg : ($existing['weightKg'] ?? null);
$finalHaemo  = $hasHaemo  ? $haemoglobinLvl : ($existing['haemoglobinLvl'] ?? null);

$wSQL = $finalWeight !== null ? (float) $finalWeight : 'NULL';
$hSQL = $finalHaemo  !== null ? (float) $finalHaemo  : 'NULL';

$upsert = "INSERT INTO health_profile (userID, weightKg, haemoglobinLvl)
           VALUES ('$userID', $wSQL, $hSQL)
           ON DUPLICATE KEY UPDATE weightKg = $wSQL, haemoglobinLvl = $hSQL";

if (!mysqli_query($conn, $upsert)) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

// Derive healthStatus from final values
$healthStatus = null;
if ($finalWeight !== null && $finalHaemo !== null) {
    if ($finalHaemo >= 13.5 && $finalWeight >= 50)     $healthStatus = 'Perfect';
    elseif ($finalHaemo >= 12.0 && $finalWeight >= 45) $healthStatus = 'Good';
    else                                                $healthStatus = 'Bad';
}

echo json_encode([
    'success'        => true,
    'weightKg'       => $finalWeight,
    'haemoglobinLvl' => $finalHaemo,
    'healthStatus'   => $healthStatus,
]);
