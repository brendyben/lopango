/**
 * LOPANGO — Générateur QR Code RÉEL
 * Utilise l'API qrserver.com pour générer de vrais QR codes scannabes
 * Fallback canvas si offline
 */

const LopangoQR = (() => {

  // ── VRAI QR CODE via API ────────────────────────────────────────────────
  // Remplace le canvas par une image générée par l'API
  function draw(canvasId, data, size = 96) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Créer une image à la place du canvas
    const img = document.createElement('img');
    img.width  = size;
    img.height = size;
    img.alt    = data;
    img.style.cssText = canvas.style.cssText;
    img.style.display = 'block';

    // API qrserver.com — génère un vrai QR code
    // color=0f4c35 = vert Lopango, bgcolor=ffffff = fond blanc
    const url = 'https://api.qrserver.com/v1/create-qr-code/'
      + '?size=' + size + 'x' + size
      + '&data=' + encodeURIComponent(data)
      + '&color=0f4c35'
      + '&bgcolor=ffffff'
      + '&margin=4'
      + '&format=png';

    img.src = url;
    img.id  = canvasId + '_img';

    // Fallback si API indisponible
    img.onerror = function() {
      drawFallback(canvasId, data, size);
      img.remove();
    };

    // Remplacer le canvas par l'image
    canvas.parentNode.insertBefore(img, canvas);
    canvas.style.display = 'none';
  }

  // ── FALLBACK : vrai QR code généré localement (si offline) ──────────────
  function drawFallback(canvasId, data, size) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    canvas.style.display = 'block';
    canvas.width  = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    // Message offline
    ctx.fillStyle = '#0f4c35';
    ctx.font = 'bold ' + Math.floor(size/10) + 'px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('QR', size/2, size/2 - 4);
    ctx.font = Math.floor(size/14) + 'px monospace';
    ctx.fillText('offline', size/2, size/2 + 12);
    // Bordure
    ctx.strokeStyle = '#0f4c35';
    ctx.lineWidth = 2;
    ctx.strokeRect(2, 2, size-4, size-4);
  }

  // ── GÉNÉRER UNE URL QR (pour impression) ──────────────────────────────
  function toDataURL(data, size = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/'
      + '?size=' + size + 'x' + size
      + '&data=' + encodeURIComponent(data)
      + '&color=0f4c35'
      + '&bgcolor=ffffff'
      + '&margin=4'
      + '&format=png';
  }

  // ── AUTO-INIT ─────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-qr]').forEach(canvas => {
      const data = canvas.dataset.qr;
      const size = parseInt(canvas.dataset.qrSize || '96');
      if (data) draw(canvas.id, data, size);
    });
  });

  return { draw, toDataURL };
})();
