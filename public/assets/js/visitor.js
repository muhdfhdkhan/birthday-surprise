/* Birthday Surprise — visitor story interactions (vanilla JS).
 * Requires optional globals: Swal, gsap, confetti (all degrade gracefully).
 */
(function () {
  'use strict';

  var body   = document.body;
  var reduced = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var STORY = document.getElementById('storyFlags');
  var SLUG = STORY ? STORY.getAttribute('data-slug') : '';
  var NAME = STORY ? (STORY.getAttribute('data-name') || '') : '';
  var SECRET_RULE = STORY ? (STORY.getAttribute('data-secret-rule') || '') : '';
  var CSRF = body.getAttribute('data-csrf') || '';

  /* ---------------- interaction beacon (Phase 9 analytics) ----------------
   * Sends a fire-and-forget POST with keepalive so it survives replays. */
  function track(event) {
    if (!SLUG || !CSRF) { return; }
    var data = new URLSearchParams();
    data.append('event', event);
    data.append('_token', CSRF);
    fetch(urlBase() + '/api/b/' + encodeURIComponent(SLUG) + '/event', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data.toString(),
      keepalive: true,
      credentials: 'same-origin'
    }).catch(function () { /* analytics never breaks the journey */ });
  }

  /* ---------------- flash toast ---------------- */
  var flashType = body.getAttribute('data-flash-type') || '';
  var flashMsg  = body.getAttribute('data-flash-message') || '';
  if (flashMsg) {
    if (window.Swal) {
      window.Swal.fire({
        toast: true,
        position: 'top',
        icon: flashType === 'error' ? 'error' : 'success',
        title: flashMsg,
        showConfirmButton: false,
        timer: 3800,
        timerProgressBar: true
      });
    } else {
      alert(flashMsg);
    }
  }

  /* ---------------- countdown formats ---------------- */
  function tick(target, onDone) {
    var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
    var d = Math.floor(diff / 86400);
    var h = Math.floor(diff % 86400 / 3600);
    var m = Math.floor(diff % 3600 / 60);
    var s = diff % 60;
    return { d: d, h: h, m: m, s: ('0' + s).slice(-2), stamp:
      (d > 0 ? d + 'd ' : '') + (h > 0 || d > 0 ? h + 'h ' : '') + m + 'm ' + s + 's' };
  }

  function runTimer(el, fn, interval) {
    var target = new Date(el.getAttribute('data-target') || '').getTime();
    if (!target || isNaN(target)) { el.hidden = true; return; }
    var update = function () { var t = tick(target); fn(t); };
    update();
    var id = setInterval(function () {
      update();
      if (target - Date.now() <= 0) { clearInterval(id); if (!reduced) { burstSmall(); } }
    }, interval || 1000);
  }

  function burst(what) {
    if (!window.confetti || reduced) { return; }
    var base = { colors: ['#FF6FA5', '#F5C451', '#6B4EE6', '#BFF3E0', '#FFD6E7'] };
    if (what === 'big') {
      window.confetti(Object.assign({ particleCount: 160, spread: 100, origin: { y: .6 } }, base));
      setTimeout(function () {
        window.confetti(Object.assign({ particleCount: 90, spread: 130, origin: { y: .5 } }, base));
      }, 260);
    } else if (what === 'hearts') {
      var heart = window.confetti.shapeFromText ? window.confetti.shapeFromText({ text: '💖' }) : undefined;
      window.confetti(Object.assign({ particleCount: 45, spread: 80, scalar: 1.6, origin: { y: .65 } },
        heart ? { shapes: [heart] } : {}, base));
    } else {
      window.confetti(Object.assign({ particleCount: 60, spread: 75, origin: { y: .7 } }, base));
    }
  }
  function burstSmall() { burst('small'); }

  /* ---------------- gate extras ---------------- */
  var tiles = document.getElementById('countTiles');
  if (tiles) {
    runTimer(tiles, function (t) {
      document.getElementById('ctD').textContent = t.d;
      document.getElementById('ctH').textContent = t.h;
      document.getElementById('ctM').textContent = t.m;
      document.getElementById('ctS').textContent = t.s;
    });
  }

  var pwToggle = document.getElementById('pwToggle');
  if (pwToggle) {
    pwToggle.addEventListener('click', function () {
      var input = document.getElementById('password');
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      pwToggle.innerHTML = '<i class="fa-solid fa-' + (show ? 'eye-slash' : 'eye') + '" aria-hidden="true"></i>';
      input.focus();
    });
  }
  var unlockForm = document.getElementById('unlockForm');
  if (unlockForm) {
    unlockForm.addEventListener('submit', function () {
      var btn = document.getElementById('unlockBtn');
      if (btn) { btn.disabled = true; btn.querySelector('.btn-label').textContent = 'Meowing the door open… 🐣'; }
    });
  }

  /* ---------------- peek countdown banner ---------------- */
  var peek = document.getElementById('peekCounter');
  if (peek) {
    runTimer(peek, function (t) {
      document.getElementById('peekTimer').textContent = t.stamp;
    }, 1000);
  }

  /* ---------------- story stage system ---------------- */
  var stages = Array.prototype.slice.call(
    document.querySelectorAll('.visit-stage .story-card[data-stage]'));
  var idx = 0;

  function show(i) {
    stages.forEach(function (el, k) { el.classList.toggle('is-active', k === i); });
    idx = i;
    if (window.gsap && !reduced) {
      window.gsap.from(stages[i], { y: 30, opacity: 0.2, duration: .5, ease: 'power2.out', clearProps: 'all' });
    }
    var stage = stages[i].getAttribute('data-stage');
    track('stage_view');
    if (stage === 'letter' && !stages[i].__typed) { startTypewriter(stages[i]); }
    if (stage === 'finale') { burst('big'); }
    if (i > 0) { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); }
  }

  if (stages.length) {
    var gate = document.querySelector('.gate-card');
    if (gate) { gate.style.display = 'none'; }

    show(0);

    document.querySelectorAll('[data-next]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (idx < stages.length - 1) { show(idx + 1); }
      });
    });
    document.querySelectorAll('.stage').forEach(function (el, i) {
      el.setAttribute('data-i', String(i));
    });
  }

  /* ---------------- sorry: forgive / still angry ---------------- */
  var ANGRY_LINES = [
    'Kitten ties an even sadder ribbon 🥺',
    'The cat offers two fish now 🐟🐟',
    'Whole band of cats holds “sorry” signs 😿',
    'Okay the cats brought a tiny orchestra 🎻',
    'Fine — the cat is now lying flat on the floor 🥺'
  ];
  var angryClicks = 0;

  document.querySelectorAll('[data-forgive]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      track('forgive_click');
      burst('hearts');
      var stage = btn.closest('.stage');
      var cat = stage ? stage.querySelector('.sorry-cat') : null;
      if (cat) {
        cat.classList.add('is-forgiven');           /* happy pounce + hearts */
        cat.classList.remove('is-flop');
        /* let the celebration read for a beat before moving on */
        if (stage && stage.__nextIdx === undefined) {
          setTimeout(function () { advance(stage); }, reduced ? 150 : 750);
        }
        return;
      }
      if (stage && stage.__nextIdx === undefined) { advance(stage); }
    });
  });

  document.querySelectorAll('[data-angry]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      track('angry_click');
      angryClicks++;
      var stage = btn.closest('.stage');
      var card = btn.closest('.story-card');
      var cat = stage ? stage.querySelector('.sorry-cat') : null;
      /* the proper CSS cat escalates its dramatics every time */
      if (cat) {
        var lvl = Math.min(3, parseInt(cat.getAttribute('data-sad'), 10) + 1);
        cat.setAttribute('data-sad', String(lvl));
        if (lvl >= 3) { cat.classList.add('is-flop'); }
      }
      var note = stage ? stage.querySelector('[data-angry-note]') : null;
      if (card && !reduced) {
        card.classList.remove('wiggle');
        void card.offsetWidth; /* restart animation */
        card.classList.add('wiggle');
      }
      /* after 3 tries the angry button playfully dodges */
      if (angryClicks % 3 === 0 && !reduced && btn.style) {
        btn.style.transform = 'translateX(' + (Math.random() > .5 ? '' : '-') + '14px) rotate(' +
          (Math.random() * 12 - 6).toFixed(1) + 'deg)';
        setTimeout(function () { btn.style.transform = ''; }, 700);
      }
      if (note) {
        note.textContent = ANGRY_LINES[Math.min(angryClicks - 1, ANGRY_LINES.length - 1)];
        note.hidden = false;
      }
      if (window.gsap && !reduced) {
        window.gsap.fromTo(card.querySelector('.cat-face'), { scale: .7 }, { scale: 1, ease: 'elastic.out(1.2, .4)', duration: .6, clearProps: 'all' });
      }
    });
  });

  function advance(stageEl) {
    stageEl.__nextIdx = idx + 1;
    /* reveal the secret right after forgiveness if the rule says so */
    if (SECRET_RULE === 'after_sorry') { revealSecret(); }
    if (idx < stages.length - 1) {
      var btn = document.querySelector('.visit-stage .story-card[data-stage].is-active [data-next]');
      if (btn) { btn.click(); } else { show(idx + 1); }
    }
  }

  /* ---------------- reveal stage: candle blow ---------------- */
  var cake = document.getElementById('cake');
  if (cake) {
    cake.addEventListener('click', function () {
      if (cake.__blown) { return; }
      cake.__blown = true;
      cake.classList.add('is-blown');
      track('candle_blown');
      var hint = document.getElementById('candleHint');
      if (hint) { hint.textContent = 'Wish granted! 🌟'; }
      burst('big');
      if (SECRET_RULE === 'after_candles') { revealSecret(); }
    });
  }

  /* ---------------- letter typewriter ---------------- */
  function startTypewriter(stage) {
    stage.__typed = true;
    var target = stage.querySelector('[data-typewriter]');
    if (!target) { return; }
    if (reduced) { return; } /* keep static text */

    var html = target.innerHTML;
    target.innerHTML = '';
    target.classList.add('typing');

    /* build filtered list of text + <br> nodes */
    var parts = html.split(/(<br\s*\/?>)/i).filter(Boolean);
    var data = [];
    parts.forEach(function (part) {
      if (/^<br/i.test(part)) { data.push({ br: true }); return; }
      var text = part.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'");
      text.split('').forEach(function (ch) { data.push({ ch: ch }); });
    });

    var pos = 0, done = false;
    var timer = setInterval(function () {
      for (var step = 0; step < 3; step++) { /* faster: 3 chars per tick */
        if (pos >= data.length) { finish(); return; }
        var item = data[pos++];
        if (item.br) { target.appendChild(document.createElement('br')); }
        else { target.insertAdjacentText('beforeend', item.ch); }
      }
    }, 24);

    function finish() {
      if (done) { return; }
      done = true;
      clearInterval(timer);
      target.innerHTML = html;
      target.classList.remove('typing');
      /* NOTE: the secret only unlocks via its own trigger
         (candles / forgiveness / manual button) — never here. */
    }
    stage.__finishType = finish;
  }

  document.querySelectorAll('[data-skip-typewriter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      track('letter_skip');
      var stage = btn.closest('.stage');
      if (stage && stage.__finishType) { stage.__finishType(); }
    });
  });

  function revealSecret(vol) {
    var secret = document.querySelector('[data-secret]');
    if (!secret || !secret.hidden) { return; }
    if (vol === 'letter' && SECRET_RULE === 'manual') {
      return; /* manual rule: only via its own click (button declared below) */
    }
    secret.hidden = false;
    track('secret_reveal');
    burst('small');
  }

  /* manual secret reveal via the "Almost done" button */
  document.querySelectorAll('.btn-next').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (SECRET_RULE === 'manual') { revealSecret(); }
    });
  });

  /* ---------------- memories: lightbox ---------------- */
  var lb = document.getElementById('lightbox');
  if (lb) {
    var lbImg = lb.querySelector('img');
    var lbCap = lb.querySelector('.lb-caption');
    var photos = Array.prototype.slice.call(document.querySelectorAll('[data-photo]'));
    var lbIdx = 0;

    function openLb(i) {
      lbIdx = (i + photos.length) % photos.length;
      var p = photos[lbIdx];
      lbImg.src = p.getAttribute('data-full') || '';
      lbImg.alt = p.getAttribute('data-caption') || '';
      lbCap.textContent = p.getAttribute('data-caption') || '';
      lb.hidden = false;
      track('photo_open');
      document.addEventListener('keydown', onKey);
      document.body.style.overflow = 'hidden';
    }
    function closeLb() {
      lb.hidden = true;
      lbImg.src = '';
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    }
    function onKey(ev) {
      if (ev.key === 'Escape') { closeLb(); }
      if (ev.key === 'ArrowRight') { openLb(lbIdx + 1); }
      if (ev.key === 'ArrowLeft') { openLb(lbIdx - 1); }
    }
    lb.querySelector('.lb-close').addEventListener('click', closeLb);
    lb.addEventListener('click', function (ev) { if (ev.target === lb) { closeLb(); } });

    photos.forEach(function (p, i) {
      p.addEventListener('click', function () { openLb(i); });
      p.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); openLb(i); }
      });
    });
  }

  /* ---------------- finale opens only after gift AND message are sent ---------------- */
  var finaleGate = (function () {
    var stage = document.querySelector('.story-card[data-stage="replies"]');
    if (!stage) { return { send: function () {} }; }
    var btn = stage.querySelector('[data-next]');
    var note = stage.querySelector('[data-finale-note]');
    var sent = { gift: false, complaint: false };

    function paint() {
      if (!note) { return; }
      note.textContent = (!sent.gift && !sent.complaint)
        ? 'Send your gift and a little message first — they open the finale 🎁💌'
        : sent.gift
          ? 'Almost there — send your message too 💌'
          : 'Send your gift first — it opens the finale 🎁';
    }

    if (btn) { btn.disabled = true; btn.setAttribute('aria-disabled', 'true'); }
    paint();
    if (note) { note.hidden = false; }

    return {
      send: function (kind) {
        if (sent[kind]) { return; }
        sent[kind] = true;
        paint();
        if (sent.gift && sent.complaint && btn) {
          btn.disabled = false;
          btn.removeAttribute('aria-disabled');
          if (note) { note.hidden = true; }
        }
      }
    };
  })();

  /* ---------------- replies (gift + complaint) ---------------- */
  document.querySelectorAll('form[data-reply]').forEach(function (form) {
    /* chip selection */
    form.querySelectorAll('[data-chip]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        if (chip.classList.contains('is-on')) {
          chip.classList.remove('is-on');
          var hidden = form.querySelector('[data-category]');
          if (hidden) { hidden.value = ''; }
          return;
        }
        form.querySelectorAll('[data-chip]').forEach(function (c) { c.classList.remove('is-on'); });
        chip.classList.add('is-on');
        var hidden = form.querySelector('[data-category]');
        if (hidden) { hidden.value = chip.getAttribute('data-chip'); }
      });
    });

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var fd = new FormData(form);
      var btn = form.querySelector('button[type="submit"]');
      var note = form.querySelector('[data-note]');
      var type = form.getAttribute('data-type') || 'reaction';

      /* the little message is mandatory too: no empty wish-letters */
      if (type === 'complaint' && (fd.get('body') || '') === '') {
        if (note) {
          note.textContent = 'Write a few words first 💌';
          note.hidden = false;
        } else if (window.Swal) {
          window.Swal.fire({ toast: true, position: 'top', title: 'Write a few words first 💌', showConfirmButton: false, timer: 2600 });
        }
        return;
      }

      if (type === 'gift') {
        var catIn = form.querySelector('[data-category]');
        if (catIn && catIn.value === '' && (fd.get('body') || '') === '') {
          if (note) {
            note.textContent = 'Pick a gift first — tap one of the little boxes 🎁';
            note.hidden = false;
          } else if (window.Swal) {
            window.Swal.fire({ toast: true, position: 'top', title: 'Pick a gift first 🎁', showConfirmButton: false, timer: 2600 });
          }
          return;
        }
      }

      if (btn) { btn.disabled = true; }
      fetch(urlBase() + '/api/b/' + encodeURIComponent(SLUG) + '/response', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: (function () {
          var body = new FormData();
          body.append('type', type);
          body.append('body', fd.get('body') || '');
          body.append('category', fd.get('category') || '');
          body.append('website', fd.get('website') || '');
          body.append('_token', CSRF);
          return body;
        })(),
        credentials: 'same-origin'
      }).then(function (res) {
        /* success strictly by HTTP status — response bodies carry only messages */
        return res.json().then(
          function (data) { return { ok: res.ok, data: data }; },
          function () { return { ok: res.ok, data: { message: 'Meow… the cat dropped the letter. Try again 🐾' } }; }
        );
      }).then(function (out) {
        var ok = !!out.ok;
        var data = out.data;
        if (ok) { burst(type === 'gift' ? 'small' : 'hearts'); }
        if (ok && (type === 'gift' || type === 'complaint')) { finaleGate.send(type); }
        if (note) {
          note.textContent = data.message || 'Sent! 🐾';
          note.hidden = false;
        } else if (window.Swal) {
          window.Swal.fire({ toast: true, position: 'top', title: data.message || 'Sent! 🐾', showConfirmButton: false, timer: 3000 });
        }
        form.reset();
        form.querySelectorAll('[data-chip].is-on').forEach(function (c) { c.classList.remove('is-on'); });
        var hidden = form.querySelector('[data-category]');
        if (hidden) { hidden.value = ''; }
      }).catch(function () {
        if (window.Swal) {
          window.Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Network hiccup — try again 🐾', showConfirmButton: false, timer: 3000 });
        }
      }).finally(function () {
        if (btn) { btn.disabled = false; }
      });
    });
  });

  /* ---------------- reactions ---------------- */
  document.querySelectorAll('[data-reaction]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-reaction]').forEach(function (b) { b.classList.remove('is-picked'); });
      btn.classList.add('is-picked');
      burst('small');
      fetch(urlBase() + '/api/b/' + encodeURIComponent(SLUG) + '/response', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: new URLSearchParams({
          type: 'reaction',
          reaction: btn.getAttribute('data-reaction') || '',
          _token: CSRF
        }),
        credentials: 'same-origin'
      }).then(function (res) { return res.json(); }).then(function (data) {
        if (window.Swal) {
          window.Swal.fire({ toast: true, position: 'top', title: data.message || 'Received 💖', showConfirmButton: false, timer: 2600 });
        }
      }).catch(function () { /* silent */ });
    });
  });

  function urlBase() {
    var path = location.pathname.replace(/\/b\/[^/]+$/, '');
    return location.origin === 'null' ? '' : (location.origin + path);
  }

  /* ---------------- share button ---------------- */
  var share = document.getElementById('shareBtn');
  if (share) {
    share.addEventListener('click', function () {
      track('share_click');
      var link = location.href.split('#')[0];
      (navigator.share ? navigator.share({ title: 'A surprise for ' + NAME, url: link })
        .catch(function () {}) : navigator.clipboard && navigator.clipboard.writeText(link).catch(function () {}));
      if (window.Swal) {
        window.Swal.fire({ toast: true, position: 'bottom', title: 'Link ready to share! 💌', showConfirmButton: false, timer: 2800 });
      }
    });
  }

  /* ---------------- replay ---------------- */
  document.querySelectorAll('[data-replay]').forEach(function (b) {
    b.addEventListener('click', function () { track('replay_click'); });
  });

  /* ---------------- music pill ---------------- */
  var pill = document.getElementById('musicPill');
  if (pill) {
    var btn = document.getElementById('musicBtn');
    var player = document.getElementById('musicPlayer');
    if (btn && player) {
      btn.addEventListener('click', function () {
        track(player.paused ? 'music_play' : 'music_pause');
        if (player.paused) {
          player.volume = 0.35;
          player.play().then(function () {
            btn.classList.add('is-playing');
            btn.innerHTML = '<i class="fa-solid fa-pause" aria-hidden="true"></i>';
          }).catch(function () { /* autoplay blocked */ });
        } else {
          player.pause();
          btn.classList.remove('is-playing');
          btn.innerHTML = '<i class="fa-solid fa-play" aria-hidden="true"></i>';
        }
      });
    }
  }

  /* ---------------- unlocked-until format ---------------- */
  document.querySelectorAll('[data-until]').forEach(function (el) {
    var ts = parseInt(el.getAttribute('data-until'), 10);
    if (!ts) { el.parentNode && (el.parentNode.style.display = 'none'); return; }
    var d = new Date(ts * 1000);
    el.textContent = d.toLocaleString(undefined, { weekday: 'short', hour: '2-digit', minute: '2-digit' });
  });
})();
