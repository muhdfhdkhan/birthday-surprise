/* Birthday Surprise — admin dashboard interactions */
(function () {
  'use strict';

  var body = document.body;
  var type = body.getAttribute('data-flash-type') || '';
  var message = body.getAttribute('data-flash-message') || '';

  var reduced = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Greet the signed-in admin: tiny confetti + toast. */
  if (message) {
    if (window.confetti && !reduced) {
      confetti({
        particleCount: 90,
        spread: 70,
        origin: { y: 0.7 },
        colors: ['#FF6FA5', '#F5C451', '#6B4EE6', '#BFF3E0']
      });
    }
    if (window.Swal) {
      Swal.fire({
        toast: true,
        position: 'top',
        icon: type === 'error' ? 'error' : 'success',
        title: message,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true
      });
    }
  }

  /* Gentle entrance for the welcome + stat cards. */
  if (window.gsap && !reduced) {
    gsap.from('.welcome-title', { y: -14, opacity: 0, duration: 0.45, ease: 'power2.out', clearProps: 'all' });
    gsap.from('.stat-card', { y: 22, opacity: 0, duration: 0.5, stagger: 0.08, ease: 'power2.out', clearProps: 'all' });
  }
})();
