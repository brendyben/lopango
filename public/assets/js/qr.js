/**
 * LOPANGO — Générateur QR Code
 * Appelle /public/qr.php pour générer un vrai QR code scannable
 */

const LopangoQR = (() => {

  function draw(canvasId, data, size = 96) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Supprimer l'image précédente si elle existe
    const existing = document.getElementById(canvasId + '_img');
    if (existing) existing.remove();

    if (!data || data.includes('XXXX') || data.includes('000-U')) {
      // Données incomplètes — afficher placeholder
      canvas.style.display = 'block';
      canvas.width = size; canvas.height = size;
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#f0f6f0';
      ctx.fillRect(0, 0, size, size);
      ctx.strokeStyle = '#c8d8c8';
      ctx.strokeRect(2, 2, size-4, size-4);
      ctx.fillStyle = '#6a8a6a';
      ctx.font = 'bold ' + Math.floor(size/8) + 'px monospace';
      ctx.textAlign = 'center';
      ctx.fillText('QR', size/2, size/2 + 4);
      return;
    }

    // Créer une image pointant vers le générateur PHP
    const img = document.createElement('img');
    img.id     = canvasId + '_img';
    img.width  = size;
    img.height = size;
    img.alt    = data;
    img.style.display = 'block';
    img.style.borderRadius = '4px';

    // URL du générateur PHP local
    img.src = '/qr.php?data=' + encodeURIComponent(data) + '&size=' + size;

    img.onerror = function() {
      // Fallback si PHP indisponible
      canvas.style.display = 'block';
      canvas.width = size; canvas.height = size;
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff'; ctx.fillRect(0,0,size,size);
      ctx.strokeStyle = '#0f4c35'; ctx.lineWidth = 2;
      ctx.strokeRect(2,2,size-4,size-4);
      ctx.fillStyle = '#0f4c35';
      ctx.font = Math.floor(size/10) + 'px monospace';
      ctx.textAlign = 'center';
      ctx.fillText(data.substring(0,12), size/2, size/2);
      img.remove();
    };

    // Insérer après le canvas
    canvas.style.display = 'none';
    canvas.parentNode.insertBefore(img, canvas.nextSibling);
  }

  function toDataURL(data, size = 200) {
    return '/qr.php?data=' + encodeURIComponent(data) + '&size=' + size;
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-qr]').forEach(canvas => {
      const data = canvas.dataset.qr;
      const size = parseInt(canvas.dataset.qrSize || '96');
      if (data) draw(canvas.id, data, size);
    });
  });

  return { draw, toDataURL };
})();
