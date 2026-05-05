<?php
$pageTitle = 'Find Donor';
require_once __DIR__ . '/includes/header.php';

$bloodType = $_GET['bloodType'] ?? '';
$city = $_GET['city'] ?? '';

$query = "SELECT u.firstName, u.surName, u.phone, u.bloodType,
                                 d.isAvailable, d.lastDonated, d.totalDonate,
                                 ul.location, hp.weightKg, hp.haemoglobinLvl,
                                 (SELECT COUNT(*) FROM diseases ds WHERE ds.healthID = hp.healthID) AS diseaseCount
                    FROM donor d
                    JOIN user u ON u.userID = d.userID
                    LEFT JOIN user_location ul ON ul.userID = d.userID
                    LEFT JOIN health_profile hp ON hp.userID = d.userID
                    WHERE d.isAvailable = 1
                        AND hp.weightKg IS NOT NULL
                        AND hp.haemoglobinLvl IS NOT NULL
                        AND hp.weightKg >= 50
                        AND hp.haemoglobinLvl >= 13.5
                        AND (d.lastDonated IS NULL OR d.lastDonated <= DATE_SUB(CURDATE(), INTERVAL 90 DAY))
                        AND NOT EXISTS (
                                SELECT 1 FROM diseases ds WHERE ds.healthID = hp.healthID
                        )";
$params = [];
$types = '';

if ($bloodType) {
    $query .= " AND d.bloodType = ?";
    $types .= 's';
    $params[] = $bloodType;
}
if ($city) {
    $query .= " AND ul.location LIKE ?";
    $types .= 's';
    $params[] = '%' . $city . '%';
}

$stmt = $mysqli->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$donors = $stmt->get_result();
$stmt->close();
?>
<section class="container">
    <h2>Find Donor</h2>
    <form class="form" method="get">
        <div>
            <label>Blood Type</label>
            <select name="bloodType">
                <option value="">Any</option>
                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type) : ?>
                    <option value="<?php echo e($type); ?>" <?php echo $bloodType === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>City / Area</label>
            <input class="input" type="text" name="city" value="<?php echo e($city); ?>" placeholder="Dhaka, Sylhet, ...">
        </div>
        <button class="btn" type="submit">Search</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Donor</th>
                <th>Blood Type</th>
                <th>Location</th>
                <th>Eligibility</th>
                <th>Last Donated</th>
                <th>Total Donations</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $donors->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo e($row['firstName'] . ' ' . $row['surName']); ?></td>
                    <td><?php echo e($row['bloodType']); ?></td>
                    <td><?php echo e($row['location'] ?? 'N/A'); ?></td>
                    <?php
                        $nextEligible = next_eligible_date($row['lastDonated']);
                        $eligibleByDate = !$row['lastDonated'] || (strtotime($nextEligible) <= strtotime(date('Y-m-d')));
                        $eligibleByHealth = is_eligible_health($row['weightKg'], $row['haemoglobinLvl'], $row['diseaseCount'] > 0);
                        $isEligible = $eligibleByDate && $eligibleByHealth && (int)$row['isAvailable'] === 1;
                    ?>
                    <td><?php echo $isEligible ? '<span class="badge">Eligible</span>' : '<span class="badge warn">Not Eligible</span>'; ?></td>
                    <td><?php echo e(format_date($row['lastDonated'])); ?></td>
                    <td><?php echo e($row['totalDonate']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
