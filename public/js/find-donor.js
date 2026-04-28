/* ── Sample donor data ─────────────────────────────────────── */
const donorsData = [
  { initials:'RK', name:'R. K.',   blood:'A+', city:'Dhaka',      dist:1.2, avail:true,  donations:18, lastDonated:'2026-02-10', urgent:true  },
  { initials:'SH', name:'S. H.',   blood:'O-', city:'Chittagong', dist:5.8, avail:true,  donations:15, lastDonated:'2026-01-28', urgent:false },
  { initials:'MI', name:'M. I.',   blood:'B+', city:'Dhaka',      dist:3.1, avail:true,  donations:13, lastDonated:'2025-12-20', urgent:false },
  { initials:'FN', name:'F. N.',   blood:'AB+',city:'Sylhet',     dist:8.4, avail:false, donations:9,  lastDonated:'2025-11-05', urgent:false },
  { initials:'ZA', name:'Z. A.',   blood:'O+', city:'Dhaka',      dist:2.7, avail:true,  donations:11, lastDonated:'2026-03-01', urgent:true  },
  { initials:'NR', name:'N. R.',   blood:'A-', city:'Rajshahi',   dist:12,  avail:true,  donations:7,  lastDonated:'2026-04-02', urgent:false },
  { initials:'TK', name:'T. K.',   blood:'B-', city:'Dhaka',      dist:0.9, avail:true,  donations:5,  lastDonated:'2025-10-18', urgent:false },
  { initials:'PM', name:'P. M.',   blood:'AB-',city:'Khulna',     dist:16,  avail:false, donations:4,  lastDonated:'2025-09-22', urgent:false },
  { initials:'JH', name:'J. H.',   blood:'A+', city:'Dhaka',      dist:4.5, avail:true,  donations:20, lastDonated:'2026-01-14', urgent:true  },
  { initials:'SB', name:'S. B.',   blood:'B+', city:'Dhaka',      dist:6.2, avail:true,  donations:8,  lastDonated:'2026-02-28', urgent:false },
  { initials:'RM', name:'R. M.',   blood:'O+', city:'Chittagong', dist:3.9, avail:false, donations:12, lastDonated:'2025-12-10', urgent:false },
  { initials:'AH', name:'A. H.',   blood:'O-', city:'Dhaka',      dist:7.1, avail:true,  donations:16, lastDonated:'2026-03-15', urgent:true  },
];

let filteredDonors = [...donorsData];
let emergencyMode = false;

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
    <div class="donor-card ${emergencyMode && d.urgent ? 'urgent' : ''} reveal">
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
          <div class="donor-info-item__value">${nextEligible(d.lastDonated)}</div>
        </div>
      </div>
      <div class="donor-card__footer">
        <div class="distance-badge">📍 ${d.dist} km away</div>
        <button class="btn ${d.avail ? 'btn-primary' : 'btn-outline'} btn-sm"
          ${d.avail ? '' : 'disabled'}>
          ${d.avail ? 'Request Donation' : 'Unavailable'}
        </button>
      </div>
    </div>
  `).join('');

  /* re-observe reveals */
  document.querySelectorAll('.reveal:not(.visible)').forEach(el => {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    obs.observe(el);
  });
}

function nextEligible(last) {
  const d = new Date(last);
  d.setDate(d.getDate() + 56);
  return d.toISOString().split('T')[0];
}

/* ── Filter & Sort ─────────────────────────────────────────── */
function applyFilters() {
  const blood = document.getElementById('filterBlood').value;
  const city  = document.getElementById('filterCity').value.toLowerCase().trim();
  const avail = document.getElementById('filterAvail').value;
  const sort  = document.getElementById('filterSort').value;

  let result = donorsData.filter(d => {
    if (blood && d.blood !== blood) return false;
    if (city && !d.city.toLowerCase().includes(city)) return false;
    if (avail !== '' && d.avail !== (avail === '1')) return false;
    if (emergencyMode && !d.avail) return false;
    return true;
  });

  if (sort === 'distance')  result.sort((a,b) => a.dist - b.dist);
  if (sort === 'donations') result.sort((a,b) => b.donations - a.donations);
  if (sort === 'recent')    result.sort((a,b) => new Date(b.lastDonated) - new Date(a.lastDonated));

  /* badge filters */
  const bf = document.getElementById('activeBloodFilter');
  const cf = document.getElementById('activeCityFilter');
  bf.style.display = blood ? 'inline-flex' : 'none';
  bf.textContent = blood;
  cf.style.display = city ? 'inline-flex' : 'none';
  cf.textContent = city;

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
renderDonors(donorsData);
