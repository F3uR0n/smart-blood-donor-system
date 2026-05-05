<?php
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_date($dateStr) {
    if (!$dateStr) {
        return 'N/A';
    }
    return date('M d, Y', strtotime($dateStr));
}

function next_eligible_date($lastDonated) {
    if (!$lastDonated) {
        return null;
    }
    return date('Y-m-d', strtotime($lastDonated . ' +90 days'));
}

function donor_points($totalDonate) {
    return (int)$totalDonate * 10;
}

function compatible_blood_types($recipientType) {
    $map = [
        'A+' => ['A+', 'A-', 'O+', 'O-'],
        'A-' => ['A-', 'O-'],
        'B+' => ['B+', 'B-', 'O+', 'O-'],
        'B-' => ['B-', 'O-'],
        'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        'AB-' => ['A-', 'B-', 'AB-', 'O-'],
        'O+' => ['O+', 'O-'],
        'O-' => ['O-'],
    ];
    return $map[$recipientType] ?? [];
}

function is_eligible_health($weight, $haemoglobin, $hasDisease) {
    if ($hasDisease) {
        return false;
    }
    if ($weight === null || $haemoglobin === null) {
        return false;
    }
    return ($haemoglobin >= 13.5 && $weight >= 50.0);
}
