<?php
/* ── AGENTS ─────────────────────────────────────────────────────────────── */
auth_require();
if (!auth_is_habitat() && !auth_is_hvk()) redirect_page(auth_default_page());
$pageTitle = 'Agents de Terrain';
$commune   = auth_is_habitat() ? auth_commune() : ($_GET['commune'] ?? null);
$agents    = db_get_utilisateurs(ROLE_AGENT);
if ($commune) $agents = array_filter($agents, fn($u) => $u['commune'] === $commune);
$agents    = array_values($agents);
$totalQ    = array_sum(array_map(fn($a) => (int)($a['quittances'] ?? 0), $agents));
$totalM    = array_sum(array_map(fn($a) => (int)($a['montant']    ?? 0), $agents));
$scoreM    = $agents ? (int)(array_sum(array_map(fn($a) => (int)($a['score'] ?? 0), $agents)) / count($agents)) : 0;
$agentsJson = json_encode(array_map(fn($a) => [
    'nom'        => $a['nom'],
    'code'       => $a['code'],
    'commune'    => $a['commune'],
    'email'      => $a['email'] ?? '',
    'actif'      => $a['actif'],
    'score'      => $a['score'] ?? 50,
    'quittances' => $a['quittances'] ?? 0,
    'montant'    => $a['montant'] ?? 0,
    'date_creation' => $a['date_creation'] ?? '',
], $agents));

// Ajouter agent (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_agent') {
    if (!csrf_verify()) { flash_set('error','Token invalide.'); redirect_page('agents'); }
    $nom      = trim($_POST['nom']      ?? '');
    $code     = strtoupper(trim($_POST['code'] ?? ''));
    $commune  = strtoupper(trim($_POST['commune'] ?? ''));
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($nom))      { flash_set('error', 'Le nom complet est obligatoire.'); redirect_page('agents'); }
    if (empty($code))     { flash_set('error', 'Le code agent est obligatoire (ex: AGT-005).'); redirect_page('agents'); }
    if (empty($password)) { flash_set('error', 'Le mot de passe provisoire est obligatoire.'); redirect_page('agents'); }
    if (!preg_match('/^AGT-\d{3,}$/i', $code)) {
        flash_set('error', 'Format de code invalide. Utilisez le format AGT-005.');
        redirect_page('agents');
    }

    $newAgent = [
        'id'             => 'USR-' . str_pad(db_next_id('utilisateurs'), 3, '0', STR_PAD_LEFT),
        'nom'            => $nom,
        'code'           => $code,
        'role'           => ROLE_AGENT,
        'commune'        => $commune,
        'email'          => $email,
        'password_plain' => $password,
        'actif'          => true,
        'date_creation'  => date('Y-m-d'),
        'score'          => 50,
        'quittances'     => 0,
        'montant'        => 0,
    ];

    if (db_create_utilisateur($newAgent)) {
        flash_set('success', "Agent {$code} — {$nom} créé avec succès. Mot de passe provisoire : {$password}");
    } else {
        flash_set('error', "Le code agent {$code} existe déjà. Choisissez un code différent.");
    }
    redirect_page('agents');
}
?>

<div class="page-hdr">
  <div class="page-title">Agents de Terrain</div>
  <div class="page-sub">
    <?= $commune ? lp_h(db_get_commune($commune)['nom'] ?? $commune) . ' · ' : 'Toutes communes · ' ?>
    <?= count($agents) ?> agent(s) déployé(s)
  </div>
  <div class="page-meta">
    <div class="page-actions">
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-add').style.display='flex'">+ Ajouter Agent</button>
      <button class="btn btn-secondary btn-sm" onclick="window.print()">🖨 Imprimer</button>
    </div>
  </div>
</div>

<div class="content">
  <!-- KPI -->
  <div class="kpi-grid kpi-grid-4" style="max-width:800px">
    <div class="kpi-card accent">
      <div class="kpi-label">Agents Actifs</div>
      <div class="kpi-val green"><?= count(array_filter($agents,fn($a)=>$a['actif'])) ?><span class="kpi-unit">/ <?= count($agents) ?></span></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Total Quittances</div>
      <div class="kpi-val"><?= $totalQ ?></div>
    </div>
    <div class="kpi-card gold">
      <div class="kpi-label">IRL Total</div>
      <div class="kpi-val mono gold" style="font-size:14px"><?= lp_fc($totalM) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Score Moyen</div>
      <div class="kpi-val mono"><?= $scoreM ?>%</div>
    </div>
  </div>

  <!-- Graphique performance -->
  <div class="panel">
    <div class="panel-hdr">
      <div class="panel-title">Performance Comparée</div>
      <div class="panel-line"></div>
    </div>
    <div class="chart-wrap" style="height:200px;margin-bottom:16px">
      <canvas id="chart-agents"></canvas>
    </div>
  </div>

  <!-- Tableau agents -->
  <div class="panel">
    <div class="panel-hdr">
      <div class="panel-title">Tableau des Agents</div>
      <div class="panel-line"></div>
    </div>
    <div class="tbl-container">
      <table class="tbl">
        <thead><tr>
          <th>Agent</th><th>Code</th><th>Commune</th>
          <th class="r">Quittances</th><th class="r">IRL Collecté</th>
          <th>Score</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($agents as $a): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--green-faint);border:1px solid rgba(15,76,53,.15);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:var(--green);flex-shrink:0;font-family:var(--mono)">
                  <?= mb_strtoupper(mb_substr($a['nom'],0,2)) ?>
                </div>
                <strong><?= lp_h($a['nom']) ?></strong>
              </div>
            </td>
            <td><?= lp_code_pill(lp_h($a['code'])) ?></td>
            <td style="font-size:11px"><?= lp_h(db_get_commune($a['commune'])['nom'] ?? $a['commune']) ?></td>
            <td class="r"><span style="font-family:var(--mono)"><?= (int)($a['quittances'] ?? 0) ?></span></td>
            <td class="r"><span style="font-family:var(--mono);color:var(--green)"><?= lp_fc($a['montant'] ?? 0) ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <?= lp_progress((int)($a['score'] ?? 50), 'auto') ?>
                <span style="font-family:var(--mono);font-size:10px;width:32px"><?= (int)($a['score'] ?? 0) ?>%</span>
              </div>
            </td>
            <td>
              <?= $a['actif']
                ? '<span class="badge badge-ok">Actif</span>'
                : '<span class="badge badge-gray">Inactif</span>' ?>
            </td>
            <td style="display:flex;gap:4px">
              <button class="btn btn-secondary btn-sm" title="Voir détails"
                      data-agent-code="<?= lp_h($a['code']) ?>"
                      onclick="showAgentByCode(this.dataset.agentCode)">📊</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal ajout agent -->
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-top:3px solid var(--green);border-radius:var(--radius-lg);padding:24px 28px;width:100%;max-width:480px;box-shadow:var(--shadow-md)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div style="font-family:var(--serif);font-size:20px;font-weight:700">Nouvel Agent</div>
      <button onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-secondary btn-sm">× Fermer</button>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_agent">
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label">Nom complet *</label>
          <input class="form-input" name="nom" required placeholder="KABILA Emile">
        </div>
        <div class="form-group">
          <label class="form-label">Code agent *</label>
          <input class="form-input form-input--mono" name="code" required placeholder="AGT-005">
        </div>
      </div>
      <div class="form-row form-row-2">
        <div class="form-group">
          <label class="form-label">Commune *</label>
          <select class="form-select" name="commune">
            <?php foreach (db_get_communes() as $c): ?>
            <option value="<?= lp_h($c['code']) ?>" <?= $c['code'] === ($commune??'') ? 'selected':'' ?>><?= lp_h($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-input" name="email" type="email" placeholder="prenom.nom@lopango.cd">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Mot de passe provisoire *</label>
        <input class="form-input" name="password" type="text" required placeholder="agent001">
        <div class="form-hint">L'agent devra le changer à la première connexion.</div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Créer l'Agent</button>
    </form>
  </div>
</div>

<!-- Modal détail agent -->
<div id="modal-agent" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-top:3px solid var(--green);border-radius:var(--radius-lg);padding:24px 28px;width:100%;max-width:420px;box-shadow:var(--shadow-md)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div id="modal-agent-name" style="font-family:var(--serif);font-size:20px;font-weight:700"></div>
      <button onclick="document.getElementById('modal-agent').style.display='none'" class="btn btn-secondary btn-sm">× Fermer</button>
    </div>
    <div id="modal-agent-body"></div>
  </div>
</div>

<?php
$pageScripts = "
const AGENTS_JSON = {$agentsJson};

// Chart agents
const ctx = document.getElementById('chart-agents');
if (ctx) {
  new Chart(ctx, {
    type:'bar',
    data:{
      labels: AGENTS_JSON.map(a => a.nom.split(' ')[0]),
      datasets:[
        {label:'IRL Collecté (M FC)', data:AGENTS_JSON.map(a => +(a.montant/1000000).toFixed(1)), backgroundColor:'rgba(15,76,53,.7)', borderRadius:3, yAxisID:'y'},
        {label:'Quittances',          data:AGENTS_JSON.map(a => a.quittances), backgroundColor:'rgba(184,146,15,.5)', borderRadius:3, yAxisID:'y1'},
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:{size:10},boxWidth:10}}},
      scales:{
        x:{ticks:{font:{size:10}}},
        y:{ticks:{font:{size:9}},grid:{color:'rgba(0,0,0,.04)'},title:{display:true,text:'M FC',font:{size:9}}},
        y1:{position:'right',ticks:{font:{size:9}},grid:{drawOnChartArea:false},title:{display:true,text:'Quittances',font:{size:9}}},
      }}
  });
}

function showAgentByCode(code) {
  var a = AGENTS_JSON.find(function(x){ return x.code === code; });
  if (!a) return;
  showAgentDetail(a);
}

function showAgentDetail(a) {
  document.getElementById('modal-agent-name').textContent = a.nom;
  var fmt = function(n){ return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FC'; };
  document.getElementById('modal-agent-body').innerHTML =
    '<div class=\"stat-row\"><span class=\"stat-label\">Code</span><span class=\"stat-val mono\">' + a.code + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Commune</span><span class=\"stat-val\">' + a.commune + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Email</span><span class=\"stat-val\">' + (a.email || '—') + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Quittances émises</span><span class=\"stat-val mono\">' + (a.quittances || 0) + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">IRL Collecté</span><span class=\"stat-val mono\">' + fmt(a.montant || 0) + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Score Performance</span><span class=\"stat-val mono\">' + (a.score || 0) + '%</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Date création</span><span class=\"stat-val\">' + (a.date_creation || '—') + '</span></div>' +
    '<div class=\"stat-row\"><span class=\"stat-label\">Statut</span><span>' + (a.actif ? '<span class=\"badge badge-ok\">Actif</span>' : '<span class=\"badge badge-gray\">Inactif</span>') + '</span></div>';
  document.getElementById('modal-agent').style.display = 'flex';
}
";
