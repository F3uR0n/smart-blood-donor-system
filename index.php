<?php
$pageTitle = 'Smart Blood Network';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero container">
    <div>
        <h1>Emergency blood donation with intelligent matching.</h1>
        <p>Connect donors, recipients & blood banks in one place with real-time eligibility checks & location-aware matching</p>
        <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a class="btn" href="login.php">Access Portal</a>
            <a class="btn ghost" href="emergency_request.php">Request Emergency Blood</a>
        </div>
    </div>
    <div class="hero-card reveal">
        <h3>Live Intelligence</h3>
        <ul>
            <li>Auto-match donors by eligibility</li>
            <li>Monitor blood bank shortage & expiry</li>
            <li>Run campaigns with reward redemption</li>
        </ul>
    </div>
</section>

<section class="section container">
    <h2>Platform Capabilities</h2>
    <div class="grid">
        <div class="card reveal">
            <h3>Emergency Request Lifecycle</h3>
            <p>Recipients submit requests. Admins validate and trigger matching. Status moves from pending to matched to fulfilled</p>
        </div>
        <div class="card reveal">
            <h3>Eligibility Intelligence</h3>
            <p>Health profiles, disease flags & 90-day cooldown checks ensure safe, compliant donations</p>
        </div>
        <div class="card reveal">
            <h3>Donor Ranking & Rewards</h3>
            <p>Points & rank scores motivate repeated donations and unlock campaign rewards</p>
        </div>
        <div class="card reveal">
            <h3>Blood Bank Insights</h3>
            <p>Track inventory & expiry windows</p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
