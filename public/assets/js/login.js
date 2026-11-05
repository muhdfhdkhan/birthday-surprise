/* Birthday Surprise — admin login interactions */
(function () {
  'use strict';

  var $ = function (sel) { return document.querySelector(sel); };

  /* Server flash -> SweetAlert toast (inline banner already shows it without JS). */
  var body = document.body;
  var type = body.getAttribute('data-flash-type') || '';
  var message = body.getAttribute('data-flash-message') || '';
  if (type && message && window.Swal) {
    Swal.fire({
      toast: true,
      position: 'top',
      icon: type === 'success' ? 'success' : 'error',
      title: message,
      showConfirmButton: false,
      timer: 3200,
      timerProgressBar: true
    });
  }

  /* Show / hide password. */
  var pw = $('#password');
  var toggle = $('#pwToggle');
  if (pw && toggle) {
    toggle.addEventListener('click', function () {
      var showing = pw.type === 'text';
      pw.type = showing ? 'password' : 'text';
      toggle.innerHTML = '<i class="fa-solid ' + (showing ? 'fa-eye' : 'fa-eye-slash') +
        '" aria-hidden="true"></i>';
      toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      pw.focus();
    });
  }

  /* Native validation + button loading state. */
  var form = $('#loginForm');
  var btn = $('#loginBtn');
  if (form && btn) {
    form.addEventListener('submit', function (event) {
      if (form.checkValidity && !form.checkValidity()) {
        event.preventDefault();
        if (form.reportValidity) { form.reportValidity(); }
        if (window.Swal) {
          Swal.fire({
            toast: true, position: 'top', icon: 'warning',
            title: 'Please fill in both fields 🐱',
            showConfirmButton: false, timer: 2400
          });
        }
        return;
      }
      btn.disabled = true;
      btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>' +
        '<span class="btn-label">Signing in…</span>';
    });
  }

  /* Entrance animation (GSAP optional; page works fine without it). */
  var reduced = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (window.gsap && !reduced) {
    gsap.from('.login-card', { y: 34, opacity: 0, duration: 0.55, ease: 'power3.out' });
    gsap.from('.brand-badge', { scale: 0.5, rotate: -25, opacity: 0, duration: 0.6, delay: 0.15, ease: 'back.out(2)' });
    gsap.from('.field', { y: 16, opacity: 0, duration: 0.4, stagger: 0.08, delay: 0.25, ease: 'power2.out' });
    gsap.from('#loginBtn', { y: 12, opacity: 0, duration: 0.4, delay: 0.45, ease: 'power2.out' });
  }
})();
