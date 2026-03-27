<?php
/* ── DASHBOARD HABITAT ──────────────────────────────────────────────────── */
auth_require(ROLE_HABITAT);
$pageTitle = 'Tableau de Bord';
$commune   = auth_commune();
$stats     = db_stats_commune($commune);
$com       = $stats['commune'];
$taux      = lp_pct($com['collecte'], $com['attendu']);

$chartData = json_encode([
    ['m'=>'Oct','c'=>42,'a'=>58],['m'=>'Nov','c'=>48,'a'=>60],
    ['m'=>'Déc','c'=>51,'a'=>62],['m'=>'Jan','c'=>38,'a'=>60],
    ['m'=>'Fév','c'=>55,'a'=>63],['m'=>'Mar','c'=>61,'a'=>65],
]);
?>

<div class="page-hdr">
  <div class="page-title">Commune de <?= lp_h($com['nom']) ?></div>
  <div class="page-sub">Tableau de bord IRL · <?= date('F Y') ?></div>
  <div class="page-meta">
    <div class="page-actions">
      <a href="<?= url('rapports') ?>" class="btn btn-secondary btn-sm">📄 Rapports</a>
      <a href="<?= url('biens', ['commune'=>$commune, 'export'=>1]) ?>" class="btn btn-secondary btn-sm">↓ CSV</a>
    </div>
  </div>
</div>

<div class="content">
  <div class="kpi-grid kpi-grid-4">
    <div class="kpi-card gold">
      <div class="kpi-label">IRL Collecté</div>
      <div class="kpi-val mono gold" style="font-size:16px"><?= lp_fc($com['collecte']) ?></div>
      <div class="kpi-trend up">▲ +11% vs mois préc.</div>
    </div>
    <div class="kpi-card accent">
      <div class="kpi-label">Taux Recouvrement</div>
      <div class="kpi-val green"><?= $taux ?><span class="kpi-unit">%</span></div>
      <div class="kpi-sub">Obj : <?= lp_fc($com['attendu']) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Biens Recensés</div>
      <div class="kpi-val"><?= number_format($com['biens'],0,',',' ') ?></div>
      <div class="kpi-sub"><?= $com['occupes'] ?> occupés</div>
    </div>
    <div class="kpi-card info">
      <div class="kpi-label">Agents Actifs</div>
      <div class="kpi-val blue"><?= count(array_filter($stats['agents'],fn($a)=>$a['actif'])) ?><span class="kpi-unit">/ <?= count($stats['agents']) ?></span></div>
      <div class="kpi-sub">En service</div>
    </div>
  </div>

  <div class="grid-21">
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Évolution IRL — 6 mois</div>
        <div class="panel-sub">Collecté vs Objectif (Millions FC)</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:220px">
        <canvas id="chart-evolution"></canvas>
      </div>
    </div>
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Statuts des Biens</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:150px;margin-bottom:10px">
        <canvas id="chart-statuts"></canvas>
      </div>
      <?php
      $statuts = [['occupé','badge-ok',$com['occupes']],['libre','badge-warn',$com['libres']],['litige','badge-danger',$com['litiges']],['travaux','badge-info',$com['travaux']]];
      foreach ($statuts as [$s,$cls,$n]):
        $p = lp_pct($n, $com['biens']);
      ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--border-s);font-size:11px">
        <?= lp_badge_statut($s) ?>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="prog-bar" style="width:80px"><?= lp_progress($p) ?></div>
          <span style="font-family:var(--mono);width:30px;text-align:right"><?= $n ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Performance agents -->
  <div class="panel">
    <div class="panel-hdr">
      <div class="panel-title">Agents — Performance ce mois</div>
      <div class="panel-line"></div>
    </div>
    <?php foreach ($stats['agents'] as $a): ?>
    <div style="display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--border-s)">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--green-faint);border:1px solid rgba(15,76,53,.15);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--green);font-family:var(--mono);flex-shrink:0">
        <?= mb_strtoupper(mb_substr($a['nom'],0,2)) ?>
      </div>
      <div style="flex:1">
        <div style="font-size:12px;font-weight:500"><?= lp_h($a['nom']) ?> <?= lp_code_pill(lp_h($a['code'])) ?></div>
        <?= lp_progress((int)($a['score'] ?? 50), 'auto') ?>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-family:var(--mono);font-size:12px;font-weight:600;color:var(--green)"><?= lp_fc($a['montant'] ?? 0) ?></div>
        <div style="font-size:9px;color:var(--hint)"><?= (int)($a['quittances'] ?? 0) ?> qttances · <?= (int)($a['score'] ?? 0) ?>%</div>
      </div>
      <?= $a['actif'] ? '<span class="badge badge-ok" style="font-size:8px">actif</span>' : '<span class="badge badge-gray" style="font-size:8px">inactif</span>' ?>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:10px">
      <a href="<?= url('agents') ?>" class="btn btn-secondary btn-sm">Voir tous les agents →</a>
    </div>
  </div>
</div>

<?php
$pageScripts = "
const chartData = {$chartData};
const ctx1 = document.getElementById('chart-evolution');
if (ctx1) {
  new Chart(ctx1, {
    type: 'bar',
    data: {
      labels: chartData.map(m => m.m),
      datasets: [
        {label:'Collecté', data:chartData.map(m=>m.c), backgroundColor:'rgba(15,76,53,.75)', borderRadius:3},
        {label:'Objectif', data:chartData.map(m=>m.a), backgroundColor:'rgba(184,146,15,.18)', borderRadius:3},
      ]
    },
    options: {responsive:true, maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{size:10},boxWidth:10}}},
      scales:{x:{ticks:{font:{size:10}}},y:{ticks:{font:{size:9}},grid:{color:'rgba(0,0,0,.04)'}}}}
  });
}
const ctx2 = document.getElementById('chart-statuts');
if (ctx2) {
  new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: ['Occupé','Libre','Litige','Travaux'],
      datasets: [{
        data: [{$com['occupes']},{$com['libres']},{$com['litiges']},{$com['travaux']}],
        backgroundColor: ['#0f4c35','#b8920f','#B91C1C','#1A5FAB'],
        borderWidth: 0, hoverOffset: 4
      }]
    },
    options: {responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{legend:{display:false}}}
  });
}
";
