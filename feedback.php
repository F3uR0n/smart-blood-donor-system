<?php
$pageTitle = 'Submit Feedback';
require_once __DIR__ . '/includes/header.php';
require_login('recipient');

$user = current_user();
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donationId = (int)($_POST['donationTrackerID'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comments = trim($_POST['comments'] ?? '');

    if ($donationId && $rating >= 1 && $rating <= 5) {
        $check = $mysqli->prepare(
            "SELECT d.donationID
             FROM donation d
             JOIN intelligent_match im ON im.userID = d.donorID AND im.matchStatus = 'donated'
             JOIN emergency_request er ON er.requestID = im.requestID AND er.userID = ?
             LEFT JOIN feedback f ON f.donationTrackerID = d.donationID AND f.givenByRecipientID = ?
             WHERE d.donationID = ? AND f.feedbackID IS NULL
             LIMIT 1"
        );
        $check->bind_param('ssi', $user['userID'], $user['userID'], $donationId);
        $check->execute();
        $eligible = $check->get_result()->fetch_assoc();
        $check->close();

        if ($eligible) {
            $stmt = $mysqli->prepare("INSERT INTO feedback (donationTrackerID, rating, comments, givenByRecipientID) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiss', $donationId, $rating, $comments, $user['userID']);
            $stmt->execute();
            $stmt->close();
            $notice = 'Feedback submitted successfully.';
        } else {
            $error = 'This donation is not eligible for your feedback or was already reviewed.';
        }
    } else {
        $error = 'Please select a valid donation and rating.';
    }
}

$stmt = $mysqli->prepare(
    "SELECT d.donationID, d.donationDate, d.hospitalCenter, u.firstName, u.surName, u.nid
     FROM donation d
     JOIN intelligent_match im ON im.userID = d.donorID AND im.matchStatus = 'donated'
     JOIN emergency_request er ON er.requestID = im.requestID AND er.userID = ?
     JOIN user u ON u.userID = d.donorID
     LEFT JOIN feedback f ON f.donationTrackerID = d.donationID AND f.givenByRecipientID = ?
     WHERE f.feedbackID IS NULL
     ORDER BY d.donationDate DESC"
);
$stmt->bind_param('ss', $user['userID'], $user['userID']);
$stmt->execute();
$donations = $stmt->get_result();
$stmt->close();
?>
<section class="container">
    <h2>Feedback & Rating</h2>
    <?php if ($notice) : ?>
        <div class="notice"><?php echo e($notice); ?></div>
    <?php endif; ?>
    <?php if ($error) : ?>
        <div class="notice warn"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form class="form" method="post">
        <div>
            <label>Donation</label>
            <select name="donationTrackerID" required>
                <option value="">Select a donation</option>
                <?php while ($row = $donations->fetch_assoc()) : ?>
                    <option value="<?php echo e($row['donationID']); ?>">
                        #<?php echo e($row['donationID']); ?> · <?php echo e($row['firstName'] . ' ' . $row['surName']); ?> · <?php echo e(format_date($row['donationDate'])); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Rating (1-5)</label>
            <select name="rating">
                <?php foreach ([5,4,3,2,1] as $rate) : ?>
                    <option value="<?php echo e($rate); ?>"><?php echo e($rate); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Comments</label>
            <textarea name="comments" rows="4" placeholder="Share your experience"></textarea>
        </div>
        <button class="btn" type="submit">Submit Feedback</button>
    </form>
    <?php if ($donations->num_rows === 0) : ?>
        <p class="muted">No eligible donated matches found for feedback yet.</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
