/* ── Role demo switcher ────────────────────────────────────── */
const roleBtns = document.querySelectorAll('.role-btn');
const roleViews = document.querySelectorAll('.role-view');

const sidebarNavs = {
  donor: [
    { icon: '📊', label: 'Overview',         href: '#' },
    { icon: '🩸', label: 'Donation History', href: '#' },
    { icon: '💪', label: 'Health Profile',   href: '#' },
    { icon: '🏆', label: 'Leaderboard',      href: '#' },
    { icon: '🎁', label: 'Rewards',          href: '#' },
    { icon: '🔔', label: 'Notifications',    href: '#' },
  ],
  recipient: [
    { icon: '📊', label: 'Overview',          href: '#' },
    { icon: '🚨', label: 'Emergency Requests',href: 'emergency.html' },
    { icon: '🔍', label: 'Find Donors',       href: 'find-donor.html' },
    { icon: '🏥', label: 'Blood Banks',       href: 'blood-banks.html' },
    { icon: '🔔', label: 'Notifications',     href: '#' },
  ],
  admin: [
    { icon: '📊', label: 'Dashboard',         href: '#' },
    { icon: '👥', label: 'User Management',   href: '#' },
    { icon: '🚨', label: 'Emergency Requests',href: '#' },
    { icon: '🏥', label: 'Blood Banks',       href: 'blood-banks.html' },
    { icon: '📣', label: 'Campaigns',         href: 'campaigns.html' },
    { icon: '📈', label: 'Reports',           href: '#' },
    { icon: '⚙️', label: 'Settings',          href: '#' },
  ],
};

const sidebarProfiles = {
  donor:     { name: 'John Doe',     initials: 'JD', role: 'Donor' },
  recipient: { name: 'Ayesha Begum', initials: 'AB', role: 'Recipient' },
  admin:     { name: 'Admin User',   initials: 'AU', role: 'Admin' },
};

function setRole(role) {
  /* role buttons */
  roleBtns.forEach(b => b.classList.toggle('active', b.dataset.role === role));

  /* views */
  roleViews.forEach(v => v.classList.toggle('active', v.id === `view-${role}`));

  /* sidebar nav */
  const nav = document.getElementById('sidebarNav');
  nav.innerHTML = sidebarNavs[role].map((item, i) =>
    `<a href="${item.href}" class="${i === 0 ? 'active' : ''}">
      <span class="nav-icon">${item.icon}</span>${item.label}
    </a>`
  ).join('');

  /* sidebar profile */
  const p = sidebarProfiles[role];
  document.getElementById('avatarInitials').textContent = p.initials;
  document.getElementById('sidebarName').textContent   = p.name;
  document.getElementById('sidebarRole').textContent   = p.role;
}

roleBtns.forEach(btn => btn.addEventListener('click', () => setRole(btn.dataset.role)));

/* init */
setRole('donor');

/* ── Mobile sidebar toggle ─────────────────────────────────── */
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');

sidebarToggle?.addEventListener('click', () => {
  sidebar.classList.toggle('mobile-open');
});
