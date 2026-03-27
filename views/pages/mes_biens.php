<?php
/* ── MES COLLECTES ──────────────────────────────────────────────────────── */
auth_require(ROLE_AGENT);
$pageTitle  = 'Mes Collectes';
$agentCode  = auth_code();
$commune    = auth_commune();
$user       = auth_user();
$paiements  = db_get_paiements($commune);
$mesPaie    = array_values(array_filter($paiements, fn($p) => $p['agent_code'] === $agentCode));
$totalMnt   = array_sum(array_column($mesPaie, 'montant'));
$nbSynced   = count(array_filter($mesPaie, fn($p) => $p['statut'] === 'synced'));

// Export
if (!empty($_GET['export'])) {
    $rows = array_map(fn($p) => [
        $p['num_quittance'], $p['bien_id'], $p['periode'],
        $p['montant'], $p['mode_paiement'], $p['statut'],
        $p['date'], $p['heure'],
    ], $mesPaie);
    lp_export_csv($rows, 'mes-collectes-' . date('Ymd') . '.csv',
        ['N° Quittance','Bien ID','Période','Montant FC','Mode','Statut','Date','Heure']);
}
?>

<div class="page-hdr">
  <div class="page-title">Mes Collectes</div>
  <div class="page-sub">
    <?= lp_h($user['nom']) ?> (<?= lp_h($agentCode) ?>) ·
    <?= lp_h(db_get_commune($commune)['nom'] ?? $commune) ?>
  </div>
  <div class="page-meta">
    <div class="page-actions">
      <a href="<?= url('mes_biens', ['export'=>1]) ?>" class="btn btn-secondary btn-sm">↓ Exporter CSV</a>
    </div>
  </div>
</div>

<div class="content">
  <div class="kpi-grid kpi-grid-4">
    <div class="kpi-card accent">
      <div class="kpi-label">Quittances émises</div>
      <div class="kpi-val green"><?= count($mesPaie) ?></div>
      <div class="kpi-sub">Total depuis déploiement</div>
    </div>
    <div class="kpi-card gold">
      <div class="kpi-label">IRL Collecté</div>
      <div class="kpi-val mono gold" style="font-size:16px"><?= lp_fc($totalMnt) ?></div>
      <div class="kpi-sub">Toutes périodes</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Synchronisées</div>
      <div class="kpi-val"><?= $nbSynced ?><span class="kpi-unit">/ <?= count($mesPaie) ?></span></div>
      <div class="kpi-sub">Confirmées cloud</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Score Performance</div>
      <div class="kpi-val mono green"><?= $user['score'] ?? 0 ?>%</div>
      <div class="kpi-sub">Classement communal</div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-hdr">
      <div class="panel-title">Historique des Quittances</div>
      <div class="panel-line"></div>
    </div>
    <div class="tbl-container">
      <table class="tbl">
        <thead><tr>
          <th>N° Quittance</th><th>Identifiant du Bien</th>
          <th>Période</th><th>Date</th><th class="r">Montant</th>
          <th>Mode</th><th>Statut</th>
        </tr></thead>
        <tbody>
          <?php if (empty($mesPaie)): ?>
          <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--hint)">Aucune quittance enregistrée</td></tr>
          <?php endif; ?>
          <?php foreach (array_reverse($mesPaie) as $p): ?>
          <tr>
            <td><?= lp_code_pill(lp_h($p['num_quittance'])) ?></td>
            <td style="font-family:var(--mono);font-size:10px;color:var(--muted)">
              <a href="<?= url('bien_detail', ['id'=>$p['bien_id']]) ?>" style="color:var(--green)">
                <?= lp_h($p['bien_id']) ?>
              </a>
            </td>
            <td style="font-family:var(--mono);font-size:11px"><?= lp_h($p['periode']) ?></td>
            <td style="font-size:11px;color:var(--hint)"><?= lp_date($p['date']) ?> <?= lp_h($p['heure']) ?></td>
            <td class="r"><span style="font-family:var(--mono);color:var(--green)"><?= lp_fc($p['montant']) ?></span></td>
            <td style="font-size:10px;color:var(--hint)"><?= lp_h($p['mode_paiement']) ?></td>
            <td><?= lp_badge_statut($p['statut']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="tbl-footer">
        <span><?= count($mesPaie) ?> quittance(s)</span>
        <span>Total : <strong style="font-family:var(--mono)"><?= lp_fc($totalMnt) ?></strong></span>
      </div>
    </div>
  </div>
</div>
