<?php
/**
 * LOPANGO — Contrat de Bail
 * Généré automatiquement depuis la fiche bien
 * URL : /index.php?page=contrat_bail&id=KIN-GOM-LUTU-120-U01
 */
// Accessible aux agents terrain, habitat et HVK
if (!auth_logged()) { redirect(url('login')); }

$bienId = $_GET['id'] ?? '';
$bien   = $bienId ? db_get_bien($bienId) : null;

if (!$bien) {
    echo '<div class="alert alert-danger">Bien introuvable.</div>';
    return;
}

// Vérification commune
if (auth_role() !== ROLE_HVK && ($bien['commune'] ?? $bien['commune_code'] ?? ''!=='' && ($bien['commune'] ?? $bien['commune_code'] ?? '') !== auth_commune()) {
    echo '<div class="alert alert-danger">Accès non autorisé.</div>';
    return;
}

$aujourd_hui = date('d/m/Y');
$pageTitle = 'Contrat de Bail — ' . $bienId;

// Données du bien
$adresse      = $bien['avenue']    ?? '';
$parcelle     = $bien['parcelle']  ?? '';
$quartier     = $bien['quartier']  ?? '';
$commune      = db_get_commune($bien['commune'] ?? $bien['commune_code'] ?? '')['nom'] ?? '';
$type_usage   = ($bien['type'] ?? 'Habitation') === 'Commerce' ? 'COMMERCIAL' : 'RÉSIDENTIEL';
$loyer        = lp_fc($bien['loyer'] ?? $bien['loyer_usd'] ?? 0);
$loyer_usd    = $bien['loyer'] ?? $bien['loyer_usd'] ?? 0;

// Garantie selon usage
if ($type_usage === 'COMMERCIAL') {
    $garantie_mois = 6;
    $garantie_txt  = 'Six (6) mois';
} else {
    $garantie_mois = 3;
    $garantie_txt  = 'Trois (3) mois';
}
$garantie_montant = lp_fc($loyer_usd * $garantie_mois);

// Bailleur
$bailleur_nom  = $bien['proprio']     ?? '...........................';
$bailleur_tel  = $bien['proprio_tel'] ?? '';

// Locataire
$locataire_nom = $bien['locataire']     ?? '...........................';
$locataire_tel = $bien['locataire_tel'] ?? '';

// Numéro du contrat
$num_contrat = 'BAIL-' . $bienId . '-' . date('Y');

// QR URL
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?data='
    . urlencode($bienId) . '&size=80x80&color=0f4c35&margin=2';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contrat de Bail — <?= lp_h($bienId) ?></title>
<style>
/* ── RESET ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Times New Roman', Times, serif;
  font-size: 11pt;
  color: #000;
  background: #f5f5f5;
  line-height: 1.5;
}

/* ── TOOLBAR (screen only) ── */
.toolbar {
  background: #0f4c35;
  color: #fff;
  padding: 10px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.toolbar-title { font-size: 14px; font-weight: 600; }
.toolbar-btns { display: flex; gap: 8px; }
.btn-print {
  background: #c9a227; color: #000; border: none;
  padding: 7px 18px; border-radius: 4px; font-size: 12px;
  font-weight: 600; cursor: pointer;
}
.btn-close {
  background: rgba(255,255,255,.2); color: #fff; border: 1px solid rgba(255,255,255,.3);
  padding: 7px 14px; border-radius: 4px; font-size: 12px; cursor: pointer;
  text-decoration: none;
}

/* ── PAGE A4 ── */
.page-wrapper {
  display: flex;
  justify-content: center;
  padding: 20px;
}
.page-a4 {
  width: 210mm;
  min-height: 297mm;
  background: #fff;
  padding: 15mm 20mm 15mm 20mm;
  box-shadow: 0 2px 20px rgba(0,0,0,.15);
  position: relative;
}

/* ── EN-TÊTE ── */
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 6mm;
}
.header-left {
  flex: 1;
}
.header-left .ministry {
  font-size: 9pt;
  font-weight: bold;
  line-height: 1.4;
  margin-bottom: 3mm;
}
.header-center {
  flex: 2;
  text-align: center;
}
.header-center .republic {
  font-size: 10pt;
  font-weight: bold;
  line-height: 1.6;
}
.header-center .main-title {
  font-size: 18pt;
  font-weight: bold;
  text-transform: uppercase;
  border: 2px solid #000;
  padding: 3mm 8mm;
  display: inline-block;
  margin-top: 3mm;
  letter-spacing: 2px;
}
.header-right {
  flex: 1;
  text-align: right;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 3px;
}
.header-right img {
  width: 80px;
  height: 80px;
}
.header-right .lopango-id {
  font-family: 'Courier New', monospace;
  font-size: 8.5pt;
  font-weight: bold;
  color: #0f4c35;
  background: #f0f8f0;
  border: 1px solid #0f4c35;
  padding: 2px 6px;
  border-radius: 3px;
  letter-spacing: 0.5px;
}
.header-right .num-contrat {
  font-size: 8pt;
  color: #666;
}

.divider {
  border: none;
  border-top: 1px solid #000;
  margin: 4mm 0;
}
.divider-double {
  border-top: 3px double #000;
  margin: 3mm 0;
}

/* ── PARTIES ── */
.parties-section {
  margin: 4mm 0;
}
.parties-title {
  font-size: 10pt;
  font-weight: bold;
  margin-bottom: 2mm;
}
.partie-row {
  margin-bottom: 1.5mm;
  line-height: 1.8;
}
.underline-field {
  border-bottom: 1px solid #000;
  display: inline-block;
  min-width: 80mm;
  padding: 0 2px;
}
.underline-field.short { min-width: 30mm; }
.underline-field.medium { min-width: 50mm; }
.underline-field.long { min-width: 100mm; }
.et-separator {
  text-align: center;
  font-size: 11pt;
  font-weight: bold;
  margin: 3mm 0;
}
.convenu {
  text-align: center;
  font-weight: bold;
  font-size: 11pt;
  margin: 4mm 0;
  text-decoration: underline;
  text-transform: uppercase;
}

/* ── ARTICLES ── */
.section-title {
  font-size: 10.5pt;
  font-weight: bold;
  text-transform: uppercase;
  margin: 4mm 0 2mm 0;
  text-decoration: underline;
}
.article {
  margin-bottom: 2mm;
  padding-left: 0;
}
.article-num {
  font-weight: bold;
  display: inline;
}
.article-text {
  display: inline;
}
.article-indent {
  margin-left: 8mm;
  margin-bottom: 1mm;
}
.bullet {
  margin-left: 12mm;
  margin-bottom: 0.5mm;
}

/* ── SIGNATURE ── */
.signature-section {
  margin-top: 8mm;
}
.signature-date {
  margin-bottom: 6mm;
}
.signature-row {
  display: flex;
  justify-content: space-between;
}
.signature-block {
  width: 45%;
  text-align: center;
}
.signature-title {
  font-weight: bold;
  margin-bottom: 2mm;
}
.signature-subtitle {
  font-size: 9pt;
  color: #333;
  margin-bottom: 12mm;
}
.signature-line {
  border-bottom: 1px solid #000;
  margin-top: 10mm;
  width: 80%;
  margin-left: auto;
  margin-right: auto;
}

/* ── PIED DE PAGE ── */
.page-footer {
  position: absolute;
  bottom: 10mm;
  left: 20mm;
  right: 20mm;
  text-align: center;
  font-size: 8pt;
  color: #666;
  border-top: 1px solid #ccc;
  padding-top: 2mm;
}
.num-bas {
  font-weight: bold;
  font-size: 12pt;
}

/* ── PRINT ── */
@media print {
  body { background: #fff; }
  .toolbar { display: none !important; }
  .page-wrapper { padding: 0; }
  .page-a4 {
    box-shadow: none;
    width: 100%;
    padding: 10mm 15mm;
  }
  @page { size: A4; margin: 0; }
}
</style>
</head>
<body>

<!-- Toolbar (masquée à l'impression) -->
<div class="toolbar">
  <div class="toolbar-title">📄 Contrat de Bail — <?= lp_h($bienId) ?></div>
  <div class="toolbar-btns">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer</button>
    <a href="javascript:history.back()" class="btn-close">✕ Fermer</a>
  </div>
</div>

<div class="page-wrapper">
<div class="page-a4">

  <!-- ══ EN-TÊTE ══ -->
  <div class="header-row">
    <div class="header-left">
      <div class="ministry">
        République Démocratique du Congo<br>
        <strong>VILLE DE KINSHASA</strong><br><br>
        Ministère Provincial du Budget,<br>
        Urbanisme et Habitat.
      </div>
    </div>
    <div class="header-center">
      <div class="main-title">CONTRAT DE BAIL</div>
    </div>
    <div class="header-right">
      <img src="<?= lp_h($qr_url) ?>" alt="QR Lopango">
      <div class="lopango-id"><?= lp_h($bienId) ?></div>
      <div class="num-contrat"><?= lp_h($num_contrat) ?></div>
    </div>
  </div>

  <hr class="divider-double">

  <!-- ══ PARTIES ══ -->
  <div class="parties-section">
    <div class="parties-title">Entre les Soussignés :</div>

    <!-- BAILLEUR -->
    <div class="partie-row">
      Monsieur, Madame, Mademoiselle
      <span class="underline-field"><?= lp_h(strtoupper($bailleur_nom)) ?></span>
    </div>
    <div class="partie-row">
      De Nationalité <span class="underline-field medium">CONGOLAISE</span>
    </div>
    <div class="partie-row">
      Carte d'identité(passeport)n°<span class="underline-field short">&nbsp;</span>
      résidant au n° <span class="underline-field short"><?= lp_h($parcelle) ?></span>
      de l'avenue (Rue) <span class="underline-field medium"><?= lp_h($adresse) ?></span>,
      Quartier <span class="underline-field medium"><?= lp_h($quartier) ?></span>
    </div>
    <div class="partie-row">
      Commune de <span class="underline-field medium"><?= lp_h(strtoupper($commune)) ?></span>
      ci-après dénommé(e) <strong>Bailleur (esse)</strong> d'une part ;
    </div>

    <div class="et-separator">Et</div>

    <!-- LOCATAIRE -->
    <div class="partie-row">
      Monsieur, Madame, Mademoiselle
      <span class="underline-field"><?= lp_h(strtoupper($locataire_nom)) ?></span>
    </div>
    <div class="partie-row">
      De Nationalité <span class="underline-field medium">CONGOLAISE</span>
    </div>
    <div class="partie-row">
      Carte d'identité(passeport)n°<span class="underline-field short">&nbsp;</span>
      résidant au n° <span class="underline-field short">&nbsp;</span>
      de l'avenue (Rue) <span class="underline-field medium">&nbsp;</span>,
      Quartier <span class="underline-field medium">&nbsp;</span>
    </div>
    <div class="partie-row">
      Commune de <span class="underline-field medium">&nbsp;</span>
      ci-après dénommé(e) <strong>Preneur</strong> d'autre part ;
    </div>
  </div>

  <div class="convenu">Il est convenu ce qui suit :</div>

  <!-- ══ ARTICLE I ══ -->
  <div class="section-title">I. Description du Bien</div>
  <div class="article">
    <span class="article-num">Article 1 :</span>
    <span class="article-text"> Le Bailleur met à la disposition du preneur, qui l'accepte, un bien immobilier
    situé au n° <span class="underline-field short"><?= lp_h($parcelle) ?></span>
    avenue(Rue) <span class="underline-field medium"><?= lp_h(strtoupper($adresse)) ?></span>
    Quartier <span class="underline-field medium"><?= lp_h(strtoupper($quartier)) ?></span>
    Commune de <span class="underline-field medium"><?= lp_h(strtoupper($commune)) ?></span>
    composé de <span class="underline-field short"><?= lp_h(strtoupper($type_usage)) ?></span>.</span>
  </div>
  <div class="article">
    <span class="article-num">Article 2 :</span>
    <span class="article-text"> Le Preneur reconnait avoir préalablement visité les lieux loués et que ceux-ci réunissent les conditions requises conformément au Procès-verbal de constat établi par le service de l'Habitat.</span>
  </div>

  <!-- ══ ARTICLE II ══ -->
  <div class="section-title">II. Usage</div>
  <div class="article">
    <span class="article-num">Article 3 :</span>
    <span class="article-text"> L'immeuble loué est à usage
    <span class="underline-field medium"><?= lp_h($type_usage) ?></span>.</span>
  </div>
  <div class="article">
    <span class="article-num">Article 4 :</span>
    <span class="article-text"> Il est interdit au preneur d'apporter une quelconque modification à l'immeuble loué ou d'en changer la destination sans l'accord préalable et écrit du bailleur.</span>
  </div>
  <div class="article-indent">Dans le cas contraire, le bailleur aura l'option soit de sommer le preneur de remettre le lieu loué à l'état initial, soit d'acquérir en sa faveur cette modification.</div>
  <div class="article-indent">Dans ce dernier cas, le preneur perd tout droit de réclamation d'une quelconque indemnité.</div>
  <div class="article-indent">Toute modification convenue entre les deux parties doit faire l'objet d'un écrit visé, par la Division Urbaine de l'Habitat.</div>

  <!-- ══ ARTICLE III ══ -->
  <div class="section-title">III. Loyer</div>
  <div class="article">
    <span class="article-num">Article 5 :</span>
    <span class="article-text"> Le loyer mensuel est fixé en francs congolais ou à l'équivalent en francs Congolais
    <span class="underline-field medium"><?= lp_h(number_format($loyer_usd, 0, ',', ' ') . ' USD') ?></span> (en lettres).</span>
  </div>
  <div class="article">
    <span class="article-num">Article 6 :</span>
    <span class="article-text"> Le taux de loyer ne peut être modifié, sauf en cas de :</span>
  </div>
  <div class="bullet">- plus-value ou moins-value du bien loué ;</div>
  <div class="bullet">- fluctuation monétaire.</div>
  <div class="article-indent">Cette modification doit faire l'objet d'un avenant signé par les deux parties et visé par le Service compétent de l'Habitat.</div>

  <!-- ══ ARTICLE IV ══ -->
  <div class="section-title">IV. Modalité de Payement</div>
  <div class="article">
    <span class="article-num">Article 7 :</span>
    <span class="article-text"> Le paiement du loyer s'effectue en espèces, par chèque certifié ou par virement bancaire au terme convenu, soit au plus tard <span class="underline-field short">au 5ème du mois</span>.</span>
  </div>

  <!-- ══ ARTICLE V ══ -->
  <div class="section-title">V. Garantie</div>
  <div class="article">
    <span class="article-num">Article 8 :</span>
    <span class="article-text"> A la signature du présent contrat de bail, le(la) bailleur(esse) reconnaît avoir reçu du locataire, la somme de <span class="underline-field medium"><?= lp_h(number_format($loyer_usd * $garantie_mois, 0, ',', ' ') . ' USD') ?></span> représentant :</span>
  </div>
  <div class="bullet">☐ Trois (3) mois de loyers pour l'immeuble à l'usage résidentiel ;</div>
  <div class="bullet">☐ Six (6) mois de loyer pour l'immeuble à l'usage commercial ;</div>
  <div class="bullet">☐ Douze (12) mois de loyer pour l'immeuble à usage industriel ou socio culturel.</div>

  <!-- ══ ARTICLE VI ══ -->
  <div class="section-title">VI. Usage de la Garantie</div>
  <div class="article-indent">La garantie locative ne peut être réajustée en cours de bail. Elle ne pourra produire des intérêts, ni être affectée au paiement du loyer.</div>
  <div class="article-indent">Elle sera remboursée, à la fin du bail, à la valeur du dernier taux de loyer payé par le locataire, déduction faite de toutes les sommes dues au bailleur.</div>
  <div class="article">
    <span class="article-num">Article 9 :</span>
    <span class="article-text"> Au terme du présent contrat de bail, la garantie locative est remboursée au locataire après déduction, le cas échéant, des sommes dues au bailleur.</span>
  </div>

  <!-- ══ ARTICLE VII ══ -->
  <div class="section-title">VII. Durée</div>
  <div class="article">
    <span class="article-num">Article 10 :</span>
    <span class="article-text"> Le présent contrat de bail est conclu pour une durée indéterminée ou déterminée de <span class="underline-field medium">INDÉTERMINÉE</span> à dater de sa signature.</span>
  </div>

  <!-- ══ ARTICLE VIII ══ -->
  <div class="section-title">VIII. Obligations du Bailleur</div>
  <div class="article">
    <span class="article-num">Article 11 :</span>
    <span class="article-text"> Le bailleur est tenu de :</span>
  </div>
  <div class="bullet">• mettre à la disposition du locataire l'immeuble loué ;</div>
  <div class="bullet">• garantir au locataire une jouissance paisible du bien loué ;</div>
  <div class="bullet">• s'acquitter des impôts, taxes et autres droits dus à l'Etat en général, en particulier l'impôt sur le revenu locatif et l'impôt foncier dû à la ville.</div>

  <!-- ══ ARTICLE IX ══ -->
  <div class="section-title">IX. Obligations du Preneur</div>
  <div class="article">
    <span class="article-num">Article 12 :</span>
    <span class="article-text"> Le preneur est tenu de retenir à la source la quotité du loyer due pour le paiement de l'impôt sur le revenu locatif et de s'en acquitter dans les dix jours qui suivent le paiement du loyer conformément aux lois et règlements.</span>
  </div>
  <div class="article">
    <span class="article-num">Article 13 :</span>
    <span class="article-text"> Le preneur est tenu de :</span>
  </div>
  <div class="bullet">• payer le loyer au terme convenu ;</div>
  <div class="bullet">• répondre des dégradations du bien loué qui surviendraient pendant le bail et pour lesquelles il serait responsable ;</div>
  <div class="bullet">• payer régulièrement sa facture ou quote-part des factures de consommation d'eau, d'électricité, et de téléphone etc.…</div>

  <!-- ══ ARTICLE X ══ -->
  <div class="section-title">X. Sous-Location ou Cession</div>
  <div class="article">
    <span class="article-num">Article 14 :</span>
    <span class="article-text"> Il est interdit au preneur de sous louer tout ou partie du bien loué ou de céder tout ou partie de son droit de bail, sauf sur accord du bailleur.</span>
  </div>

  <!-- ══ ARTICLE XI ══ -->
  <div class="section-title">XI. Conditions de Résiliation</div>
  <div class="article">
    <span class="article-num">Article 15 :</span>
  </div>
  <div class="article-indent">1. à l'expiration du terme convenu ;</div>
  <div class="article-indent">2. en cas de défaut par l'une des parties de s'exécuter de ses obligations ;</div>
  <div class="article-indent">3. en cas de force majeure ayant rendu l'immeuble inhabitable ;</div>
  <div class="article-indent">4. en cas de révocation mutuelle.</div>
  <div class="article">
    <span class="article-num">Article 16 :</span>
    <span class="article-text"> En cas de vente, cession ou décès, le contrat de bail n'est pas résilié de plein droit. Le nouveau bailleur ou preneur est tenu de se conformer aux dispositions légales en vigueur.</span>
  </div>

  <!-- ══ ARTICLE XII ══ -->
  <div class="section-title">XII. Litige</div>
  <div class="article">
    <span class="article-num">Article 17 :</span>
    <span class="article-text"> A défaut du règlement à l'amiable, tout conflit né de l'interprétation ou de l'exécution du présent contrat est préalablement soumis, à l'arbitrage de la Division Urbaine de l'Habitat. En cas de non conciliation, les instances judiciaires de la ville de Kinshasa sont seules compétentes, pour connaître du litige.</span>
  </div>
  <div class="article-indent">
    Ainsi fait à Kinshasa, le <span class="underline-field short"><?= $aujourd_hui ?></span>
  </div>

  <!-- ══ SIGNATURES ══ -->
  <div class="signature-section">
    <div class="signature-row">
      <div class="signature-block">
        <div class="signature-title">Le Bailleur</div>
        <div class="signature-subtitle">(Nom et Signature)</div>
        <br>
        <div><?= lp_h($bailleur_nom) ?></div>
        <div style="font-size:9pt;color:#666"><?= lp_h($bailleur_tel) ?></div>
        <div class="signature-line"></div>
      </div>
      <div class="signature-block">
        <div class="signature-title">Le Preneur</div>
        <div class="signature-subtitle">(Nom et Signature)</div>
        <br>
        <div><?= lp_h($locataire_nom) ?></div>
        <div style="font-size:9pt;color:#666"><?= lp_h($locataire_tel) ?></div>
        <div class="signature-line"></div>
      </div>
    </div>
  </div>

  <!-- Pied de page -->
  <div class="page-footer">
    <span class="num-bas"><?= lp_h($num_contrat) ?></span> &nbsp;|&nbsp;
    LOPANGO · Gouvernance Locative · Ville de Kinshasa &nbsp;|&nbsp;
    Généré le <?= $aujourd_hui ?>
  </div>

</div><!-- /page-a4 -->
</div><!-- /page-wrapper -->

</body>
</html>
