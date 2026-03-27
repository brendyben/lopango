/**
 * LOPANGO — Générateur QR Code (Canvas)
 * Pseudo-QR stylisé avec les couleurs Lopango
 * Pour un vrai QR code en prod, utiliser phpqrcode côté serveur
 */

const LopangoQR = (() => {

  function draw(canvasId, data, size = 96) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width  = size;
    canvas.height = size;

    const N  = 21;
    const cs = size / N;

    // Fond blanc
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    // Fonction : est-ce une zone de coin (finder pattern) ?
    const isCornerZone = (row, col) => {
      const inBox = (r, c, sr, sc) => r >= sr && r < sr + 7 && c >= sc && c < sc + 7;
      return inBox(row, col, 0, 0) || inBox(row, col, 0, N - 7) || inBox(row, col, N - 7, 0);
    };

    // Dessin du finder pattern
    const isCornerFilled = (row, col) => {
      const check = (r, c, sr, sc) => {
        const lr = r - sr, lc = c - sc;
        if (lr < 0 || lr >= 7 || lc < 0 || lc >= 7) return false;
        return lr === 0 || lr === 6 || lc === 0 || lc === 6
          || (lr >= 2 && lr <= 4 && lc >= 2 && lc <= 4);
      };
      return check(row, col, 0, 0) || check(row, col, 0, N - 7) || check(row, col, N - 7, 0);
    };

    // Hash déterministe de la chaîne
    const hash = data.split('').reduce((acc, ch) => (acc * 31 + ch.charCodeAt(0)) | 0, 0);
    const isDataFilled = (row, col) => Math.abs((hash + row * 17 + col * 13) % 5) < 2;

    ctx.fillStyle = '#0f4c35';

    for (let row = 0; row < N; row++) {
      for (let col = 0; col < N; col++) {
        const filled = isCornerZone(row, col)
          ? isCornerFilled(row, col)
          : isDataFilled(row, col);

        if (filled) {
          const x = col * cs + 0.5;
          const y = row * cs + 0.5;
          const w = cs - 0.5;
          const r = cs * 0.08; // Coins légèrement arrondis

          ctx.beginPath();
          if (ctx.roundRect) {
            ctx.roundRect(x, y, w, w, r);
          } else {
            // Fallback pour les navigateurs anciens
            ctx.rect(x, y, w, w);
          }
          ctx.fill();
        }
      }
    }

    // Accent or sur le coin bas-droit (signature Lopango)
    ctx.fillStyle = '#c9a227';
    const accentRow = N - 3, accentCol = N - 3;
    ctx.fillRect(accentCol * cs + 1, accentRow * cs + 1, cs - 2, cs - 2);
  }

  // Générer un Data URL (pour impression)
  function toDataURL(data, size = 200) {
    const canvas = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;
    // Injecter temporairement dans le DOM pour draw()
    canvas.id = '_lopango_qr_tmp_' + Date.now();
    canvas.style.display = 'none';
    document.body.appendChild(canvas);
    draw(canvas.id, data, size);
    const dataURL = canvas.toDataURL('image/png');
    canvas.remove();
    return dataURL;
  }

  // Auto-init : chercher tous les canvas[data-qr]
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-qr]').forEach(canvas => {
      const data = canvas.dataset.qr;
      const size = parseInt(canvas.dataset.qrSize || '96');
      if (data) draw(canvas.id, data, size);
    });
  });

  return { draw, toDataURL };
})();
