<?php
/* ── COLLECTE IRL ───────────────────────────────────────────────────────── */
auth_require(ROLE_AGENT);
$pageTitle = 'Collecte IRL';
$communes  = db_get_communes();
$errors    = [];
$quittance = null;
$preBienId = $_GET['bien'] ?? '';
$preBien   = $preBienId ? db_get_bien($preBienId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'collecte') {
    if (!csrf_verify()) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $bienId  = trim($_POST['bien_id'] ?? '');
        $montant = (int)($_POST['montant'] ?? 0);
        $periode = trim($_POST['periode'] ?? date('Y-m'));
        $mode    = trim($_POST['mode_paiement'] ?? 'Espèces');
        $ref     = trim($_POST['reference'] ?? '');
        $bien    = $bienId ? db_get_bien($bienId) : null;

        if (!$bien) {
            $errors[] = 'Bien introuvable. Vérifiez l\'identifiant.';
        } elseif ($bien['commune'] !== auth_commune()) {
            // Sécurité : l'agent ne peut pas collecter hors de sa commune
            $errors[] = 'Ce bien appartient à la commune ' . $bien['commune'] . '. Vous êtes affecté à ' . auth_commune() . '. Opération non autorisée.';
            $bien = null;
        }
        if ($montant <= 0) $errors[] = 'Le montant doit être supérieur à 0.';
        if (empty($periode)) $errors[] = 'La période est obligatoire.';

        if (empty($errors)) {
            $num = lp_gen_quittance_num($bien['commune']);
            $paiement = [
                'id'            => 'PAY-' . str_pad(db_next_id('paiements'), 4, '0', STR_PAD_LEFT),
                'num_quittance' => $num,
                'bien_id'       => $bien['id'],
                'agent_code'    => auth_code(),
                'commune'       => $bien['commune'],
                'montant'       => $montant,
                'periode'       => $periode,
                'mode_paiement' => $mode,
                'reference'     => $ref,
                'statut'        => 'pending',
                'date'          => date('Y-m-d'),
                'heure'         => date('H:i'),
                'synced_at'     => null,
            ];

            if (db_create_paiement($paiement)) {
                $quittance = array_merge($paiement, [
                    'bien'       => $bien,
                    'agent_nom'  => auth_user()['nom'],
                    'date_fmt'   => date('d/m/Y'),
                    'heure_fmt'  => date('H:i'),
                ]);
                flash_set('success', "Quittance {$num} générée — {$montant} FC");
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement de la quittance.';
            }
        }
    }
}

// IRL théorique si bien pré-sélectionné
$irlTheo = $preBien ? lp_calc_irl($preBien['loyer'] ?? 0, $preBien['type']) : 0;
?>

<div class="page-hdr">
  <div class="page-title">Collecte IRL</div>
  <div class="page-sub">Impôt sur les Revenus Locatifs · Enregistrement paiement</div>
  <div class="page-meta"></div>
</div>

<div class="content">
  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="margin-bottom:16px">
    <span class="alert-icon">✕</span>
    <div class="alert-body">
      <div class="alert-title">Erreur</div>
      <?php foreach ($errors as $e): ?>
      <div><?= lp_h($e) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-2">
    <!-- FORMULAIRE SAISIE -->
    <div>
      <div class="panel">
        <div class="panel-hdr">
          <div class="panel-title">Saisir un Paiement IRL</div>
          <div class="panel-sub">Scannez le QR code ou saisissez l'identifiant</div>
          <div class="panel-line"></div>
        </div>
        <form method="POST" id="form-collecte">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="collecte">

          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label">Identifiant du Bien *</label>
            <div style="display:flex;gap:8px">
              <input class="form-input form-input--mono" type="text" name="bien_id" id="inp-bien-id"
                     placeholder="KIN-GOM-TLCM-070C-U01"
                     value="<?= lp_h($preBienId) ?>"
                     oninput="lookupBien(this.value)" style="flex:1">
              <button type="button" class="btn btn-secondary btn-icon" title="Scanner QR code"
                      onclick="openScanner()" id="btn-scan"
                      style="min-width:44px;font-size:18px">📲</button>
            </div>
          </div>

          <!-- ── MODAL SCANNER QR ── -->
          <div id="qr-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);flex-direction:column;align-items:center;justify-content:center">

            <!-- Header modal -->
            <div style="width:100%;max-width:480px;display:flex;justify-content:space-between;align-items:center;padding:14px 18px;color:#fff">
              <div>
                <div style="font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600">Scanner le QR Code</div>
                <div style="font-size:11px;color:rgba(255,255,255,.5);margin-top:2px">Pointez vers le code QR Lopango</div>
              </div>
              <button onclick="closeScanner()"
                style="background:rgba(255,255,255,.12);border:none;color:#fff;width:36px;height:36px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center">×</button>
            </div>

            <!-- Viewfinder -->
            <div style="position:relative;width:100%;max-width:480px;padding:0 18px">
              <div style="position:relative;border-radius:12px;overflow:hidden;background:#000;aspect-ratio:1">
                <video id="qr-video" autoplay playsinline muted
                       style="width:100%;height:100%;object-fit:cover;display:block"></video>
                <canvas id="qr-canvas" style="display:none"></canvas>

                <!-- Cadre de visée -->
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">
                  <div style="width:65%;aspect-ratio:1;position:relative">
                    <!-- 4 coins du cadre -->
                    <div style="position:absolute;top:0;left:0;width:28px;height:28px;border-top:3px solid #c9a227;border-left:3px solid #c9a227;border-radius:3px 0 0 0"></div>
                    <div style="position:absolute;top:0;right:0;width:28px;height:28px;border-top:3px solid #c9a227;border-right:3px solid #c9a227;border-radius:0 3px 0 0"></div>
                    <div style="position:absolute;bottom:0;left:0;width:28px;height:28px;border-bottom:3px solid #c9a227;border-left:3px solid #c9a227;border-radius:0 0 0 3px"></div>
                    <div style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-bottom:3px solid #c9a227;border-right:3px solid #c9a227;border-radius:0 0 3px 0"></div>
                    <!-- Ligne de scan animée -->
                    <div id="scan-line" style="position:absolute;left:4px;right:4px;height:2px;background:linear-gradient(90deg,transparent,#c9a227,transparent);top:50%;animation:scanline 1.8s ease-in-out infinite"></div>
                  </div>
                </div>

                <!-- Overlay résultat OK -->
                <div id="scan-ok" style="display:none;position:absolute;inset:0;background:rgba(15,76,53,.85);align-items:center;justify-content:center;flex-direction:column;gap:10px">
                  <div style="font-size:52px">✓</div>
                  <div id="scan-ok-code" style="font-family:'JetBrains Mono',monospace;font-size:13px;color:#fff;letter-spacing:1px;text-align:center;padding:0 20px"></div>
                </div>
              </div>

              <!-- Status -->
              <div id="scan-status" style="text-align:center;padding:14px 0 4px;font-size:12px;color:rgba(255,255,255,.6)">
                Initialisation de la caméra…
              </div>

              <!-- Saisie manuelle -->
              <div style="margin-top:8px;padding:14px 16px;background:rgba(255,255,255,.07);border-radius:8px">
                <div style="font-size:10px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px">Ou saisir manuellement</div>
                <div style="display:flex;gap:8px">
                  <input type="text" id="manual-input" placeholder="KIN-GOM-TLCM-070C-U01"
                         style="flex:1;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;padding:8px 12px;border-radius:6px;font-family:'JetBrains Mono',monospace;font-size:11px;outline:none"
                         oninput="this.value=this.value.toUpperCase()">
                  <button onclick="confirmManual()"
                          style="background:#0f4c35;border:none;color:#fff;padding:8px 16px;border-radius:6px;font-size:12px;cursor:pointer;white-space:nowrap">
                    Confirmer
                  </button>
                </div>
              </div>
            </div>

            <!-- Bas de page -->
            <div style="padding:16px;text-align:center;font-size:10px;color:rgba(255,255,255,.3);max-width:480px">
              Accès caméra requis · Pointez vers le QR code Lopango sur la fiche ou l'étiquette du bien
            </div>
          </div>

          <style>
          @keyframes scanline { 0%,100%{top:10%} 50%{top:88%} }
          #qr-modal { display: none; }
          #qr-modal.open { display: flex; }
          #scan-ok.visible { display: flex; }
          #qr-video { transform: scaleX(-1); } /* miroir par défaut, annulé sur mobile si besoin */
          </style>

          <!-- Zone info bien (remplie dynamiquement ou depuis PHP) -->
          <div id="bien-info-zone">
            <?php if ($preBien): ?>
            <div class="alert alert-ok" style="margin-bottom:10px">
              <span class="alert-icon">✓</span>
              <div class="alert-body">
                <div class="alert-title"><?= lp_h($preBien['adresse']) ?></div>
                <div style="display:flex;gap:8px;margin-top:3px;font-size:11px;flex-wrap:wrap">
                  <span><?= lp_h($preBien['proprio']) ?></span>
                  <span>·</span>
                  <span><?= lp_h($preBien['type']) ?></span>
                  <span>·</span>
                  <?= lp_badge_statut($preBien['statut']) ?>
                </div>
                <?php if ($irlTheo): ?>
                <div style="font-size:10px;color:var(--muted);margin-top:4px">
                  Loyer déclaré : <?= lp_usd($preBien['loyer']) ?>/mois — IRL estimé : <?= lp_fc($irlTheo) ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="form-row form-row-2" style="margin-top:10px">
            <div class="form-group">
              <label class="form-label">Montant IRL (FC) *</label>
              <input class="form-input" type="number" name="montant" id="inp-montant"
                     min="1" step="100" placeholder="52 500" required
                     value="<?= $irlTheo ?: '' ?>"
                     style="font-family:var(--mono);font-size:15px;font-weight:600">
            </div>
            <div class="form-group">
              <label class="form-label">Période *</label>
              <input class="form-input" type="month" name="periode" id="inp-periode"
                     value="<?= date('Y-m') ?>" required>
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">Mode de Paiement</label>
              <select class="form-select" name="mode_paiement">
                <?php foreach (['Espèces','Mobile Money (M-Pesa)','Airtel Money','Orange Money','Virement'] as $m): ?>
                <option><?= lp_h($m) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Référence transaction</label>
              <input class="form-input" type="text" name="reference"
                     placeholder="N° transaction (si applicable)">
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:10px">
            <button type="submit" class="btn btn-primary" style="flex:1">🎫 Générer la Quittance</button>
            <a href="<?= url('collecte') ?>" class="btn btn-secondary">Effacer</a>
          </div>
        </form>
      </div>

      <!-- Dernières quittances du jour -->
      <?php
      $buffer = db_get_paiements(auth_commune());
      $today  = array_filter($buffer, fn($p) => $p['date'] === date('Y-m-d') && $p['agent_code'] === auth_code());
      $today  = array_reverse(array_values($today));
      ?>
      <div class="panel">
        <div class="panel-hdr">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <div class="panel-title">Aujourd'hui</div>
            <?php
            $pending = count(array_filter($buffer, fn($p) => $p['statut'] === 'pending'));
            if ($pending > 0):
            ?>
            <span class="badge badge-warn"><?= $pending ?> en attente</span>
            <?php endif; ?>
          </div>
          <div class="panel-line"></div>
        </div>
        <?php if (empty($today)): ?>
        <div style="text-align:center;padding:20px;color:var(--hint);font-size:12px">
          Aucune quittance émise aujourd'hui
        </div>
        <?php else: ?>
        <div class="tbl-container">
          <table class="tbl">
            <thead><tr><th>N° Quittance</th><th>Heure</th><th class="r">Montant</th><th>Statut</th></tr></thead>
            <tbody>
              <?php foreach (array_slice($today, 0, 5) as $q): ?>
              <tr>
                <td><?= lp_code_pill(lp_h($q['num_quittance'])) ?></td>
                <td style="font-size:11px;color:var(--hint)"><?= lp_h($q['heure']) ?></td>
                <td class="r"><span style="font-family:var(--mono)"><?= lp_fc($q['montant']) ?></span></td>
                <td><?= lp_badge_statut($q['statut']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <div style="margin-top:10px">
          <a href="<?= url('buffer') ?>" class="btn btn-secondary btn-sm">Voir la synchronisation →</a>
        </div>
      </div>
    </div>

    <!-- QUITTANCE GÉNÉRÉE -->
    <div>
      <?php if ($quittance): ?>
      <!-- Quittance PHP générée -->
      <div class="quittance" id="quittance-print">
        <div class="quittance-hdr">
          <div style="font-size:8px;color:var(--hint);letter-spacing:2px;text-transform:uppercase;margin-bottom:2px">République Démocratique du Congo</div>
          <div class="quittance-logo">LOPANGO</div>
          <div class="quittance-gov">Hôtel de Ville de Kinshasa · Direction des Impôts Locatifs</div>
          <div class="quittance-num"><?= lp_h($quittance['num_quittance']) ?></div>
          <canvas id="qr-quittance" style="display:block;margin:10px auto 0;border:3px solid #fff;border-radius:3px;box-shadow:var(--shadow)"></canvas>
        </div>

        <div class="quittance-amount">
          <div class="amount-label">Montant IRL Perçu</div>
          <div>
            <span class="amount-val"><?= number_format($quittance['montant'],0,',',' ') ?></span>
            <span class="amount-unit">FC</span>
          </div>
        </div>

        <?php
        $rows = [
          ['Bien',           $quittance['bien_id']],
          ['Adresse',        $quittance['bien']['adresse']],
          ['Propriétaire',   $quittance['bien']['proprio']],
          ['Période',        $quittance['periode']],
          ['Mode paiement',  $quittance['mode_paiement']],
          ['Agent',          $quittance['agent_nom'] . ' (' . $quittance['agent_code'] . ')'],
          ['Date & Heure',   $quittance['date_fmt'] . ' à ' . $quittance['heure_fmt']],
        ];
        foreach ($rows as $r):
        ?>
        <div class="quittance-row">
          <span class="q-label"><?= lp_h($r[0]) ?></span>
          <span class="q-val"><?= lp_h($r[1]) ?></span>
        </div>
        <?php endforeach; ?>

        <div class="quittance-footer">
          Document officiel LOPANGO · V<?= APP_VERSION ?> · <?= $quittance['date_fmt'] ?>
        </div>

        <div style="display:flex;gap:8px;margin-top:16px">
          <button onclick="window.print()" class="btn btn-primary btn-sm" style="flex:1">🖨 Imprimer</button>
          <a href="<?= url('collecte') ?>" class="btn btn-secondary btn-sm">Nouveau →</a>
        </div>
      </div>

      <?php else: ?>
      <!-- Placeholder -->
      <div class="panel" style="text-align:center;padding:52px 24px;border:2px dashed var(--border);background:var(--card)">
        <div style="font-size:48px;margin-bottom:16px;opacity:.3">🎫</div>
        <div style="font-family:var(--serif);font-size:22px;color:var(--hint);margin-bottom:8px">Quittance IRL</div>
        <div style="font-size:12px;color:var(--hint)">
          Saisissez un bien et un montant,<br>puis cliquez « Générer la Quittance »
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$biensJson = json_encode(array_map(fn($b) => [
    'id'=>$b['id'],'adresse'=>$b['adresse'],'proprio'=>$b['proprio'],
    'type'=>$b['type'],'statut'=>$b['statut'],'loyer'=>$b['loyer']??0,
], db_get_biens(auth_commune())));
$TAUX_FC = 2750;
$TAUX_IRL_JSON = json_encode(TAUX_IRL);
$qrNum = $quittance['num_quittance'] ?? '';

$pageScripts = "
const BIENS_LOCAL = {$biensJson};
const TAUX_FC     = {$TAUX_FC};
const TAUX_IRL    = {$TAUX_IRL_JSON};
" . ($quittance ? "
if (typeof LopangoQR !== 'undefined') LopangoQR.draw('qr-quittance', " . json_encode($qrNum) . ", 100);
" : "") . "

// ── LOOKUP BIEN ───────────────────────────────────────────────────────────
function lookupBien(id) {
  const zone = document.getElementById('bien-info-zone');
  const mnt  = document.getElementById('inp-montant');
  if (!zone) return;
  if (!id || id.length < 10) { zone.innerHTML = ''; return; }
  const b = BIENS_LOCAL.find(x => x.id === id.trim().toUpperCase());
  if (b) {
    const irl   = Math.round(b.loyer * TAUX_FC * (TAUX_IRL[b.type] || 15) / 100);
    const badge = b.statut === 'occupé' ? 'badge-ok' : b.statut === 'libre' ? 'badge-warn' : b.statut === 'litige' ? 'badge-danger' : 'badge-info';
    zone.innerHTML = '<div class=\"alert alert-ok\" style=\"margin-bottom:10px\"><span class=\"alert-icon\">✓</span><div class=\"alert-body\"><div class=\"alert-title\">' + b.adresse + '</div><div style=\"font-size:11px;margin-top:3px;display:flex;gap:8px;flex-wrap:wrap\">' + b.proprio + ' · ' + b.type + ' · <span class=\"badge ' + badge + '\">' + b.statut + '</span></div>' + (irl ? '<div style=\"font-size:10px;color:var(--muted);margin-top:4px\">IRL estimé : ' + new Intl.NumberFormat(\"fr-FR\").format(irl) + ' FC</div>' : '') + '</div></div>';
    if (mnt && !mnt.value && irl) mnt.value = irl;
  } else {
    zone.innerHTML = '<div class=\"alert alert-danger\" style=\"margin-bottom:10px\"><span class=\"alert-icon\">⚠</span><div>Bien introuvable dans la base locale.</div></div>';
  }
}

// ── QR SCANNER ────────────────────────────────────────────────────────────
var _scanStream = null;
var _scanLoop   = null;
var _jsqrLoaded = false;

function loadJsQR(cb) {
  if (window.jsQR) { cb(); return; }
  var s = document.createElement('script');
  s.src = '/assets/js/jsqr.min.js';
  s.onload  = function(){ _jsqrLoaded = true; cb(); };
  s.onerror = function(){ setStatus('Impossible de charger le scanner. Utilisez la saisie manuelle.'); };
  document.head.appendChild(s);
}

function openScanner() {
  var modal = document.getElementById('qr-modal');
  modal.style.display = 'flex';  // forcer — override l'inline style:none
  modal.classList.add('open');
  document.getElementById('scan-ok').classList.remove('visible');
  document.getElementById('manual-input').value = '';
  setStatus('Initialisation de la caméra…');
  loadJsQR(startCamera);
}

function closeScanner() {
  stopCamera();
  var modal = document.getElementById('qr-modal');
  modal.style.display = 'none';
  modal.classList.remove('open');
}

function startCamera() {
  var video = document.getElementById('qr-video');
  // Préférer caméra arrière sur mobile
  var constraints = {
    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
  };
  navigator.mediaDevices.getUserMedia(constraints)
    .then(function(stream) {
      _scanStream = stream;
      video.srcObject = stream;
      // Sur caméra arrière, pas besoin de miroir
      var track = stream.getVideoTracks()[0];
      var settings = track.getSettings ? track.getSettings() : {};
      if (settings.facingMode === 'environment') video.style.transform = 'none';
      video.play().then(function() {
        setStatus('Pointez vers le QR code Lopango');
        requestAnimationFrame(scanFrame);
      });
    })
    .catch(function(err) {
      var msg = err.name === 'NotAllowedError'
        ? 'Accès caméra refusé. Autorisez l\\'accès dans les paramètres du navigateur, ou utilisez la saisie manuelle ci-dessous.'
        : 'Caméra indisponible. Utilisez la saisie manuelle ci-dessous.';
      setStatus(msg);
    });
}

function stopCamera() {
  if (_scanLoop) { cancelAnimationFrame(_scanLoop); _scanLoop = null; }
  if (_scanStream) { _scanStream.getTracks().forEach(function(t){ t.stop(); }); _scanStream = null; }
  var video = document.getElementById('qr-video');
  if (video) video.srcObject = null;
}

function scanFrame() {
  var video  = document.getElementById('qr-video');
  var canvas = document.getElementById('qr-canvas');
  if (!video || !canvas || !video.readyState || video.readyState < 2) {
    _scanLoop = requestAnimationFrame(scanFrame); return;
  }
  var w = video.videoWidth, h = video.videoHeight;
  if (!w || !h) { _scanLoop = requestAnimationFrame(scanFrame); return; }
  canvas.width = w; canvas.height = h;
  var ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0, w, h);
  var imgData = ctx.getImageData(0, 0, w, h);
  var code = window.jsQR && jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'dontInvert' });
  if (code && code.data) {
    onCodeDetected(code.data);
  } else {
    _scanLoop = requestAnimationFrame(scanFrame);
  }
}

function onCodeDetected(raw) {
  stopCamera();
  // Extraire l'identifiant Lopango du QR (peut être juste le code ou une URL)
  var code = raw.trim();
  var match = code.match(/KIN-[A-Z0-9]{2,6}-[A-Z0-9]{4}-[A-Z0-9]{3,5}-U\d{2}/i);
  if (match) code = match[0].toUpperCase();

  // Feedback visuel
  var okZone = document.getElementById('scan-ok');
  document.getElementById('scan-ok-code').textContent = code;
  okZone.classList.add('visible');
  setStatus('QR code détecté !');

  // Vibration sur mobile si supporté
  if (navigator.vibrate) navigator.vibrate([60, 30, 60]);

  // Fermer après 1.2s et remplir le champ
  setTimeout(function() {
    closeScanner();
    var inp = document.getElementById('inp-bien-id');
    if (inp) { inp.value = code; lookupBien(code); inp.focus(); }
  }, 1200);
}

function confirmManual() {
  var val = document.getElementById('manual-input').value.trim().toUpperCase();
  if (!val) return;
  onCodeDetected(val);
}

function setStatus(msg) {
  var el = document.getElementById('scan-status');
  if (el) el.textContent = msg;
}

// Fermer avec touche Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeScanner();
});
";

