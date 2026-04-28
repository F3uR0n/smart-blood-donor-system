<?php
// Feature 3: Emergency Request Status Tracker
// GET ?requestID=1

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$requestID = intval($_GET['requestID'] ?? 0);

if (!$requestID) {
    echo json_encode(['error' => 'requestID is required']);
    exit;
}

$res = mysqli_query($conn, "SELECT * FROM emergency_request WHERE requestID = $requestID");

if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}
$request = mysqli_fetch_assoc($res);

// Matched donors via intelligent_match
$matchedDonors = [];
$matchRes = mysqli_query($conn,
    "SELECT im.matchStatus, im.sendNotificationStatus,
            u.firstName, u.surName, u.phone,
            d.bloodType,
            ul.location
     FROM intelligent_match im
     JOIN donor d ON im.userID = d.userID
     JOIN user u ON d.userID = u.userID
     JOIN user_location ul ON u.userID = ul.userID
     WHERE im.requestID = $requestID");

while ($m = mysqli_fetch_assoc($matchRes)) {
    $matchedDonors[] = [
        'name'        => $m['firstName'] . ' ' . $m['surName'],
        'phone'       => $m['phone'],
        'bloodType'   => $m['bloodType'],
        'location'    => $m['location'],
        'matchStatus' => $m['matchStatus'],
        'notified'    => (bool) $m['sendNotificationStatus'],
    ];
}

// Blood banks with the required blood type
$bt = mysqli_real_escape_string($conn, $request['bloodTypeNeeded']);
$banksRes = mysqli_query($conn,
    "SELECT bb.bankName, bb.city, bb.isOpen, bi.unitsAvailable
     FROM blood_inventory bi
     JOIN blood_bank bb ON bi.bankID = bb.bankID
     WHERE bi.bloodType = '$bt' AND bi.unitsAvailable > 0
     ORDER BY bi.unitsAvailable DESC");

$banks = [];
while ($b = mysqli_fetch_assoc($banksRes)) {
    $banks[] = [
        'bankName'       => $b['bankName'],
        'city'           => $b['city'],
        'isOpen'         => (bool) $b['isOpen'],
        'unitsAvailable' => (int) $b['unitsAvailable'],
    ];
}

echo json_encode([
    'requestID'      => $request['requestID'],
    'bloodType'      => $request['bloodTypeNeeded'],
    'unitsNeeded'    => $request['unitsNeeded'],
    'urgencyLevel'   => $request['urgencyLvl'],
    'hospital'       => $request['reqHospitalCenter'],
    'city'           => $request['requestCity'],
    'currentStatus'  => $request['currentStatus'],
    'createdAt'      => $request['createdAt'],
    'matchedDonors'  => $matchedDonors,
    'availableBanks' => $banks,
]);
