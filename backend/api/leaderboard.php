<?php
// Feature 4: Donor Leaderboard
// GET (no params)
// Returns top 10 donors ordered by totalDonate DESC; globalRank and points derived.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$result = mysqli_query($conn, "SELECT u.firstName, u.surName,
                                      d.bloodType, d.totalDonate,
                                      (d.totalDonate * 50) AS points,
                                      (SELECT COUNT(*)+1 FROM donor d2 WHERE d2.totalDonate > d.totalDonate) AS globalRank,
                                      ul.location
                               FROM donor d
                               JOIN user u ON d.userID = u.userID
                               JOIN user_location ul ON u.userID = ul.userID
                               ORDER BY d.totalDonate DESC, globalRank ASC
                               LIMIT 10");

$donors = [];
$position = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $initials = strtoupper(substr($row['firstName'], 0, 1) . substr($row['surName'], 0, 1));

    $donors[] = [
        'position'    => $position++,
        'name'        => $row['firstName'] . ' ' . $row['surName'],
        'initials'    => $initials,
        'bloodType'   => $row['bloodType'],
        'city'        => $row['location'],
        'totalDonate' => (int) $row['totalDonate'],
        'points'      => (int) $row['points'],
        'globalRank'  => (int) $row['globalRank'],
    ];
}

echo json_encode(['leaderboard' => $donors, 'count' => count($donors)]);
