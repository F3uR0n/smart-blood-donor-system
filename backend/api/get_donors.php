<?php
// Donor list for the Find Donor page
// GET ?bloodType=A%2B&location=Dhaka&available=1  (all params optional)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$where  = ['1=1'];
$params = [];
$types  = '';

if (!empty($_GET['bloodType'])) {
    $where[]  = 'd.bloodType = ?';
    $params[] = $_GET['bloodType'];
    $types   .= 's';
}

// Filter by location string (partial match)
if (!empty($_GET['city'])) {
    $where[]  = 'ul.location LIKE ?';
    $params[] = '%' . $_GET['city'] . '%';
    $types   .= 's';
}

if (isset($_GET['available']) && $_GET['available'] === '1') {
    $where[] = 'd.isAvailable = 1';
}

$whereSQL = implode(' AND ', $where);

$sql = "SELECT u.userID, u.firstName, u.surName,
               d.bloodType, d.isAvailable, d.totalDonate, d.lastDonated,
               ul.location,
               (d.totalDonate * 50) AS points,
               DATE_ADD(d.lastDonated, INTERVAL 90 DAY) AS nextEligible,
               (SELECT COUNT(*)+1 FROM donor d2 WHERE d2.totalDonate > d.totalDonate) AS globalRank
        FROM donor d
        JOIN user u ON d.userID = u.userID
        JOIN user_location ul ON u.userID = ul.userID
        WHERE $whereSQL
        ORDER BY d.totalDonate DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

$donors = [];
while ($row = mysqli_fetch_assoc($result)) {
    $firstName = $row['firstName'];
    $surName   = $row['surName'];
    $initials  = strtoupper(substr($firstName, 0, 1) . substr($surName, 0, 1));

    $donors[] = [
        'userID'      => $row['userID'],
        'initials'    => $initials,
        'name'        => $firstName . ' ' . $surName,
        'blood'       => $row['bloodType'],
        'city'        => $row['location'],
        'avail'       => (bool) $row['isAvailable'],
        'donations'   => (int) $row['totalDonate'],
        'lastDonated' => $row['lastDonated'] ?: 'N/A',
        'nextEligible'=> $row['nextEligible'] ?: 'N/A',
        'points'      => (int) $row['points'],
        'globalRank'  => (int) $row['globalRank'],
    ];
}

echo json_encode(['donors' => $donors, 'count' => count($donors)]);
