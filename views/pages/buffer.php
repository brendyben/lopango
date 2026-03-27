<?php
/* ── BUFFER SYNCHRONISATION ─────────────────────────────────────────────── */
auth_require(ROLE_AGENT);
$pageTitle = 'Synchronisation';
$commune   = auth_commune();

// Action sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sync') {
    if (!csrf_verify()) {
        flash_set('error', 'Token invalide.');
    } else {
        $count = db_sync_paiements($commune);
        flash_set('success', "{$count} quittance(s) synchronisée(s) vers le cloud.");
    }
    redirect_page('buffer');
}

$buffer  = db_get_paiements($commune);
$pending = array_values(array_filter($buffer, fn($p) => $p['statut'] === 'pending'));
$synced  = array_values(array_filter($buffer, fn($p) => $p['statut'] === 'synced'));
$totalMontant = array_sum(array_column($buffer, 'montant'));
?>

<div class="page-hdr">
  <div class="page-title">Synchronisation</div>
  <div class="page-sub">
    Buffer local · <?= count($pending) ?> quittance(s) en attente
  </div>
  <div class="page-meta">
    <div class="page-actions">
      <?php if (!empty($pending)): ?>
      <form method="POST" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="sync">
        <button type="submit" class="btn btn-gold">🔄 Synchroniser vers Cloud (<?= count($pending) ?>)</button>
      </form>
      <?php else: ?>
      <button class="btn btn-secondary" disabled>✓ Tout est synchronisé</button>
      <?php endif; ?>
      <a href="<?= url('collecte') ?>" class="btn btn-secondary btn-sm">+ Nouvelle quittance</a>
    </div>
  </div>
</div>

<div class="content">
  <!-- KPI -->
  <div class="kpi-grid kpi-grid-3" style="max-width:640px">
    <div class="kpi-card gold">
      <div class="kpi-label">En attente</div>
      <div class="kpi-val mono gold"><?= count($pending) ?></div>
      <div class="kpi-sub">À synchroniser</div>
    </div>
    <div class="kpi-card accent">
      <div class="kpi-label">Synchronisées</div>
      <div class="kpi-val mono green"><?= count($synced) ?></div>
      <div class="kpi-sub">Confirmées cloud</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Total session</div>
      <div class="kpi-val mono"><?= count($buffer) ?></div>
      <div class="kpi-sub"><?= lp_fc($totalMontant) ?></div>
    </div>
  </div>

  <?php if (!empty($pending)): ?>
  <div class="alert alert-warn" style="max-width:640px;margin-bottom:16px">
    <span class="alert-icon">⚠</span>
    <div class="alert-body">
      <div class="alert-title"><?= count($pending) ?> quittance(s) non synchronisée(s)</div>
      <div>Ces données sont stockées localement. Synchronisez dès que la connexion est disponible pour éviter toute perte de données.</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tableau complet -->
  <div class="panel">
    <div class="panel-hdr">
      <div class="panel-title">Buffer Local</div>
      <div class="panel-sub">Toutes les quittances enregistrées</div>
      <div class="panel-line"></div>
    </div>
    <div class="tbl-container">
      <table class="tbl">
        <thead>
          <tr>
            <th>N° Quittance</th>
            <th>Identifiant du Bien</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Mode</th>
            <th class="r">Montant</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($buffer)): ?>
          <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--hint)">Aucune quittance enregistrée</td></tr>
          <?php endif; ?>
          <?php foreach (array_reverse($buffer) as $q): ?>
          <tr>
            <td><?= lp_code_pill(lp_h($q['num_quittance'])) ?></td>
            <td style="font-family:var(--mono);font-size:10px;color:var(--muted)"><?= lp_h($q['bien_id']) ?></td>
            <td style="font-size:11px;color:var(--hint)"><?= lp_date($q['date']) ?></td>
            <td style="font-size:11px;color:var(--hint)"><?= lp_h($q['heure']) ?></td>
            <td style="font-size:10px;color:var(--hint)"><?= lp_h($q['mode_paiement']) ?></td>
            <td class="r"><span style="font-family:var(--mono)"><?= lp_fc($q['montant']) ?></span></td>
            <td><?= lp_badge_statut($q['statut']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="tbl-footer">
        <span><?= count($buffer) ?> enregistrement(s)</span>
        <span>Total : <strong style="font-family:var(--mono)"><?= lp_fc($totalMontant) ?></strong></span>
      </div>
    </div>
  </div>
</div>
