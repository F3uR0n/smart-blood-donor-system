<?php
// User Registration
// POST with JSON body: { firstName, surName, email, phone, nid, bloodType, role, location, password }
// Returns: { success: true, userID, role }
//          { success: false, error/errors: "..." }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$firstName = trim($data['firstName'] ?? '');
$surName   = trim($data['surName']   ?? '');
$email     = trim($data['email']     ?? '');
$phone     = trim($data['phone']     ?? '');
$nid       = trim($data['nid']       ?? '');
$bloodType = $data['bloodType']       ?? '';
$role      = $data['role']            ?? '';
$location  = trim($data['location']  ?? '');
$password  = $data['password']        ?? '';

// ── Validation ──────────────────────────────────────────────
$errors = [];

if (!$firstName)                                    $errors[] = 'First name required';
if (!$surName)                                      $errors[] = 'Last name required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Valid email required';
if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone))  $errors[] = 'Valid phone number required';
if (!preg_match('/^\d{10,17}$/', $nid))             $errors[] = 'NID must be 10–17 digits (numbers only)';
if (!in_array($role, ['donor', 'recipient', 'admin'])) $errors[] = 'Invalid role selected';
if (!$location)                                     $errors[] = 'Location required';
if (strlen($password) < 8)                          $errors[] = 'Password must be at least 8 characters';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Uniqueness checks ─────────────────────────────────────
$emailEsc = mysqli_real_escape_string($conn, $email);
$nidEsc   = mysqli_real_escape_string($conn, $nid);

$emailCheck = mysqli_query($conn, "SELECT userID FROM user WHERE email = '$emailEsc'");
if (mysqli_num_rows($emailCheck) > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already registered']);
    exit;
}

$nidCheck = mysqli_query($conn, "SELECT userID FROM user WHERE nid = '$nidEsc'");
if (mysqli_num_rows($nidCheck) > 0) {
    echo json_encode(['success' => false, 'error' => 'NID already registered']);
    exit;
}

// ── Generate UUID ─────────────────────────────────────────
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM user");
$row = mysqli_fetch_assoc($result);

$next = $row['total'] + 1;
$userID = "u" . str_pad($next, 3, "0", STR_PAD_LEFT);

$firstNameEsc = mysqli_real_escape_string($conn, $firstName);
$surNameEsc   = mysqli_real_escape_string($conn, $surName);
$phoneEsc     = mysqli_real_escape_string($conn, $phone);
$bloodTypeEsc = mysqli_real_escape_string($conn, $bloodType);
$roleEsc      = mysqli_real_escape_string($conn, $role);
$locationEsc  = mysqli_real_escape_string($conn, $location);

// ── Insert user ───────────────────────────────────────────
mysqli_query($conn,
    "INSERT INTO user (userID, firstName, surName, email, phone, nid, bloodType, role, password)
     VALUES ('$userID', '$firstNameEsc', '$surNameEsc', '$emailEsc', '$phoneEsc', '$nidEsc',
             '$bloodTypeEsc', '$roleEsc', '$password')"
);

if (mysqli_error($conn)) {
    echo json_encode(['success' => false, 'error' => 'Registration failed: ' . mysqli_error($conn)]);
    exit;
}

// Insert location
mysqli_query($conn,
    "INSERT INTO user_location (userID, location) VALUES ('$userID', '$locationEsc')"
);

// Role-specific table inserts
if ($role === 'donor') {
    mysqli_query($conn,
        "INSERT INTO donor (userID, bloodType, isAvailable, totalDonate)
         VALUES ('$userID', '$bloodTypeEsc', 1, 0)"
    );
    mysqli_query($conn,
        "INSERT INTO health_profile (userID) VALUES ('$userID')"
    );
} elseif ($role === 'recipient') {
    mysqli_query($conn, "INSERT INTO recipient (recipientID) VALUES ('$userID')");
}

echo json_encode(['success' => true, 'userID' => $userID, 'role' => $role]);
