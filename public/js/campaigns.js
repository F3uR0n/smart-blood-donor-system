/* ── Sample campaigns data ─────────────────────────────────── */
const campaignsData = [
  {
    id: 1,
    title: 'Life Drop Drive 2026',
    tagline: '"Every drop counts, every life matters"',
    company: 'RedCross Bangladesh',
    companyIcon: '🏥',
    location: 'Dhaka, Banani',
    startDate: '2026-04-15',
    endDate: '2026-05-15',
    budget: 500000,
    currentDonors: 84,
    goalDonors: 200,
    status: 'active',
  },
  {
    id: 2,
    title: 'Corporate Blood Pledge',
    tagline: '"Professionals uniting for life"',
    company: 'BRAC Bank',
    companyIcon: '🏦',
    location: 'Dhaka, Gulshan',
    startDate: '2026-05-01',
    endDate: '2026-05-30',
    budget: 300000,
    currentDonors: 12,
    goalDonors: 100,
    status: 'upcoming',
  },
  {
    id: 3,
    title: 'Sylhet Summer Save',
    tagline: '"Together stronger, together saving lives"',
    company: 'ACI Pharmaceuticals',
    companyIcon: '💊',
    location: 'Sylhet, Zindabazar',
    startDate: '2026-03-01',
    endDate: '2026-03-31',
    budget: 200000,
    currentDonors: 150,
    goalDonors: 150,
    status: 'ended',
  },
  {
    id: 4,
    title: 'Port City Blood Fest',
    tagline: '"Chittagong gives back"',
    company: 'Chittagong Port Authority',
    companyIcon: '⚓',
    location: 'Chittagong, Agrabad',
    startDate: '2026-04-20',
    endDate: '2026-06-20',
    budget: 400000,
    currentDonors: 58,
    goalDonors: 300,
    status: 'active',
  },
  {
    id: 5,
    title: 'Youth Blood Champions',
    tagline: '"Young hearts, big impact"',
    company: 'Dhaka University',
    companyIcon: '🎓',
    location: 'Dhaka, Nilkhet',
    startDate: '2026-06-01',
    endDate: '2026-06-15',
    budget: 150000,
    currentDonors: 0,
    goalDonors: 500,
    status: 'upcoming',
  },
  {
    id: 6,
    title: 'Rajshahi Harvest of Hope',
    tagline: '"Harvest season, save a life"',
    company: 'Rajshahi Chamber of Commerce',
    companyIcon: '🌾',
    location: 'Rajshahi, Shaheb Bazar',
    startDate: '2026-04-10',
    endDate: '2026-04-25',
    budget: 180000,
    currentDonors: 95,
    goalDonors: 120,
    status: 'active',
  },
];

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
    const pct = Math.min(Math.round((c.currentDonors / c.goalDonors) * 100), 100);
    return `
    <div class="campaign-card reveal">
      <div class="campaign-card__banner"></div>
      <div class="campaign-card__body">
        <div class="campaign-card__company">
          <div class="company-logo">${c.companyIcon}</div>
          <div class="company-name">${c.company}</div>
          ${statusBadge(c.status)}
        </div>
        <div class="campaign-card__title">${c.title}</div>
        <div class="campaign-card__tagline">${c.tagline}</div>
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
            <div class="meta-item__value">${c.location}</div>
          </div>
          <div class="meta-item">
            <div class="meta-item__label">💰 Budget</div>
            <div class="meta-item__value">৳${(c.budget/1000).toFixed(0)}K</div>
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
      </div>
      <div class="campaign-card__footer">
        <div class="campaign-status">🎯 Goal: ${c.goalDonors} donors</div>
        <button class="btn ${c.status === 'ended' ? 'btn-outline' : 'btn-primary'} btn-sm camp-register-btn"
          data-id="${c.id}" data-title="${c.title}"
          ${c.status === 'ended' ? 'disabled' : ''}>
          ${c.status === 'ended' ? 'Ended' : 'Register Interest'}
        </button>
      </div>
    </div>`;
  }).join('');

  /* re-attach button handlers */
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
    if (q && !c.title.toLowerCase().includes(q) && !c.company.toLowerCase().includes(q)) return false;
    if (city && !c.location.toLowerCase().includes(city)) return false;
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
  alert('Thank you! Your interest has been registered. A confirmation will be sent to your email.');
  document.getElementById('campModal').classList.remove('open');
  e.target.reset();
});

renderCampaigns(campaignsData);
