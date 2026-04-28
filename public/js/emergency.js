const BACKEND = 'http://localhost/smart-blood-donor/backend';

/* ── Blood compatibility map (recipient type -> donor types) ── */
const compatMap = {
  'A+':  ['A+', 'A-', 'O+', 'O-'],
  'A-':  ['A-', 'O-'],
  'B+':  ['B+', 'B-', 'O+', 'O-'],
  'B-':  ['B-', 'O-'],
  'AB+': ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
  'AB-': ['A-', 'B-', 'AB-', 'O-'],
  'O+':  ['O+', 'O-'],
  'O-':  ['O-'],
};

/* ── Live match preview (uses backend) ──────────────────────── */
async function renderMatchPreview(bloodNeeded, city) {
  const container = document.getElementById('matchPreview');

  if (!bloodNeeded) {
    container.innerHTML = `
      <div class="match-preview-empty">
        <div class="match-preview-empty__icon">💡</div>
        <p>Select a blood group and city to see potential donor matches near the hospital.</p>
      </div>`;
    return;
  }

  container.innerHTML = `<div style="text-align:center;padding:1rem;color:var(--white-muted);">⏳ Searching donors...</div>`;

  try {
    const params = new URLSearchParams({ bloodType: bloodNeeded });
    if (city) params.set('city', city);
    params.set('available', '1');

    const res  = await fetch(`${BACKEND}/api/get_donors.php?${params}`);
    const data = await res.json();
    const matches = (data.donors || []).slice(0, 4);

    if (!matches.length) {
      container.innerHTML = `
        <div class="match-preview-empty">
          <div class="match-preview-empty__icon">😔</div>
          <p>No available donors found for <strong>${bloodNeeded}</strong>${city ? ' in ' + city : ''}. Your request will still be submitted and donors will be notified.</p>
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
            <div class="match-donor-meta">📍 ${d.city} · <span class="badge badge-success" style="padding:0.1rem 0.5rem;font-size:0.7rem;">Available</span></div>
          </div>
        </div>
      `).join('')}
      <div style="font-size:0.78rem;color:var(--white-muted);margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border-glass);">
        All ${matches.length} donors will be notified when you submit.
      </div>`;
  } catch {
    container.innerHTML = `<div class="match-preview-empty"><div class="match-preview-empty__icon">💡</div><p>Could not load donor preview. Backend may not be running.</p></div>`;
  }
}

/* ── Live preview triggers ──────────────────────────────────── */
const bloodInput = document.getElementById('bloodTypeNeeded');
const cityInput  = document.getElementById('requestCity');

function updatePreview() {
  renderMatchPreview(bloodInput?.value, cityInput?.value);
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

['patientName','bloodTypeNeeded','unitsNeeded','hospital','requestCity','contactNum'].forEach(id => {
  const el = document.getElementById(id);
  el?.addEventListener('input',  () => clearErr(id + 'Err'));
  el?.addEventListener('change', () => clearErr(id + 'Err'));
});

/* ── Form submit → POST to backend ──────────────────────────── */
document.getElementById('emergencyForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  let valid = true;

  const checks = [
      ['bloodTypeNeeded', v => v !== '',             'bloodTypeErr',   'Blood group required.'],
      ['unitsNeeded',     v => Number(v) >= 1,       'unitsErr',       'At least 1 unit required.'],
      ['hospital',        v => v.trim().length > 0,  'hospitalErr',    'Hospital name required.'],
      ['requestCity',     v => v.trim().length > 0,  'cityErr',        'City required.'],
    ];

  checks.forEach(([id, fn, errId, msg]) => {
    const el = document.getElementById(id);
    if (!el || !fn(el.value)) { showErr(errId, msg); valid = false; }
  });

  if (!valid) return;

  const submitBtn = e.target.querySelector('[type=submit]');
  if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting...'; }

  const payload = {
      bloodTypeNeeded:   document.getElementById('bloodTypeNeeded').value,
      unitsNeeded:       parseInt(document.getElementById('unitsNeeded').value),
      reqHospitalCenter: document.getElementById('hospital').value.trim(),
      requestCity:       document.getElementById('requestCity').value.trim(),
      urgencyLvl:        document.querySelector('input[name="urgency"]:checked')?.value || 'high',
      userID:            sessionStorage.getItem('userID') || '',
    };

  try {
    const res  = await fetch(`${BACKEND}/api/emergency_request.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      // Show success panel
      document.getElementById('formContent').style.display = 'none';
      const successEl = document.getElementById('successState');
      if (successEl) {
        successEl.classList.add('show');
        // Show the request ID in the success message if there's a spot for it
        const ridEl = document.getElementById('successRequestID');
        if (ridEl) ridEl.textContent = '#ER-' + String(data.requestID).padStart(4, '0');
      }
      document.querySelector('.emergency-form-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      showErr('contactErr', data.error || 'Submission failed. Please try again.');
    }
  } catch {
    showErr('contactErr', 'Network error. Make sure the PHP backend is running.');
  } finally {
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Emergency Request'; }
  }
});
