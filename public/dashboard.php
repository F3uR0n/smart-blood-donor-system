<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: login.html');
    exit;
}
$userID    = $_SESSION['userID'];
$userRole  = $_SESSION['role'];
$firstName = $_SESSION['firstName'];
$fullName  = $_SESSION['name'] ?? $firstName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — BloodNet</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; }

    /* ── Dashboard Layout ──────────────────────────────────── */
    .dashboard-layout {
      display: flex;
      flex: 1;
      padding-top: 70px;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      flex-shrink: 0;
      background: var(--bg-card);
      border-right: 1px solid var(--border-glass);
      padding: 2rem 1.25rem;
      position: sticky;
      top: 70px;
      height: calc(100vh - 70px);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }
    .sidebar__avatar {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, var(--crimson) 0%, #7f1d1d 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 800;
      margin: 0 auto 1rem;
      box-shadow: 0 0 20px var(--crimson-glow);
    }
    .sidebar__name {
      text-align: center;
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 1.05rem;
      margin-bottom: 0.2rem;
    }
    .sidebar__role-badge {
      display: flex;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    .sidebar__divider {
      height: 1px;
      background: var(--border-glass);
      margin-bottom: 1.5rem;
    }
    .sidebar__nav-label {
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--white-muted);
      padding: 0 0.75rem;
      margin-bottom: 0.5rem;
    }
    .sidebar__nav a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.65rem 0.75rem;
      border-radius: var(--radius);
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--white-dim);
      transition: var(--transition);
      margin-bottom: 0.25rem;
    }
    .sidebar__nav a:hover,
    .sidebar__nav a.active {
      background: var(--crimson-dim);
      color: var(--crimson-light);
    }
    .sidebar__nav a .nav-icon { font-size: 1.1rem; width: 20px; }
    .sidebar__bottom {
      margin-top: auto;
    }
    .sidebar__logout {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.65rem 0.75rem;
      border-radius: var(--radius);
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--white-muted);
      cursor: pointer;
      transition: var(--transition);
      width: 100%;
    }
    .sidebar__logout:hover { color: var(--crimson-light); background: var(--crimson-dim); }

    /* ── Role Demo Switcher ─────────────────────────────────── */
    .role-demo-bar {
      background: var(--bg-card-2);
      border-bottom: 1px solid var(--border-glass);
      padding: 0.6rem 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 0.82rem;
      color: var(--white-muted);
    }
    .role-demo-bar span { font-weight: 600; }
    .role-btn {
      padding: 0.3rem 0.9rem;
      border-radius: 50px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      border: 1px solid var(--border-glass);
      color: var(--white-dim);
    }
    .role-btn.active { background: var(--crimson); color: #fff; border-color: var(--crimson); }

    /* Main content */
    .dashboard-main {
      flex: 1;
      overflow-y: auto;
      padding: 0;
      display: flex;
      flex-direction: column;
    }
    .dashboard-content {
      padding: 2rem;
      flex: 1;
    }

    /* ── Stat cards ────────────────────────────────────────── */
    .dash-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .dash-stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border-glass);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      transition: var(--transition);
    }
    .dash-stat-card:hover {
      border-color: rgba(220,38,38,0.3);
      transform: translateY(-3px);
    }
    .dash-stat-card__label {
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--white-muted);
      margin-bottom: 0.5rem;
    }
    .dash-stat-card__value {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 800;
      color: var(--white);
      line-height: 1;
    }
    .dash-stat-card__sub {
      font-size: 0.8rem;
      color: var(--white-muted);
      margin-top: 0.35rem;
    }
    .dash-stat-card__icon {
      font-size: 1.5rem;
      margin-bottom: 0.75rem;
    }

    /* ── Section panels ────────────────────────────────────── */
    .dash-panel {
      background: var(--bg-card);
      border: 1px solid var(--border-glass);
      border-radius: var(--radius-lg);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    .dash-panel__header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border-glass);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .dash-panel__title {
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 700;
    }
    .dash-panel__body { padding: 1.5rem; }

    /* ── Table ─────────────────────────────────────────────── */
    .dash-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }
    .dash-table th {
      text-align: left;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--white-muted);
      padding: 0 0 0.75rem;
      border-bottom: 1px solid var(--border-glass);
    }
    .dash-table td {
      padding: 0.85rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
      color: var(--white-dim);
    }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-table td:first-child { color: var(--white); font-weight: 500; }

    /* ── Progress bar ──────────────────────────────────────── */
    .progress-bar {
      height: 6px;
      background: var(--border-glass);
      border-radius: 3px;
      overflow: hidden;
      margin-top: 0.5rem;
    }
    .progress-bar__fill {
      height: 100%;
      border-radius: 3px;
      background: linear-gradient(90deg, var(--crimson), #f87171);
    }

    /* ── Leaderboard ───────────────────────────────────────── */
    .leaderboard-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .leaderboard-item:last-child { border-bottom: none; }
    .leaderboard-item__rank {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 800;
      width: 32px;
      text-align: center;
      color: var(--white-muted);
    }
    .leaderboard-item:nth-child(1) .leaderboard-item__rank { color: #fbbf24; }
    .leaderboard-item:nth-child(2) .leaderboard-item__rank { color: #94a3b8; }
    .leaderboard-item:nth-child(3) .leaderboard-item__rank { color: #cd7c2f; }
    .leaderboard-item__avatar {
      width: 38px; height: 38px;
      background: var(--crimson-dim);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--crimson-light);
    }
    .leaderboard-item__info { flex: 1; }
    .leaderboard-item__name { font-weight: 600; font-size: 0.9rem; }
    .leaderboard-item__meta { font-size: 0.78rem; color: var(--white-muted); }
    .leaderboard-item__points {
      font-family: var(--font-display);
      font-weight: 700;
      color: var(--crimson-light);
    }

    /* ── Notification item ─────────────────────────────────── */
    .notif-item {
      display: flex;
      gap: 1rem;
      padding: 0.85rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item__icon {
      width: 36px; height: 36px;
      background: var(--crimson-dim);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    .notif-item__text { font-size: 0.875rem; color: var(--white-dim); }
    .notif-item__text strong { color: var(--white); }
    .notif-item__time { font-size: 0.75rem; color: var(--white-muted); margin-top: 0.2rem; }

    /* Role views */
    .role-view { display: none; }
    .role-view.active { display: block; }

    /* ── Two-col layout ────────────────────────────────────── */
    .dash-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    /* Admin table */
    .admin-controls { display: flex; gap: 0.5rem; }

    /* Mobile sidebar toggle */
    .sidebar-toggle {
      display: none;
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      width: 52px; height: 52px;
      background: var(--crimson);
      border-radius: 50%;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      z-index: 900;
      box-shadow: 0 4px 20px var(--crimson-glow);
    }
    .sidebar.mobile-open {
      position: fixed;
      top: 70px; left: 0; bottom: 0;
      z-index: 800;
    }

    @media (max-width: 1100px) {
      .dash-stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
      .sidebar { display: none; }
      .sidebar-toggle { display: flex; }
      .sidebar.mobile-open { display: flex; }
      .dash-grid-2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
      .dash-stats { grid-template-columns: 1fr; }
      .dashboard-content { padding: 1rem; }
      .role-demo-bar { flex-wrap: wrap; gap: 0.5rem; }
    }
  </style>
</head>
<body>

  <!-- Pass server-side session data to JS -->
  <script>
    const USER_ID        = '<?= htmlspecialchars($userID,    ENT_QUOTES) ?>';
    const USER_ROLE      = '<?= htmlspecialchars($userRole,  ENT_QUOTES) ?>';
    const USER_FIRSTNAME = '<?= htmlspecialchars($firstName, ENT_QUOTES) ?>';
    const USER_NAME      = '<?= htmlspecialchars($fullName,  ENT_QUOTES) ?>';
  </script>

  <!-- Navbar -->
  <nav class="navbar scrolled">
    <div class="navbar__inner">
      <a href="index.html" class="navbar__logo">
        <div class="navbar__logo-icon">🩸</div>
        BloodNet
      </a>
      <ul class="navbar__links">
        <li><a href="find-donor.html">Find Donor</a></li>
        <li><a href="blood-banks.html">Blood Banks</a></li>
        <li><a href="campaigns.html">Campaigns</a></li>
        <li><a href="emergency.html">Emergency</a></li>
      </ul>
      <a href="logout.php" class="btn btn-outline btn-sm navbar__cta">Logout</a>
    </div>
  </nav>

  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar__avatar" id="avatarInitials">
        <?= htmlspecialchars(strtoupper(substr($firstName, 0, 1) . (strpos($fullName, ' ') !== false ? substr(strrchr($fullName, ' '), 1, 1) : '')), ENT_QUOTES) ?>
      </div>
      <div class="sidebar__name" id="sidebarName"><?= htmlspecialchars($fullName, ENT_QUOTES) ?></div>
      <div class="sidebar__role-badge">
        <span class="badge badge-blood" id="sidebarRole"><?= htmlspecialchars(ucfirst($userRole), ENT_QUOTES) ?></span>
      </div>
      <div class="sidebar__divider"></div>

      <div class="sidebar__nav-label">Navigation</div>
      <nav class="sidebar__nav" id="sidebarNav">
        <!-- populated by JS -->
      </nav>

      <div class="sidebar__bottom">
        <div class="sidebar__divider"></div>
        <a href="logout.php" class="sidebar__logout">
          <span class="nav-icon">🚪</span> Logout
        </a>
      </div>
    </aside>

    <!-- Main -->
    <div class="dashboard-main">
      <!-- Role demo switcher -->
      <div class="role-demo-bar">
        <span>View:</span>
        <button class="role-btn" data-role="donor">Donor</button>
        <button class="role-btn" data-role="recipient">Recipient</button>
        <button class="role-btn" data-role="admin">Admin</button>
      </div>

      <div class="dashboard-content">

        <!-- ── DONOR VIEW ───────────────────────────────────── -->
        <div class="role-view" id="view-donor">
          <h2 style="margin-bottom: 0.25rem;">Welcome back, <span class="text-crimson" id="donorWelcomeName">—</span> 👋</h2>
          <p style="margin-bottom: 1.75rem;" id="donorSubtext">Loading your stats...</p>

          <div class="dash-stats">
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">🩸</div>
              <div class="dash-stat-card__label">Total Donations</div>
              <div class="dash-stat-card__value" id="statTotalDonate">—</div>
              <div class="dash-stat-card__sub" id="statDonateSub">times donated</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">⭐</div>
              <div class="dash-stat-card__label">Points Earned</div>
              <div class="dash-stat-card__value" id="statPoints">—</div>
              <div class="dash-stat-card__sub" id="statPointsSub">reward points</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">🏆</div>
              <div class="dash-stat-card__label">Global Rank</div>
              <div class="dash-stat-card__value" id="statRank">—</div>
              <div class="dash-stat-card__sub">leaderboard position</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">📅</div>
              <div class="dash-stat-card__label">Next Eligible</div>
              <div class="dash-stat-card__value" id="statNextEligible">—</div>
              <div class="dash-stat-card__sub" id="statEligibleSub">next donation date</div>
            </div>
          </div>

          <div class="dash-grid-2">
            <!-- Donation History -->
            <div class="dash-panel">
              <div class="dash-panel__header">
                <div class="dash-panel__title">Donation History</div>
                <a href="donation-history.html" class="btn btn-ghost btn-sm">View All</a>
              </div>
              <div class="dash-panel__body">
                <table class="dash-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Hospital</th>
                      <th>Units</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="donationHistoryBody">
                    <tr><td colspan="4" style="text-align:center;color:var(--white-muted);">Loading...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Health Profile -->
            <div class="dash-panel">
              <div class="dash-panel__header">
                <div class="dash-panel__title">Health Profile</div>
                <a href="check-eligibility.html" class="btn btn-outline btn-sm">Check Eligibility</a>
              </div>
              <div class="dash-panel__body">
                <table class="dash-table">
                  <tbody id="healthTableBody">
                    <tr><td colspan="2" style="text-align:center;color:var(--white-muted);">Loading...</td></tr>
                  </tbody>
                </table>
                <div id="healthActions" style="margin-top:1rem;display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                  <button id="editHealthBtn" class="btn btn-outline btn-sm">✏️ Edit Profile</button>
                  <button id="saveHealthBtn" class="btn btn-primary btn-sm" style="display:none;">💾 Save</button>
                  <button id="cancelHealthBtn" class="btn btn-ghost btn-sm" style="display:none;">Cancel</button>
                  <span id="healthSaveMsg" style="font-size:0.85rem;margin-left:0.5rem;"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Leaderboard + Notifications -->
          <div class="dash-grid-2" id="leaderboard">
            <div class="dash-panel">
              <div class="dash-panel__header">
                <div class="dash-panel__title">🏆 Leaderboard</div>
                <span class="badge badge-blood" id="myRankBadge">Your rank: —</span>
              </div>
              <div class="dash-panel__body" id="leaderboardBody">
                <div style="text-align:center;padding:1rem;color:var(--white-muted);">⏳ Loading...</div>
              </div>
            </div>

            <div class="dash-panel" id="notifications">
              <div class="dash-panel__header">
                <div class="dash-panel__title">🔔 Notifications</div>
              </div>
              <div class="dash-panel__body" id="notificationsBody">
                <div style="text-align:center;padding:1rem;color:var(--white-muted);">Loading notifications...</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── RECIPIENT VIEW ────────────────────────────────── -->
        <div class="role-view" id="view-recipient">
          <h2 style="margin-bottom: 0.25rem;">Hello, <span class="text-crimson" id="recipientWelcomeName">—</span> 👋</h2>
          <p style="margin-bottom: 1.75rem;">Track your blood requests and matched donors below.</p>

          <div class="dash-stats">
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">📋</div>
              <div class="dash-stat-card__label">Active Requests</div>
              <div class="dash-stat-card__value" id="statActiveReqs">—</div>
              <div class="dash-stat-card__sub">pending &amp; matched</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">✅</div>
              <div class="dash-stat-card__label">Matched Donors</div>
              <div class="dash-stat-card__value" id="statMatchedDonors">—</div>
              <div class="dash-stat-card__sub">available now</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">✔️</div>
              <div class="dash-stat-card__label">Fulfilled</div>
              <div class="dash-stat-card__value" id="statFulfilled">—</div>
              <div class="dash-stat-card__sub">total requests</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">🩸</div>
              <div class="dash-stat-card__label">Blood Group</div>
              <div class="dash-stat-card__value" id="statRecipBlood">—</div>
              <div class="dash-stat-card__sub">your blood type</div>
            </div>
          </div>

          <div class="dash-panel">
            <div class="dash-panel__header">
              <div class="dash-panel__title">Active Emergency Requests</div>
              <a href="emergency.html" class="btn btn-primary btn-sm">+ New Request</a>
            </div>
            <div class="dash-panel__body">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Request ID</th>
                    <th>Blood Type</th>
                    <th>Units</th>
                    <th>Hospital</th>
                    <th>Urgency</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="recipRequestsBody">
                  <tr><td colspan="6" style="text-align:center;color:var(--white-muted);">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="dash-panel">
            <div class="dash-panel__header">
              <div class="dash-panel__title">Quick Links</div>
            </div>
            <div class="dash-panel__body" style="display:flex;gap:1rem;flex-wrap:wrap;">
              <a href="find-donor.html" class="btn btn-outline">🔍 Find Donors</a>
              <a href="blood-banks.html" class="btn btn-outline">🏥 Blood Banks</a>
              <a href="request-status.html" class="btn btn-outline">📋 Request Status</a>
            </div>
          </div>
        </div>

        <!-- ── ADMIN VIEW ─────────────────────────────────────── -->
        <div class="role-view" id="view-admin">
          <h2 style="margin-bottom: 0.25rem;">Admin Panel <span class="text-crimson">🛡️</span></h2>
          <p style="margin-bottom: 1.75rem;">System overview and management controls.</p>

          <div class="dash-stats">
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">👥</div>
              <div class="dash-stat-card__label">Total Users</div>
              <div class="dash-stat-card__value" id="statTotalUsers">—</div>
              <div class="dash-stat-card__sub">registered</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">🚨</div>
              <div class="dash-stat-card__label">Open Requests</div>
              <div class="dash-stat-card__value" id="statOpenReqs">—</div>
              <div class="dash-stat-card__sub" id="statOpenReqsSub">active</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">🏥</div>
              <div class="dash-stat-card__label">Blood Banks</div>
              <div class="dash-stat-card__value" id="statBanks">—</div>
              <div class="dash-stat-card__sub" id="statBanksSub">registered</div>
            </div>
            <div class="dash-stat-card">
              <div class="dash-stat-card__icon">📣</div>
              <div class="dash-stat-card__label">Active Campaigns</div>
              <div class="dash-stat-card__value" id="statCampaigns">—</div>
              <div class="dash-stat-card__sub">running now</div>
            </div>
          </div>

          <div class="dash-panel">
            <div class="dash-panel__header">
              <div class="dash-panel__title">User Management</div>
              <div class="admin-controls">
                <input class="form-control" style="width:180px; padding: 0.4rem 0.75rem; font-size:0.85rem;" placeholder="Search users…">
                <button class="btn btn-primary btn-sm">Add User</button>
              </div>
            </div>
            <div class="dash-panel__body">
              <table class="dash-table">
                <thead>
                  <tr><th>Name</th><th>Role</th><th>Blood Type</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="adminUsersBody">
                  <tr><td colspan="6" style="text-align:center;color:var(--white-muted);">Loading users...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="dash-grid-2">
            <div class="dash-panel">
              <div class="dash-panel__header">
                <div class="dash-panel__title">Emergency Requests</div>
                <a href="emergency.html" class="btn btn-outline btn-sm">View All</a>
              </div>
              <div class="dash-panel__body">
                <table class="dash-table">
                  <thead><tr><th>ID</th><th>Type</th><th>Urgency</th><th>Status</th></tr></thead>
                  <tbody id="adminReqsBody">
                    <tr><td colspan="4" style="text-align:center;color:var(--white-muted);">Loading...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="dash-panel">
              <div class="dash-panel__header">
                <div class="dash-panel__title">Quick Links</div>
              </div>
              <div class="dash-panel__body" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <a href="blood-banks.html" class="btn btn-outline btn-sm">🏥 Blood Banks</a>
                <a href="campaigns.html" class="btn btn-outline btn-sm">📣 Campaigns</a>
                <a href="feedback.html" class="btn btn-outline btn-sm">⭐ Feedback</a>
                <a href="find-donor.html" class="btn btn-outline btn-sm">🔍 Find Donors</a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <button class="sidebar-toggle" id="sidebarToggle">☰</button>

  <script src="js/main.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>
