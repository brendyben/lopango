<?php
/* ── BIENS PAGE ─────────────────────────────────────────────────────────── */
auth_require();
$pageTitle = 'Biens Locatifs';

// Filtres
$commune  = $_GET['commune'] ?? (auth_is_hvk() ? null : auth_commune());
$statut   = $_GET['statut']  ?? null;
$search   = trim($_GET['q']  ?? '');
$page     = max(1, (int)($_GET['p'] ?? 1));

// Chargement et filtrage
if ($search) {
    $biens = db_search_biens($search);
    if ($commune) $biens = array_values(array_filter($biens, fn($b) => $b['commune'] === $commune));
} else {
    $biens = db_get_biens($commune, $statut);
}

// Export CSV
if (!empty($_GET['export'])) {
    $rows = array_map(fn($b) => [
        $b['id'], $b['adresse'], $b['commune'], $b['quartier'],
        $b['type'], $b['proprio'], $b['locataire'] ?? '',
        $b['statut'], $b['loyer'] ?? 0,
        $b['date_creation'],
    ], $biens);
    lp_export_csv($rows, 'lopango-biens-' . date('Ymd') . '.csv', [
        'Identifiant','Adresse','Commune','Quartier',
        'Type','Propriétaire','Locataire',
        'Statut','Loyer (USD)','Date Création',
    ]);
}

// Pagination
$paged = lp_paginate($biens, $page);
$biensPaged = $paged['items'];

// Résumé statuts
$countStatuts = ['occupé'=>0,'libre'=>0,'litige'=>0,'travaux'=>0];
foreach ($biens as $b) { if (isset($countStatuts[$b['statut']])) $countStatuts[$b['statut']]++; }
?>

<div class="page-hdr">
  <div class="page-title">Biens Locatifs</div>
  <div class="page-sub">
    <?= $commune ? lp_h($commune) . ' · ' : '' ?>
    <?= count($biens) ?> biens<?= $search ? " · Résultats pour « {$search} »" : '' ?>
  </div>
  <div class="page-meta">
    <!-- Recherche -->
    <form method="GET" action="" style="display:flex;align-items:center;gap:8px">
      <input type="hidden" name="page" value="biens">
      <?php if ($commune): ?><input type="hidden" name="commune" value="<?= lp_h($commune) ?>"><?php endif; ?>
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input class="search-input" type="text" name="q" placeholder="ID, adresse, propriétaire…"
               value="<?= lp_h($search) ?>">
      </div>
      <!-- Filtre statut -->
      <select name="statut" class="form-select" style="width:140px" onchange="this.form.submit()">
        <option value="">Tous statuts</option>
        <?php foreach (STATUTS_BIEN as $s): ?>
        <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
      <?php if ($search || $statut): ?>
      <a href="<?= url('biens') ?>" class="btn btn-secondary btn-sm">× Effacer</a>
      <?php endif; ?>
    </form>

    <div class="page-actions">
      <?php if (auth_is_agent()): ?>
      <a href="<?= url('recensement') ?>" class="btn btn-primary btn-sm">+ Recenser un Bien</a>
      <?php endif; ?>
      <a href="<?= url('biens') . '&export=1' . ($commune ? '&commune=' . $commune : '') ?>"
         class="btn btn-secondary btn-sm">↓ Exporter CSV</a>
    </div>
  </div>
</div>

<div class="content">
  <!-- Résumé par statut -->
  <div class="kpi-grid kpi-grid-4" style="max-width:800px;margin-bottom:16px">
    <div class="kpi-card" style="cursor:pointer" onclick="window.location='<?= url('biens', ['statut'=>'occupé', 'commune'=>$commune??'']) ?>'">
      <div class="kpi-label">Occupés</div>
      <div class="kpi-val green"><?= $countStatuts['occupé'] ?></div>
      <span class="badge badge-ok" style="font-size:8px;margin-top:4px">occupé</span>
    </div>
    <div class="kpi-card" style="cursor:pointer" onclick="window.location='<?= url('biens', ['statut'=>'libre', 'commune'=>$commune??'']) ?>'">
      <div class="kpi-label">Libres</div>
      <div class="kpi-val gold"><?= $countStatuts['libre'] ?></div>
      <span class="badge badge-warn" style="font-size:8px;margin-top:4px">libre</span>
    </div>
    <div class="kpi-card" style="cursor:pointer" onclick="window.location='<?= url('biens', ['statut'=>'litige', 'commune'=>$commune??'']) ?>'">
      <div class="kpi-label">Litiges</div>
      <div class="kpi-val red"><?= $countStatuts['litige'] ?></div>
      <span class="badge badge-danger" style="font-size:8px;margin-top:4px">litige</span>
    </div>
    <div class="kpi-card" style="cursor:pointer" onclick="window.location='<?= url('biens', ['statut'=>'travaux', 'commune'=>$commune??'']) ?>'">
      <div class="kpi-label">Travaux</div>
      <div class="kpi-val blue"><?= $countStatuts['travaux'] ?></div>
      <span class="badge badge-info" style="font-size:8px;margin-top:4px">travaux</span>
    </div>
  </div>

  <!-- Tableau -->
  <div class="panel">
    <div class="tbl-container">
      <table class="tbl">
        <thead>
          <tr>
            <th>Identifiant Lopango</th>
            <th>Adresse</th>
            <?php if (!$commune): ?><th>Commune</th><?php endif; ?>
            <th>Type</th>
            <th>Propriétaire</th>
            <th>Locataire</th>
            <th>Statut</th>
            <th class="r">Loyer (USD)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($biensPaged)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:40px;color:var(--hint)">
              Aucun bien trouvé<?= $search ? " pour « {$search} »" : '' ?>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($biensPaged as $b): ?>
          <tr>
            <td><?= lp_code_pill(lp_h($b['id'])) ?></td>
            <td style="font-size:11px;max-width:200px"><?= lp_h($b['adresse']) ?></td>
            <?php if (!$commune): ?>
            <td style="font-size:11px"><?= lp_h($b['commune']) ?></td>
            <?php endif; ?>
            <td style="font-size:11px;color:var(--hint)"><?= lp_h($b['type']) ?></td>
            <td style="font-size:11px"><?= lp_h($b['proprio']) ?></td>
            <td style="font-size:10px;color:var(--hint)"><?= lp_h($b['locataire'] ?? '—') ?></td>
            <td><?= lp_badge_statut($b['statut']) ?></td>
            <td class="r">
              <span style="font-family:var(--mono)"><?= $b['loyer'] ? lp_usd($b['loyer']) : '—' ?></span>
            </td>
            <td style="display:flex;gap:4px">
              <a href="<?= url('bien_detail', ['id' => $b['id']]) ?>"
                 class="btn btn-secondary btn-sm">Voir</a>
              <?php if (auth_is_agent() || auth_is_habitat()): ?>
              <a href="<?= url('collecte', ['bien' => $b['id']]) ?>"
                 class="btn btn-gold btn-sm">🎫</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="tbl-footer">
        <span><?= $paged['total'] ?> bien(s) — page <?= $paged['page'] ?>/<?= max(1,$paged['pages']) ?></span>
        <!-- Pagination -->
        <div style="display:flex;gap:4px;align-items:center">
          <?php if ($paged['has_prev']): ?>
          <a href="<?= url('biens', ['p'=>$paged['page']-1,'q'=>$search,'commune'=>$commune??'','statut'=>$statut??'']) ?>"
             class="btn btn-secondary btn-sm">←</a>
          <?php endif; ?>
          <?php for ($pg=1; $pg <= $paged['pages']; $pg++): ?>
          <a href="<?= url('biens', ['p'=>$pg,'q'=>$search,'commune'=>$commune??'','statut'=>$statut??'']) ?>"
             class="btn btn-sm <?= $pg === $paged['page'] ? 'btn-primary' : 'btn-secondary' ?>"><?= $pg ?></a>
          <?php endfor; ?>
          <?php if ($paged['has_next']): ?>
          <a href="<?= url('biens', ['p'=>$paged['page']+1,'q'=>$search,'commune'=>$commune??'','statut'=>$statut??'']) ?>"
             class="btn btn-secondary btn-sm">→</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
