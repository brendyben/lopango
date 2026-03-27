/**
 * LOPANGO — Application JavaScript Core
 */

'use strict';

const LopangoApp = (() => {

  // ── TOAST NOTIFICATIONS ─────────────────────────────────────────────────
  function toast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = { success: '✓', error: '✕', warn: '⚠', info: 'ℹ' };
    const classes = { success: '', error: ' warn', warn: ' warn', info: '' };

    const item = document.createElement('div');
    item.className = 'toast-item' + (classes[type] || '');
    item.innerHTML = `
      <div class="toast-title">${icons[type] || 'ℹ'} ${escapeHtml(message)}</div>
    `;
    container.appendChild(item);

    setTimeout(() => {
      item.style.opacity = '0';
      item.style.transform = 'translateX(16px)';
      item.style.transition = 'all .3s ease';
      setTimeout(() => item.remove(), 320);
    }, duration);
  }

  // ── ESCAPE HTML ─────────────────────────────────────────────────────────
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── CONFIRM DIALOG ──────────────────────────────────────────────────────
  function confirm(message, onConfirm) {
    if (window.confirm(message)) onConfirm();
  }

  // ── FORMAT NUMBERS ──────────────────────────────────────────────────────
  function formatFC(n) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FC';
  }

  function formatUSD(n) {
    return '$' + new Intl.NumberFormat('fr-FR').format(Math.round(n));
  }

  // ── COPY TO CLIPBOARD ───────────────────────────────────────────────────
  function copyText(text) {
    navigator.clipboard?.writeText(text)
      .then(() => toast('Copié dans le presse-papiers', 'success', 2000))
      .catch(() => {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        el.remove();
        toast('Copié', 'success', 2000);
      });
  }

  // ── FETCH API HELPER ────────────────────────────────────────────────────
  async function api(endpoint, options = {}) {
    const defaults = {
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    };
    const config = { ...defaults, ...options };
    if (config.body && typeof config.body === 'object') {
      config.body = JSON.stringify(config.body);
    }
    try {
      const res = await fetch(endpoint, config);
      const data = await res.json();
      if (!data.success) throw new Error(data.error || 'Erreur API');
      return data.data;
    } catch (err) {
      toast(err.message || 'Erreur réseau', 'error');
      throw err;
    }
  }

  // ── SIDEBAR ACTIVE STATE ─────────────────────────────────────────────────
  function initSidebar() {
    const params = new URLSearchParams(window.location.search);
    const currentPage = params.get('page') || '';
    document.querySelectorAll('.sb-item').forEach(item => {
      const href = item.getAttribute('href') || '';
      const itemPage = new URLSearchParams(href.split('?')[1] || '').get('page') || '';
      if (itemPage && itemPage === currentPage) {
        item.classList.add('active');
      }
    });
  }

  // ── FLASH ZONE AUTO-DISMISS ──────────────────────────────────────────────
  function initFlash() {
    const zone = document.getElementById('flash-zone');
    if (!zone) return;
    // Auto-afficher
    zone.classList.add('visible');
    // Auto-fermer après 5s
    setTimeout(() => {
      zone.style.opacity = '0';
      zone.style.transition = 'opacity .4s';
      setTimeout(() => zone.remove(), 420);
    }, 5000);
  }

  // ── PRINT HANDLER ────────────────────────────────────────────────────────
  function initPrint() {
    window.addEventListener('beforeprint', () => {
      document.body.classList.add('printing');
    });
    window.addEventListener('afterprint', () => {
      document.body.classList.remove('printing');
    });
  }

  // ── DEBOUNCE ─────────────────────────────────────────────────────────────
  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  // ── INIT ─────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initFlash();
    initPrint();

    // Fermer modals avec Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-"]').forEach(m => {
          m.style.display = 'none';
        });
      }
    });

    // Fermer modal en cliquant sur l'overlay
    document.querySelectorAll('[id^="modal-"]').forEach(modal => {
      modal.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
      });
    });

    // Tables : ligne cliquable
    document.querySelectorAll('tr[data-href]').forEach(row => {
      row.style.cursor = 'pointer';
      row.addEventListener('click', () => {
        window.location.href = row.dataset.href;
      });
    });
  });

  return { toast, escapeHtml, confirm, formatFC, formatUSD, copyText, api, debounce };
})();

// ── SPLASH ENGINE ─────────────────────────────────────────────────────────
(function () {
  const splash = document.getElementById('splash');
  if (!splash) return;

  const STEPS = [
    { lbl: 'Vérification des certificats…', pct: 18,  delay: 120  },
    { lbl: 'Chargement des communes…',      pct: 38,  delay: 680  },
    { lbl: 'Connexion Google Sheets…',      pct: 62,  delay: 1280 },
    { lbl: 'Chargement des biens…',         pct: 81,  delay: 1880 },
    { lbl: 'Prêt.',                         pct: 100, delay: 2500 },
  ];

  function buildWordmark() {
    const el = document.getElementById('sp-wordmark');
    if (!el) return;
    el.innerHTML = '';
    'LOPANGO'.split('').forEach((ch, i) => {
      const s = document.createElement('span');
      s.textContent = ch;
      s.style.cssText = `animation-delay:${0.35 + i * 0.055}s;animation-duration:.3s;animation-fill-mode:both;animation-timing-function:cubic-bezier(.34,1.56,.64,1);animation-name:sp-letter`;
      el.appendChild(s);
    });
  }

  function animCounter(id, target, duration, decimals = 0) {
    const el = document.getElementById(id);
    if (!el) return;
    const t0 = performance.now();
    (function tick(now) {
      const progress = Math.min((now - t0) / duration, 1);
      const ease     = 1 - Math.pow(1 - progress, 3);
      const value    = target * ease;
      el.textContent = decimals
        ? value.toFixed(decimals)
        : Math.round(value).toLocaleString('fr-FR');
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = decimals ? target.toFixed(decimals) : target.toLocaleString('fr-FR');
    })(t0);
  }

  function setProgress(pct, label, dotIndex) {
    const fill = document.getElementById('sp-fill');
    const lbl  = document.getElementById('sp-lbl');
    const pctEl= document.getElementById('sp-pct');
    if (fill)  fill.style.width  = pct + '%';
    if (lbl)   lbl.textContent   = label;
    if (pctEl) pctEl.textContent = pct + '%';
    for (let i = 0; i < 5; i++) {
      const dot = document.getElementById('sd' + i);
      if (!dot) continue;
      dot.className = 'sp-dot' + (i < dotIndex ? ' done' : i === dotIndex ? ' active' : '');
    }
  }

  function run() {
    buildWordmark();
    setTimeout(() => animCounter('sp-biens',   24700, 900),     1200);
    setTimeout(() => animCounter('sp-communes', 21,    700),     1200);
    setTimeout(() => animCounter('sp-irl',      89.4,  900, 1),  1400);
    STEPS.forEach((step, i) => setTimeout(() => setProgress(step.pct, step.lbl, i), step.delay));
    setTimeout(() => {
      splash.classList.add('exit');
      setTimeout(() => { splash.style.display = 'none'; }, 680);
    }, 3350);
  }

  setTimeout(run, 150);
})();
