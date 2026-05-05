<?php
$pageTitle = 'Emergency Intelligence';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/app/matching.php';
require_login('recipient');

$user = current_user();
$requestId = (int)($_GET['requestID'] ?? 0);

$stmt = $mysqli->prepare("SELECT * FROM emergency_request WHERE requestID = ? AND userID = ?");
$stmt->bind_param('is', $requestId, $user['userID']);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    header('Location: recipient_dashboard.php');
    exit;
}

$notice = '';
if (isset($_POST['notify'])) {
    $stmt = $mysqli->prepare("UPDATE intelligent_match SET sendNotificationStatus = 1 WHERE requestID = ?");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $stmt->close();
    $notice = 'Notification flag updated for matched donors.';
}

$matches = $mysqli->prepare(
    "SELECT im.userID, im.locationProximity, im.matchStatus, im.sendNotificationStatus,
            u.firstName, u.surName, u.phone, d.bloodType, d.lastDonated, d.totalDonate,
            hp.weightKg, hp.haemoglobinLvl
     FROM intelligent_match im
     JOIN donor d ON d.userID = im.userID
     JOIN user u ON u.userID = d.userID
     LEFT JOIN health_profile hp ON hp.userID = d.userID
     WHERE im.requestID = ?
     ORDER BY im.locationProximity ASC"
);
$matches->bind_param('i', $requestId);
$matches->execute();
$matchResult = $matches->get_result();
$matches->close();
?>
<section class="container">
    <h2>Emergency Intelligence</h2>
    <p><strong>Request:</strong> #<?php echo e($request['requestID']); ?> | <?php echo e($request['bloodTypeNeeded']); ?> | <?php echo e($request['urgencyLvl']); ?></p>
    <?php if ($notice) : ?>
        <div class="notice"><?php echo e($notice); ?></div>
    <?php endif; ?>

    <div class="card" style="margin-top: 18px;">
        <h3>Matched Donors</h3>
        <form method="post" style="margin-bottom: 12px;">
            <button class="btn ghost" type="submit" name="notify">Send Notification</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Donor</th>
                    <th>Blood</th>
                    <th>Phone</th>
                    <th>Proximity</th>
                    <th>Last Donated</th>
                    <th>Status</th>
                    <th>Notified</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $matchResult->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                        <td><?php echo e($row['bloodType']); ?></td>
                        <td><?php echo e($row['phone']); ?></td>
                        <td><?php echo e($row['locationProximity']); ?> km</td>
                        <td><?php echo e(format_date($row['lastDonated'])); ?></td>
                        <td><?php echo e($row['matchStatus']); ?></td>
                        <td><?php echo $row['sendNotificationStatus'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
