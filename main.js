// mobile nav toggle
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('mobileToggle');
  const nav = document.getElementById('primary-navigation');

  toggle.addEventListener('click', function () {
    const expanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!expanded));
    if (!expanded) {
      nav.style.display = 'block';
    } else {
      nav.style.display = '';
    }
  });

  // set current year in footer
  const yearEl = document.getElementById('year');
  if(yearEl) yearEl.textContent = new Date().getFullYear();
});
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js').then(() => {
    console.log('Service worker registered');
  }).catch(err => console.warn('SW registration failed', err));
}
