<?php
// User Login
// POST with JSON body: { email, password }
// Returns: { success: true, userID, firstName, name, role }
//          { success: false, error: "..." }

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../db.php';

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = $data['password']       ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit;
}

$email = mysqli_real_escape_string($conn, $email);
$res   = mysqli_query($conn, "SELECT userID, firstName, surName, role, password
                               FROM user WHERE email = '$email'");

if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

$user = mysqli_fetch_assoc($res);

if ($password !== $user['password']) {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

$_SESSION['userID']    = $user['userID'];
$_SESSION['role']      = $user['role'];
$_SESSION['firstName'] = $user['firstName'];
$_SESSION['name']      = $user['firstName'] . ' ' . $user['surName'];

echo json_encode([
    'success'   => true,
    'userID'    => $user['userID'],
    'firstName' => $user['firstName'],
    'name'      => $user['firstName'] . ' ' . $user['surName'],
    'role'      => $user['role'],
]);
