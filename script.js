// Mobile menu
const mobileToggle = document.getElementById('mobileToggle');
const navLinks = document.getElementById('navLinks');

mobileToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
  const icon = mobileToggle.querySelector('i');
  icon.classList.toggle('fa-bars');
  icon.classList.toggle('fa-times');
});

// Scroll animations
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      if (entry.target.classList.contains('animate-card')) {
        const delay = entry.target.dataset.delay || 0;
        setTimeout(() => entry.target.classList.add('in-view'), delay);
      } else {
        entry.target.classList.add('in-view');
      }
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.animate-fade, .animate-card').forEach(el => observer.observe(el));

// Hero title
window.addEventListener('load', () => {
  document.querySelector('.animate-title').style.opacity = '1';
});

// Form alert
document.querySelector('form').addEventListener('submit', function(e) {
  setTimeout(() => {
    alert('Demande envoyée ! Réponse sous 24h.');
  }, 500);
});