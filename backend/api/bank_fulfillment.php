<?php
// Feature 9: Blood Bank Fulfillment Check
// GET ?requestID=1

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$requestID = intval($_GET['requestID'] ?? 0);

if (!$requestID) {
    echo json_encode(['error' => 'requestID is required']);
    exit;
}

$reqRes = mysqli_query($conn,
    "SELECT bloodTypeNeeded, unitsNeeded, reqHospitalCenter, requestCity
     FROM emergency_request WHERE requestID = $requestID");
if (!$reqRes || mysqli_num_rows($reqRes) === 0) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}
$req = mysqli_fetch_assoc($reqRes);

$bt          = mysqli_real_escape_string($conn, $req['bloodTypeNeeded']);
$unitsNeeded = (int) $req['unitsNeeded'];

$res = mysqli_query($conn,
    "SELECT bb.bankID, bb.bankName, bb.city, bb.isOpen,
            bi.unitsAvailable, bi.expiryDate
     FROM blood_inventory bi
     JOIN blood_bank bb ON bi.bankID = bb.bankID
     WHERE bi.bloodType = '$bt'
     ORDER BY bi.unitsAvailable DESC");

$banks = [];
while ($row = mysqli_fetch_assoc($res)) {
    $canFulfill = $row['unitsAvailable'] >= $unitsNeeded;
    $banks[] = [
        'bankName'       => $row['bankName'],
        'city'           => $row['city'],
        'isOpen'         => (bool) $row['isOpen'],
        'unitsAvailable' => (int) $row['unitsAvailable'],
        'expiryDate'     => $row['expiryDate'],
        'canFulfill'     => $canFulfill,
        'fulfillStatus'  => $canFulfill ? 'can_fulfill' : 'insufficient',
    ];
}

$fulfillableCount = count(array_filter($banks, fn($b) => $b['canFulfill']));

echo json_encode([
    'requestID'        => $requestID,
    'bloodType'        => $req['bloodTypeNeeded'],
    'unitsNeeded'      => $unitsNeeded,
    'hospital'         => $req['reqHospitalCenter'],
    'city'             => $req['requestCity'],
    'fulfillableCount' => $fulfillableCount,
    'banks'            => $banks,
]);
