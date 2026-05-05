<?php
$pageTitle = 'Campaigns';
require_once __DIR__ . '/includes/header.php';

$sql = "SELECT c.campaignID, c.title, c.tagLine, c.hostPlace, c.startDate, c.endDate, c.minDonations,
               co.name AS companyName, r.rewardItem
        FROM campaign c
        JOIN company co ON co.companyID = c.companyID
        LEFT JOIN offers o ON o.campaignID = c.campaignID
        LEFT JOIN reward r ON r.rewardID = o.rewardID
        ORDER BY c.startDate DESC";
$result = $mysqli->query($sql);

$campaigns = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['campaignID'];
    if (!isset($campaigns[$id])) {
        $campaigns[$id] = $row;
        $campaigns[$id]['rewards'] = [];
    }
    if ($row['rewardItem']) {
        $campaigns[$id]['rewards'][] = $row['rewardItem'];
    }
}
?>
<section class="container">
    <h2>Active Campaigns</h2>
    <div class="grid">
        <?php foreach ($campaigns as $campaign) : ?>
            <div class="card reveal">
                <h3><?php echo e($campaign['title']); ?></h3>
                <p><?php echo e($campaign['tagLine']); ?></p>
                <p><strong>Host:</strong> <?php echo e($campaign['hostPlace']); ?></p>
                <p><strong>Company:</strong> <?php echo e($campaign['companyName']); ?></p>
                <p><strong>Dates:</strong> <?php echo e(format_date($campaign['startDate'])); ?> - <?php echo e(format_date($campaign['endDate'])); ?></p>
                <p><strong>Min Donations:</strong> <?php echo e($campaign['minDonations']); ?></p>
                <p><strong>Rewards:</strong> <?php echo e($campaign['rewards'] ? implode(', ', $campaign['rewards']) : 'To be announced'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
