<?php
$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $mysqli->prepare("SELECT userID, firstName, surName, role, email, password FROM user WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isAuthenticated = false;
    if ($user) {
        $storedPassword = (string)$user['password'];
        $isAuthenticated = password_verify($password, $storedPassword);

        if (!$isAuthenticated && !preg_match('/^\$2y\$/', $storedPassword) && hash_equals($storedPassword, $password)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $mysqli->prepare("UPDATE user SET password = ? WHERE userID = ?");
            $update->bind_param('ss', $newHash, $user['userID']);
            $update->execute();
            $update->close();
            $isAuthenticated = true;
        }
    }

    if ($isAuthenticated) {
        unset($user['password']);
        login_user($user);
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
        } elseif ($user['role'] === 'recipient') {
            header('Location: recipient_dashboard.php');
        } else {
            header('Location: donor_dashboard.php');
        }
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<section class="container">
    <div class="card" style="max-width: 520px; margin: 40px auto;">
        <h2>Login</h2>
        <p class="muted">Use the email and password you registered with.</p>
        <?php if ($error) : ?>
            <div class="notice warn"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form class="form" method="post">
            <div>
                <label>Email</label>
                <input class="input" type="email" name="email" required>
            </div>
            <div>
                <label>Password</label>
                <input class="input" type="password" name="password" required>
            </div>
            <button class="btn" type="submit">Enter Portal</button>
        </form>
        <p class="muted" style="margin-top: 14px;">No account yet? <a href="register.php">Register</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
