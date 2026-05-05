<?php
require_once __DIR__ . '/helpers.php';

function build_intelligent_matches(mysqli $mysqli, $requestId) {
    $stmt = $mysqli->prepare("SELECT requestID, bloodTypeNeeded, requestCity FROM emergency_request WHERE requestID = ?");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        return 0;
    }

    $compatible = compatible_blood_types($request['bloodTypeNeeded']);
    if (!$compatible) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($compatible), '?'));
    $types = str_repeat('s', count($compatible));

    $sql = "SELECT d.userID, d.bloodType, d.isAvailable, d.lastDonated, u.firstName, u.surName,
                   ul.location, hp.weightKg, hp.haemoglobinLvl,
                   (SELECT COUNT(*) FROM diseases ds WHERE ds.healthID = hp.healthID) AS diseaseCount
            FROM donor d
            JOIN user u ON u.userID = d.userID
            LEFT JOIN user_location ul ON ul.userID = d.userID
            LEFT JOIN health_profile hp ON hp.userID = d.userID
            WHERE d.bloodType IN ($placeholders)";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$compatible);
    $stmt->execute();
    $result = $stmt->get_result();

    $inserted = 0;
    while ($row = $result->fetch_assoc()) {
        $lastDonated = $row['lastDonated'];
        $eligibleDate = next_eligible_date($lastDonated);
        $eligibleByDate = !$lastDonated || (strtotime($eligibleDate) <= strtotime(date('Y-m-d')));
        $eligibleByHealth = is_eligible_health($row['weightKg'], $row['haemoglobinLvl'], $row['diseaseCount'] > 0);
        $isAvailable = (int)$row['isAvailable'] === 1;

        if (!$eligibleByDate || !$eligibleByHealth || !$isAvailable) {
            continue;
        }

        $location = $row['location'] ?? '';
        $proximity = (stripos($location, $request['requestCity']) !== false) ? 1.2 : 5.0;

        $stmtInsert = $mysqli->prepare(
            "INSERT IGNORE INTO intelligent_match (userID, requestID, locationProximity, donorAvailabilityStatus, matchStatus, sendNotificationStatus)
             VALUES (?, ?, ?, 1, 'pending', 0)"
        );
        $stmtInsert->bind_param('sid', $row['userID'], $requestId, $proximity);
        $stmtInsert->execute();
        $inserted += $stmtInsert->affected_rows;
        $stmtInsert->close();
    }

    $stmt->close();

    return $inserted;
}
