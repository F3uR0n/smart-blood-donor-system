<?php
$pageTitle = 'Emergency Request';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/app/matching.php';
require_login('recipient');

$user = current_user();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bloodType = $_POST['bloodTypeNeeded'] ?? '';
    $units = (int)($_POST['unitsNeeded'] ?? 1);
    $urgency = $_POST['urgencyLvl'] ?? 'high';
    $city = trim($_POST['requestCity'] ?? '');
    $hospital = trim($_POST['reqHospitalCenter'] ?? '');

    $stmt = $mysqli->prepare("INSERT INTO emergency_request (bloodTypeNeeded, unitsNeeded, urgencyLvl, requestCity, reqHospitalCenter, userID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sissss', $bloodType, $units, $urgency, $city, $hospital, $user['userID']);
    $stmt->execute();
    $stmt->close();

    $requestId = $mysqli->insert_id;
    $matchCount = build_intelligent_matches($mysqli, $requestId);
    if ($matchCount > 0) {
        $stmt = $mysqli->prepare("UPDATE intelligent_match SET sendNotificationStatus = 1 WHERE requestID = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("UPDATE emergency_request SET currentStatus = 'matched' WHERE requestID = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        $success = "Emergency request submitted. $matchCount eligible donors notified.";
    } else {
        $success = 'Emergency request submitted. No eligible donors found yet.';
    }
}

$stmt = $mysqli->prepare("SELECT requestID, bloodTypeNeeded, unitsNeeded, urgencyLvl, currentStatus, createdAt FROM emergency_request WHERE userID = ? ORDER BY createdAt DESC");
$stmt->bind_param('s', $user['userID']);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
?>
<section class="container">
    <h2>Submit Emergency Request</h2>
    <?php if ($success) : ?>
        <div class="notice"><?php echo e($success); ?></div>
    <?php endif; ?>
    <form class="form" method="post">
        <div>
            <label>Blood Type Needed</label>
            <select name="bloodTypeNeeded" required>
                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type) : ?>
                    <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Units Needed</label>
            <input class="input" type="number" name="unitsNeeded" min="1" value="1">
        </div>
        <div>
            <label>Urgency Level</label>
            <select name="urgencyLvl">
                <?php foreach (['critical','high','medium','low'] as $lvl) : ?>
                    <option value="<?php echo e($lvl); ?>"><?php echo e($lvl); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>City</label>
            <input class="input" type="text" name="requestCity" required>
        </div>
        <div>
            <label>Hospital / Center</label>
            <input class="input" type="text" name="reqHospitalCenter" required>
        </div>
        <button class="btn" type="submit">Create Request</button>
    </form>

    <h3 class="section">My Requests</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Blood</th>
                <th>Units</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Intelligence</th>
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
