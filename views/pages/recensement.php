<?php
/* ── RECENSEMENT ────────────────────────────────────────────────────────── */
auth_require(ROLE_AGENT);
$pageTitle = 'Recenser un Bien';
$communes  = db_get_communes();
$errors    = [];
$success   = null;
$formData  = [];

// Commune de l'agent — défini ICI pour être disponible dans le POST handler
$agentCommune    = auth_commune() ?? 'GOM';
$agentCommuneNom = db_get_commune($agentCommune)['nom'] ?? $agentCommune;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $formData = [
            'commune'   => $agentCommune, // ← TOUJOURS la commune de l'agent, POST ignoré
            'quartier'  => trim($_POST['quartier']  ?? ''),
            'avenue'    => trim($_POST['avenue']    ?? ''),
            'parcelle'  => strtoupper(trim($_POST['parcelle'] ?? '')),
            'unite'     => strtoupper(trim($_POST['unite']    ?? 'U01')),
            'type'      => trim($_POST['type']      ?? 'Habitation'),
            'proprio'   => trim($_POST['proprio']   ?? ''),
            'proprio_tel'=> trim($_POST['proprio_tel'] ?? ''),
            'loyer'     => (int)($_POST['loyer']    ?? 0),
            'statut'    => trim($_POST['statut']    ?? 'occupé'),
            'locataire' => trim($_POST['locataire'] ?? ''),
            'locataire_tel'=> trim($_POST['locataire_tel'] ?? ''),
            'observations'=> trim($_POST['observations'] ?? ''),
        ];

        // Validation
        if (empty($formData['avenue']))    $errors[] = 'L\'avenue est obligatoire.';
        if (empty($formData['parcelle']))  $errors[] = 'Le numéro de parcelle est obligatoire.';
        if (empty($formData['proprio']))   $errors[] = 'Le nom du propriétaire est obligatoire.';
        if ($formData['loyer'] <= 0)       $errors[] = 'Le loyer doit être supérieur à 0.';
        if (!in_array($formData['type'], TYPES_BIEN)) $errors[] = 'Type de bien invalide.';
        if (!in_array($formData['statut'], STATUTS_BIEN)) $errors[] = 'Statut invalide.';

        if (empty($errors)) {
            // Générer le code Lopango
            $code = lp_gen_code(
                $formData['commune'],
                $formData['avenue'],
                $formData['parcelle'],
                $formData['unite']
            );

            // Vérifier unicité
            if (db_get_bien($code)) {
                $errors[] = "Un bien avec l'identifiant {$code} existe déjà. Vérifiez la parcelle ou l'unité.";
            } else {
                $bien = [
                    'id'           => $code,
                    'adresse'      => ucfirst($formData['avenue']) . ' ' . $formData['parcelle'],
                    'commune'      => $formData['commune'],
                    'quartier'     => $formData['quartier'],
                    'avenue'       => strtoupper(substr(preg_replace('/[^A-Z0-9]/i','',$formData['avenue']),0,4)),
                    'parcelle'     => $formData['parcelle'],
                    'unite'        => $formData['unite'],
                    'type'         => $formData['type'],
                    'proprio'      => $formData['proprio'],
                    'proprio_tel'  => $formData['proprio_tel'],
                    'loyer'        => $formData['loyer'],
                    'statut'       => $formData['statut'],
                    'locataire'    => $formData['locataire'],
                    'locataire_tel'=> $formData['locataire_tel'],
                    'date_creation'=> date('Y-m-d'),
                    'agent_recenseur' => auth_code(),
                    'observations' => $formData['observations'],
                    'score_conformite' => 50,
                ];

                try {
                    if (db_create_bien($bien)) {
                        $success = $code;
                        flash_set('success', "Bien {$code} enregistré avec succès.");
                        $formData = []; // Reset form
                    } else {
                        $errors[] = 'Erreur lors de l\'enregistrement. Veuillez réessayer.';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Erreur BD : ' . $e->getMessage();
                }
            }
        }
    }
}

// Code prévisuel (JS ou POST)
$communeDefaut = $agentCommune; // Toujours la commune de l'agent — non modifiable

$previewCode = isset($formData['commune']) ? lp_gen_code(
    $agentCommune,           // ← commune forcée côté serveur
    $formData['avenue']  ?? 'XXXX',
    $formData['parcelle']?? '000',
    $formData['unite']   ?? 'U01'
) : 'KIN-' . $agentCommune . '-XXXX-000-U01';
?>

<div class="page-hdr">
  <div class="page-title">Recenser un Bien</div>
  <div class="page-sub">
    <?= lp_h(db_get_commune(auth_commune())['nom'] ?? auth_commune()) ?> ·
    Nouveau bien locatif
  </div>
  <div class="page-meta">
    <div class="page-actions">
      <a href="<?= url('mes_biens') ?>" class="btn btn-secondary btn-sm">← Mes collectes</a>
    </div>
  </div>
</div>

<div class="content">

  <?php if ($success): ?>
  <div class="alert alert-ok" style="margin-bottom:16px">
    <span class="alert-icon">✓</span>
    <div class="alert-body">
      <div class="alert-title">Bien enregistré avec succès</div>
      <div>Identifiant : <strong><?= lp_h($success) ?></strong> — QR code généré et prêt à imprimer.</div>
      <div style="margin-top:8px;display:flex;gap:8px">
        <a href="<?= url('bien_detail', ['id'=>$success]) ?>" class="btn btn-primary btn-sm">Voir la fiche</a>
        <a href="<?= url('collecte', ['bien'=>$success]) ?>" class="btn btn-gold btn-sm">🎫 Émettre une quittance</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" style="margin-bottom:16px">
    <span class="alert-icon">✕</span>
    <div class="alert-body">
      <div class="alert-title">Erreur(s) de validation</div>
      <ul style="margin:6px 0 0 16px;font-size:11px">
        <?php foreach ($errors as $e): ?>
        <li><?= lp_h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-31">
    <!-- FORMULAIRE -->
    <div>
      <form method="POST" id="form-recensement">
        <?= csrf_field() ?>

        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Localisation du Bien</div>
            <div class="panel-sub">Ces informations génèrent le code Lopango unique</div>
            <div class="panel-line"></div>
          </div>
          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input class="form-input" value="KIN — Kinshasa" readonly
                     style="opacity:.6;cursor:not-allowed;background:var(--card)">
            </div>
            <div class="form-group">
              <label class="form-label">Commune</label>
              <!-- Verrouillé sur la commune de l'agent — non modifiable -->
              <input class="form-input" value="<?= lp_h($agentCommuneNom) ?>" readonly
                     style="background:var(--green-faint);color:var(--green);font-weight:600;cursor:not-allowed;border-color:rgba(15,76,53,.2)">
              <input type="hidden" name="commune" id="sel-commune" value="<?= lp_h($agentCommune) ?>">
              <div class="form-hint" style="color:var(--green)">
                ✓ Verrouillé sur votre commune d'affectation
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Unité *</label>
              <select class="form-select" name="unite" id="sel-unite" onchange="updatePreview()">
                <?php for ($i=1;$i<=10;$i++): $u = 'U'.str_pad($i,2,'0',STR_PAD_LEFT); ?>
                <option value="<?= $u ?>" <?= ($formData['unite']??'U01') === $u ? 'selected':'' ?>><?= $u ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="form-row form-row-3">
            <div class="form-group">
              <label class="form-label">Quartier</label>
              <input class="form-input" name="quartier" id="inp-quartier"
                     placeholder="ex: Golf, Gombe…"
                     value="<?= lp_h($formData['quartier'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Avenue / Rue *</label>
              <input class="form-input" name="avenue" id="inp-avenue" required
                     placeholder="ex: Télécom, Lumumba…"
                     value="<?= lp_h($formData['avenue'] ?? '') ?>"
                     oninput="updatePreview()">
              <div class="form-hint">Les 4 premières lettres seront utilisées</div>
            </div>
            <div class="form-group">
              <label class="form-label">N° Parcelle *</label>
              <input class="form-input" name="parcelle" id="inp-parcelle" required
                     placeholder="ex: 070C, 012B…"
                     value="<?= lp_h($formData['parcelle'] ?? '') ?>"
                     oninput="updatePreview()">
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Informations du Bien</div>
            <div class="panel-line"></div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">Type de Bien *</label>
              <select class="form-select" name="type" onchange="updateIRL()">
                <?php foreach (TYPES_BIEN as $t): ?>
                <option value="<?= $t ?>" <?= ($formData['type']??'Habitation') === $t ? 'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Statut Initial *</label>
              <select class="form-select" name="statut">
                <?php foreach (STATUTS_BIEN as $s): ?>
                <option value="<?= $s ?>" <?= ($formData['statut']??'occupé') === $s ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">Loyer Mensuel (USD) *</label>
              <input class="form-input" type="number" name="loyer" id="inp-loyer" required
                     min="0" step="10" placeholder="350"
                     value="<?= lp_h((string)($formData['loyer'] ?? '')) ?>"
                     oninput="updateIRL()">
            </div>
            <div class="form-group">
              <label class="form-label">IRL Théorique Estimé</label>
              <div class="form-input" id="irl-preview" style="background:var(--green-faint);color:var(--green);font-family:var(--mono);cursor:default">—</div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Propriétaire (Bailleur) *</div>
            <div class="panel-line"></div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">Nom complet *</label>
              <input class="form-input" name="proprio" required
                     placeholder="ex: MWAMBA Jean-Pierre"
                     value="<?= lp_h($formData['proprio'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input class="form-input" name="proprio_tel" type="tel"
                     placeholder="+243 8X XXX XXXX"
                     value="<?= lp_h($formData['proprio_tel'] ?? '') ?>">
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Locataire (si occupé)</div>
            <div class="panel-line"></div>
          </div>
          <div class="form-row form-row-2">
            <div class="form-group">
              <label class="form-label">Nom complet</label>
              <input class="form-input" name="locataire"
                     placeholder="Nom du locataire actuel"
                     value="<?= lp_h($formData['locataire'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input class="form-input" name="locataire_tel" type="tel"
                     placeholder="+243 9X XXX XXXX"
                     value="<?= lp_h($formData['locataire_tel'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row form-row-1">
            <div class="form-group">
              <label class="form-label">Observations</label>
              <textarea class="form-textarea" name="observations"
                        placeholder="Remarques, litiges, état du bien…"><?= lp_h($formData['observations'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:10px;padding:0 0 20px">
          <button type="submit" class="btn btn-primary" style="flex:1">💾 Enregistrer le Bien</button>
          <button type="button" class="btn btn-secondary" onclick="window.print()">🖨 Imprimer la Fiche</button>
          <button type="reset" class="btn btn-secondary" onclick="updatePreview()">Réinitialiser</button>
        </div>
      </form>
    </div>

    <!-- PANNEAU DROIT : aperçu du code -->
    <div>
      <div class="panel" style="position:sticky;top:20px">
        <div class="panel-hdr">
          <div class="panel-title">Identifiant Généré</div>
          <div class="panel-sub">Mis à jour en temps réel</div>
          <div class="panel-line"></div>
        </div>
        <div id="code-display"
             style="font-family:var(--mono);font-size:12px;font-weight:600;color:var(--green);background:var(--green-faint);border:1px solid rgba(15,76,53,.2);padding:12px;border-radius:var(--radius);text-align:center;letter-spacing:1px;margin-bottom:16px;word-break:break-all">
          <?= lp_h($previewCode) ?>
        </div>
        <div style="display:flex;justify-content:center;margin-bottom:12px">
          <canvas id="qr-preview" style="border:4px solid #fff;border-radius:4px;box-shadow:var(--shadow)"></canvas>
        </div>
        <div style="font-size:8.5px;color:var(--hint);text-align:center;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px">QR Code Lopango</div>
        <div class="divider"></div>
        <div id="code-details" style="display:flex;flex-direction:column;gap:4px;font-size:11px">
          <!-- Rempli par JS -->
        </div>
        <div class="alert alert-info" style="margin-top:14px">
          <span class="alert-icon">ℹ</span>
          <div class="alert-body">
            <div class="alert-title">Format Lopango</div>
            <div style="font-family:var(--mono);font-size:10px;line-height:1.8;color:var(--muted)">
              KIN-[COM]-[AVN]-[PAR]-[UNT]<br>
              → Ville/Commune/Avenue/Parcelle/Unité
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = "
const TAUX_FC = 2250;
const TAUX_IRL = " . json_encode(TAUX_IRL) . ";

function updatePreview() {
  const com = document.getElementById('sel-commune')?.value || 'GOM';
  const av  = (document.getElementById('inp-avenue')?.value || 'XXXX').toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,4).padEnd(4,'X');
  const par = (document.getElementById('inp-parcelle')?.value || '000').toUpperCase().slice(0,5).padStart(3,'0');
  const uni = document.getElementById('sel-unite')?.value || 'U01';
  const code = 'KIN-' + com + '-' + av + '-' + par + '-' + uni;

  const display = document.getElementById('code-display');
  if (display) display.textContent = code;

  // QR
  if (typeof LopangoQR !== 'undefined') LopangoQR.draw('qr-preview', code, 220);

  // Détails
  const details = document.getElementById('code-details');
  if (details) {
    const q = document.getElementById('inp-quartier')?.value || '—';
    details.innerHTML = [
      ['Ville', 'KIN — Kinshasa'], ['Commune', com],
      ['Quartier', q || '—'], ['Avenue (code)', av], ['Parcelle', par], ['Unité', uni]
    ].map(([k,v]) => '<div class=\"stat-row\"><span class=\"stat-label\">' + k + '</span><span class=\"stat-val\">' + v + '</span></div>').join('');
  }
}

function updateIRL() {
  const loyer = parseFloat(document.getElementById('inp-loyer')?.value) || 0;
  const type  = document.querySelector('[name=type]')?.value || 'Habitation';
  const taux  = TAUX_IRL[type] || 15;
  const irl   = Math.round(loyer * TAUX_FC * taux / 100);
  const el    = document.getElementById('irl-preview');
  if (el) el.textContent = irl > 0 ? new Intl.NumberFormat('fr-FR').format(irl) + ' FC' : '—';
}

document.addEventListener('DOMContentLoaded', () => { updatePreview(); updateIRL(); });
";
