/* ── Static compatible donor pool (for match preview) ──────── */
const matchPool = [
  { initials:'RK', name:'R. K.',   blood:['A+','AB+','B+','O+'],    city:'Dhaka',      dist:1.2, avail:true  },
  { initials:'ZA', name:'Z. A.',   blood:['A+','A-','B+','B-','AB+','AB-','O+','O-'], city:'Dhaka',  dist:2.7, avail:true  },
  { initials:'JH', name:'J. H.',   blood:['A+','AB+','B+','O+'],    city:'Dhaka',      dist:4.5, avail:true  },
  { initials:'SH', name:'S. H.',   blood:['A+','A-','B+','B-','AB+','AB-','O+','O-'], city:'Chittagong', dist:1.0, avail:true  },
  { initials:'MI', name:'M. I.',   blood:['B+','AB+','O+'],         city:'Dhaka',      dist:3.1, avail:true  },
  { initials:'NR', name:'N. R.',   blood:['A-','A+','AB-','AB+','O-','O+'], city:'Rajshahi', dist:0.8, avail:true },
  { initials:'TK', name:'T. K.',   blood:['B-','B+','AB-','AB+','O-','O+'], city:'Dhaka',   dist:0.9, avail:true },
  { initials:'AH', name:'A. H.',   blood:['A+','A-','B+','B-','AB+','AB-','O+','O-'], city:'Dhaka', dist:7.1, avail:true },
];

/* Blood compatibility: recipient type → compatible donor types */
const compatMap = {
  'A+':  ['A+','A-','O+','O-'],
  'A-':  ['A-','O-'],
  'B+':  ['B+','B-','O+','O-'],
  'B-':  ['B-','O-'],
  'AB+': ['A+','A-','B+','B-','AB+','AB-','O+','O-'],
  'AB-': ['A-','B-','AB-','O-'],
  'O+':  ['O+','O-'],
  'O-':  ['O-'],
};

function getMatches(bloodNeeded, city) {
  if (!bloodNeeded) return [];
  const compatDonorTypes = compatMap[bloodNeeded] || [];
  return matchPool.filter(d => {
    const bloodMatch = d.blood.some(bt => compatDonorTypes.includes(bt));
    const cityMatch  = !city || d.city.toLowerCase().includes(city.toLowerCase());
    return bloodMatch && cityMatch && d.avail;
  }).slice(0, 4);
}

function renderMatchPreview(bloodNeeded, city) {
  const container = document.getElementById('matchPreview');
  const matches = getMatches(bloodNeeded, city);

  if (!bloodNeeded) {
    container.innerHTML = `
      <div class="match-preview-empty">
        <div class="match-preview-empty__icon">💡</div>
        <p>Select a blood group and city to see potential donor matches near the hospital.</p>
      </div>`;
    return;
  }

  if (!matches.length) {
    container.innerHTML = `
      <div class="match-preview-empty">
        <div class="match-preview-empty__icon">😔</div>
        <p>No available donors found for <strong>${bloodNeeded}</strong>${city ? ' in ' + city : ''}. Your request will still be submitted and we'll notify donors when they become available.</p>
      </div>`;
    return;
  }

  container.innerHTML = `
    <div style="font-size:0.78rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--white-muted);margin-bottom:0.75rem;">
      ${matches.length} potential match${matches.length > 1 ? 'es' : ''} found
    </div>
    ${matches.map(d => `
      <div class="match-donor-item">
        <div class="match-donor-avatar">${d.initials}</div>
        <div>
          <div class="match-donor-name">${d.name}</div>
          <div class="match-donor-meta">📍 ${d.city} · ${d.dist} km · <span class="badge badge-success" style="padding:0.1rem 0.5rem;font-size:0.7rem;">Available</span></div>
        </div>
      </div>
    `).join('')}
    <div style="font-size:0.78rem;color:var(--white-muted);margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border-glass);">
      All ${matches.length} donors will be notified when you submit.
    </div>`;
}

/* ── Live update preview ────────────────────────────────────── */
const bloodInput = document.getElementById('bloodTypeNeeded');
const cityInput  = document.getElementById('requestCity');

function updatePreview() {
  renderMatchPreview(bloodInput.value, cityInput.value);
}

bloodInput?.addEventListener('change', updatePreview);
cityInput?.addEventListener('input', () => {
  clearTimeout(window._prevTimer);
  window._prevTimer = setTimeout(updatePreview, 400);
});

/* ── Validation helpers ─────────────────────────────────────── */
function showErr(id, msg) {
  const el = document.getElementById(id);
  if (el) { if (msg) el.textContent = msg; el.classList.add('show'); }
}
function clearErr(id) { document.getElementById(id)?.classList.remove('show'); }
function isPhone(v)   { return /^\+?[\d\s\-]{7,15}$/.test(v); }

/* Live clear on input */
['patientName','bloodTypeNeeded','unitsNeeded','hospital','requestCity','contactNum'].forEach(id => {
  const el = document.getElementById(id);
  el?.addEventListener('input',  () => clearErr(id + 'Err'));
  el?.addEventListener('change', () => clearErr(id + 'Err'));
});

/* ── Form submit ─────────────────────────────────────────────── */
document.getElementById('emergencyForm')?.addEventListener('submit', e => {
  e.preventDefault();
  let valid = true;

  const checks = [
    ['patientName', v => v.trim().length > 0, 'patientNameErr', 'Patient name required.'],
    ['bloodTypeNeeded', v => v !== '',         'bloodTypeErr',   'Blood group required.'],
    ['unitsNeeded', v => Number(v) >= 1,       'unitsErr',       'At least 1 unit required.'],
    ['hospital',    v => v.trim().length > 0,  'hospitalErr',    'Hospital name required.'],
    ['requestCity', v => v.trim().length > 0,  'cityErr',        'City required.'],
    ['contactNum',  v => isPhone(v),           'contactErr',     'Valid contact number required.'],
  ];

  checks.forEach(([id, fn, errId, msg]) => {
    const el = document.getElementById(id);
    if (!el || !fn(el.value)) { showErr(errId, msg); valid = false; }
  });

  if (!valid) return;

  /* Show success */
  document.getElementById('formContent').style.display = 'none';
  document.getElementById('successState').classList.add('show');

  /* Scroll into view */
  document.querySelector('.emergency-form-card')?.scrollIntoView({ behavior:'smooth', block:'center' });
});
