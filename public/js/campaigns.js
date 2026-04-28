const BACKEND = 'http://localhost/smart-blood-donor/backend';

let campaignsData = [];

/* ── Load campaigns from backend ────────────────────────────── */
async function loadCampaigns() {
  const grid = document.getElementById('campaignsGrid');
  grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
    <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>Loading campaigns...</div>`;

  try {
    const res  = await fetch(`${BACKEND}/api/campaigns_rewards.php`);
    const data = await res.json();

    if (data.campaigns && data.campaigns.length > 0) {
      campaignsData = data.campaigns;
      renderCampaigns(campaignsData);
    } else {
      grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">📣</div>
        <h3>No campaigns found</h3><p>Run schema.sql to seed sample data.</p></div>`;
    }
  } catch {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
      <h3>Could not load campaigns</h3>
      <p>Make sure the PHP backend is running (XAMPP / PHP server).</p></div>`;
  }
}

const companyIcons = {
  'RedCross': '🏥', 'BRAC': '🏦', 'ACI': '💊',
  'Chittagong': '⚓', 'Dhaka University': '🎓', 'Rajshahi': '🌾',
};

function getIcon(companyName) {
  for (const [key, icon] of Object.entries(companyIcons)) {
    if (companyName.includes(key)) return icon;
  }
  return '📣';
}

function statusBadge(status) {
  if (status === 'active')   return '<span class="badge badge-success">Active</span>';
  if (status === 'upcoming') return '<span class="badge badge-warning">Upcoming</span>';
  return '<span class="badge" style="background:rgba(100,100,100,0.12);color:var(--white-muted);border:1px solid rgba(100,100,100,0.2);">Ended</span>';
}

function renderCampaigns(data) {
  const grid = document.getElementById('campaignsGrid');
  document.getElementById('campCount').textContent = data.length;

  if (!data.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">📣</div>
      <h3>No campaigns found</h3>
      <p>Check back soon for upcoming donation drives.</p>
    </div>`;
    return;
  }

  grid.innerHTML = data.map(c => {
    const pct     = Math.min(Math.round((c.currentDonors / (c.goalDonors || 1)) * 100), 100);
    const icon    = getIcon(c.companyName);
    const rewards = c.rewards && c.rewards.length > 0
      ? `<div style="font-size:0.78rem;color:var(--white-muted);margin-top:0.5rem;">
           🎁 ${c.rewards.map(r => r.rewardItem).join(' · ')}
         </div>`
      : '';

    return `
    <div class="campaign-card reveal">
      <div class="campaign-card__banner"></div>
      <div class="campaign-card__body">
        <div class="campaign-card__company">
          <div class="company-logo">${icon}</div>
          <div class="company-name">${c.companyName}</div>
          ${statusBadge(c.status)}
        </div>
        <div class="campaign-card__title">${c.title}</div>
        <div class="campaign-card__tagline">${c.tagline || ''}</div>
        <div class="campaign-card__meta">
          <div class="meta-item">
            <div class="meta-item__label">📅 Start</div>
            <div class="meta-item__value">${c.startDate}</div>
          </div>
          <div class="meta-item">
            <div class="meta-item__label">📅 End</div>
            <div class="meta-item__value">${c.endDate}</div>
          </div>
          <div class="meta-item">
            <div class="meta-item__label">📍 Location</div>
            <div class="meta-item__value">${c.location || '—'}</div>
          </div>
          <div class="meta-item">
            <div class="meta-item__label">💰 Budget</div>
            <div class="meta-item__value">৳${(c.budget / 1000).toFixed(0)}K</div>
          </div>
        </div>
        <div class="campaign-progress">
          <div class="progress-header">
            <span>Donors: <strong>${c.currentDonors}</strong> / ${c.goalDonors}</span>
            <span>${pct}%</span>
          </div>
          <div class="progress-bar">
            <div class="progress-bar__fill" style="width:${pct}%;"></div>
          </div>
        </div>
        ${rewards}
      </div>
      <div class="campaign-card__footer">
        <div class="campaign-status">🎯 Goal: ${c.goalDonors} donors</div>
        <button class="btn ${c.status === 'ended' ? 'btn-outline' : 'btn-primary'} btn-sm camp-register-btn"
          data-id="${c.campaignID}" data-title="${c.title}"
          ${c.status === 'ended' ? 'disabled' : ''}>
          ${c.status === 'ended' ? 'Ended' : 'Register Interest'}
        </button>
      </div>
    </div>`;
  }).join('');

  document.querySelectorAll('.camp-register-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('modalCampTitle').textContent = btn.dataset.title;
      document.getElementById('campModal').classList.add('open');
    });
  });

  document.querySelectorAll('.reveal:not(.visible)').forEach(el => {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.08 });
    obs.observe(el);
  });
}

function applyFilters() {
  const q      = document.getElementById('campSearch').value.toLowerCase();
  const city   = document.getElementById('campCity').value.toLowerCase();
  const status = document.getElementById('campStatus').value;

  const result = campaignsData.filter(c => {
    if (q && !c.title.toLowerCase().includes(q) && !c.companyName.toLowerCase().includes(q)) return false;
    if (city && !(c.location || '').toLowerCase().includes(city)) return false;
    if (status && c.status !== status) return false;
    return true;
  });
  renderCampaigns(result);
}

document.getElementById('campSearchBtn').addEventListener('click', applyFilters);
['campSearch','campCity'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', () => {
    clearTimeout(window._campTimer);
    window._campTimer = setTimeout(applyFilters, 350);
  });
});
document.getElementById('campStatus')?.addEventListener('change', applyFilters);

/* Modal close */
document.getElementById('campModalClose').addEventListener('click', () => {
  document.getElementById('campModal').classList.remove('open');
});
document.getElementById('campModal').addEventListener('click', e => {
  if (e.target === document.getElementById('campModal'))
    document.getElementById('campModal').classList.remove('open');
});
document.getElementById('campRegForm').addEventListener('submit', e => {
  e.preventDefault();
  alert('Thank you! Your interest has been registered.');
  document.getElementById('campModal').classList.remove('open');
  e.target.reset();
});

loadCampaigns();
