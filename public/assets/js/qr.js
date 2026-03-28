/*!
 * LOPANGO — QR Code Generator (Pure JS, no dependencies)
 * Based on qrcode-generator by Kazuhiko Arase (MIT License)
 * Adapted for Lopango with green color scheme
 */

var LopangoQR = (function() {

// ── MINIMAL QR CODE ENGINE ──────────────────────────────────────────────────
var QR = {};

QR.stringToBytes = function(s) {
  var b = [];
  for (var i = 0; i < s.length; i++) {
    var c = s.charCodeAt(i);
    if (c < 128) b.push(c);
    else if (c < 2048) { b.push(192|(c>>6)); b.push(128|(c&63)); }
    else { b.push(224|(c>>12)); b.push(128|((c>>6)&63)); b.push(128|(c&63)); }
  }
  return b;
};

// GF(256) tables
var EXP_TABLE = new Array(256);
var LOG_TABLE = new Array(256);
(function() {
  for (var i = 0; i < 8; i++) EXP_TABLE[i] = 1 << i;
  for (var i = 8; i < 256; i++) EXP_TABLE[i] = EXP_TABLE[i-4]^EXP_TABLE[i-5]^EXP_TABLE[i-6]^EXP_TABLE[i-8];
  for (var i = 0; i < 255; i++) LOG_TABLE[EXP_TABLE[i]] = i;
})();

function gfMul(a,b) { if(a===0||b===0) return 0; return EXP_TABLE[(LOG_TABLE[a]+LOG_TABLE[b])%255]; }

function Polynomial(num, shift) {
  this.num = num; this.shift = shift || 0;
}
Polynomial.prototype = {
  get: function(i) { return this.num[i+this.shift]; },
  len: function() { return this.num.length - this.shift; },
  multiply: function(e) {
    var n = new Array(this.len() + e.len() - 1);
    for (var i = 0; i < n.length; i++) n[i] = 0;
    for (var i = 0; i < this.len(); i++)
      for (var j = 0; j < e.len(); j++)
        n[i+j] ^= gfMul(this.get(i), e.get(j));
    return new Polynomial(n);
  },
  mod: function(e) {
    if (this.len() - e.len() < 0) return this;
    var ratio = LOG_TABLE[this.get(0)] - LOG_TABLE[e.get(0)];
    var n = new Array(this.num.length);
    for (var i = 0; i < this.num.length; i++) n[i] = this.num[i];
    for (var i = 0; i < e.len(); i++) n[i] ^= gfMul(e.get(i), EXP_TABLE[(ratio+256)%255]);
    return new Polynomial(n, 1).mod(e);
  }
};

function getErrorCorrectPolynomial(errorCorrectLength) {
  var a = new Polynomial([1]);
  for (var i = 0; i < errorCorrectLength; i++) a = a.multiply(new Polynomial([1, EXP_TABLE[i]]));
  return a;
}

var PATTERN_POSITION_TABLE = [[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38]];
var MAX_DATA_CODEWORDS = [0,19,34,55,80,108,136];
var RS_BLOCK_TABLE = [
  null,
  [1,26,19],[1,44,34],[1,70,55],[1,100,80],[1,134,108],[2,86,68]
];

function getRSBlocks(version) {
  var b = RS_BLOCK_TABLE[version];
  if (!b) return [];
  return [{totalCount: b[0], dataCount: b[2]}];
}

function QRCode(typeNumber, errorCorrectionLevel) {
  this.typeNumber = typeNumber;
  this.errorCorrectionLevel = errorCorrectionLevel;
  this.modules = null;
  this.moduleCount = 0;
  this.dataCache = null;
  this.dataList = [];
}

QRCode.prototype.addData = function(data) {
  this.dataList.push(data);
  this.dataCache = null;
};

QRCode.prototype.isDark = function(row, col) {
  if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) throw new Error(row+','+col);
  return this.modules[row][col];
};

QRCode.prototype.getModuleCount = function() { return this.moduleCount; };

QRCode.prototype.make = function() {
  this.makeImpl(false, this.getBestMaskPattern());
};

QRCode.prototype.makeImpl = function(test, maskPattern) {
  this.moduleCount = this.typeNumber * 4 + 17;
  this.modules = [];
  for (var row = 0; row < this.moduleCount; row++) {
    this.modules[row] = [];
    for (var col = 0; col < this.moduleCount; col++) this.modules[row][col] = null;
  }
  this.setupPositionProbePattern(0, 0);
  this.setupPositionProbePattern(this.moduleCount - 7, 0);
  this.setupPositionProbePattern(0, this.moduleCount - 7);
  this.setupPositionAdjustPattern();
  this.setupTimingPattern();
  this.setupTypeInfo(test, maskPattern);
  if (this.typeNumber >= 7) this.setupTypeNumber(test);
  if (this.dataCache == null) this.dataCache = QRCode.createData(this.typeNumber, this.errorCorrectionLevel, this.dataList);
  this.mapData(this.dataCache, maskPattern);
};

QRCode.prototype.setupPositionProbePattern = function(row, col) {
  for (var r = -1; r <= 7; r++) for (var c = -1; c <= 7; c++) {
    if (row+r<=-1||this.moduleCount<=row+r||col+c<=-1||this.moduleCount<=col+c) continue;
    this.modules[row+r][col+c] = (0<=r&&r<=6&&(c===0||c===6))||(0<=c&&c<=6&&(r===0||r===6))||(2<=r&&r<=4&&2<=c&&c<=4);
  }
};

QRCode.prototype.getBestMaskPattern = function() {
  var minLostPoint = 0, pattern = 0;
  for (var i = 0; i < 8; i++) {
    this.makeImpl(true, i);
    var lostPoint = this.getLostPoint();
    if (i === 0 || minLostPoint > lostPoint) { minLostPoint = lostPoint; pattern = i; }
  }
  return pattern;
};

QRCode.prototype.getLostPoint = function() {
  var mc = this.moduleCount, lostPoint = 0;
  for (var row = 0; row < mc; row++) for (var col = 0; col < mc; col++) {
    var sameCount = 0, dark = this.isDark(row, col);
    for (var r = -1; r <= 1; r++) for (var c = -1; c <= 1; c++) {
      if (r===0&&c===0) continue;
      try { if (dark===this.isDark(row+r,col+c)) sameCount++; } catch(e){}
    }
    if (sameCount > 5) lostPoint += 3 + sameCount - 5;
  }
  for (var row = 0; row < mc-1; row++) for (var col = 0; col < mc-1; col++) {
    var count = 0;
    if (this.isDark(row,col)) count++;
    if (this.isDark(row+1,col)) count++;
    if (this.isDark(row,col+1)) count++;
    if (this.isDark(row+1,col+1)) count++;
    if (count===0||count===4) lostPoint += 3;
  }
  for (var row = 0; row < mc; row++) for (var col = 0; col < mc-6; col++) {
    if (this.isDark(row,col)&&!this.isDark(row,col+1)&&this.isDark(row,col+2)&&this.isDark(row,col+3)&&this.isDark(row,col+4)&&!this.isDark(row,col+5)&&this.isDark(row,col+6))
      lostPoint += 40;
  }
  var darkCount = 0;
  for (var col = 0; col < mc; col++) for (var row = 0; row < mc; row++) if (this.isDark(row,col)) darkCount++;
  var ratio = Math.abs(100*darkCount/mc/mc-50)/5;
  lostPoint += ratio * 10;
  return lostPoint;
};

QRCode.prototype.setupTimingPattern = function() {
  for (var r = 8; r < this.moduleCount-8; r++) if (this.modules[r][6]==null) this.modules[r][6] = r%2===0;
  for (var c = 8; c < this.moduleCount-8; c++) if (this.modules[6][c]==null) this.modules[6][c] = c%2===0;
};

QRCode.prototype.setupPositionAdjustPattern = function() {
  var pos = PATTERN_POSITION_TABLE[this.typeNumber];
  for (var i = 0; i < pos.length; i++) for (var j = 0; j < pos.length; j++) {
    var row = pos[i], col = pos[j];
    if (this.modules[row][col] != null) continue;
    for (var r = -2; r <= 2; r++) for (var c = -2; c <= 2; c++)
      this.modules[row+r][col+c] = r===-2||r===2||c===-2||c===2||(r===0&&c===0);
  }
};

QRCode.prototype.setupTypeNumber = function(test) {
  var bits = BCHTypeNumber(this.typeNumber);
  for (var i = 0; i < 18; i++) {
    this.modules[~~(i/3)][i%3+this.moduleCount-8-3] = !test && ((bits>>i)&1)===1;
  }
  for (var i = 0; i < 18; i++) {
    this.modules[i%3+this.moduleCount-8-3][~~(i/3)] = !test && ((bits>>i)&1)===1;
  }
};

QRCode.prototype.setupTypeInfo = function(test, maskPattern) {
  var data = (1<<3)|maskPattern, bits = BCHTypeInfo(data);
  var pos1 = [[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
  for (var i = 0; i < 15; i++) {
    var mod = !test&&((bits>>i)&1)===1;
    this.modules[pos1[i][0]][pos1[i][1]] = mod;
  }
  var pos2 = [[this.moduleCount-1,8],[this.moduleCount-2,8],[this.moduleCount-3,8],[this.moduleCount-4,8],[this.moduleCount-5,8],[this.moduleCount-6,8],[this.moduleCount-7,8],[8,this.moduleCount-8],[8,this.moduleCount-7],[8,this.moduleCount-6],[8,this.moduleCount-5],[8,this.moduleCount-4],[8,this.moduleCount-3],[8,this.moduleCount-2],[8,this.moduleCount-1]];
  for (var i = 0; i < 15; i++) {
    var mod = !test&&((bits>>i)&1)===1;
    this.modules[pos2[i][0]][pos2[i][1]] = mod;
  }
  this.modules[this.moduleCount-8][8] = !test;
};

QRCode.prototype.mapData = function(data, maskPattern) {
  var inc = -1, row = this.moduleCount-1, bitIndex = 7, byteIndex = 0;
  for (var col = this.moduleCount-1; col > 0; col -= 2) {
    if (col===6) col--;
    while (true) {
      for (var c = 0; c < 2; c++) {
        if (this.modules[row][col-c]==null) {
          var dark = false;
          if (byteIndex < data.length) dark = ((data[byteIndex]>>>bitIndex)&1)===1;
          var mask = QRCode.getMask(maskPattern, row, col-c);
          if (mask) dark = !dark;
          this.modules[row][col-c] = dark;
          bitIndex--;
          if (bitIndex===-1) { byteIndex++; bitIndex=7; }
        }
      }
      row += inc;
      if (row<0||this.moduleCount<=row) { row-=inc; inc=-inc; break; }
    }
  }
};

QRCode.getMask = function(maskPattern, i, j) {
  switch(maskPattern) {
    case 0: return (i+j)%2===0;
    case 1: return i%2===0;
    case 2: return j%3===0;
    case 3: return (i+j)%3===0;
    case 4: return (~~(i/2)+~~(j/3))%2===0;
    case 5: return (i*j)%2+(i*j)%3===0;
    case 6: return ((i*j)%2+(i*j)%3)%2===0;
    case 7: return ((i*j)%3+(i+j)%2)%2===0;
  }
  return false;
};

QRCode.createData = function(typeNumber, errorCorrectionLevel, dataList) {
  var rsBlocks = getRSBlocks(typeNumber);
  var buffer = [];
  for (var i = 0; i < dataList.length; i++) {
    var data = dataList[i];
    var bytes = QR.stringToBytes(data);
    // Mode byte
    buffer.push(0); buffer.push(0); buffer.push(0); buffer.push(1); // 0100 = byte
    // Length
    var len = bytes.length;
    for (var b = 7; b >= 0; b--) buffer.push((len>>b)&1);
    // Data
    for (var j = 0; j < bytes.length; j++) for (var b = 7; b >= 0; b--) buffer.push((bytes[j]>>b)&1);
  }
  // Calculate total data count
  var totalDataCount = 0;
  for (var i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
  // Terminator
  for (var i = 0; i < 4 && buffer.length < totalDataCount*8; i++) buffer.push(0);
  // Padding
  while (buffer.length % 8 !== 0) buffer.push(0);
  // Padding bytes
  while (buffer.length < totalDataCount*8) {
    for (var b=7;b>=0;b--) buffer.push((0xEC>>b)&1);
    if (buffer.length < totalDataCount*8) for (var b=7;b>=0;b--) buffer.push((0x11>>b)&1);
  }
  // Build bytes
  var data = [];
  for (var i = 0; i < buffer.length; i += 8) {
    var byte = 0;
    for (var b = 0; b < 8; b++) byte = (byte<<1)|(buffer[i+b]||0);
    data.push(byte);
  }
  // RS error correction
  var offset = 0;
  var maxDcCount = 0, maxEcCount = 0;
  var dcdata = [], ecdata = [];
  for (var r = 0; r < rsBlocks.length; r++) {
    var dcCount = rsBlocks[r].dataCount;
    var ecCount = rsBlocks[r].totalCount - dcCount;
    maxDcCount = Math.max(maxDcCount, dcCount);
    maxEcCount = Math.max(maxEcCount, ecCount);
    dcdata[r] = data.slice(offset, offset+dcCount);
    offset += dcCount;
    var rsPoly = getErrorCorrectPolynomial(ecCount);
    var rawPoly = new Polynomial(dcdata[r], rsPoly.len()-1);
    var modPoly = rawPoly.mod(rsPoly);
    ecdata[r] = [];
    for (var i = 0; i < rsPoly.len()-1; i++) {
      var modIndex = i + modPoly.len() - (rsPoly.len()-1);
      ecdata[r].push(modIndex >= 0 ? modPoly.get(modIndex) : 0);
    }
  }
  var totalCodeCount = 0;
  for (var i = 0; i < rsBlocks.length; i++) totalCodeCount += rsBlocks[i].totalCount;
  var result = new Array(totalCodeCount);
  var index = 0;
  for (var i = 0; i < maxDcCount; i++) for (var r = 0; r < rsBlocks.length; r++) if (i < dcdata[r].length) result[index++] = dcdata[r][i];
  for (var i = 0; i < maxEcCount; i++) for (var r = 0; r < rsBlocks.length; r++) if (i < ecdata[r].length) result[index++] = ecdata[r][i];
  return result;
};

function BCHTypeInfo(data) {
  var d = data<<10;
  while (BCHDigit(d)-BCHDigit(1335)>=0) d ^= 1335<<(BCHDigit(d)-BCHDigit(1335));
  return (data<<10)|d^21522;
}
function BCHTypeNumber(data) {
  var d = data<<12;
  while (BCHDigit(d)-BCHDigit(7973)>=0) d ^= 7973<<(BCHDigit(d)-BCHDigit(7973));
  return (data<<12)|d;
}
function BCHDigit(data) { var d=0; while(data!==0){d++;data>>>=1;} return d; }

// ── DETERMINE QR VERSION NEEDED ─────────────────────────────────────────────
function getVersion(data) {
  var len = QR.stringToBytes(data).length;
  // Version 1: up to 17 bytes, Version 2: up to 32, Version 3: up to 53, etc.
  if (len <= 17) return 1;
  if (len <= 32) return 2;
  if (len <= 53) return 3;
  if (len <= 78) return 4;
  if (len <= 106) return 5;
  return 6;
}

// ── PUBLIC API ───────────────────────────────────────────────────────────────

function draw(canvasId, data, size) {
  size = size || 120;
  var canvas = document.getElementById(canvasId);
  if (!canvas) return;

  // Supprimer image précédente
  var existing = document.getElementById(canvasId + '_qr');
  if (existing) existing.remove();

  if (!data || data.indexOf('XXXX') >= 0 || data.indexOf('000-U') >= 0) {
    // Placeholder
    canvas.style.display = 'block';
    canvas.width = size; canvas.height = size;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#f0f6f0'; ctx.fillRect(0,0,size,size);
    ctx.strokeStyle = '#c8d8c8'; ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle = '#6a8a6a';
    ctx.font = 'bold ' + Math.floor(size/8) + 'px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('QR', size/2, size/2 + 4);
    return;
  }

  try {
    var version = getVersion(data);
    var qr = new QRCode(version, 1); // ECC Level M
    qr.addData(data);
    qr.make();

    var mc = qr.getModuleCount();
    var cellSize = Math.floor(size / (mc + 8));
    if (cellSize < 1) cellSize = 1;
    var margin = Math.floor((size - mc * cellSize) / 2);
    var totalSize = mc * cellSize + margin * 2;

    // Créer un canvas temporaire
    var tmpCanvas = document.createElement('canvas');
    tmpCanvas.width = totalSize;
    tmpCanvas.height = totalSize;
    var ctx = tmpCanvas.getContext('2d');

    // Fond blanc
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, totalSize, totalSize);

    // Modules
    ctx.fillStyle = '#0f4c35'; // Vert Lopango
    for (var row = 0; row < mc; row++) {
      for (var col = 0; col < mc; col++) {
        if (qr.isDark(row, col)) {
          ctx.fillRect(margin + col * cellSize, margin + row * cellSize, cellSize, cellSize);
        }
      }
    }

    // Convertir en image
    var img = document.createElement('img');
    img.id = canvasId + '_qr';
    img.width = size;
    img.height = size;
    img.src = tmpCanvas.toDataURL('image/png');
    img.style.display = 'block';
    img.style.borderRadius = '4px';

    canvas.style.display = 'none';
    canvas.parentNode.insertBefore(img, canvas.nextSibling);

  } catch(e) {
    // Fallback texte
    canvas.style.display = 'block';
    canvas.width = size; canvas.height = size;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0,0,size,size);
    ctx.strokeStyle = '#0f4c35'; ctx.lineWidth = 2; ctx.strokeRect(2,2,size-4,size-4);
    ctx.fillStyle = '#0f4c35';
    ctx.font = Math.floor(size/12) + 'px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(data.substring(0,16), size/2, size/2);
  }
}

function toDataURL(data, size) {
  return ''; // Généré dynamiquement
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('canvas[data-qr]').forEach(function(canvas) {
    var data = canvas.dataset.qr;
    var size = parseInt(canvas.dataset.qrSize || '96');
    if (data) draw(canvas.id, data, size);
  });
});

return { draw: draw, toDataURL: toDataURL };

})();
