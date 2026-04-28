<?php
// Submit a new emergency request
// POST with JSON body: { bloodTypeNeeded, unitsNeeded, reqHospitalCenter, requestCity, urgencyLvl, userID }
// Returns: { success: true, requestID: X }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$bloodTypeNeeded  = trim($data['bloodTypeNeeded']  ?? '');
$unitsNeeded      = intval($data['unitsNeeded']    ?? 1);
$reqHospitalCenter= trim($data['reqHospitalCenter']?? ($data['hospital'] ?? ''));
$requestCity      = trim($data['requestCity']      ?? '');
$urgencyLvl       = $data['urgencyLvl']             ?? ($data['urgencyLevel'] ?? 'high');
$userID           = trim($data['userID']            ?? '');

$allowed_bt = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$allowed_ul = ['critical','high','medium','low'];

if (!$bloodTypeNeeded || !$reqHospitalCenter || !$requestCity) {
    echo json_encode(['success' => false, 'error' => 'Blood type, hospital, and city are required']);
    exit;
}
if (!in_array($bloodTypeNeeded, $allowed_bt)) {
    echo json_encode(['success' => false, 'error' => 'Invalid blood type']);
    exit;
}
if (!in_array($urgencyLvl, $allowed_ul)) {
    $urgencyLvl = 'high';
}
if ($unitsNeeded < 1) { $unitsNeeded = 1; }

$bloodTypeNeeded   = mysqli_real_escape_string($conn, $bloodTypeNeeded);
$reqHospitalCenter = mysqli_real_escape_string($conn, $reqHospitalCenter);
$requestCity       = mysqli_real_escape_string($conn, $requestCity);
$urgencyLvl        = mysqli_real_escape_string($conn, $urgencyLvl);
if ($userID) {
    $userIDEsc = mysqli_real_escape_string($conn, $userID);
    $check = mysqli_query($conn, "SELECT recipientID FROM recipient WHERE recipientID = '$userIDEsc'");
    $userIDSQL = (mysqli_num_rows($check) > 0) ? "'$userIDEsc'" : 'NULL';
} else {
    $userIDSQL = 'NULL';
}

$query = "INSERT INTO emergency_request
            (userID, bloodTypeNeeded, unitsNeeded, reqHospitalCenter, requestCity, urgencyLvl, currentStatus)
          VALUES
            ($userIDSQL, '$bloodTypeNeeded', $unitsNeeded, '$reqHospitalCenter', '$requestCity', '$urgencyLvl', 'pending')";

if (!mysqli_query($conn, $query)) {
    echo json_encode(['success' => false, 'error' => 'Could not save request: ' . mysqli_error($conn)]);
    exit;
}

$requestID = mysqli_insert_id($conn);

echo json_encode([
    'success'   => true,
    'requestID' => $requestID,
    'message'   => 'Emergency request submitted. Donors in your area are being notified.',
]);
