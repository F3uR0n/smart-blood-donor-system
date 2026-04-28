const BACKEND = 'http://localhost/smart-blood-donor/backend';

let donorsData   = [];
let filteredDonors = [];
let emergencyMode  = false;

/* ── Load donors from backend ───────────────────────────────── */
async function loadDonors() {
  const grid = document.getElementById('donorsGrid');
  grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
    <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>Loading donors...</div>`;

  try {
    const res  = await fetch(`${BACKEND}/api/get_donors.php`);
    const data = await res.json();

    if (data.donors && data.donors.length > 0) {
      donorsData     = data.donors;
      filteredDonors = [...donorsData];
      applyFilters();
    } else {
      grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
        <h3>No donors found</h3><p>The database may be empty. Run schema.sql to seed sample data.</p></div>`;
    }
  } catch (err) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
      <h3>Could not load donors</h3>
      <p>Make sure the PHP backend is running (XAMPP / PHP server).</p></div>`;
  }
}

/* ── Render donors ─────────────────────────────────────────── */
function renderDonors(donors) {
  const grid = document.getElementById('donorsGrid');
  document.getElementById('resultCount').textContent = donors.length;

  if (donors.length === 0) {
    grid.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:3rem 0; color:var(--white-muted);">
      <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
      <h3>No donors found</h3>
      <p>Try adjusting your filters or posting an emergency request.</p>
      <a href="emergency.html" class="btn btn-primary" style="margin-top:1rem;">Emergency Request</a>
    </div>`;
    return;
  }

  grid.innerHTML = donors.map(d => `
    <div class="donor-card ${emergencyMode && !d.avail ? '' : ''} reveal">
      <div class="donor-card__header">
        <div class="donor-avatar">${d.initials}</div>
        <div>
          <div class="donor-card__name">${d.name}</div>
          <div class="donor-card__meta">📍 ${d.city}</div>
        </div>
        <span class="badge badge-blood" style="margin-left:auto;">${d.blood}</span>
      </div>
      <div class="donor-card__info">
        <div class="donor-info-item">
          <div class="donor-info-item__label">Availability</div>
          <div class="donor-info-item__value">
            ${d.avail
              ? '<span class="badge badge-success">Available</span>'
              : '<span class="badge" style="background:rgba(100,100,100,0.12);color:var(--white-muted);border:1px solid rgba(100,100,100,0.2);">Unavailable</span>'}
          </div>
        </div>
        <div class="donor-info-item">
          <div class="donor-info-item__label">Total Donations</div>
          <div class="donor-info-item__value">${d.donations}</div>
        </div>
        <div class="donor-info-item">
          <div class="donor-info-item__label">Last Donated</div>
          <div class="donor-info-item__value">${d.lastDonated}</div>
        </div>
        <div class="donor-info-item">
          <div class="donor-info-item__label">Next Eligible</div>
          <div class="donor-info-item__value">${d.nextEligible}</div>
        </div>
      </div>
      <div class="donor-card__footer">
        ${d.globalRank ? `<div class="distance-badge">🏆 Rank #${d.globalRank}</div>` : `<div class="distance-badge">⭐ ${d.points} pts</div>`}
        <button class="btn ${d.avail ? 'btn-primary' : 'btn-outline'} btn-sm"
          ${d.avail ? '' : 'disabled'}>
          ${d.avail ? 'Request Donation' : 'Unavailable'}
        </button>
      </div>
    </div>
  `).join('');

  document.querySelectorAll('.reveal:not(.visible)').forEach(el => {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    obs.observe(el);
  });
}

/* ── Filter & Sort ─────────────────────────────────────────── */
function applyFilters() {
  const blood = document.getElementById('filterBlood').value;
  const city  = document.getElementById('filterCity').value.toLowerCase().trim();
  const avail = document.getElementById('filterAvail').value;
  const sort  = document.getElementById('filterSort').value;

  let result = donorsData.filter(d => {
    if (blood && d.blood !== blood) return false;
    if (city  && !d.city.toLowerCase().includes(city)) return false;
    if (avail !== '' && d.avail !== (avail === '1')) return false;
    if (emergencyMode && !d.avail) return false;
    return true;
  });

  if (sort === 'donations') result.sort((a, b) => b.donations - a.donations);
  if (sort === 'recent')    result.sort((a, b) => new Date(b.lastDonated) - new Date(a.lastDonated));
  if (sort === 'rank')      result.sort((a, b) => (a.globalRank || 9999) - (b.globalRank || 9999));

  const bf = document.getElementById('activeBloodFilter');
  const cf = document.getElementById('activeCityFilter');
  if (bf) { bf.style.display = blood ? 'inline-flex' : 'none'; bf.textContent = blood; }
  if (cf) { cf.style.display = city  ? 'inline-flex' : 'none'; cf.textContent = city; }

  filteredDonors = result;
  renderDonors(result);
}

document.getElementById('searchBtn').addEventListener('click', applyFilters);

['filterBlood','filterCity','filterAvail','filterSort'].forEach(id => {
  document.getElementById(id)?.addEventListener('change', applyFilters);
});
document.getElementById('filterCity')?.addEventListener('input', () => {
  clearTimeout(window._searchTimer);
  window._searchTimer = setTimeout(applyFilters, 400);
});

/* ── Emergency toggle ──────────────────────────────────────── */
document.getElementById('emergencyToggle')?.addEventListener('change', e => {
  emergencyMode = e.target.checked;
  applyFilters();
});

/* ── Init ──────────────────────────────────────────────────── */
loadDonors();
