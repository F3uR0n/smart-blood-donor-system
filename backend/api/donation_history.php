<?php
// Feature 7: Donation History Timeline
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

$check = mysqli_query($conn,
    "SELECT d.userID, u.firstName, u.surName, d.bloodType, d.totalDonate
     FROM donor d
     JOIN user u ON d.userID = u.userID
     WHERE d.userID = '$userID'");

if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['error' => 'Donor not found']);
    exit;
}
$donor = mysqli_fetch_assoc($check);

$res = mysqli_query($conn,
    "SELECT donationID, donationDate, hospitalCenter, unitsDonated, status
     FROM donation
     WHERE donorID = '$userID'
     ORDER BY donationDate DESC");

$donations = [];
while ($row = mysqli_fetch_assoc($res)) {
    $donations[] = [
        'donationID'     => $row['donationID'],
        'lastDonated'    => $row['donationDate'],
        'hospitalCenter' => $row['hospitalCenter'],
        'unitsDonated'   => (int) $row['unitsDonated'],
        'status'         => $row['status'],
    ];
}

$totalRes  = mysqli_query($conn,
    "SELECT SUM(unitsDonated) AS total FROM donation WHERE donorID = '$userID' AND status = 'completed'");
$totalRow  = mysqli_fetch_assoc($totalRes);
$totalUnits = (int) ($totalRow['total'] ?? 0);

echo json_encode([
    'userID'      => $userID,
    'name'        => $donor['firstName'] . ' ' . $donor['surName'],
    'bloodType'   => $donor['bloodType'],
    'totalDonate' => (int) $donor['totalDonate'],
    'totalUnits'  => $totalUnits,
    'donations'   => $donations,
    'count'       => count($donations),
]);
