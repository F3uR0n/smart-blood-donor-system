<?php
// Feature 5: Blood Inventory Monitor
// GET (no params)
// Returns all blood banks with their inventory. Flags low stock and expiring soon.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../db.php';

$today   = date('Y-m-d');
$in7days = date('Y-m-d', strtotime('+7 days'));

$banksRes = mysqli_query($conn, "SELECT bankID, bankName, location, city, isOpen FROM blood_bank ORDER BY bankID");

$banks = [];
while ($bank = mysqli_fetch_assoc($banksRes)) {
    $bankID = $bank['bankID'];

    $invRes = mysqli_query($conn,
        "SELECT bloodType, unitsAvailable, expiryDate
         FROM blood_inventory
         WHERE bankID = $bankID
         ORDER BY bloodType");

    $inventory = [];
    while ($inv = mysqli_fetch_assoc($invRes)) {
        $flags = [];
        if ($inv['unitsAvailable'] < 5) $flags[] = 'low_stock';
        if ($inv['expiryDate'] && $inv['expiryDate'] <= $in7days) $flags[] = 'expiring_soon';

        $inventory[] = [
            'bloodType'      => $inv['bloodType'],
            'unitsAvailable' => (int) $inv['unitsAvailable'],
            'expiryDate'     => $inv['expiryDate'],
            'flags'          => $flags,
            'isCritical'     => !empty($flags),
        ];
    }

    $banks[] = [
        'bankID'    => $bank['bankID'],
        'bankName'  => $bank['bankName'],
        'location'  => $bank['location'],
        'city'      => $bank['city'],
        'isOpen'    => (bool) $bank['isOpen'],
        'inventory' => $inventory,
    ];
}

echo json_encode(['banks' => $banks, 'count' => count($banks), 'checkedOn' => $today]);
