const navToggle = document.getElementById('navToggle');
const siteNav = document.getElementById('siteNav');

if (navToggle && siteNav) {
    navToggle.addEventListener('click', () => {
        siteNav.classList.toggle('open');
    });
}

const revealElements = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                revealObserver.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 }
);

revealElements.forEach((el) => revealObserver.observe(el));

