<?php
/* ── RAPPORT MENSUEL HVK ─────────────────────────────────────────────────── */
auth_require(ROLE_HVK);
$pageTitle = 'Rapport Mensuel HVK';
$communes  = db_get_communes();
$totalC    = array_sum(array_column($communes,'collecte'));
$totalA    = array_sum(array_column($communes,'attendu'));
$totalB    = array_sum(array_column($communes,'biens'));
$taux      = lp_pct($totalC,$totalA);
$sorted    = $communes; usort($sorted,fn($a,$b)=>lp_pct($b['collecte'],$b['attendu'])<=>lp_pct($a['collecte'],$a['attendu']));
?>
<div class="page-hdr">
  <div class="page-title">Rapport Mensuel HVK</div>
  <div class="page-sub">IRL Ville de Kinshasa · <?= date('F Y') ?></div>
  <div class="page-meta">
    <div class="page-actions">
      <button onclick="window.print()" class="btn btn-gold btn-sm">🖨 Imprimer</button>
      <a href="<?= url('communes', ['export'=>1]) ?>" class="btn btn-secondary btn-sm">↓ Exporter</a>
    </div>
  </div>
</div>
<div class="content">
  <div class="panel" style="border-top:3px solid var(--green)">
    <div style="text-align:center;padding:8px 0 20px;border-bottom:1px solid var(--border);margin-bottom:24px">
      <div style="font-family:var(--serif);font-size:10px;color:var(--hint);letter-spacing:3px;text-transform:uppercase;margin-bottom:4px">République Démocratique du Congo</div>
      <div style="font-family:var(--serif);font-size:28px;font-weight:700;color:var(--green);letter-spacing:4px">LOPANGO</div>
      <div style="font-size:9px;color:var(--hint);letter-spacing:2px;text-transform:uppercase;margin-top:3px">Hôtel de Ville de Kinshasa · Direction des Impôts Locatifs</div>
      <div style="font-family:var(--serif);font-size:20px;font-weight:600;margin-top:14px">Rapport Mensuel — IRL <?= date('F Y') ?></div>
      <div style="font-size:10px;color:var(--hint);margin-top:4px">Généré le <?= date('d/m/Y') ?> à <?= date('H:i') ?> · Confidentiel</div>
    </div>
    <div class="grid-2">
      <div>
        <div style="font-family:var(--serif);font-size:16px;font-weight:600;color:var(--green);margin-bottom:12px">1. Synthèse Financière</div>
        <?php foreach([
          ['IRL Total Collecté',lp_fc($totalC)],['IRL Attendu (Ville)',lp_fc($totalA)],
          ['Taux de Recouvrement',$taux.'%'],['Écart vs Objectif',lp_fc($totalA-$totalC)],
          ['Variation vs mois préc.','+8,4%'],['IRL cumulé (T1 2025)',lp_fc($totalC*3)],
        ] as [$k,$v]): ?>
        <div class="stat-row"><span class="stat-label"><?= lp_h($k) ?></span><span class="stat-val mono"><?= lp_h($v) ?></span></div>
        <?php endforeach; ?>
      </div>
      <div>
        <div style="font-family:var(--serif);font-size:16px;font-weight:600;color:var(--green);margin-bottom:12px">2. Données Terrain</div>
        <?php foreach([
          ['Communes actives','21 / 21'],['Biens recensés',number_format($totalB,0,',',' ')],
          ['Agents déployés',array_sum(array_column($communes,'agents'))],['Quittances émises','1 847'],
          ['Biens en litige',number_format(array_sum(array_column($communes,'litiges')),0,',',' ')],['Taux fraude estimé','2,3%'],
        ] as [$k,$v]): ?>
        <div class="stat-row"><span class="stat-label"><?= lp_h($k) ?></span><span class="stat-val mono"><?= lp_h((string)$v) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:24px">
      <div style="font-family:var(--serif);font-size:16px;font-weight:600;color:var(--green);margin-bottom:12px">3. Classement des Communes</div>
      <div class="grid-2">
        <div>
          <div style="font-size:10px;color:var(--green);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;font-weight:600">Meilleures Communes</div>
          <?php foreach(array_slice($sorted,0,5) as $i=>$c): $tp=lp_pct($c['collecte'],$c['attendu']); ?>
          <div class="stat-row"><span class="stat-label"><?=$i+1?>. <?=lp_h($c['nom'])?></span><span class="stat-val" style="color:var(--green);font-family:var(--mono)"><?=$tp?>%</span></div>
          <?php endforeach; ?>
        </div>
        <div>
          <div style="font-size:10px;color:var(--red);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;font-weight:600">À Renforcer</div>
          <?php foreach(array_slice(array_reverse($sorted),0,3) as $i=>$c): $tp=lp_pct($c['collecte'],$c['attendu']); ?>
          <div class="stat-row"><span class="stat-label"><?=$i+1?>. <?=lp_h($c['nom'])?></span><span class="stat-val" style="color:var(--red);font-family:var(--mono)"><?=$tp?>%</span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="margin-top:24px;padding:16px;background:var(--card);border:1px solid var(--border-s);border-radius:var(--radius);border-left:3px solid var(--gold-l)">
      <div style="font-size:10px;color:var(--gold);text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:8px">Note du Directeur IRL</div>
      <div style="font-size:11px;color:var(--muted);line-height:1.7">Le mois de <?=date('F Y')?> confirme la tendance haussière observée. Le taux de recouvrement global de <?=$taux?>% reste en progression. Les efforts de sensibilisation doivent être intensifiés dans les communes déficitaires. La lutte contre la fraude reste une priorité absolue de l'Hôtel de Ville.</div>
    </div>
  </div>
</div>
