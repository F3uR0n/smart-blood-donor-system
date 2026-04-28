<?php
// Feature 8: Campaign–Reward Linker
// GET (no params)
// Returns all campaigns with company info, derived status, and rewards via offers table.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$today = date('Y-m-d');

// Fetch campaigns with company info; derive status from dates
$campRes = mysqli_query($conn,
    "SELECT c.campaignID, c.budget, c.tagLine, c.title, c.hostPlace,
            c.startDate, c.endDate,
            co.name AS companyName,
            CASE
                WHEN CURDATE() BETWEEN c.startDate AND c.endDate THEN 'active'
                WHEN CURDATE() < c.startDate                     THEN 'upcoming'
                ELSE 'ended'
            END AS status
     FROM campaign c
     JOIN company co ON c.companyID = co.companyID
     ORDER BY
         FIELD(
             CASE WHEN CURDATE() BETWEEN c.startDate AND c.endDate THEN 'active'
                  WHEN CURDATE() < c.startDate THEN 'upcoming'
                  ELSE 'ended' END,
             'active','upcoming','ended'
         ),
         c.endDate DESC");

$campaigns = [];
while ($camp = mysqli_fetch_assoc($campRes)) {
    $cid = (int) $camp['campaignID'];

    // Rewards for this campaign via offers table
    $rewRes = mysqli_query($conn,
        "SELECT r.rewardID, r.rewardItem,
                COUNT(a.userID) AS totalClaims,
                SUM(CASE WHEN a.claimedStatus = 1 THEN 1 ELSE 0 END) AS claimedCount
         FROM offers o
         JOIN reward r ON o.rewardID = r.rewardID
         LEFT JOIN accept a ON a.rewardID = r.rewardID
         WHERE o.campaignID = $cid
         GROUP BY r.rewardID, r.rewardItem");

    $rewards = [];
    while ($rew = mysqli_fetch_assoc($rewRes)) {
        // Donors who claimed this reward
        $claimRes = mysqli_query($conn,
            "SELECT u.firstName, u.surName, a.claimedAt
             FROM accept a
             JOIN donor d ON a.userID = d.userID
             JOIN user u ON d.userID = u.userID
             WHERE a.rewardID = {$rew['rewardID']} AND a.claimedStatus = 1");

        $claimers = [];
        while ($c = mysqli_fetch_assoc($claimRes)) {
            $claimers[] = ['name' => $c['firstName'] . ' ' . $c['surName'], 'claimedAt' => $c['claimedAt']];
        }

        $rewards[] = [
            'rewardID'    => (int) $rew['rewardID'],
            'rewardItem'  => $rew['rewardItem'],
            'claimedCount'=> (int) $rew['claimedCount'],
            'claimers'    => $claimers,
        ];
    }

    $campaigns[] = [
        'campaignID'    => $cid,
        'companyName'   => $camp['companyName'],
        'title'         => $camp['title'],
        'tagline'       => $camp['tagLine'],
        'location'      => $camp['hostPlace'],
        'startDate'     => $camp['startDate'],
        'endDate'       => $camp['endDate'],
        'budget'        => (float) $camp['budget'],
        'status'        => $camp['status'],
        'isActive'      => ($camp['status'] === 'active'),
        'rewards'       => $rewards,
    ];
}

echo json_encode(['campaigns' => $campaigns, 'count' => count($campaigns)]);
