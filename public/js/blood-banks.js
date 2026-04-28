const BACKEND = 'http://localhost/smart-blood-donor/backend';

let banksData = [];

/* ── Load blood banks from backend ─────────────────────────── */
async function loadBanks() {
  const grid = document.getElementById('banksGrid');
  grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
    <div style="font-size:2rem;margin-bottom:0.5rem;">⏳</div>Loading blood banks...</div>`;

  try {
    const res  = await fetch(`${BACKEND}/api/blood_inventory.php`);
    const data = await res.json();

    if (data.banks && data.banks.length > 0) {
      // Transform backend format to match the render function's expected shape
      banksData = data.banks.map(b => ({
        id:       b.bankID,
        name:     b.bankName,
        location: b.location,
        city:     b.city,
        open:     b.isOpen,
        inventory: b.inventory.map(inv => ({
          type:       inv.bloodType,
          units:      inv.unitsAvailable,
          max:        inv.maxCapacity,
          critical:   inv.isCritical,
          flags:      inv.flags,
          expiryDate: inv.expiryDate,
        })),
        updated: data.checkedOn,
      }));
      renderBanks(banksData);
    } else {
      grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">🏥</div>
        <h3>No blood banks found</h3><p>Run schema.sql to seed sample data.</p></div>`;
    }
  } catch {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:3rem 0;color:var(--white-muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">⚠️</div>
      <h3>Could not load blood banks</h3>
      <p>Make sure the PHP backend is running (XAMPP / PHP server).</p></div>`;
  }
}

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
              <div class="inv-item__type">${inv.type}${inv.critical ? ' ⚠️' : ''}</div>
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
    if (area && !b.location.toLowerCase().includes(area) && !b.city.toLowerCase().includes(area)) return false;
    if (blood) {
      const found = b.inventory.find(i => i.type.replace('−', '-') === blood.replace('−', '-') && i.units > 0);
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

loadBanks();
