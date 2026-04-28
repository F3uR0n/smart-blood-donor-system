<?php
// Feature 6: Notification Dispatch System
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
    "SELECT bloodTypeNeeded, currentStatus FROM emergency_request WHERE requestID = $requestID");
if (!$reqRes || mysqli_num_rows($reqRes) === 0) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}
$req = mysqli_fetch_assoc($reqRes);

$res = mysqli_query($conn,
    "SELECT im.matchStatus, im.sendNotificationStatus, im.matched_at,
            u.firstName, u.surName, u.phone,
            d.bloodType,
            ul.location
     FROM intelligent_match im
     JOIN donor d ON im.userID = d.userID
     JOIN user u ON d.userID = u.userID
     JOIN user_location ul ON u.userID = ul.userID
     WHERE im.requestID = $requestID
     ORDER BY im.matched_at ASC");

$donors = [];
while ($row = mysqli_fetch_assoc($res)) {
    $donors[] = [
        'name'       => $row['firstName'] . ' ' . $row['surName'],
        'phone'      => $row['phone'],
        'bloodType'  => $row['bloodType'],
        'city'       => $row['location'],
        'matchStatus'=> $row['matchStatus'],
        'notified'   => (bool) $row['sendNotificationStatus'],
        'notifiedAt' => $row['matched_at'],
    ];
}

$notifiedCount = count(array_filter($donors, fn($d) => $d['notified']));

echo json_encode([
    'requestID'     => $requestID,
    'bloodType'     => $req['bloodTypeNeeded'],
    'requestStatus' => $req['currentStatus'],
    'totalMatched'  => count($donors),
    'notifiedCount' => $notifiedCount,
    'donors'        => $donors,
]);
