const BACKEND = 'http://localhost/smart-blood-donor/backend';

/* ── Session identity (PHP session vars injected into page, with sessionStorage fallback) */
const _userID    = (typeof USER_ID        !== 'undefined' && USER_ID)        ? USER_ID        : sessionStorage.getItem('userID')    || '';
const _userRole  = (typeof USER_ROLE      !== 'undefined' && USER_ROLE)      ? USER_ROLE      : sessionStorage.getItem('role')      || 'donor';
const _firstName = (typeof USER_FIRSTNAME !== 'undefined' && USER_FIRSTNAME) ? USER_FIRSTNAME : sessionStorage.getItem('firstName') || '';
const _fullName  = (typeof USER_NAME      !== 'undefined' && USER_NAME)      ? USER_NAME      : sessionStorage.getItem('name')      || _firstName;

let _currentDonorData = null; // holds last-fetched donor data for edit/cancel

/* ── Role demo switcher ────────────────────────────────────── */
const roleBtns  = document.querySelectorAll('.role-btn');
const roleViews = document.querySelectorAll('.role-view');

const sidebarNavs = {
  donor: [
    { icon: '🩸', label: 'Donation History',  href: 'donation-history.html' },
    { icon: '💪', label: 'Check Eligibility', href: 'check-eligibility.html' },
    { icon: '🏆', label: 'Leaderboard',       href: '#leaderboard' },
    { icon: '🎁', label: 'Rewards',           href: 'campaigns.html' },
    { icon: '🔔', label: 'Notifications',     href: '#notifications' },
  ],
  recipient: [
    { icon: '🚨', label: 'Emergency Requests', href: 'emergency.html' },
    { icon: '📋', label: 'Request Status',     href: 'request-status.html' },
    { icon: '🔍', label: 'Find Donors',        href: 'find-donor.html' },
    { icon: '🏥', label: 'Blood Banks',        href: 'blood-banks.html' },
    { icon: '🔔', label: 'Notifications',      href: '#notifications' },
  ],
  admin: [
    { icon: '👥', label: 'User Management',    href: '#' },
    { icon: '🚨', label: 'Emergency Requests', href: 'emergency.html' },
    { icon: '🏥', label: 'Blood Banks',        href: 'blood-banks.html' },
    { icon: '📣', label: 'Campaigns',          href: 'campaigns.html' },
    { icon: '⭐', label: 'Feedback',           href: 'feedback.html' },
    { icon: '📈', label: 'Reports',            href: '#' },
  ],
};

function setRole(role) {
  roleBtns.forEach(b  => b.classList.toggle('active', b.dataset.role === role));
  roleViews.forEach(v => v.classList.toggle('active', v.id === `view-${role}`));

  const nav = document.getElementById('sidebarNav');
  nav.innerHTML = sidebarNavs[role].map((item, i) =>
    `<a href="${item.href}" class="${i === 0 ? 'active' : ''}">
      <span class="nav-icon">${item.icon}</span>${item.label}
    </a>`
  ).join('');
}

roleBtns.forEach(btn => btn.addEventListener('click', () => setRole(btn.dataset.role)));

/* ── Mobile sidebar toggle ─────────────────────────────────── */
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('mobile-open');
});

/* ── Leaderboard from backend ───────────────────────────────── */
async function loadLeaderboard() {
  const body = document.getElementById('leaderboardBody');
  if (!body) return;
  body.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--white-muted);">⏳ Loading...</div>';

  try {
    const res  = await fetch(`${BACKEND}/api/leaderboard.php`);
    const data = await res.json();

    if (!data.leaderboard || data.leaderboard.length === 0) {
      body.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--white-muted);">No leaderboard data yet.</div>';
      return;
    }

    const medals = ['🥇', '🥈', '🥉'];
    body.innerHTML = data.leaderboard.map((d, i) => `
      <div class="leaderboard-item">
        <div class="leaderboard-item__rank">${medals[i] || '#' + d.position}</div>
        <div class="leaderboard-item__avatar">${d.initials}</div>
        <div class="leaderboard-item__info">
          <div class="leaderboard-item__name">${d.name}</div>
          <div class="leaderboard-item__meta">${d.bloodType} · ${d.city} · ${d.totalDonate} donations</div>
        </div>
        <div class="leaderboard-item__points">${d.points} pts</div>
      </div>
    `).join('');
  } catch {
    body.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--white-muted);">Could not load leaderboard.</div>';
  }
}

/* ── Dashboard stats from backend ──────────────────────────── */
async function loadDashboardStats() {
  if (!_userID || !_userRole) return;

  try {
    const res  = await fetch(`${BACKEND}/api/dashboard_stats.php?userID=${encodeURIComponent(_userID)}&role=${encodeURIComponent(_userRole)}`);
    const data = await res.json();
    if (data.error) return;

    if (_userRole === 'donor')     renderDonorStats(data);
    if (_userRole === 'recipient') renderRecipientStats(data);
    if (_userRole === 'admin')     renderAdminStats(data);
  } catch {
    /* stats unavailable */
  }
}

/* ── Donor view ─────────────────────────────────────────────── */
function renderDonorStats(d) {
  _currentDonorData = d;

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  const setHTML = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML = val; };

  set('donorWelcomeName', d.firstName || _firstName);
  set('donorSubtext', `Blood type ${d.bloodType} · ${d.totalDonate} donations · ${d.points} pts`);
  set('statTotalDonate', d.totalDonate ?? '—');
  set('statDonateSub', 'times donated');
  set('statPoints', d.points ?? '—');
  set('statPointsSub', 'reward points');
  set('statRank', d.globalRank ? '#' + d.globalRank : 'Unranked');
  set('statNextEligible', d.nextEligible ?? 'Now');
  set('statEligibleSub', d.daysLeft > 0 ? d.daysLeft + ' days away' : 'Eligible now!');
  set('myRankBadge', d.globalRank ? 'Your rank: #' + d.globalRank : 'Unranked');

  /* Donation history table */
  const tbody = document.getElementById('donationHistoryBody');
  if (tbody) {
    if (!d.recentDonations || d.recentDonations.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--white-muted);">No donations recorded yet.</td></tr>';
    } else {
      tbody.innerHTML = d.recentDonations.map(r => `
        <tr>
          <td>${r.donationDate || '—'}</td>
          <td>${r.hospitalCenter || '—'}</td>
          <td>${r.unitsDonated}</td>
          <td><span class="badge ${r.status === 'completed' ? 'badge-success' : 'badge-warning'}">${r.status}</span></td>
        </tr>
      `).join('');
    }
  }

  renderHealthTable(d);
  wireHealthButtons();

  /* Notifications panel */
  const notifBody = document.getElementById('notificationsBody');
  if (notifBody) {
    const eligMsg = d.daysLeft > 0
      ? `Your next eligible donation date is <strong>${d.nextEligible}</strong>.`
      : `You are <strong>eligible to donate</strong> now!`;
    notifBody.innerHTML = `
      <div class="notif-item">
        <div class="notif-item__icon">📅</div>
        <div>
          <div class="notif-item__text">${eligMsg}</div>
          <div class="notif-item__time">Eligibility status</div>
        </div>
      </div>
      <div class="notif-item">
        <div class="notif-item__icon">🏅</div>
        <div>
          <div class="notif-item__text">You have <strong>${d.points} pts</strong> — check <a href="campaigns.html" style="color:var(--crimson-light);">Campaigns</a> for rewards.</div>
          <div class="notif-item__time">Rewards available</div>
        </div>
      </div>
    `;
  }
}

function renderHealthTable(d) {
  const tbody = document.getElementById('healthTableBody');
  if (!tbody) return;

  const wVal  = d.weightKG      != null ? d.weightKG      : null;
  const hVal  = d.hemoglobinLvl != null ? d.hemoglobinLvl : null;
  const wDisp = wVal !== null ? `${wVal} kg`      : '<em style="color:var(--white-muted)">N/A</em>';
  const hDisp = hVal !== null ? `${hVal} g/dL`    : '<em style="color:var(--white-muted)">N/A</em>';

  const statusCls = { Perfect: 'badge-success', Good: 'badge-blood', Bad: 'badge-warning' }[d.healthStatus] || '';
  const eligCls   = d.eligible ? 'badge-success' : 'badge-warning';
  const nextElig  = d.nextEligibleRaw || 'Now';

  tbody.innerHTML = `
    <tr>
      <td>Blood Group</td>
      <td><span class="badge badge-blood">${d.bloodType || '—'}</span></td>
    </tr>
    <tr>
      <td>Weight</td>
      <td>
        <span id="weightDisplay">${wDisp}</span>
        <input id="weightInput" type="number" class="form-control" min="0" max="300" step="0.1"
               value="${wVal ?? ''}" placeholder="kg"
               style="display:none;width:110px;padding:0.3rem 0.6rem;font-size:0.85rem;">
      </td>
    </tr>
    <tr>
      <td>Hemoglobin</td>
      <td>
        <span id="hemoDisplay">${hDisp}</span>
        <input id="hemoInput" type="number" class="form-control" min="0" max="25" step="0.1"
               value="${hVal ?? ''}" placeholder="g/dL"
               style="display:none;width:110px;padding:0.3rem 0.6rem;font-size:0.85rem;">
      </td>
    </tr>
    <tr>
      <td>Health Status</td>
      <td><span id="healthStatusBadge" class="badge ${statusCls}">${d.healthStatus || 'N/A'}</span></td>
    </tr>
    <tr>
      <td>Diseases</td>
      <td>${d.diseases || 'None recorded'}</td>
    </tr>
    <tr>
      <td>Last Donated</td>
      <td>
        <span id="lastDonatedDisplay">${d.lastDonated || 'Never'}</span>
        <input id="lastDonatedInput" type="date" class="form-control"
               value="${d.lastDonated || ''}"
               style="display:none;width:155px;padding:0.3rem 0.6rem;font-size:0.85rem;">
      </td>
    </tr>
    <tr>
      <td>Next Eligible</td>
      <td id="nextEligibleRow">${nextElig}</td>
    </tr>
    <tr>
      <td>Eligibility</td>
      <td><span class="badge ${eligCls}">${d.eligible ? 'Eligible' : 'Not Eligible'}</span></td>
    </tr>
  `;
}

function wireHealthButtons() {
  const editBtn   = document.getElementById('editHealthBtn');
  const saveBtn   = document.getElementById('saveHealthBtn');
  const cancelBtn = document.getElementById('cancelHealthBtn');
  if (!editBtn) return;

  editBtn.onclick   = () => toggleHealthEdit(true);
  cancelBtn.onclick = () => toggleHealthEdit(false);
  saveBtn.onclick   = saveHealthProfile;
}

function toggleHealthEdit(on) {
  ['weightDisplay', 'hemoDisplay', 'lastDonatedDisplay'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = on ? 'none' : '';
  });
  ['weightInput', 'hemoInput', 'lastDonatedInput'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = on ? '' : 'none';
  });

  const editBtn   = document.getElementById('editHealthBtn');
  const saveBtn   = document.getElementById('saveHealthBtn');
  const cancelBtn = document.getElementById('cancelHealthBtn');
  if (editBtn)   editBtn.style.display   = on ? 'none' : '';
  if (saveBtn)   saveBtn.style.display   = on ? ''     : 'none';
  if (cancelBtn) cancelBtn.style.display = on ? ''     : 'none';

  const msg = document.getElementById('healthSaveMsg');
  if (msg) msg.textContent = '';

  // On cancel: reset inputs to last saved values
  if (!on && _currentDonorData) {
    const wi = document.getElementById('weightInput');
    if (wi) wi.value = _currentDonorData.weightKG ?? '';
    const hi = document.getElementById('hemoInput');
    if (hi) hi.value = _currentDonorData.hemoglobinLvl ?? '';
    const li = document.getElementById('lastDonatedInput');
    if (li) li.value = _currentDonorData.lastDonated ?? '';
  }
}

async function saveHealthProfile() {
  const msg        = document.getElementById('healthSaveMsg');
  const weightVal  = document.getElementById('weightInput')?.value.trim();
  const hemoVal    = document.getElementById('hemoInput')?.value.trim();
  const lastDonVal = document.getElementById('lastDonatedInput')?.value.trim();

  let healthSaved = false;
  let donorSaved  = false;

  // Save weight / hemoglobin
  if (weightVal !== '' || hemoVal !== '') {
    const payload = { userID: _userID };
    if (weightVal !== '') payload.weightKg       = parseFloat(weightVal);
    if (hemoVal   !== '') payload.haemoglobinLvl = parseFloat(hemoVal);

    try {
      const r = await fetch(`${BACKEND}/api/update_health_profile.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const resp = await r.json();
      if (resp.success) {
        // Update display spans
        const wDisp = document.getElementById('weightDisplay');
        if (wDisp && payload.weightKg !== undefined) {
          wDisp.innerHTML = payload.weightKg + ' kg';
          if (_currentDonorData) _currentDonorData.weightKG = payload.weightKg;
        }
        const hDisp = document.getElementById('hemoDisplay');
        if (hDisp && payload.haemoglobinLvl !== undefined) {
          hDisp.innerHTML = payload.haemoglobinLvl + ' g/dL';
          if (_currentDonorData) _currentDonorData.hemoglobinLvl = payload.haemoglobinLvl;
        }
        // Update health status badge
        const statusBadge = document.getElementById('healthStatusBadge');
        if (statusBadge && resp.healthStatus) {
          const cls = { Perfect: 'badge-success', Good: 'badge-blood', Bad: 'badge-warning' }[resp.healthStatus] || '';
          statusBadge.className = `badge ${cls}`;
          statusBadge.textContent = resp.healthStatus;
          if (_currentDonorData) _currentDonorData.healthStatus = resp.healthStatus;
        }
        healthSaved = true;
      }
    } catch {}
  }

  // Save last donated date
  if (lastDonVal) {
    try {
      const r = await fetch(`${BACKEND}/api/update_donor.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userID: _userID, lastDonated: lastDonVal }),
      });
      const resp = await r.json();
      if (resp.success) {
        const ldDisp = document.getElementById('lastDonatedDisplay');
        if (ldDisp) ldDisp.textContent = resp.lastDonated;

        const neRow = document.getElementById('nextEligibleRow');
        if (neRow) neRow.textContent = resp.nextEligible || 'Now';

        // Update stat card
        const statNext = document.getElementById('statNextEligible');
        if (statNext && resp.nextEligible) {
          const dt    = new Date(resp.nextEligible);
          const today = new Date();
          statNext.textContent = dt > today
            ? dt.toLocaleDateString('en', { month: 'short', day: 'numeric' })
            : 'Now';
        }

        if (_currentDonorData) {
          _currentDonorData.lastDonated    = resp.lastDonated;
          _currentDonorData.nextEligibleRaw= resp.nextEligible;
        }
        donorSaved = true;
      }
    } catch {}
  }

  if (healthSaved || donorSaved) {
    if (msg) { msg.style.color = 'var(--success, #10b981)'; msg.textContent = '✓ Saved successfully!'; }
    toggleHealthEdit(false);
    setTimeout(() => { if (msg) msg.textContent = ''; }, 3000);
  } else {
    if (msg) { msg.style.color = 'var(--crimson-light)'; msg.textContent = 'Nothing changed or save failed.'; }
  }
}

/* ── Recipient view ─────────────────────────────────────────── */
function renderRecipientStats(d) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

  set('recipientWelcomeName', d.firstName || _firstName);
  set('statActiveReqs',    d.activeRequests    ?? '0');
  set('statMatchedDonors', d.matchedDonors     ?? '0');
  set('statFulfilled',     d.fulfilledRequests ?? '0');
  set('statRecipBlood',    d.bloodType         || '—');

  const tbody = document.getElementById('recipRequestsBody');
  if (tbody) {
    if (!d.recentRequests || d.recentRequests.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--white-muted);">No requests yet. <a href="emergency.html" style="color:var(--crimson-light);">Submit one →</a></td></tr>';
    } else {
      const urgencyClass = u => {
        if (u === 'critical') return 'style="background:rgba(220,38,38,0.12);color:var(--crimson-light);border:1px solid rgba(220,38,38,0.3);"';
        if (u === 'high')     return 'class="badge-warning"';
        return '';
      };
      const statusClass = s => (s === 'matched' || s === 'fulfilled') ? 'badge-success' : 'badge-warning';
      tbody.innerHTML = d.recentRequests.map(r => `
        <tr>
          <td><a href="request-status.html?id=${r.requestID}" style="color:var(--crimson-light);">#ER-${String(r.requestID).padStart(4,'0')}</a></td>
          <td><span class="badge badge-blood">${r.bloodType}</span></td>
          <td>${r.unitsNeeded}</td>
          <td>${r.hospital || '—'}</td>
          <td><span class="badge ${urgencyClass(r.urgencyLevel)}">${r.urgencyLevel}</span></td>
          <td><span class="badge ${statusClass(r.currentStatus)}">${r.currentStatus}</span></td>
        </tr>
      `).join('');
    }
  }
}

/* ── Admin view ─────────────────────────────────────────────── */
function renderAdminStats(d) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

  set('statTotalUsers',  d.totalUsers      ?? '—');
  set('statOpenReqs',    d.openRequests    ?? '—');
  set('statOpenReqsSub', (d.criticalRequests ?? 0) + ' critical');
  set('statBanks',       d.totalBanks      ?? '—');
  set('statBanksSub',    (d.lowStockBanks  ?? 0) + ' low stock');
  set('statCampaigns',   d.activeCampaigns ?? '—');

  const tbody = document.getElementById('adminUsersBody');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--white-muted);">Use the <a href="find-donor.html" style="color:var(--crimson-light);">Find Donor</a> page to browse registered users.</td></tr>';
  }
  const reqBody = document.getElementById('adminReqsBody');
  if (reqBody) {
    reqBody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--white-muted);">${d.openRequests} open requests — <a href="emergency.html" style="color:var(--crimson-light);">view emergency page →</a></td></tr>`;
  }
}

/* ── Init ──────────────────────────────────────────────────── */
setRole(_userRole);
loadLeaderboard();
loadDashboardStats();
