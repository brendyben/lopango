<?php
/* ── VALIDATION ─────────────────────────────────────────────────────────── */
auth_require(ROLE_HABITAT);
$pageTitle = 'Dossiers à Valider';
$dossiers  = [
    ['id'=>'KIN-GOM-BDUL-067B-U01','adresse'=>'Boulevard Lumumba 67B — Apt 1','proprio'=>'KASONGO Marie','type'=>'Habitation','loyer'=>400,'agent'=>'MBEKI Sandra','date'=>'25/03/2025'],
    ['id'=>'KIN-GOM-ABAS-014A-U01','adresse'=>'Avenue Bas-Congo 14A — Commerce','proprio'=>'MUKEBA Robert','type'=>'Commerce','loyer'=>850,'agent'=>'KABILA Emile','date'=>'26/03/2025'],
];
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['action']) && csrf_verify()) {
    $action = $_POST['action']; $did = $_POST['dossier_id'] ?? '';
    flash_set('success', $action==='valider' ? "Bien {$did} validé et enregistré." : "Dossier {$did} rejeté.");
    redirect_page('validation');
}
?>
<div class="page-hdr">
  <div class="page-title">Dossiers à Valider</div>
  <div class="page-sub">Commune de <?=lp_h(db_get_commune(auth_commune())['nom']??auth_commune())?> · <?=count($dossiers)?> dossier(s) en attente</div>
  <div class="page-meta"></div>
</div>
<div class="content">
  <?php foreach($dossiers as $d): ?>
  <div class="panel">
    <div class="panel-hdr">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div><div class="panel-title">Nouveau Recensement</div><div class="panel-sub">Soumis par <?=lp_h($d['agent'])?> · <?=lp_h($d['date'])?></div></div>
        <span class="badge badge-warn badge-lg">En attente validation</span>
      </div>
      <div class="panel-line"></div>
    </div>
    <div class="grid-2" style="margin-bottom:16px">
      <?php foreach([['Identifiant proposé',$d['id'],true],['Adresse',$d['adresse'],false],['Propriétaire',$d['proprio'],false],['Type',$d['type'],false],['Loyer déclaré',lp_usd($d['loyer']).'/mois',true],['Agent recenseur',$d['agent'],false]] as [$k,$v,$mono]): ?>
      <div class="stat-row"><span class="stat-label"><?=lp_h($k)?></span><span class="stat-val <?=$mono?'mono':''?>"><?=lp_h($v)?></span></div>
      <?php endforeach; ?>
    </div>
    <form method="POST" style="display:inline">
      <?=csrf_field()?><input type="hidden" name="dossier_id" value="<?=lp_h($d['id'])?>">
      <input type="hidden" name="action" value="valider">
      <button type="submit" class="btn btn-primary btn-sm">✓ Valider & Enregistrer</button>
    </form>
    <form method="POST" style="display:inline;margin-left:8px">
      <?=csrf_field()?><input type="hidden" name="dossier_id" value="<?=lp_h($d['id'])?>">
      <input type="hidden" name="action" value="rejeter">
      <button type="submit" class="btn btn-danger btn-sm">✗ Rejeter</button>
    </form>
    <a href="<?=url('bien_detail',['id'=>$d['id']])?>" class="btn btn-secondary btn-sm" style="margin-left:8px">Voir détails →</a>
  </div>
  <?php endforeach; ?>
</div>
