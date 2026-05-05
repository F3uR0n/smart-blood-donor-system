<?php
$pageTitle = 'Admin Panel';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/app/matching.php';
require_login('admin');

$user = current_user();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['requestID'] ?? 0);
    $feedbackId = (int)($_POST['feedbackID'] ?? 0);
    $targetUserId = $_POST['userID'] ?? '';
    $targetRole = $_POST['role'] ?? '';
    $bankId = (int)($_POST['bankID'] ?? 0);
    $campaignId = (int)($_POST['campaignID'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($targetUserId && $action === 'make_admin') {
        $stmt = $mysqli->prepare("UPDATE user SET role = 'admin' WHERE userID = ?");
        $stmt->bind_param('s', $targetUserId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("INSERT IGNORE INTO admin (AdminID) VALUES (?)");
        $stmt->bind_param('s', $targetUserId);
        $stmt->execute();
        $stmt->close();
        $notice = 'User promoted to admin.';
    }

    if ($targetUserId && $action === 'delete_user' && in_array($targetRole, ['donor', 'recipient'], true)) {
        $stmt = $mysqli->prepare("DELETE FROM user WHERE userID = ? AND role = ?");
        $stmt->bind_param('ss', $targetUserId, $targetRole);
        $stmt->execute();
        $stmt->close();
        $notice = ucfirst($targetRole) . ' account deleted.';
    }

    if ($feedbackId && $action === 'delete_feedback') {
        $stmt = $mysqli->prepare("DELETE FROM feedback WHERE feedbackID = ?");
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();
        $stmt->close();
        $notice = 'Feedback removed.';
    }

    if ($requestId && $action === 'accept') {
        $stmt = $mysqli->prepare("UPDATE emergency_request SET currentStatus = 'matched' WHERE requestID = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("INSERT IGNORE INTO manages (RequestID, UserID) VALUES (?, ?)");
        $stmt->bind_param('is', $requestId, $user['userID']);
        $stmt->execute();
        $stmt->close();

        $count = build_intelligent_matches($mysqli, $requestId);
        $notice = "Request accepted. $count donor matches created.";
    }

    if ($requestId && $action === 'cancel') {
        $stmt = $mysqli->prepare("UPDATE emergency_request SET currentStatus = 'cancelled' WHERE requestID = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        $notice = 'Request cancelled.';
    }

    if ($bankId && $action === 'toggle_bank') {
        $stmt = $mysqli->prepare("UPDATE blood_bank SET isOpen = IF(isOpen = 1, 0, 1) WHERE bankID = ?");
        $stmt->bind_param('i', $bankId);
        $stmt->execute();
        $stmt->close();
        $notice = 'Blood bank status updated.';
    }

    if ($bankId && $action === 'delete_bank') {
        $stmt = $mysqli->prepare("DELETE FROM blood_bank WHERE bankID = ?");
        $stmt->bind_param('i', $bankId);
        $stmt->execute();
        $stmt->close();
        $notice = 'Blood bank deleted.';
    }

    if ($campaignId && $action === 'delete_campaign') {
        $stmt = $mysqli->prepare("DELETE FROM campaign WHERE campaignID = ?");
        $stmt->bind_param('i', $campaignId);
        $stmt->execute();
        $stmt->close();
        $notice = 'Campaign deleted.';
    }
}

$stats = $mysqli->query("SELECT COUNT(*) AS total, SUM(currentStatus='pending') AS pending, SUM(currentStatus='matched') AS matched FROM emergency_request")->fetch_assoc();

$requests = $mysqli->query("SELECT requestID, bloodTypeNeeded, unitsNeeded, urgencyLvl, currentStatus, requestCity, reqHospitalCenter, createdAt FROM emergency_request ORDER BY createdAt DESC");

$feedback = $mysqli->query(
    "SELECT f.feedbackID, f.rating, f.comments, f.donationTrackerID,
            d.donationDate, d.hospitalCenter,
            donor.userID AS donorID, donor.firstName AS donorFirst, donor.surName AS donorLast, donor.nid AS donorNid,
            rec.userID AS recipientID, rec.firstName AS recFirst, rec.surName AS recLast
     FROM feedback f
     JOIN donation d ON d.donationID = f.donationTrackerID
     JOIN user donor ON donor.userID = d.donorID
     LEFT JOIN user rec ON rec.userID = f.givenByRecipientID
     ORDER BY f.feedbackID DESC"
);

$donors = $mysqli->query(
    "SELECT u.userID, u.firstName, u.surName, u.nid, d.bloodType, d.isAvailable,
          AVG(f.rating) AS avgRating, COUNT(f.feedbackID) AS feedbackCount,
          SUBSTRING(GROUP_CONCAT(NULLIF(f.comments, '') SEPARATOR ' | '), 1, 200) AS comments
    FROM donor d
    JOIN user u ON u.userID = d.userID
    LEFT JOIN donation dn ON dn.donorID = d.userID
    LEFT JOIN feedback f ON f.donationTrackerID = dn.donationID
    WHERE u.role = 'donor'
    GROUP BY u.userID, u.firstName, u.surName, u.nid, d.bloodType, d.isAvailable
    ORDER BY u.firstName"
);

$recipients = $mysqli->query(
    "SELECT u.userID, u.firstName, u.surName, u.nid, u.bloodType
    FROM recipient r
    JOIN user u ON u.userID = r.recipientID
    WHERE u.role = 'recipient'
    ORDER BY u.firstName"
);

$banks = $mysqli->query(
    "SELECT bankID, bankName, city, isOpen
     FROM blood_bank
     ORDER BY city, bankName"
);

$campaigns = $mysqli->query(
    "SELECT c.campaignID, c.title, c.startDate, c.endDate, co.name AS companyName
     FROM campaign c
     JOIN company co ON co.companyID = c.companyID
     ORDER BY c.startDate DESC"
);
?>
<section class="container">
    <h2>Admin Emergency Control</h2>
    <?php if ($notice) : ?>
        <div class="notice"><?php echo e($notice); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3>Requests Overview</h3>
            <p><strong>Total:</strong> <?php echo e($stats['total']); ?></p>
            <p><strong>Pending:</strong> <?php echo e($stats['pending']); ?></p>
            <p><strong>Matched:</strong> <?php echo e($stats['matched']); ?></p>
        </div>
        <div class="card">
            <h3>Admin Actions</h3>
            <p>Accept requests to trigger intelligent donor matching. Cancel invalid requests to keep the queue clean.</p>
        </div>
    </div>

    <h3 class="section">Emergency Requests</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Blood</th>
                <th>Units</th>
                <th>Urgency</th>
                <th>City</th>
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
                    <td><?php echo e($row['requestCity']); ?></td>
                    <td><?php echo e($row['currentStatus']); ?></td>
                    <td>
                        <form method="post" style="display: inline-flex; gap: 8px;">
                            <input type="hidden" name="requestID" value="<?php echo e($row['requestID']); ?>">
                            <?php if ($row['currentStatus'] === 'pending') : ?>
                                <button class="btn" type="submit" name="action" value="accept">Accept</button>
                                <button class="btn ghost" type="submit" name="action" value="cancel">Cancel</button>
                            <?php else : ?>
                                <span class="badge">Locked</span>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">Feedback Management</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Donor</th>
                <th>Donor NID</th>
                <th>Recipient</th>
                <th>Donation</th>
                <th>Rating</th>
                <th>Comments</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $feedback->fetch_assoc()) : ?>
                <tr>
                    <td>#<?php echo e($row['feedbackID']); ?></td>
                    <td><?php echo e($row['donorFirst'] . ' ' . $row['donorLast']); ?></td>
                    <td><?php echo e($row['donorNid']); ?></td>
                    <td><?php echo e(trim(($row['recFirst'] ?? '') . ' ' . ($row['recLast'] ?? '')) ?: 'Unknown'); ?></td>
                    <td>#<?php echo e($row['donationTrackerID']); ?> · <?php echo e(format_date($row['donationDate'])); ?></td>
                    <td><?php echo e($row['rating']); ?>/5</td>
                    <td><?php echo e($row['comments'] ?: 'No comments'); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="feedbackID" value="<?php echo e($row['feedbackID']); ?>">
                            <button class="btn ghost" type="submit" name="action" value="delete_feedback">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">User Management</h3>
    <h4>Donors</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>NID</th>
                <th>Blood</th>
                <th>Availability</th>
                <th>Rating</th>
                <th>Feedback</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $donors->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                    <td><?php echo e($row['nid']); ?></td>
                    <td><?php echo e($row['bloodType']); ?></td>
                    <td><?php echo $row['isAvailable'] ? 'Available' : 'Unavailable'; ?></td>
                    <td><?php echo $row['feedbackCount'] ? e(number_format((float)$row['avgRating'], 1)) . '/5' : 'No ratings'; ?></td>
                    <td><?php echo e($row['comments'] ?: 'No comments'); ?></td>
                    <td>
                        <form method="post" style="display: inline-flex; gap: 8px;">
                            <input type="hidden" name="userID" value="<?php echo e($row['userID']); ?>">
                            <input type="hidden" name="role" value="donor">
                            <button class="btn" type="submit" name="action" value="make_admin">Make Admin</button>
                            <button class="btn ghost" type="submit" name="action" value="delete_user">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h4>Recipients</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>NID</th>
                <th>Blood</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $recipients->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                    <td><?php echo e($row['nid']); ?></td>
                    <td><?php echo e($row['bloodType']); ?></td>
                    <td>
                        <form method="post" style="display: inline-flex; gap: 8px;">
                            <input type="hidden" name="userID" value="<?php echo e($row['userID']); ?>">
                            <input type="hidden" name="role" value="recipient">
                            <button class="btn" type="submit" name="action" value="make_admin">Make Admin</button>
                            <button class="btn ghost" type="submit" name="action" value="delete_user">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">Blood Bank Management</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Bank</th>
                <th>City</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $banks->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['bankName']); ?></td>
                    <td><?php echo e($row['city']); ?></td>
                    <td><?php echo $row['isOpen'] ? 'Open' : 'Closed'; ?></td>
                    <td>
                        <form method="post" style="display: inline-flex; gap: 8px;">
                            <input type="hidden" name="bankID" value="<?php echo e($row['bankID']); ?>">
                            <button class="btn" type="submit" name="action" value="toggle_bank">Toggle</button>
                            <button class="btn ghost" type="submit" name="action" value="delete_bank">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="section">Campaign Management</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Campaign</th>
                <th>Company</th>
                <th>Dates</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $campaigns->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['title']); ?></td>
                    <td><?php echo e($row['companyName']); ?></td>
                    <td><?php echo e(format_date($row['startDate'])); ?> - <?php echo e(format_date($row['endDate'])); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="campaignID" value="<?php echo e($row['campaignID']); ?>">
                            <button class="btn ghost" type="submit" name="action" value="delete_campaign">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
