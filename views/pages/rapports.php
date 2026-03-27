<?php
/* ── RAPPORTS HABITAT ────────────────────────────────────────────────────── */
auth_require(ROLE_HABITAT);
$pageTitle = 'Rapports & Export';
$commune   = auth_commune();
$com       = db_get_commune($commune);
$biens     = db_get_biens($commune);
$paiements = db_get_paiements($commune);

if (!empty($_GET['export_biens'])) {
    $rows = array_map(fn($b)=>[$b['id'],$b['adresse'],$b['quartier'],$b['type'],$b['proprio'],$b['locataire']??'',$b['statut'],$b['loyer']??0,$b['date_creation']], $biens);
    lp_export_csv($rows,'biens-'.$commune.'-'.date('Ymd').'.csv',['Identifiant','Adresse','Quartier','Type','Propriétaire','Locataire','Statut','Loyer USD','Date']);
}
if (!empty($_GET['export_paie'])) {
    $rows = array_map(fn($p)=>[$p['num_quittance'],$p['bien_id'],$p['periode'],$p['montant'],$p['mode_paiement'],$p['statut'],$p['date']], $paiements);
    lp_export_csv($rows,'paiements-'.$commune.'-'.date('Ymd').'.csv',['Quittance','Bien ID','Période','Montant FC','Mode','Statut','Date']);
}
?>
<div class="page-hdr">
  <div class="page-title">Rapports & Export</div>
  <div class="page-sub">Commune de <?=lp_h($com['nom']??$commune)?> · Extraction de données</div>
  <div class="page-meta"></div>
</div>
<div class="content">
  <div class="grid-2">
    <div class="panel">
      <div class="panel-hdr"><div class="panel-title">Exporter les Données</div><div class="panel-line"></div></div>
      <?php foreach([
        ['export_biens','📋','Biens locatifs complet','Tous les biens avec statuts et propriétaires'],
        ['export_paie', '💰','Paiements IRL',         'Toutes les quittances enregistrées'],
      ] as [$key,$icon,$titre,$desc]): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-s)">
        <div><div style="font-size:12px;font-weight:500"><?=$icon?> <?=lp_h($titre)?></div><div style="font-size:10px;color:var(--hint);margin-top:2px"><?=lp_h($desc)?></div></div>
        <a href="<?=url('rapports',[$key=>1])?>" class="btn btn-gold btn-sm">↓ CSV</a>
      </div>
      <?php endforeach; ?>
      <?php foreach([
        ['Biens en litige','litige'],['Biens libres','libre'],
      ] as [$label,$statut]): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-s)">
        <div><div style="font-size:12px;font-weight:500">⚠️ <?=lp_h($label)?></div><div style="font-size:10px;color:var(--hint);margin-top:2px">Filtrés par statut</div></div>
        <a href="<?=url('biens',['commune'=>$commune,'statut'=>$statut,'export'=>1])?>" class="btn btn-gold btn-sm">↓ CSV</a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="panel">
      <div class="panel-hdr"><div class="panel-title">Résumé du Mois</div><div class="panel-line"></div></div>
      <?php foreach([
        ['Période',date('F Y')],['Commune',$com['nom']??$commune],
        ['IRL Collecté',lp_fc($com['collecte']??0)],['IRL Attendu',lp_fc($com['attendu']??0)],
        ['Taux recouvrement',lp_pct($com['collecte']??0,$com['attendu']??1).'%'],
        ['Biens recensés',count($biens)],['Paiements',count($paiements)],
        ['Généré le',date('d/m/Y H:i')],
      ] as [$k,$v]): ?>
      <div class="stat-row"><span class="stat-label"><?=lp_h($k)?></span><span class="stat-val mono"><?=lp_h((string)$v)?></span></div>
      <?php endforeach; ?>
      <div style="margin-top:14px">
        <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Imprimer le Rapport</button>
      </div>
    </div>
  </div>
</div>
