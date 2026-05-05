<?php
$pageTitle = 'Recipient Dashboard';
require_once __DIR__ . '/includes/header.php';
require_login('recipient');

$user = current_user();

$stmt = $mysqli->prepare("SELECT COUNT(*) AS total, SUM(currentStatus='pending') AS pending, SUM(currentStatus='matched') AS matched FROM emergency_request WHERE userID = ?");
$stmt->bind_param('s', $user['userID']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare("SELECT requestID, bloodTypeNeeded, unitsNeeded, urgencyLvl, currentStatus, createdAt FROM emergency_request WHERE userID = ? ORDER BY createdAt DESC");
$stmt->bind_param('s', $user['userID']);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();

$notifStmt = $mysqli->prepare(
    "SELECT im.requestID, im.matchStatus, er.urgencyLvl, er.unitsNeeded,
            er.requestCity, er.reqHospitalCenter, u.firstName, u.surName, u.phone
     FROM intelligent_match im
     JOIN emergency_request er ON er.requestID = im.requestID
     JOIN user u ON u.userID = im.userID
     WHERE er.userID = ? AND im.sendNotificationStatus = 1
     ORDER BY er.createdAt DESC"
);
$notifStmt->bind_param('s', $user['userID']);
$notifStmt->execute();
$notifications = $notifStmt->get_result();
$notifStmt->close();

$bankStmt = $mysqli->prepare("SELECT b.bankName, b.city FROM can_check c JOIN blood_bank b ON b.bankID = c.BankID WHERE c.UserID = ?");
$bankStmt->bind_param('s', $user['userID']);
$bankStmt->execute();
$banks = $bankStmt->get_result();
$bankStmt->close();
?>
<section class="container">
    <h2>Recipient Dashboard</h2>
    <div class="grid">
        <div class="card">
            <h3>My Requests</h3>
            <p><strong>Total:</strong> <?php echo e($stats['total']); ?></p>
            <p><strong>Pending:</strong> <?php echo e($stats['pending']); ?></p>
            <p><strong>Matched:</strong> <?php echo e($stats['matched']); ?></p>
            <a class="btn" href="emergency_request.php">Create Emergency Request</a>
        </div>
        <div class="card">
            <h3>Authorized Blood Banks</h3>
            <?php while ($bank = $banks->fetch_assoc()) : ?>
                <p><?php echo e($bank['bankName']); ?> - <?php echo e($bank['city']); ?></p>
            <?php endwhile; ?>
        </div>
        <div class="card">
            <h3>Quick Links</h3>
            <p><a href="find_donor.php">Find Donor</a></p>
            <p><a href="blood_banks.php">Blood Inventory</a></p>
            <p><a href="campaigns.php">Campaigns</a></p>
            <p><a href="feedback.php">Submit Feedback</a></p>
        </div>
    </div>

    <h3 class="section">Notifications</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Donor</th>
                <th>Phone</th>
                <th>Urgency</th>
                <th>Hospital</th>
                <th>City</th>
                <th>Units</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $notifications->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                    <td><?php echo e($row['phone']); ?></td>
                    <td><?php echo e($row['urgencyLvl']); ?></td>
                    <td><?php echo e($row['reqHospitalCenter']); ?></td>
                    <td><?php echo e($row['requestCity']); ?></td>
                    <td><?php echo e($row['unitsNeeded']); ?></td>
                    <td><?php echo e($row['matchStatus']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">Emergency Requests</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Blood</th>
                <th>Units</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()) : ?>
                <tr>
                    <td>#<?php echo e($row['requestID']); ?></td>
                    <td><?php echo e($row['bloodTypeNeeded']); ?></td>
                    <td><?php echo e($row['unitsNeeded']); ?></td>
                    <td><?php echo e($row['urgencyLvl']); ?></td>
                    <td><?php echo e($row['currentStatus']); ?></td>
                    <td><a href="emergency_matches.php?requestID=<?php echo e($row['requestID']); ?>">View Intelligence</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
