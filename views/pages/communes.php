<?php
/* ── COMMUNES ────────────────────────────────────────────────────────────── */
auth_require(ROLE_HVK);
$pageTitle = 'Détail par Commune';
$communes  = db_get_communes();
$q         = $_GET['q'] ?? '';
if ($q) $communes = array_values(array_filter($communes, fn($c) => stripos($c['nom'],$q)!==false || stripos($c['code'],$q)!==false));

if (!empty($_GET['export'])) {
    $rows = array_map(fn($c) => [$c['code'],$c['nom'],$c['biens'],$c['occupes'],$c['libres'],$c['litiges'],$c['travaux'],$c['collecte'],$c['attendu'],lp_pct($c['collecte'],$c['attendu']).'%',$c['agents']], $communes);
    lp_export_csv($rows,'lopango-communes-'.date('Ymd').'.csv',['Code','Commune','Biens','Occupés','Libres','Litiges','Travaux','IRL Collecté','IRL Attendu','Taux','Agents']);
}
?>

<div class="page-hdr">
  <div class="page-title">Toutes les Communes</div>
  <div class="page-sub"><?= count($communes) ?> communes · Ville de Kinshasa</div>
  <div class="page-meta">
    <form method="GET" style="display:flex;gap:8px">
      <input type="hidden" name="page" value="communes">
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input class="search-input" name="q" placeholder="Filtrer communes…" value="<?= lp_h($q) ?>">
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Filtrer</button>
      <?php if ($q): ?><a href="<?= url('communes') ?>" class="btn btn-secondary btn-sm">× Effacer</a><?php endif; ?>
    </form>
    <div class="page-actions">
      <a href="<?= url('communes', ['export'=>1]) ?>" class="btn btn-secondary btn-sm">↓ Exporter CSV</a>
    </div>
  </div>
</div>

<div class="content">
  <div class="panel">
    <div class="tbl-container">
      <table class="tbl">
        <thead><tr>
          <th>Commune</th><th>Code</th><th class="r">Biens</th>
          <th class="r">Agents</th><th class="r">IRL Collecté</th>
          <th class="r">IRL Attendu</th><th>Taux Recouvrement</th>
          <th class="r">Litiges</th><th>Tendance</th>
        </tr></thead>
        <tbody>
          <?php
          $trends = ['▲ +12%','▲ +8%','▲ +5%','▼ -3%','▲ +2%','▼ -1%','▲ +7%','▲ +4%','▲ +9%','▼ -5%','▲ +3%','▲ +6%','▲ +8%','▼ -2%','▲ +5%','▲ +1%','▲ +4%','▼ -1%','▲ +2%','▲ +3%','▼ -4%'];
          foreach ($communes as $i => $c):
            $tp = lp_pct($c['collecte'], $c['attendu']);
            $barClass = $tp>=80?'prog-green':($tp>=65?'prog-gold':'prog-red');
            $textColor = $tp>=80?'var(--green)':($tp>=65?'var(--gold)':'var(--red)');
            $trend = $trends[$i] ?? '▲';
            $trendUp = str_contains($trend, '▲');
          ?>
          <tr>
            <td>
              <a href="<?= url('biens', ['commune'=>$c['code']]) ?>" style="font-weight:600;color:var(--text)">
                <?= lp_h($c['nom']) ?>
              </a>
            </td>
            <td><?= lp_code_pill(lp_h($c['code'])) ?></td>
            <td class="r"><span style="font-family:var(--mono)"><?= number_format($c['biens'],0,',',' ') ?></span></td>
            <td class="r"><?= $c['agents'] ?></td>
            <td class="r"><span style="font-family:var(--mono);color:var(--green)"><?= lp_fc($c['collecte']) ?></span></td>
            <td class="r"><span style="font-family:var(--mono);color:var(--hint)"><?= lp_fc($c['attendu']) ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="prog-bar" style="width:90px"><?= lp_progress($tp, $barClass) ?></div>
                <span style="font-family:var(--mono);font-size:11px;font-weight:600;color:<?= $textColor ?>;width:36px"><?= $tp ?>%</span>
              </div>
            </td>
            <td class="r">
              <span style="font-family:var(--mono);color:<?= $c['litiges']>150?'var(--red)':'var(--hint)' ?>">
                <?= $c['litiges'] ?>
              </span>
            </td>
            <td>
              <span style="font-size:11px;color:<?= $trendUp?'var(--green)':'var(--red)' ?>"><?= $trend ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="tbl-footer">
        <span><?= count($communes) ?> commune(s)</span>
        <span>IRL Ville : <strong style="font-family:var(--mono)"><?= lp_fc(array_sum(array_column($communes,'collecte'))) ?></strong></span>
      </div>
    </div>
  </div>
</div>
