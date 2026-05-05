<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

$user = current_user();
$pageTitle = $pageTitle ?? 'Smart Blood Network';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Work+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/reveal.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="brand">
            <a href="index.php">Smart Blood</a>
            <span>Emergency Donor Network</span>
        </div>
        <nav class="site-nav" id="siteNav">
            <a href="index.php">Home</a>
            <a href="campaigns.php">Campaigns</a>
            <a href="find_donor.php">Find Donor</a>
            <a href="blood_banks.php">Blood Banks</a>
            <?php if ($user && $user['role'] === 'donor') : ?>
                <a href="donor_dashboard.php">Donor Dashboard</a>
            <?php elseif ($user && $user['role'] === 'recipient') : ?>
                <a href="recipient_dashboard.php">Recipient Dashboard</a>
                <a href="emergency_request.php">Emergency Request</a>
                <a href="feedback.php">Feedback</a>
            <?php elseif ($user && $user['role'] === 'admin') : ?>
                <a href="admin_dashboard.php">Admin Panel</a>
            <?php endif; ?>
        </nav>
        <div class="nav-actions">
            <?php if ($user) : ?>
                <span class="user-badge"><?php echo e($user['firstName']); ?> (<?php echo e($user['role']); ?>)</span>
                <a class="btn ghost" href="logout.php">Logout</a>
            <?php else : ?>
                <a class="btn ghost" href="login.php">Login</a>
            <?php endif; ?>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">☰</button>
        </div>
    </div>
</header>
<main class="page">