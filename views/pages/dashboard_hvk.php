<?php
/* ── DASHBOARD HVK ──────────────────────────────────────────────────────── */
auth_require(ROLE_HVK);
$pageTitle = 'Vue Globale';
$stats     = db_stats_ville();
$communes  = db_get_communes();
$totalC    = $stats['total_collecte'];
$totalA    = $stats['total_attendu'];
$taux      = lp_pct($totalC, $totalA);

// Top 5 et Bottom 3
$sorted    = $communes;
usort($sorted, fn($a,$b) => lp_pct($b['collecte'],$b['attendu']) <=> lp_pct($a['collecte'],$a['attendu']));
$top5   = array_slice($sorted, 0, 5);
$bottom3 = array_slice(array_reverse($sorted), 0, 3);

// Données pour Chart.js (JSON)
$chartCommunes = json_encode(array_map(fn($c) => [
    'nom'      => mb_substr($c['nom'], 0, 8),
    'collecte' => round($c['collecte'] / 1000000, 1),
    'attendu'  => round($c['attendu']  / 1000000, 1),
], $communes));

$chartMonthly = json_encode([
    ['m'=>'Oct','c'=>42,'a'=>58],['m'=>'Nov','c'=>48,'a'=>60],
    ['m'=>'Déc','c'=>51,'a'=>62],['m'=>'Jan','c'=>38,'a'=>60],
    ['m'=>'Fév','c'=>55,'a'=>63],['m'=>'Mar','c'=>61,'a'=>65],
]);
?>

<!-- PAGE HEADER -->
<div class="page-hdr">
  <div class="page-title">Ville de Kinshasa</div>
  <div class="page-sub">IRL Global · <?= date('F Y') ?> · <?= count($communes) ?> communes actives</div>
  <div class="page-meta">
    <div class="page-actions">
      <a href="<?= url('rapport_hvk') ?>" class="btn btn-gold btn-sm">📊 Rapport PDF</a>
      <a href="<?= url('communes') ?>&export=1" class="btn btn-secondary btn-sm">↓ Exporter CSV</a>
    </div>
  </div>
</div>

<!-- KPI GRID -->
<div class="content">
  <div class="kpi-grid kpi-grid-4">
    <div class="kpi-card gold">
      <div class="kpi-label">IRL Total Collecté</div>
      <div class="kpi-val mono gold" style="font-size:15px"><?= lp_fc($totalC) ?></div>
      <div class="kpi-trend up">▲ +8.4% vs mois préc.</div>
    </div>
    <div class="kpi-card accent">
      <div class="kpi-label">Taux Recouvrement Global</div>
      <div class="kpi-val green"><?= $taux ?><span class="kpi-unit">%</span></div>
      <div class="kpi-sub">Obj : <?= lp_fc($totalA) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Biens Recensés (Ville)</div>
      <div class="kpi-val"><?= number_format($stats['total_biens_all'], 0, ',', ' ') ?></div>
      <div class="kpi-sub"><?= count($communes) ?> communes</div>
    </div>
    <div class="kpi-card info">
      <div class="kpi-label">Agents Déployés</div>
      <div class="kpi-val blue"><?= $stats['total_agents'] ?></div>
      <div class="kpi-sub">Personnel de terrain</div>
    </div>
  </div>

  <!-- GRAPHIQUES -->
  <div class="grid-21">
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">IRL Collecté vs Objectif — Toutes Communes</div>
        <div class="panel-sub">Millions FC</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:240px">
        <canvas id="chart-communes"></canvas>
      </div>
    </div>
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Répartition des Biens</div>
        <div class="panel-sub">Par statut — Ville entière</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:160px;margin-bottom:12px">
        <canvas id="chart-statuts"></canvas>
      </div>
      <?php
      $totOcc  = array_sum(array_column($communes,'occupes'));
      $totLib  = array_sum(array_column($communes,'libres'));
      $totLit  = array_sum(array_column($communes,'litiges'));
      $totTrx  = array_sum(array_column($communes,'travaux'));
      $totAll  = $totOcc + $totLib + $totLit + $totTrx;
      foreach ([['occupé','badge-ok',$totOcc],['libre','badge-warn',$totLib],['litige','badge-danger',$totLit],['travaux','badge-info',$totTrx]] as [$s,$cls,$n]):
      ?>
      <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border-s);font-size:11px">
        <?= lp_badge_statut($s) ?>
        <span style="font-family:var(--mono);font-weight:600"><?= number_format($n, 0, ',', ' ') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- CLASSEMENT + TREND -->
  <div class="grid-2">
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Classement — Taux de Recouvrement</div>
        <div class="panel-sub">Top 5 communes</div>
        <div class="panel-line"></div>
      </div>
      <?php foreach ($top5 as $i => $c):
        $tp = lp_pct($c['collecte'],$c['attendu']);
        $barClass = $tp >= 80 ? 'prog-green' : ($tp >= 65 ? 'prog-gold' : 'prog-red');
        $textColor = $tp >= 80 ? 'var(--green)' : ($tp >= 65 ? 'var(--gold)' : 'var(--red)');
      ?>
      <div class="commune-row">
        <div class="commune-rank"><?= $i+1 ?></div>
        <div class="commune-name">
          <a href="<?= url('communes') . '&code=' . urlencode($c['code']) ?>" style="color:var(--text);text-decoration:none">
            <?= lp_h($c['nom']) ?>
          </a>
        </div>
        <div class="commune-bar-wrap"><?= lp_progress($tp, $barClass) ?></div>
        <div class="commune-pct" style="color:<?= $textColor ?>"><?= $tp ?>%</div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border-s)">
        <div class="panel-sub" style="margin-bottom:6px">Communes à renforcer</div>
        <?php foreach ($bottom3 as $c):
          $tp = lp_pct($c['collecte'],$c['attendu']);
        ?>
        <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:11px">
          <span><?= lp_h($c['nom']) ?></span>
          <span style="font-family:var(--mono);color:var(--red)"><?= $tp ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Évolution Mensuelle (Ville)</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:250px">
        <canvas id="chart-trend"></canvas>
      </div>
    </div>
  </div>

  <!-- ALERTES CRITIQUES -->
  <?php $alertes = db_get_alertes(); if (!empty($alertes)): ?>
  <div class="panel" style="border-left:3px solid var(--red)">
    <div class="panel-hdr">
      <div class="panel-title">Alertes Critiques</div>
      <div class="panel-sub"><?= count($alertes) ?> alerte(s) active(s)</div>
      <div class="panel-line"></div>
    </div>
    <?php foreach (array_slice($alertes, 0, 3) as $alerte): ?>
    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-s)">
      <span style="font-size:18px"><?= $alerte['type'] === 'litige' ? '🔴' : '🟠' ?></span>
      <div style="flex:1">
        <div style="font-size:12px;font-weight:500"><?= lp_h($alerte['titre']) ?></div>
        <div style="font-size:11px;color:var(--hint);margin-top:2px"><?= lp_h($alerte['msg']) ?></div>
      </div>
      <?= lp_badge_niveau($alerte['niveau']) ?>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:10px">
      <a href="<?= url('alertes') ?>" class="btn btn-danger btn-sm">Voir toutes les alertes →</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
$pageScripts = "
const chartCommunes = {$chartCommunes};
const chartMonthly  = {$chartMonthly};

// Graphique communes
const ctx1 = document.getElementById('chart-communes');
if (ctx1) {
  new Chart(ctx1, {
    type:'bar',
    data:{
      labels: chartCommunes.map(c=>c.nom),
      datasets:[
        {label:'Collecté',  data:chartCommunes.map(c=>c.collecte), backgroundColor:'rgba(15,76,53,.7)', borderRadius:3},
        {label:'Objectif',  data:chartCommunes.map(c=>c.attendu),  backgroundColor:'rgba(184,146,15,.2)', borderRadius:3},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{size:9},boxWidth:8}}},
      scales:{x:{ticks:{font:{size:8}}},y:{ticks:{font:{size:9}},grid:{color:'rgba(0,0,0,.04)'},
        title:{display:true,text:'M FC',font:{size:9}}}}}
  });
}

// Graphique statuts
const ctx2 = document.getElementById('chart-statuts');
if (ctx2) {
  new Chart(ctx2, {
    type:'doughnut',
    data:{
      labels:['Occupé','Libre','Litige','Travaux'],
      datasets:[{data:[" . $totOcc . "," . $totLib . "," . $totLit . "," . $totTrx . "],
        backgroundColor:['#0f4c35','#b8920f','#B91C1C','#1A5FAB'],borderWidth:0,hoverOffset:4}]
    },
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false}}}
  });
}

// Graphique tendance
const ctx3 = document.getElementById('chart-trend');
if (ctx3) {
  new Chart(ctx3, {
    type:'line',
    data:{
      labels: chartMonthly.map(m=>m.m),
      datasets:[
        {label:'Collecté',data:chartMonthly.map(m=>m.c),borderColor:'#0f4c35',backgroundColor:'rgba(15,76,53,.08)',tension:.3,fill:true,pointRadius:3,borderWidth:2},
        {label:'Objectif',data:chartMonthly.map(m=>m.a),borderColor:'rgba(184,146,15,.6)',borderDash:[4,4],tension:.3,fill:false,pointRadius:2,borderWidth:1.5},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{size:10},boxWidth:10}}},
      scales:{x:{ticks:{font:{size:10}}},y:{ticks:{font:{size:9}},grid:{color:'rgba(0,0,0,.04)'}}}}
  });
}
";
