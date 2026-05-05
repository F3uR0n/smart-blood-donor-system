<?php
$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';

$notice = '';
$error = '';
$fieldErrors = [
    'email' => '',
    'nid' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $surName = trim($_POST['surName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $bloodType = $_POST['bloodType'] ?? '';
    $role = $_POST['role'] ?? 'donor';
    $password = trim($_POST['password'] ?? '');
    $weight = $_POST['weightKg'] !== '' ? (float)$_POST['weightKg'] : null;
    $haemoglobin = $_POST['haemoglobinLvl'] !== '' ? (float)$_POST['haemoglobinLvl'] : null;
    $lastDonated = trim($_POST['lastDonated'] ?? '');
    $lastDonated = $lastDonated !== '' ? $lastDonated : null;

    if (!in_array($role, ['donor', 'recipient'], true)) {
        $error = 'Invalid role selection.';
    } elseif (!$firstName || !$surName || !$email || !$phone || !$nid || !$bloodType || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($role === 'donor' && ($weight === null || $haemoglobin === null)) {
        $error = 'Donors must provide weight and hemoglobin level.';
    } else {
        $check = $mysqli->prepare("SELECT SUM(email = ?) AS emailExists, SUM(nid = ?) AS nidExists FROM user");
        $check->bind_param('ss', $email, $nid);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            if ((int)$exists['emailExists'] > 0) {
                $fieldErrors['email'] = 'Email already registered.';
            }
            if ((int)$exists['nidExists'] > 0) {
                $fieldErrors['nid'] = 'NID already registered.';
            }
        }

        if (!$fieldErrors['email'] && !$fieldErrors['nid']) {
            $idStmt = $mysqli->query("SELECT MAX(CAST(SUBSTRING(userID, 2) AS UNSIGNED)) AS maxId FROM user WHERE userID REGEXP '^u[0-9]+$' ");
            $row = $idStmt->fetch_assoc();
            $maxId = (int)($row['maxId'] ?? 0);
            $userId = sprintf('u%03d', $maxId + 1);

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare(
                "INSERT INTO user (userID, firstName, surName, email, phone, nid, bloodType, role, password)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssssssss', $userId, $firstName, $surName, $email, $phone, $nid, $bloodType, $role, $passwordHash);
            $stmt->execute();
            $stmt->close();

            if ($role === 'donor') {
                $stmt = $mysqli->prepare(
                    "INSERT INTO donor (userID, bloodType, isAvailable, lastDonated, totalDonate)
                     VALUES (?, ?, 1, ?, 0)"
                );
                $stmt->bind_param('sss', $userId, $bloodType, $lastDonated);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare(
                    "INSERT INTO health_profile (userID, weightKg, haemoglobinLvl)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param('sdd', $userId, $weight, $haemoglobin);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $mysqli->prepare("INSERT INTO recipient (recipientID) VALUES (?)");
                $stmt->bind_param('s', $userId);
                $stmt->execute();
                $stmt->close();
            }

            login_user([
                'userID' => $userId,
                'firstName' => $firstName,
                'surName' => $surName,
                'role' => $role,
                'email' => $email
            ]);

            if ($role === 'recipient') {
                header('Location: recipient_dashboard.php');
            } else {
                header('Location: donor_dashboard.php');
            }
            exit;
        }
    }
}
?>
<section class="container">
    <div class="card" style="max-width: 620px; margin: 40px auto;">
        <h2>Create Account</h2>
        <p class="muted">Register as a donor or recipient to access the portal.</p>
        <?php if ($notice) : ?>
            <div class="notice"><?php echo e($notice); ?></div>
        <?php endif; ?>
        <?php if ($error) : ?>
            <div class="notice warn"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form class="form" method="post">
            <div>
                <label>First Name</label>
                <input class="input" type="text" name="firstName" required>
            </div>
            <div>
                <label>Sur Name</label>
                <input class="input" type="text" name="surName" required>
            </div>
            <div>
                <label>Email</label>
                <input class="input" type="email" name="email" required>
                <?php if ($fieldErrors['email']) : ?>
                    <p style="color: var(--accent); font-size: 12px; margin: 4px 0 0;"><?php echo e($fieldErrors['email']); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label>Phone</label>
                <input class="input" type="text" name="phone" required>
            </div>
            <div>
                <label>NID</label>
                <input class="input" type="text" name="nid" required>
                <?php if ($fieldErrors['nid']) : ?>
                    <p style="color: var(--accent); font-size: 12px; margin: 4px 0 0;"><?php echo e($fieldErrors['nid']); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label>Blood Type</label>
                <select name="bloodType" required>
                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type) : ?>
                        <option value="<?php echo e($type); ?>"><?php echo e($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Role</label>
                <select name="role" required>
                    <option value="donor">Donor</option>
                    <option value="recipient">Recipient</option>
                </select>
            </div>
            <div>
                <label>Password</label>
                <input class="input" type="password" name="password" required>
            </div>
            <div>
                <label>Weight (kg) - required for donors</label>
                <input class="input" type="number" step="0.1" name="weightKg">
            </div>
            <div>
                <label>Hemoglobin (g/dL) - required for donors</label>
                <input class="input" type="number" step="0.1" name="haemoglobinLvl">
            </div>
            <div>
                <label>Last Donation Date (donors only)</label>
                <input class="input" type="date" name="lastDonated">
            </div>
            <button class="btn" type="submit">Register</button>
            <p class="muted">Already have an account? <a href="login.php">Log in</a></p>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
