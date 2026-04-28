/* ── Tab switching ─────────────────────────────────────────── */
function switchTab(tabName) {
  document.querySelectorAll('.login-tab').forEach(t => {
    t.classList.toggle('active', t.dataset.tab === tabName);
  });
  document.querySelectorAll('.tab-content').forEach(c => {
    c.classList.toggle('active', c.id === `tab-${tabName}`);
  });
}

document.querySelectorAll('.login-tab').forEach(tab => {
  tab.addEventListener('click', () => switchTab(tab.dataset.tab));
});
document.querySelectorAll('[data-switch]').forEach(link => {
  link.addEventListener('click', e => { e.preventDefault(); switchTab(link.dataset.switch); });
});
if (window.location.hash === '#register') switchTab('register');

/* ── Validation helpers ────────────────────────────────────── */
function showErr(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  if (msg) el.textContent = msg;
  el.classList.add('show');
}
function clearErr(id) { document.getElementById(id)?.classList.remove('show'); }
function isEmail(val) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val); }
function isPhone(val) { return /^\+?[\d\s\-]{7,15}$/.test(val); }

/* ── Live validation ───────────────────────────────────────── */
const rules = {
  loginEmail:     v => isEmail(v) || 'loginEmailErr',
  loginPass:      v => v.length > 0 || 'loginPassErr',
  firstName:      v => v.trim().length > 0 || 'firstNameErr',
  surName:        v => v.trim().length > 0 || 'surNameErr',
  regEmail:       v => isEmail(v) || 'regEmailErr',
  regPhone:       v => isPhone(v) || 'regPhoneErr',
  regNID:         v => /^\d{10,17}$/.test(v) || 'regNIDErr',
  bloodGroup:     v => v !== '' || 'bloodGroupErr',
  role:           v => v !== '' || 'roleErr',
  location:       v => v.trim().length > 0 || 'locationErr',
  regPass:        v => v.length >= 8 || 'regPassErr',
  regPassConfirm: v => (v === document.getElementById('regPass')?.value) || 'regPassConfirmErr',
};

Object.keys(rules).forEach(fieldId => {
  const el = document.getElementById(fieldId);
  if (!el) return;
  el.addEventListener('blur',  () => { const r = rules[fieldId](el.value); if (r === true) clearErr(fieldId + 'Err'); else showErr(typeof r === 'string' ? r : fieldId + 'Err'); });
  el.addEventListener('input', () => clearErr(fieldId + 'Err'));
});

/* ── Password strength ─────────────────────────────────────── */
const regPassEl   = document.getElementById('regPass');
const strengthFill  = document.getElementById('strengthFill');
const strengthLabel = document.getElementById('strengthLabel');

regPassEl?.addEventListener('input', () => {
  const v = regPassEl.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const levels = [
    { label:'Too short',   color:'#7f1d1d', pct:'15%' },
    { label:'Weak',        color:'#dc2626', pct:'35%' },
    { label:'Fair',        color:'#f59e0b', pct:'60%' },
    { label:'Strong',      color:'#10b981', pct:'85%' },
    { label:'Very strong', color:'#059669', pct:'100%'},
  ];
  const lvl = v.length === 0 ? null : levels[score];
  if (lvl && strengthFill && strengthLabel) {
    strengthFill.style.width      = lvl.pct;
    strengthFill.style.background = lvl.color;
    strengthLabel.textContent     = lvl.label;
    strengthLabel.style.color     = lvl.color;
  } else if (strengthFill) {
    strengthFill.style.width = '0%';
    if (strengthLabel) { strengthLabel.textContent = '—'; strengthLabel.style.color = ''; }
  }
});

/* ── CSRF helper ───────────────────────────────────────────── */
const BACKEND = 'http://localhost/smart-blood-donor/backend';
let _csrfToken = null;

async function getCsrfToken() {
  if (_csrfToken) return _csrfToken;
  try {
    const res = await fetch(`${BACKEND}/api/csrf_token.php`, { credentials: 'include' });
    const data = await res.json();
    _csrfToken = data.csrf_token;
    return _csrfToken;
  } catch {
    return '';
  }
}

function setSubmitLoading(btn, loading, label = 'Submit') {
  btn.disabled    = loading;
  btn.textContent = loading ? 'Please wait...' : label;
}

/* ── Login form ────────────────────────────────────────────── */
document.getElementById('loginForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  let valid = true;
  const email = document.getElementById('loginEmail').value;
  const pass  = document.getElementById('loginPass').value;

  if (!isEmail(email)) { showErr('loginEmailErr'); valid = false; }
  if (!pass)           { showErr('loginPassErr');  valid = false; }
  if (!valid) return;

  const btn = e.target.querySelector('[type=submit]');
  setSubmitLoading(btn, true, 'Log In');

  try {
    const csrf = await getCsrfToken();
    const res  = await fetch(`${BACKEND}/auth/login.php`, {
      method:      'POST',
      credentials: 'include',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body:        JSON.stringify({ email, password: pass, csrf_token: csrf }),
    });
    const data = await res.json();

    if (data.success) {
      // Store session info for dashboard
      sessionStorage.setItem('userID',    data.userID);
      sessionStorage.setItem('role',      data.role);
      sessionStorage.setItem('firstName', data.firstName);
      sessionStorage.setItem('name',      data.name);
      if (data.csrf_token) _csrfToken = data.csrf_token;
      window.location.href = 'dashboard.php';
    } else {
      const msg = data.error || 'Login failed. Please try again.';
      showErr('loginPassErr', msg);
      if (data.unverified) {
        showErr('loginEmailErr', 'Email not verified. Check your inbox.');
      }
    }
  } catch {
    showErr('loginPassErr', 'Network error. Please try again.');
  } finally {
    setSubmitLoading(btn, false, 'Log In');
  }
});

/* ── Register form ─────────────────────────────────────────── */
document.getElementById('registerForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  let valid = true;

  const checks = [
    ['firstName',      v => v.trim().length > 0,           'firstNameErr',      'First name required.'],
    ['surName',        v => v.trim().length > 0,           'surNameErr',        'Last name required.'],
    ['regEmail',       v => isEmail(v),                    'regEmailErr',       'Valid email required.'],
    ['regPhone',       v => isPhone(v),                    'regPhoneErr',       'Valid phone required.'],
    ['regNID',         v => /^\d{10,17}$/.test(v),         'regNIDErr',         'NID must be 10–17 digits.'],
    ['bloodGroup',     v => v !== '',                       'bloodGroupErr',     'Select a blood group.'],
    ['role',           v => v !== '',                       'roleErr',           'Select a role.'],
    ['location',       v => v.trim().length > 0,           'locationErr',       'Location required.'],
    ['regPass',        v => v.length >= 8,                 'regPassErr',        'Min 8 characters.'],
    ['regPassConfirm', v => v === document.getElementById('regPass').value, 'regPassConfirmErr', 'Passwords do not match.'],
  ];

  checks.forEach(([id, fn, errId, msg]) => {
    const el = document.getElementById(id);
    if (!el || !fn(el.value)) { showErr(errId, msg); valid = false; }
  });

  const terms = document.getElementById('terms');
  if (!terms?.checked) { showErr('termsErr', 'You must accept the terms.'); valid = false; }
  if (!valid) return;

  const btn = e.target.querySelector('[type=submit]');
  setSubmitLoading(btn, true, 'Create Account');

  const roleVal      = document.getElementById('role').value;
  const bloodGroup   = document.getElementById('bloodGroup').value;

  try {
    const csrf = await getCsrfToken();
    const res  = await fetch(`${BACKEND}/auth/register.php`, {
      method:      'POST',
      credentials: 'include',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({
        csrf_token: csrf,
        firstName:  document.getElementById('firstName').value.trim(),
        surName:    document.getElementById('surName').value.trim(),
        email:      document.getElementById('regEmail').value.trim(),
        phone:      document.getElementById('regPhone').value.trim(),
        nid:        document.getElementById('regNID').value.trim(),
        bloodType:  bloodGroup,
        role:       roleVal,
        location:   document.getElementById('location').value.trim(),
        password:   document.getElementById('regPass').value,
      }),
    });
    const data = await res.json();

    if (data.success) {
      // Show success notice
      const notice = document.getElementById('verifyNotice');
      if (notice) notice.style.display = 'block';
      e.target.reset();
      switchTab('login');
      showErr('loginEmailErr', 'Registration successful! Check your email to verify your account.');
    } else {
      const errors = data.errors || [data.error || 'Registration failed.'];
      errors.forEach((msg, i) => {
        if (i === 0) showErr('regPassErr', msg);
      });
      if (data.error?.includes('Email'))     showErr('regEmailErr', data.error);
      if (data.error?.includes('NID'))       showErr('regNIDErr',   data.error);
    }
  } catch {
    showErr('regPassErr', 'Network error. Please try again.');
  } finally {
    setSubmitLoading(btn, false, 'Create Account');
  }
});

/* ── Blood group field: hide for recipient role ────────────── */
document.getElementById('role')?.addEventListener('change', function () {
  const bloodRow = document.getElementById('bloodGroupRow');
  if (bloodRow) bloodRow.style.display = this.value === 'recipient' ? 'none' : '';
});
