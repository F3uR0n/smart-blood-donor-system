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
  link.addEventListener('click', e => {
    e.preventDefault();
    switchTab(link.dataset.switch);
  });
});

/* ── Handle hash ───────────────────────────────────────────── */
if (window.location.hash === '#register') switchTab('register');

/* ── Validation helpers ────────────────────────────────────── */
function showErr(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg || el.textContent;
  el.classList.add('show');
}
function clearErr(id) {
  document.getElementById(id)?.classList.remove('show');
}
function isEmail(val) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val); }
function isPhone(val) { return /^\+?[\d\s\-]{7,15}$/.test(val); }

/* ── Live validation on blur ───────────────────────────────── */
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
  el.addEventListener('blur', () => {
    const valid = rules[fieldId](el.value);
    if (valid === true) clearErr(fieldId + 'Err');
    else showErr(typeof valid === 'string' ? valid : fieldId + 'Err');
  });
  el.addEventListener('input', () => clearErr(fieldId + 'Err'));
});

/* ── Password strength ─────────────────────────────────────── */
const regPass = document.getElementById('regPass');
const strengthFill = document.getElementById('strengthFill');
const strengthLabel = document.getElementById('strengthLabel');

regPass?.addEventListener('input', () => {
  const v = regPass.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { label: 'Too short', color: '#7f1d1d', pct: '15%' },
    { label: 'Weak',      color: '#dc2626', pct: '35%' },
    { label: 'Fair',      color: '#f59e0b', pct: '60%' },
    { label: 'Strong',    color: '#10b981', pct: '85%' },
    { label: 'Very strong', color: '#059669', pct: '100%' },
  ];
  const lvl = v.length === 0 ? null : levels[score];
  if (lvl) {
    strengthFill.style.width = lvl.pct;
    strengthFill.style.background = lvl.color;
    strengthLabel.textContent = lvl.label;
    strengthLabel.style.color = lvl.color;
  } else {
    strengthFill.style.width = '0%';
    strengthLabel.textContent = '—';
    strengthLabel.style.color = '';
  }
});

/* ── Login form submit ─────────────────────────────────────── */
document.getElementById('loginForm')?.addEventListener('submit', e => {
  e.preventDefault();
  let valid = true;
  const email = document.getElementById('loginEmail').value;
  const pass  = document.getElementById('loginPass').value;

  if (!isEmail(email)) { showErr('loginEmailErr'); valid = false; }
  if (!pass)            { showErr('loginPassErr');  valid = false; }

  if (valid) {
    /* POST to backend/auth/login.php — placeholder until PHP is ready */
    window.location.href = 'dashboard.html';
  }
});

/* ── Register form submit ──────────────────────────────────── */
document.getElementById('registerForm')?.addEventListener('submit', e => {
  e.preventDefault();
  let valid = true;

  const checks = [
    ['firstName',      v => v.trim().length > 0,             'firstNameErr',      'First name required.'],
    ['surName',        v => v.trim().length > 0,             'surNameErr',        'Last name required.'],
    ['regEmail',       v => isEmail(v),                      'regEmailErr',       'Valid email required.'],
    ['regPhone',       v => isPhone(v),                      'regPhoneErr',       'Valid phone required.'],
    ['regNID',         v => /^\d{10,17}$/.test(v),           'regNIDErr',         'NID must be 10–17 digits.'],
    ['bloodGroup',     v => v !== '',                         'bloodGroupErr',     'Select a blood group.'],
    ['role',           v => v !== '',                         'roleErr',           'Select a role.'],
    ['location',       v => v.trim().length > 0,             'locationErr',       'Location required.'],
    ['regPass',        v => v.length >= 8,                   'regPassErr',        'Min 8 characters.'],
    ['regPassConfirm', v => v === document.getElementById('regPass').value, 'regPassConfirmErr', 'Passwords do not match.'],
  ];

  checks.forEach(([id, fn, errId, msg]) => {
    const el = document.getElementById(id);
    if (!el || !fn(el.value)) { showErr(errId, msg); valid = false; }
  });

  const terms = document.getElementById('terms');
  if (!terms.checked) { showErr('termsErr', 'You must accept the terms.'); valid = false; }

  if (valid) {
    /* POST to backend/auth/register.php — placeholder */
    alert('Account created! Please check your email for a verification link.');
    switchTab('login');
  }
});
