<?php
/* ── BIEN DETAIL ────────────────────────────────────────────────────────── */
auth_require();
$id   = $_GET['id'] ?? '';
$bien = $id ? db_get_bien($id) : null;
if (!$bien) {
    flash_set('error', 'Bien introuvable : ' . $id);
    redirect_page('biens');
}
$pageTitle = 'Fiche Bien — ' . $bien['id'];
$paiements = db_get_paiements_bien($bien['id']);
$commune   = db_get_commune($bien['commune']);
$score     = lp_score_conformite($bien['id']);
$irl_theo  = lp_calc_irl($bien['loyer'] ?? 0, $bien['type']);

// Mise à jour statut (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_verify()) { flash_set('error', 'Token invalide.'); redirect_page('bien_detail', ['id'=>$id]); }
    if ($_POST['action'] === 'update_statut') {
        $newStatut = $_POST['statut'] ?? '';
        if (in_array($newStatut, STATUTS_BIEN)) {
            db_update_bien($id, ['statut' => $newStatut, 'locataire' => $_POST['locataire'] ?? $bien['locataire'], 'observations' => $_POST['observations'] ?? '']);
            flash_set('success', 'Statut mis à jour → ' . $newStatut);
        }
        redirect_page('bien_detail', ['id'=>$id]);
    }
}
?>

<div class="page-hdr">
  <div class="page-title">Fiche Bien Locatif</div>
  <div class="page-sub"><?= lp_h($bien['id']) ?></div>
  <div class="page-meta">
    <div class="page-actions">
      <a href="<?= url('biens', ['commune'=>$bien['commune']]) ?>" class="btn btn-secondary btn-sm">← Retour</a>
      <?php if (auth_is_agent() || auth_is_habitat()): ?>
      <a href="<?= url('collecte', ['bien'=>$bien['id']]) ?>" class="btn btn-gold btn-sm">🎫 Collecte IRL</a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Imprimer</button>
    </div>
  </div>
</div>

<div class="content">
  <div class="grid-21">

    <!-- COLONNE PRINCIPALE -->
    <div>
      <!-- Identité du bien -->
      <div class="panel">
        <div class="panel-hdr">
          <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
              <div class="panel-title"><?= lp_h($bien['adresse']) ?></div>
              <div class="panel-sub"><?= lp_h($bien['quartier']) ?> · <?= lp_h($commune['nom'] ?? $bien['commune']) ?></div>
            </div>
            <?= lp_badge_statut($bien['statut']) ?>
          </div>
          <div class="panel-line"></div>
        </div>
        <div class="grid-2" style="margin-bottom:0">
          <?php
          $rows = [
            ['Identifiant Lopango', $bien['id'], true],
            ['Type de Bien',        $bien['type'], false],
            ['Commune',             $commune['nom'] ?? $bien['commune'], false],
            ['Quartier',            $bien['quartier'], false],
            ['Avenue (code)',       $bien['avenue'], false],
            ['Parcelle',            $bien['parcelle'], false],
            ['Unité',               $bien['unite'], false],
            ['Date Recensement',    lp_date($bien['date_creation']), false],
            ['Agent Recenseur',     $bien['agent_recenseur'], false],
            ['Statut',              $bien['statut'], false],
          ];
          foreach ($rows as $r):
          ?>
          <div class="stat-row">
            <span class="stat-label"><?= lp_h($r[0]) ?></span>
            <span class="stat-val <?= $r[2] ? 'mono' : '' ?>"><?= lp_h($r[1]) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Propriétaire & Locataire -->
      <div class="grid-2">
        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Propriétaire (Bailleur)</div>
            <div class="panel-line"></div>
          </div>
          <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--green-faint);border:1px solid rgba(15,76,53,.2);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:11px;font-weight:600;color:var(--green);flex-shrink:0">
              <?= mb_strtoupper(mb_substr($bien['proprio'],0,2)) ?>
            </div>
            <div>
              <div style="font-weight:500;font-size:13px"><?= lp_h($bien['proprio']) ?></div>
              <div style="font-size:10px;color:var(--hint)"><?= lp_h($bien['proprio_tel'] ?? '') ?></div>
            </div>
          </div>
          <div class="stat-row">
            <span class="stat-label">Loyer déclaré</span>
            <span class="stat-val mono"><?= lp_usd($bien['loyer'] ?? 0) ?>/mois</span>
          </div>
          <div class="stat-row">
            <span class="stat-label">IRL Théorique</span>
            <span class="stat-val mono" style="color:var(--green)"><?= lp_fc($irl_theo) ?></span>
          </div>
          <div class="stat-row">
            <span class="stat-label">Score conformité</span>
            <span class="stat-val mono" style="color:<?= $score>=80?'var(--green)':($score>=60?'var(--gold)':'var(--red)') ?>"><?= $score ?>%</span>
          </div>
          <?= lp_progress($score, 'auto') ?>
        </div>

        <div class="panel">
          <div class="panel-hdr">
            <div class="panel-title">Locataire</div>
            <div class="panel-line"></div>
          </div>
          <?php if ($bien['locataire']): ?>
          <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--blue-faint);border:1px solid rgba(26,95,171,.2);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:11px;font-weight:600;color:var(--blue);flex-shrink:0">
              <?= mb_strtoupper(mb_substr($bien['locataire'],0,2)) ?>
            </div>
            <div>
              <div style="font-weight:500;font-size:13px"><?= lp_h($bien['locataire']) ?></div>
              <div style="font-size:10px;color:var(--hint)"><?= lp_h($bien['locataire_tel'] ?? '') ?></div>
            </div>
          </div>
          <?php else: ?>
          <div style="text-align:center;padding:20px;color:var(--hint);font-size:12px">
            <?= lp_badge_statut($bien['statut']) ?>
            <div style="margin-top:8px">Aucun locataire enregistré</div>
          </div>
          <?php endif; ?>
          <?php if (!empty($bien['observations'])): ?>
          <div class="alert alert-warn" style="margin-top:10px;font-size:11px">
            <span class="alert-icon">⚠</span>
            <div><?= lp_h($bien['observations']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Historique paiements -->
      <div class="panel">
        <div class="panel-hdr">
          <div class="panel-title">Historique des Paiements IRL</div>
          <div class="panel-sub"><?= count($paiements) ?> paiement(s) enregistré(s)</div>
          <div class="panel-line"></div>
        </div>
        <?php if (empty($paiements)): ?>
        <div style="text-align:center;padding:30px;color:var(--hint);font-size:12px">
          Aucun paiement IRL enregistré pour ce bien.
        </div>
        <?php else: ?>
        <div class="tbl-container">
          <table class="tbl">
            <thead><tr>
              <th>N° Quittance</th><th>Période</th><th>Agent</th>
              <th class="r">Montant</th><th>Mode</th><th>Statut</th>
            </tr></thead>
            <tbody>
              <?php foreach (array_reverse($paiements) as $p): ?>
              <tr>
                <td><?= lp_code_pill(lp_h($p['num_quittance'])) ?></td>
                <td style="font-family:var(--mono);font-size:11px"><?= lp_h($p['periode']) ?></td>
                <td style="font-size:11px"><?= lp_h($p['agent_code']) ?></td>
                <td class="r"><span style="font-family:var(--mono);color:var(--green)"><?= lp_fc($p['montant']) ?></span></td>
                <td style="font-size:10px;color:var(--hint)"><?= lp_h($p['mode_paiement']) ?></td>
                <td><?= lp_badge_statut($p['statut']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- COLONNE DROITE -->
    <div>
      <!-- QR Code + Identifiant -->
      <div class="panel" style="text-align:center">
        <div class="panel-hdr">
          <div class="panel-title">Code Lopango</div>
          <div class="panel-line"></div>
        </div>
        <div style="font-family:var(--mono);font-size:11px;font-weight:600;color:var(--green);background:var(--green-faint);border:1px solid rgba(15,76,53,.2);padding:10px;border-radius:var(--radius);margin-bottom:14px;word-break:break-all">
          <?= lp_h($bien['id']) ?>
        </div>
        <canvas id="qr-canvas" style="border:4px solid #fff;border-radius:4px;box-shadow:var(--shadow);display:block;margin:0 auto 8px"></canvas>
        <div style="font-size:8.5px;color:var(--hint);letter-spacing:2px;text-transform:uppercase">QR Code Lopango</div>
      </div>

      <!-- Mise à jour statut (admin/habitat seulement) -->
      <?php if (auth_is_habitat() || auth_is_hvk()): ?>
      <div class="panel">
        <div class="panel-hdr">
          <div class="panel-title">Mettre à Jour</div>
          <div class="panel-line"></div>
        </div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_statut">
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
              <?php foreach (STATUTS_BIEN as $s): ?>
              <option value="<?= $s ?>" <?= $bien['statut'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label">Locataire</label>
            <input type="text" name="locataire" class="form-input" value="<?= lp_h($bien['locataire'] ?? '') ?>">
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">Observations</label>
            <textarea name="observations" class="form-textarea"><?= lp_h($bien['observations'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">Enregistrer les modifications</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Stats rapides -->
      <div class="panel">
        <div class="panel-hdr">
          <div class="panel-title">Statistiques</div>
          <div class="panel-line"></div>
        </div>
        <?php
        $totalIRL = array_sum(array_column($paiements, 'montant'));
        ?>
        <div class="stat-row"><span class="stat-label">Total IRL perçu</span><span class="stat-val mono" style="color:var(--green)"><?= lp_fc($totalIRL) ?></span></div>
        <div class="stat-row"><span class="stat-label">IRL théorique/mois</span><span class="stat-val mono"><?= lp_fc($irl_theo) ?></span></div>
        <div class="stat-row"><span class="stat-label">Nombre de quittances</span><span class="stat-val mono"><?= count($paiements) ?></span></div>
        <div class="stat-row"><span class="stat-label">Dernière période</span><span class="stat-val mono"><?= lp_h(end($paiements)['periode'] ?? '—') ?></span></div>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = "
// QR Code
if (typeof LopangoQR !== 'undefined') {
  LopangoQR.draw('qr-canvas', " . json_encode($bien['id']) . ", 120);
}
";
