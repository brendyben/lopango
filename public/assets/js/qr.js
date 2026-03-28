/*!
 * LOPANGO QR Code Generator
 * API: api.qrserver.com — fiable, HTTPS, gratuit, sans clé API
 * Le QR est généré dans le navigateur du client (pas sur le serveur)
 */
(function(global){

function getUrl(data, size) {
  // API primaire: qrserver.com
  return 'https://api.qrserver.com/v1/create-qr-code/'
    + '?data='   + encodeURIComponent(data)
    + '&size='   + size + 'x' + size
    + '&color=0f4c35'   // Vert Lopango
    + '&bgcolor=ffffff'
    + '&margin=2'
    + '&format=png'
    + '&ecc=M';
}

function getFallbackUrl(data, size) {
  // API de secours: quickchart.io
  return 'https://quickchart.io/qr'
    + '?text='  + encodeURIComponent(data)
    + '&size='  + size
    + '&dark=0f4c35'
    + '&margin=1';
}

function isIncomplete(data) {
  return !data || data.indexOf('XXXX') >= 0 || data.indexOf('-000-') >= 0;
}

function drawPlaceholder(canvas, size) {
  canvas.style.display = 'block';
  canvas.width = size; canvas.height = size;
  var ctx = canvas.getContext('2d');
  ctx.fillStyle = '#f2f6f2'; ctx.fillRect(0, 0, size, size);
  ctx.strokeStyle = '#c8d8c8'; ctx.strokeRect(2, 2, size-4, size-4);
  ctx.fillStyle = '#6a8a6a';
  ctx.font = 'bold ' + Math.floor(size/8) + 'px sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText('QR', size/2, size/2 + 5);
}

function draw(canvasId, data, size) {
  size = size || 120;
  var canvas = document.getElementById(canvasId);
  if (!canvas) return;

  // Supprimer image précédente
  var ex = document.getElementById(canvasId + '_qr');
  if (ex) ex.remove();

  if (isIncomplete(data)) {
    drawPlaceholder(canvas, size);
    return;
  }

  var img = document.createElement('img');
  img.id     = canvasId + '_qr';
  img.width  = size;
  img.height = size;
  img.alt    = data;
  img.style.cssText = 'display:block;border-radius:2px;';

  // Essai API principale
  img.src = getUrl(data, size);

  img.onerror = function() {
    // Essai API de secours
    img.onerror = function() {
      // Dernier recours : afficher le texte du code
      img.remove();
      canvas.style.display = 'block';
      canvas.width = size; canvas.height = size;
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size);
      ctx.strokeStyle = '#0f4c35'; ctx.lineWidth = 2; ctx.strokeRect(2, 2, size-4, size-4);
      ctx.fillStyle = '#0f4c35'; ctx.textAlign = 'center';
      ctx.font = 'bold ' + Math.floor(size/10) + 'px monospace';
      ctx.fillText(data.substring(0, 18), size/2, size/2);
    };
    img.src = getFallbackUrl(data, size);
  };

  canvas.style.display = 'none';
  canvas.parentNode.insertBefore(img, canvas.nextSibling);
}

function toDataURL(data, size) {
  return getUrl(data || 'LOPANGO', size || 200);
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('canvas[data-qr]').forEach(function(canvas) {
    var d = canvas.dataset.qr;
    var s = parseInt(canvas.dataset.qrSize || '96');
    if (d) draw(canvas.id, d, s);
  });
});

global.LopangoQR = { draw: draw, toDataURL: toDataURL };

})(window);
