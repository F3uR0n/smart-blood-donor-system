<?php
// Database connection — update credentials to match your local setup
$host   = 'localhost';
$user   = 'root';
$pass   = '';           // XAMPP default: empty password
$dbname = 'smart_blood_donor';

$conn = mysqli_connect($host, $user, $pass, $dbname, 3307);

if (!$conn) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(['error' => 'DB connection failed: ' . mysqli_connect_error()]));
}

mysqli_set_charset($conn, 'utf8mb4');
