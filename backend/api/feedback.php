<?php
// Feature 10: Feedback & Rating Aggregator
// GET (no params)
// Feedback is linked via donation; derives donor name and hospital from donation record.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

// All feedback with donor info via donation → donor → user
$res = mysqli_query($conn,
    "SELECT f.feedbackID, f.rating, f.comments,
            dn.donationDate, dn.hospitalCenter,
            u.firstName, u.surName
     FROM feedback f
     JOIN donation dn ON f.donationTrackerID = dn.donationID
     JOIN donor d  ON dn.donorID = d.userID
     JOIN user u   ON d.userID   = u.userID
     ORDER BY dn.donationDate DESC");

$feedbacks = [];
while ($row = mysqli_fetch_assoc($res)) {
    $feedbacks[] = [
        'feedbackID'     => (int) $row['feedbackID'],
        'donorName'      => $row['firstName'] . ' ' . $row['surName'],
        'rating'         => (int) $row['rating'],
        'comment'        => $row['comments'],
        'hospitalCenter' => $row['hospitalCenter'],
        'createdAt'      => $row['donationDate'],
    ];
}

// Overall average
$avgRes = mysqli_query($conn, "SELECT ROUND(AVG(rating), 2) AS overallAvg, COUNT(*) AS total FROM feedback");
$avg    = mysqli_fetch_assoc($avgRes);

// Per-hospital average via donation join
$hospRes = mysqli_query($conn,
    "SELECT dn.hospitalCenter,
            ROUND(AVG(f.rating), 2) AS avgRating,
            COUNT(*) AS reviewCount
     FROM feedback f
     JOIN donation dn ON f.donationTrackerID = dn.donationID
     WHERE dn.hospitalCenter IS NOT NULL
     GROUP BY dn.hospitalCenter
     ORDER BY avgRating DESC");

$byHospital = [];
while ($h = mysqli_fetch_assoc($hospRes)) {
    $byHospital[] = [
        'hospital'    => $h['hospitalCenter'],
        'avgRating'   => (float) $h['avgRating'],
        'reviewCount' => (int) $h['reviewCount'],
    ];
}

echo json_encode([
    'overallAvgRating' => (float) ($avg['overallAvg'] ?? 0),
    'totalReviews'     => (int) ($avg['total'] ?? 0),
    'byHospital'       => $byHospital,
    'feedbacks'        => $feedbacks,
]);
