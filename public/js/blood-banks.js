/* ── Sample blood bank data ─────────────────────────────────── */
const banksData = [
  {
    id: 1, name: 'Dhaka Medical College Blood Bank', location: 'Dhaka, Bakshibazar',
    open: true,
    inventory: [
      { type:'A+',  units:24, max:40 }, { type:'A−',  units:6,  max:20 },
      { type:'B+',  units:18, max:40 }, { type:'B−',  units:3,  max:20 },
      { type:'AB+', units:10, max:20 }, { type:'AB−', units:2,  max:10 },
      { type:'O+',  units:30, max:40 }, { type:'O−',  units:8,  max:20 },
    ],
    updated: '2026-04-25 08:00',
  },
  {
    id: 2, name: 'Square Hospital Blood Bank', location: 'Dhaka, Panthapath',
    open: true,
    inventory: [
      { type:'A+',  units:12, max:40 }, { type:'A−',  units:4,  max:20 },
      { type:'B+',  units:20, max:40 }, { type:'B−',  units:1,  max:20 },
      { type:'AB+', units:5,  max:20 }, { type:'AB−', units:0,  max:10 },
      { type:'O+',  units:22, max:40 }, { type:'O−',  units:5,  max:20 },
    ],
    updated: '2026-04-25 07:30',
  },
  {
    id: 3, name: 'Chittagong General Hospital', location: 'Chittagong, Anderkilla',
    open: true,
    inventory: [
      { type:'A+',  units:8,  max:40 }, { type:'A−',  units:2,  max:20 },
      { type:'B+',  units:15, max:40 }, { type:'B−',  units:6,  max:20 },
      { type:'AB+', units:3,  max:20 }, { type:'AB−', units:1,  max:10 },
      { type:'O+',  units:12, max:40 }, { type:'O−',  units:4,  max:20 },
    ],
    updated: '2026-04-24 18:00',
  },
  {
    id: 4, name: 'MAG Osmani Medical College Blood Bank', location: 'Sylhet, Sylhet Sadar',
    open: false,
    inventory: [
      { type:'A+',  units:16, max:40 }, { type:'A−',  units:5,  max:20 },
      { type:'B+',  units:9,  max:40 }, { type:'B−',  units:2,  max:20 },
      { type:'AB+', units:7,  max:20 }, { type:'AB−', units:0,  max:10 },
      { type:'O+',  units:18, max:40 }, { type:'O−',  units:3,  max:20 },
    ],
    updated: '2026-04-24 20:00',
  },
  {
    id: 5, name: 'Rajshahi Medical College Blood Bank', location: 'Rajshahi, Boalia',
    open: true,
    inventory: [
      { type:'A+',  units:20, max:40 }, { type:'A−',  units:8,  max:20 },
      { type:'B+',  units:14, max:40 }, { type:'B−',  units:4,  max:20 },
      { type:'AB+', units:6,  max:20 }, { type:'AB−', units:3,  max:10 },
      { type:'O+',  units:25, max:40 }, { type:'O−',  units:9,  max:20 },
    ],
    updated: '2026-04-25 09:00',
  },
  {
    id: 6, name: 'Khulna Medical College Blood Bank', location: 'Khulna, KDA Avenue',
    open: true,
    inventory: [
      { type:'A+',  units:10, max:40 }, { type:'A−',  units:3,  max:20 },
      { type:'B+',  units:7,  max:40 }, { type:'B−',  units:0,  max:20 },
      { type:'AB+', units:4,  max:20 }, { type:'AB−', units:1,  max:10 },
      { type:'O+',  units:14, max:40 }, { type:'O−',  units:2,  max:20 },
    ],
    updated: '2026-04-25 06:45',
  },
];

function levelClass(units, max) {
  const pct = units / max;
  if (pct <= 0.2) return 'inv-low';
  if (pct <= 0.5) return 'inv-med';
  return 'inv-high';
}
function levelWidth(units, max) {
  return Math.round((units / max) * 100) + '%';
}

function renderBanks(banks) {
  const grid = document.getElementById('banksGrid');
  document.getElementById('bankCount').textContent = banks.length;

  if (!banks.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">🏥</div>
      <h3>No blood banks found</h3>
      <p>Try a different search term or area.</p>
    </div>`;
    return;
  }

  grid.innerHTML = banks.map(b => `
    <div class="bank-card reveal">
      <div class="bank-card__header">
        <div class="bank-card__icon">🏥</div>
        <div style="flex:1;">
          <div class="bank-card__name">${b.name}</div>
          <div class="bank-card__location">📍 ${b.location}</div>
        </div>
        <div class="bank-card__status">
          <span class="badge ${b.open ? 'badge-success' : ''}" style="${!b.open ? 'background:rgba(100,100,100,0.12);color:var(--white-muted);border:1px solid rgba(100,100,100,0.2);' : ''}">
            ${b.open ? 'Open' : 'Closed'}
          </span>
        </div>
      </div>
      <div class="bank-card__inventory">
        <div class="inventory-label">Blood Inventory</div>
        <div class="inventory-grid">
          ${b.inventory.map(inv => `
            <div class="inv-item">
              <div class="inv-item__type">${inv.type}</div>
              <div class="inv-item__units">${inv.units} units</div>
              <div class="inv-item__bar">
                <div class="inv-item__bar-fill ${levelClass(inv.units, inv.max)}" style="width:${levelWidth(inv.units, inv.max)};"></div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
      <div class="bank-card__footer">
        <span>Updated: ${b.updated}</span>
        <a href="emergency.html" class="btn btn-outline btn-sm">Request Blood</a>
      </div>
    </div>
  `).join('');

  document.querySelectorAll('.reveal:not(.visible)').forEach(el => {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.08 });
    obs.observe(el);
  });
}

function applyBankFilter() {
  const name  = document.getElementById('searchName').value.toLowerCase();
  const area  = document.getElementById('searchArea').value.toLowerCase();
  const blood = document.getElementById('filterBloodBank').value;

  const results = banksData.filter(b => {
    if (name && !b.name.toLowerCase().includes(name)) return false;
    if (area && !b.location.toLowerCase().includes(area)) return false;
    if (blood) {
      const normalised = blood.replace('−', '-');
      const found = b.inventory.find(i => i.type.replace('−','-') === normalised && i.units > 0);
      if (!found) return false;
    }
    return true;
  });
  renderBanks(results);
}

document.getElementById('bankSearchBtn').addEventListener('click', applyBankFilter);
['searchName','searchArea'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', () => {
    clearTimeout(window._bankTimer);
    window._bankTimer = setTimeout(applyBankFilter, 350);
  });
});
document.getElementById('filterBloodBank')?.addEventListener('change', applyBankFilter);

renderBanks(banksData);
