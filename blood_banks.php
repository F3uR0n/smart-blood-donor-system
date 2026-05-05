<?php
$pageTitle = 'Blood Banks';
require_once __DIR__ . '/includes/header.php';

$sql = "SELECT b.bankID, b.bankName, b.location, b.city, b.isOpen,
               i.bloodType, i.unitsAvailable, i.expiryDate
        FROM blood_bank b
        LEFT JOIN blood_inventory i ON i.bankID = b.bankID
        ORDER BY b.city, b.bankName";
$result = $mysqli->query($sql);

$banks = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['bankID'];
    if (!isset($banks[$id])) {
        $banks[$id] = $row;
        $banks[$id]['inventory'] = [];
    }
    if ($row['bloodType']) {
        $banks[$id]['inventory'][] = $row;
    }
}
?>
<section class="container">
    <h2>Blood Bank Inventory</h2>
    <div class="grid">
        <?php foreach ($banks as $bank) : ?>
            <div class="card reveal">
                <h3><?php echo e($bank['bankName']); ?></h3>
                <p><?php echo e($bank['location']); ?>, <?php echo e($bank['city']); ?></p>
                <p><?php echo $bank['isOpen'] ? '<span class="badge">Open</span>' : '<span class="badge warn">Closed</span>'; ?></p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Blood</th>
                            <th>Units</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bank['inventory'] as $inv) : ?>
                            <tr>
                                <td><?php echo e($inv['bloodType']); ?></td>
                                <td><?php echo e($inv['unitsAvailable']); ?></td>
                                <td><?php echo e(format_date($inv['expiryDate'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
