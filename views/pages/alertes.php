<?php
/* ── ALERTES HVK ─────────────────────────────────────────────────────────── */
auth_require(ROLE_HVK);
$pageTitle = 'Alertes Système';
$alertes   = array_merge(
    db_get_alertes(),
    lp_detect_fraude('GOM'),
    [
        ['type'=>'impayé','niveau'=>'warn', 'titre'=>'IRL non payé > 60 jours','msg'=>'23 biens occupés en Masina n\'ont pas réglé l\'IRL depuis 2 mois.','commune'=>'Masina','date'=>'24/03/2025','bien_id'=>null],
        ['type'=>'fraude','niveau'=>'danger','titre'=>'Bien non déclaré','msg'=>'Immeuble 8 unités avenue Victoire, Gombe — loyers perçus, aucune déclaration.','commune'=>'Gombe','date'=>'22/03/2025','bien_id'=>null],
        ['type'=>'sync',  'niveau'=>'warn', 'titre'=>'Synchronisation en retard','msg'=>'Agent AGT-007 (Makala) n\'a pas synchronisé depuis 5 jours. 12 quittances locales.','commune'=>'Makala','date'=>'23/03/2025','bien_id'=>null],
    ]
);
$nbDanger = count(array_filter($alertes, fn($a)=>$a['niveau']==='danger'));
$nbWarn   = count(array_filter($alertes, fn($a)=>$a['niveau']==='warn'));
?>
<div class="page-hdr">
  <div class="page-title">Alertes Système</div>
  <div class="page-sub"><?= count($alertes) ?> alertes actives · Ville de Kinshasa</div>
  <div class="page-meta">
    <div class="page-actions">
      <button class="btn btn-secondary btn-sm">↓ Exporter Alertes</button>
    </div>
  </div>
</div>
<div class="content">
  <div class="kpi-grid kpi-grid-3" style="max-width:700px">
    <div class="kpi-card danger"><div class="kpi-label">Critiques</div><div class="kpi-val red"><?= $nbDanger ?></div><div class="kpi-sub">Action immédiate requise</div></div>
    <div class="kpi-card gold"><div class="kpi-label">Avertissements</div><div class="kpi-val gold"><?= $nbWarn ?></div><div class="kpi-sub">Surveillance requise</div></div>
    <div class="kpi-card"><div class="kpi-label">Total</div><div class="kpi-val"><?= count($alertes) ?></div><div class="kpi-sub">Alertes actives</div></div>
  </div>
  <?php foreach ($alertes as $i => $a):
    $borderColor = $a['niveau']==='danger' ? 'var(--red)' : 'var(--gold-l)';
    $typeIcons   = ['fraude'=>'🔴','impayé'=>'🟠','sync'=>'🟡','litige'=>'🔴'];
    $icon        = $typeIcons[$a['type']] ?? '⚠️';
  ?>
  <div class="panel" style="border-left:3px solid <?= $borderColor ?>;margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:22px"><?= $icon ?></span>
        <div>
          <div style="font-size:13px;font-weight:600"><?= lp_h($a['titre']) ?></div>
          <div style="font-size:10px;color:var(--hint);margin-top:2px">
            <?= lp_h($a['commune']) ?> · <?= lp_h($a['date'] ?? date('d/m/Y')) ?>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
        <?= lp_badge_niveau($a['niveau']) ?>
        <button class="btn btn-secondary btn-sm" onclick="this.closest('.panel').style.opacity='.4'">✓ Traité</button>
      </div>
    </div>
    <div style="font-size:11px;color:var(--muted);line-height:1.65;padding-left:34px"><?= lp_h($a['msg']) ?></div>
    <?php if (!empty($a['bien_id'])): ?>
    <div style="padding-left:34px;margin-top:10px">
      <a href="<?= url('bien_detail', ['id'=>$a['bien_id']]) ?>" class="btn btn-primary btn-sm">Voir le bien →</a>
    </div>
    <?php else: ?>
    <div style="padding-left:34px;margin-top:10px;display:flex;gap:6px">
      <button class="btn btn-primary btn-sm">Escalader</button>
      <button class="btn btn-secondary btn-sm">Voir détails</button>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
