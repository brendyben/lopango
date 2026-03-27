<?php
/* ── PROJECTIONS ─────────────────────────────────────────────────────────── */
auth_require(ROLE_HVK);
$pageTitle  = 'Projections Budgétaires';
$communes   = db_get_communes();
$totalC     = array_sum(array_column($communes,'collecte'));
$totalA     = array_sum(array_column($communes,'attendu'));
$proj9      = (int)round($totalC * 3.15);
$annuelObj  = $totalA * 12;
$gap        = $annuelObj - ($totalC * 4 + $proj9);
?>

<div class="page-hdr">
  <div class="page-title">Projections Budgétaires</div>
  <div class="page-sub">Simulation IRL annuelle 2025 · Ville de Kinshasa</div>
  <div class="page-meta">
    <div class="page-actions">
      <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Imprimer</button>
    </div>
  </div>
</div>

<div class="content">
  <div class="kpi-grid kpi-grid-4">
    <div class="kpi-card gold">
      <div class="kpi-label">IRL Cumulé (T1 2025)</div>
      <div class="kpi-val mono gold" style="font-size:14px"><?= lp_fc($totalC) ?></div>
      <div class="kpi-sub">3 mois réels</div>
    </div>
    <div class="kpi-card accent">
      <div class="kpi-label">Projection Annuelle</div>
      <div class="kpi-val mono green" style="font-size:14px" id="proj-display"><?= lp_fc($totalC*4+$proj9) ?></div>
      <div class="kpi-sub">Basée sur tendance actuelle</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Objectif Annuel Ville</div>
      <div class="kpi-val mono" style="font-size:14px"><?= lp_fc($annuelObj) ?></div>
      <div class="kpi-sub">Fixé par Hôtel de Ville</div>
    </div>
    <div class="kpi-card danger">
      <div class="kpi-label">Gap à Combler</div>
      <div class="kpi-val mono red" style="font-size:14px" id="gap-display"><?= lp_fc($gap) ?></div>
      <div class="kpi-sub">Sur 9 mois restants</div>
    </div>
  </div>

  <div class="grid-21">
    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Tendance & Projection 2025</div>
        <div class="panel-sub">Réel (T1) + projection (T2–T4)</div>
        <div class="panel-line"></div>
      </div>
      <div class="chart-wrap" style="height:250px">
        <canvas id="chart-projection"></canvas>
      </div>
    </div>

    <div class="panel">
      <div class="panel-hdr">
        <div class="panel-title">Simulation Paramétrique</div>
        <div class="panel-sub">Ajustez le taux de croissance mensuel</div>
        <div class="panel-line"></div>
      </div>
      <div style="margin-bottom:16px">
        <label class="form-label">Taux de croissance mensuel</label>
        <input type="range" id="sim-taux" min="-10" max="20" value="5" step="1"
               style="width:100%;margin:10px 0"
               oninput="updateSim(this.value)">
        <div style="display:flex;justify-content:space-between;font-family:var(--mono);font-size:10px;color:var(--hint)">
          <span>-10%</span>
          <span id="sim-taux-val" style="color:var(--green);font-weight:600">+5%</span>
          <span>+20%</span>
        </div>
      </div>
      <div id="sim-results"></div>
    </div>
  </div>

  <!-- Recommandations -->
  <div class="panel" style="background:linear-gradient(135deg,rgba(15,76,53,.04),rgba(201,162,39,.04));border-color:rgba(201,162,39,.2)">
    <div class="panel-hdr">
      <div class="panel-title">Recommandations Stratégiques</div>
      <div class="panel-sub">Analyse basée sur les données Mars 2025</div>
      <div class="panel-line"></div>
    </div>
    <div class="grid-2">
      <?php
      $recommandations = [
        ['🔴','Priorité 1','Intensifier N\'Sele, Makala & Kintambo','Taux < 65% — déployer des agents supplémentaires en Q2.'],
        ['🟠','Priorité 2','Débloquer les litiges critiques','18,7M FC bloqués par 47 litiges actifs > 90 jours.'],
        ['🟡','Priorité 3','Campagne de sensibilisation bailleurs','Avant le 30 avril — 2 340 biens occupés sans quittance IRL.'],
        ['🟢','Priorité 4','Activer alertes automatiques IRL','Non-paiement > 30j → notification mobile bailleur.'],
        ['💡','Opportunité','Extension cartographie N\'Sele & Maluku','Sous-couverture estimée à 60% de ces deux communes.'],
        ['⚠️','Risque','Résistance terrain à Masina','Signal corruption — audit interne recommandé Q2.'],
      ];
      foreach ($recommandations as [$icon,$label,$titre,$desc]):
      ?>
      <div style="background:var(--card);border:1px solid var(--border-s);border-radius:var(--radius-lg);padding:14px 16px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
          <span style="font-size:14px"><?= $icon ?></span>
          <div style="font-size:9px;color:var(--hint);text-transform:uppercase;letter-spacing:1.5px"><?= lp_h($label) ?></div>
        </div>
        <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:3px"><?= lp_h($titre) ?></div>
        <div style="font-size:10px;color:var(--hint);line-height:1.6"><?= lp_h($desc) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$totalC_js    = $totalC;
$annuelObj_js = $annuelObj;
$pageScripts  = "
const TOTAL_C     = {$totalC_js};
const ANNUEL_OBJ  = {$annuelObj_js};

// Graphique projection
const ctx = document.getElementById('chart-projection');
const labels  = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
const reel    = [38,55,61,null,null,null,null,null,null,null,null,null];
const proj    = [null,null,61,65,68,71,74,77,80,83,86,89];
const objectif= [60,63,65,67,69,71,73,75,77,79,81,83];
if (ctx) {
  new Chart(ctx, {
    type:'line',
    data:{labels, datasets:[
      {label:'Réel T1',   data:reel,     borderColor:'#0f4c35',backgroundColor:'rgba(15,76,53,.08)',tension:.3,fill:true,pointRadius:4,borderWidth:2.5},
      {label:'Projection',data:proj,     borderColor:'rgba(184,146,15,.8)',borderDash:[6,3],tension:.3,fill:false,pointRadius:3,borderWidth:2},
      {label:'Objectif',  data:objectif, borderColor:'rgba(100,100,100,.3)',borderDash:[3,3],tension:.3,fill:false,pointRadius:2,borderWidth:1.5},
    ]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{size:10},boxWidth:10}}},
      scales:{x:{ticks:{font:{size:10}}},y:{ticks:{font:{size:9}},grid:{color:'rgba(0,0,0,.04)'},title:{display:true,text:'Millions FC',font:{size:9}}}}}
  });
}

function updateSim(taux) {
  taux = parseInt(taux);
  const el = document.getElementById('sim-taux-val');
  if (el) el.textContent = (taux >= 0 ? '+' : '') + taux + '%';

  const base  = TOTAL_C;
  let cumul   = base * 3;
  let mensuel = base;
  for (let i = 0; i < 9; i++) {
    mensuel = Math.round(mensuel * (1 + taux / 100));
    cumul  += mensuel;
  }
  const gap    = ANNUEL_OBJ - cumul;
  const fmt    = n => new Intl.NumberFormat('fr-FR').format(Math.round(Math.abs(n))) + ' FC';
  const pos    = n => n >= 0 ? '+' : '-';
  const gapTxt = gap > 0 ? 'var(--red)' : 'var(--green)';
  const gapLbl = gap > 0 ? fmt(gap) : '✓ Objectif atteint (' + fmt(-gap) + ' surplus)';

  const si = document.getElementById('sim-results');
  if (!si) return;
  si.innerHTML = [
    ['IRL projeté (mois 12)',fmt(mensuel),'var(--green)'],
    ['IRL annuel projeté',   fmt(cumul),  cumul >= ANNUEL_OBJ ? 'var(--green)' : 'var(--gold)'],
    ['Objectif annuel',      fmt(ANNUEL_OBJ), 'var(--text)'],
    ['Gap résiduel',         gapLbl,      gapTxt],
  ].map(([k,v,col]) => '<div class=\"stat-row\"><span class=\"stat-label\">' + k + '</span><span class=\"stat-val\" style=\"color:' + col + ';font-family:var(--mono)\">' + v + '</span></div>').join('');

  // Mettre à jour affichage KPI
  const pd = document.getElementById('proj-display'); if (pd) pd.textContent = fmt(cumul);
  const gd = document.getElementById('gap-display');  if (gd) { gd.textContent = gapLbl; gd.style.color = gapTxt; }
}

document.addEventListener('DOMContentLoaded', () => updateSim(5));
";
