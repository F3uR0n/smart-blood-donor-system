<?php
$pageTitle = 'Donor Dashboard';
require_once __DIR__ . '/includes/header.php';
require_login('donor');

$user = current_user();
$notice = '';
$availabilityError = '';
$rewardNotice = '';
$notificationNotice = '';

$stmt = $mysqli->prepare("SELECT d.userID, d.bloodType, d.isAvailable, d.lastDonated, d.totalDonate,
                                  hp.weightKg, hp.haemoglobinLvl,
                                  (SELECT COUNT(*) FROM diseases ds WHERE ds.healthID = hp.healthID) AS diseaseCount
                           FROM donor d
                           LEFT JOIN health_profile hp ON hp.userID = d.userID
                           WHERE d.userID = ?");
$stmt->bind_param('s', $user['userID']);
$stmt->execute();
$donor = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nextEligible = next_eligible_date($donor['lastDonated']);
$eligibleByDate = !$donor['lastDonated'] || (strtotime($nextEligible) <= strtotime(date('Y-m-d')));
$eligibleByHealth = is_eligible_health($donor['weightKg'], $donor['haemoglobinLvl'], $donor['diseaseCount'] > 0);
$isEligible = $eligibleByDate && $eligibleByHealth;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability'])) {
    $avail = $_POST['availability'] === '1' ? 1 : 0;
    if ($isEligible) {
        $stmt = $mysqli->prepare("UPDATE donor SET isAvailable = ? WHERE userID = ?");
        $stmt->bind_param('is', $avail, $user['userID']);
        $stmt->execute();
        $stmt->close();
        $donor['isAvailable'] = $avail;
        $notice = 'Availability updated.';
    } else {
        $availabilityError = 'You can only set availability when you are fully eligible.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'respond_notification') {
    $requestId = (int)($_POST['requestID'] ?? 0);
    $response = $_POST['response'] ?? '';
    if ($requestId && in_array($response, ['accept', 'reject'], true)) {
        $newStatus = $response === 'accept' ? 'matched' : 'declined';
        $stmt = $mysqli->prepare("UPDATE intelligent_match SET matchStatus = ? WHERE requestID = ? AND userID = ?");
        $stmt->bind_param('sis', $newStatus, $requestId, $user['userID']);
        $stmt->execute();
        $stmt->close();
        $notificationNotice = 'Notification response saved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim_reward') {
    $rewardId = (int)($_POST['rewardID'] ?? 0);
    $campaignId = (int)($_POST['campaignID'] ?? 0);
    if ($rewardId && $campaignId) {
        $existing = $mysqli->prepare("SELECT 1 FROM accept WHERE rewardID = ? AND userID = ?");
        $existing->bind_param('is', $rewardId, $user['userID']);
        $existing->execute();
        $alreadyClaimed = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($alreadyClaimed) {
            $rewardNotice = 'You already claimed this reward.';
        } else {
        $check = $mysqli->prepare(
            "SELECT c.endDate
             FROM campaign c
             JOIN offers o ON o.campaignID = c.campaignID
             WHERE c.campaignID = ? AND o.rewardID = ? AND c.minDonations <= ?"
        );
        $check->bind_param('iii', $campaignId, $rewardId, $donor['totalDonate']);
        $check->execute();
        $campaign = $check->get_result()->fetch_assoc();
        $check->close();

        if ($campaign) {
            $claimDate = $campaign['endDate'] . ' 00:00:00';
            $stmt = $mysqli->prepare(
                "INSERT IGNORE INTO accept (rewardID, userID, claimedAt, claimedStatus, eligibilityChecked)
                 VALUES (?, ?, ?, 1, 1)"
            );
            $stmt->bind_param('iss', $rewardId, $user['userID'], $claimDate);
            $stmt->execute();
            $stmt->close();
            $detailStmt = $mysqli->prepare(
                "SELECT co.name AS companyName,
                        GROUP_CONCAT(DISTINCT cl.location SEPARATOR ', ') AS locations
                 FROM offers o
                 JOIN campaign c ON c.campaignID = o.campaignID
                 JOIN company co ON co.companyID = c.companyID
                 LEFT JOIN company_location cl ON cl.companyID = co.companyID
                 WHERE o.rewardID = ? AND o.campaignID = ?
                 GROUP BY co.name"
            );
            $detailStmt->bind_param('ii', $rewardId, $campaignId);
            $detailStmt->execute();
            $claimDetails = $detailStmt->get_result()->fetch_assoc();
            $detailStmt->close();

            $companyName = $claimDetails['companyName'] ?? 'the company';
            $locations = $claimDetails['locations'] ?? '';
            if ($locations) {
                $rewardNotice = 'Reward claimed. Please visit ' . $companyName . ' (' . $locations . ') to collect it.';
            } else {
                $rewardNotice = 'Reward claimed. Please visit ' . $companyName . ' to collect it.';
            }
        } else {
            $rewardNotice = 'Reward claim failed. Please verify eligibility.';
        }
        }
    }
}

$donations = $mysqli->prepare("SELECT donationDate, hospitalCenter, unitsDonated FROM donation WHERE donorID = ? ORDER BY donationDate DESC");
$donations->bind_param('s', $user['userID']);
$donations->execute();
$donationResult = $donations->get_result();
$donations->close();

$rewards = $mysqli->prepare(
    "SELECT c.campaignID, c.title, c.minDonations, c.endDate,
            r.rewardID, r.rewardItem, co.name AS companyName,
            GROUP_CONCAT(DISTINCT cl.location SEPARATOR ', ') AS locations
     FROM campaign c
     JOIN offers o ON o.campaignID = c.campaignID
     JOIN reward r ON r.rewardID = o.rewardID
     JOIN company co ON co.companyID = c.companyID
     LEFT JOIN company_location cl ON cl.companyID = co.companyID
     WHERE c.minDonations <= ?
     GROUP BY c.campaignID, r.rewardID, co.name"
);
$rewards->bind_param('i', $donor['totalDonate']);
$rewards->execute();
$rewardResult = $rewards->get_result();
$rewards->close();

$claimStmt = $mysqli->prepare("SELECT rewardID, claimedAt FROM accept WHERE userID = ?");
$claimStmt->bind_param('s', $user['userID']);
$claimStmt->execute();
$claimResult = $claimStmt->get_result();
$claimStmt->close();

$claimedRewards = [];
while ($row = $claimResult->fetch_assoc()) {
    $claimedRewards[(int)$row['rewardID']] = $row['claimedAt'];
}

$notifications = $mysqli->prepare(
    "SELECT im.requestID, im.matchStatus, er.urgencyLvl, er.unitsNeeded,
            er.requestCity, er.reqHospitalCenter, er.createdAt,
            u.firstName, u.surName, u.phone
     FROM intelligent_match im
     JOIN emergency_request er ON er.requestID = im.requestID
     JOIN user u ON u.userID = er.userID
     WHERE im.userID = ? AND im.sendNotificationStatus = 1
     ORDER BY er.createdAt DESC"
);
$notifications->bind_param('s', $user['userID']);
$notifications->execute();
$notificationResult = $notifications->get_result();
$notifications->close();

$rewardClaims = $mysqli->prepare(
    "SELECT a.rewardID, a.claimedAt, r.rewardItem
     FROM accept a
     JOIN reward r ON r.rewardID = a.rewardID
     WHERE a.userID = ?
     ORDER BY a.claimedAt DESC"
);
$rewardClaims->bind_param('s', $user['userID']);
$rewardClaims->execute();
$rewardClaimResult = $rewardClaims->get_result();
$rewardClaims->close();

$leaderboard = $mysqli->query(
    "SELECT u.firstName, u.surName, d.totalDonate,
            DENSE_RANK() OVER (ORDER BY d.totalDonate DESC) AS rankPos
     FROM donor d
     JOIN user u ON u.userID = d.userID
     ORDER BY d.totalDonate DESC
     LIMIT 5"
);
?>
<section class="container">
    <h2>Donor Dashboard</h2>
    <?php if ($notice) : ?>
        <div class="notice"><?php echo e($notice); ?></div>
    <?php endif; ?>
    <?php if ($rewardNotice) : ?>
        <div class="notice"><?php echo e($rewardNotice); ?></div>
    <?php endif; ?>
    <?php if ($notificationNotice) : ?>
        <div class="notice"><?php echo e($notificationNotice); ?></div>
    <?php endif; ?>
    <?php if ($availabilityError) : ?>
        <div class="notice warn"><?php echo e($availabilityError); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3>Eligibility Status</h3>
            <p><strong>Blood:</strong> <?php echo e($donor['bloodType']); ?></p>
            <p><strong>Last Donated:</strong> <?php echo e(format_date($donor['lastDonated'])); ?></p>
            <p><strong>Next Eligible:</strong> <?php echo e($nextEligible ? format_date($nextEligible) : 'Now'); ?></p>
            <p><strong>Health:</strong> <?php echo $eligibleByHealth ? '<span class="badge">Cleared</span>' : '<span class="badge warn">Review</span>'; ?></p>
            <p><strong>Date Gap:</strong> <?php echo $eligibleByDate ? '<span class="badge">Eligible</span>' : '<span class="badge warn">Cooldown</span>'; ?></p>
            <p><strong>Overall:</strong> <?php echo $isEligible ? '<span class="badge">Eligible</span>' : '<span class="badge warn">Not Eligible</span>'; ?></p>
            <a class="btn ghost" href="#health-card">View Health Card</a>
        </div>
        <div class="card">
            <h3>Availability</h3>
            <form method="post" class="form">
                <label>Set availability</label>
                <select name="availability" <?php echo $isEligible ? '' : 'disabled'; ?>>
                    <option value="1" <?php echo $donor['isAvailable'] ? 'selected' : ''; ?>>Available</option>
                    <option value="0" <?php echo !$donor['isAvailable'] ? 'selected' : ''; ?>>Unavailable</option>
                </select>
                <button class="btn" type="submit" <?php echo $isEligible ? '' : 'disabled'; ?>>Update</button>
                <?php if (!$isEligible) : ?>
                    <p class="muted">Complete eligibility checks to unlock availability.</p>
                <?php endif; ?>
            </form>
        </div>
        <div class="card">
            <h3>Points & Rank</h3>
            <p><strong>Total Donations:</strong> <?php echo e($donor['totalDonate']); ?></p>
            <p><strong>Points:</strong> <?php echo e(donor_points($donor['totalDonate'])); ?></p>
            <p>Top donors appear in the leaderboard.</p>
        </div>
    </div>

    <h3 class="section" id="health-card">Health Card</h3>
    <div class="card">
        <p><strong>Weight:</strong> <?php echo $donor['weightKg'] !== null ? e($donor['weightKg']) . ' kg' : 'Not recorded'; ?></p>
        <p><strong>Hemoglobin:</strong> <?php echo $donor['haemoglobinLvl'] !== null ? e($donor['haemoglobinLvl']) . ' g/dL' : 'Not recorded'; ?></p>
        <p><strong>Recorded Diseases:</strong> <?php echo $donor['diseaseCount'] > 0 ? e($donor['diseaseCount']) : 'None'; ?></p>
        <p><strong>Eligibility Rules:</strong> Weight >= 50 kg, Hemoglobin >= 13.5 g/dL, last donation gap >= 90 days.</p>
        <p><strong>Status:</strong> <?php echo $isEligible ? '<span class="badge">Eligible</span>' : '<span class="badge warn">Not Eligible</span>'; ?></p>
    </div>

    <h3 class="section">Notifications</h3>
    <div class="card">
        <h4>Emergency Requests</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Urgency</th>
                    <th>Hospital</th>
                    <th>City</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $notificationResult->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                        <td><?php echo e($row['urgencyLvl']); ?></td>
                        <td><?php echo e($row['reqHospitalCenter']); ?></td>
                        <td><?php echo e($row['requestCity']); ?></td>
                        <td><?php echo e($row['unitsNeeded']); ?></td>
                        <td><?php echo e($row['matchStatus']); ?></td>
                        <td>
                            <?php if ($row['matchStatus'] === 'pending') : ?>
                                <form method="post" style="display: inline-flex; gap: 8px;">
                                    <input type="hidden" name="action" value="respond_notification">
                                    <input type="hidden" name="requestID" value="<?php echo e($row['requestID']); ?>">
                                    <button class="btn" type="submit" name="response" value="accept">Accept</button>
                                    <button class="btn ghost" type="submit" name="response" value="reject">Reject</button>
                                </form>
                            <?php else : ?>
                                <span class="badge">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h4>Reward Claims</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Reward</th>
                    <th>Claim Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $rewardClaimResult->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo e($row['rewardItem']); ?></td>
                        <td><?php echo e(format_date($row['claimedAt'])); ?></td>
                        <td>Claimed</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h3 class="section">Recent Donations</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Hospital</th>
                <th>Units</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $donationResult->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e(format_date($row['donationDate'])); ?></td>
                    <td><?php echo e($row['hospitalCenter']); ?></td>
                    <td><?php echo e($row['unitsDonated']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">Eligible Rewards</h3>
    <div class="grid">
        <?php while ($row = $rewardResult->fetch_assoc()) : ?>
            <?php $isClaimed = array_key_exists((int)$row['rewardID'], $claimedRewards); ?>
            <div class="card">
                <h4><?php echo e($row['rewardItem']); ?></h4>
                <p><?php echo e($row['title']); ?> by <?php echo e($row['companyName']); ?></p>
                <p>Minimum donations required: <?php echo e($row['minDonations']); ?></p>
                <p>Claim date: <?php echo e(format_date($row['endDate'])); ?></p>
                <?php if ($isClaimed) : ?>
                    <span class="badge">Claimed</span>
                <?php else : ?>
                    <form method="post">
                        <input type="hidden" name="action" value="claim_reward">
                        <input type="hidden" name="rewardID" value="<?php echo e($row['rewardID']); ?>">
                        <input type="hidden" name="campaignID" value="<?php echo e($row['campaignID']); ?>">
                        <button class="btn claim-btn" type="submit"
                            data-claim-location="<?php echo e($row['locations'] ?: 'Check with the company'); ?>"
                            data-claim-date="<?php echo e(format_date($row['endDate'])); ?>">
                            Claim Reward
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <h3 class="section">Leaderboard</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Donor</th>
                <th>Donations</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $leaderboard->fetch_assoc()) : ?>
                <tr>
                    <td>#<?php echo e($row['rankPos']); ?></td>
                    <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                    <td><?php echo e($row['totalDonate']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
