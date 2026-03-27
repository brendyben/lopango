<?php
/* ── LOGIN PAGE ─────────────────────────────────────────────────────────── */

$ROLES_CONFIG = [
    'agent'   => ['icon'=>'👮', 'title'=>'Agent de Terrain',  'sub'=>'Recensement & Collecte',     'color'=>'#16a34a', 'hint'=>'ex: AGT-001'],
    'habitat' => ['icon'=>'🏢', 'title'=>'Service Habitat',   'sub'=>'Gestion Communale',           'color'=>'#1A5FAB', 'hint'=>'ex: HAB-GOM'],
    'hvk'     => ['icon'=>'🏛️', 'title'=>'Hôtel de Ville',    'sub'=>'Pilotage Stratégique · HVK', 'color'=>'#b8920f', 'hint'=>'ex: HVK-IRL-001'],
];

$selectedRole = $_GET['role'] ?? null;
if ($selectedRole && !isset($ROLES_CONFIG[$selectedRole])) $selectedRole = null;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $code     = trim($_POST['code']     ?? '');
        $password =      $_POST['password'] ?? '';
        if (auth_login($code, $password)) {
            $_SESSION['run_splash'] = true;
            redirect(url(auth_default_page()));
        } else {
            $error = 'Code ou mot de passe incorrect. Vérifiez vos identifiants.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LOPANGO — Connexion</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/lopango.css">
</head>
<body class="login-body-page">

<div id="login-overlay" style="min-height:100vh;display:flex;flex-direction:column">

  <!-- Topbar -->
  <div class="login-topbar">
    <div class="login-topbar-brand">
      <div class="login-topbar-logo">LOPANGO</div>
      <div class="login-topbar-divider"></div>
      <div class="login-topbar-sub">Gouvernance Locative · Ville de Kinshasa</div>
    </div>
    <div class="login-topbar-right">
      <div class="login-topbar-badge"><div class="login-topbar-dot"></div>Système actif</div>
      <span style="font-family:var(--mono);font-size:9px;color:var(--hint)">V<?= APP_VERSION ?> · <?= date('M Y') ?></span>
    </div>
  </div>

  <!-- Corps -->
  <div class="login-body">

    <?php if ($selectedRole): ?>
    <!-- ══════════════════════════ PAGE CONNEXION DÉDIÉE ══════════════ -->
    <?php $cfg = $ROLES_CONFIG[$selectedRole]; ?>
    <div style="width:100%;max-width:460px">

      <!-- Retour -->
      <a href="<?= lp_h(BASE_URL) ?>/login.php"
         style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--hint);margin-bottom:28px;text-decoration:none;font-family:var(--mono);letter-spacing:.5px;transition:color .18s"
         onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--hint)'">
        ← Changer de rôle
      </a>

      <!-- Carte identité du rôle -->
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;padding:18px 20px;background:var(--surface);border:1px solid var(--border);border-top:3px solid <?= lp_h($cfg['color']) ?>;border-radius:var(--radius-lg);box-shadow:var(--shadow)">
        <div style="width:52px;height:52px;border-radius:var(--radius-lg);background:<?= lp_h($cfg['color']) ?>18;border:1px solid <?= lp_h($cfg['color']) ?>30;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">
          <?= $cfg['icon'] ?>
        </div>
        <div>
          <div style="font-family:var(--serif);font-size:22px;font-weight:700;color:var(--text);line-height:1.1"><?= lp_h($cfg['title']) ?></div>
          <div style="font-size:9px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-top:3px;font-family:var(--mono);color:<?= lp_h($cfg['color']) ?>"><?= lp_h($cfg['sub']) ?></div>
        </div>
      </div>

      <!-- Erreur -->
      <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:16px">
        <span class="alert-icon">✕</span>
        <div class="alert-body"><?= lp_h($error) ?></div>
      </div>
      <?php endif; ?>

      <!-- Formulaire -->
      <div class="panel">
        <div class="panel-hdr">
          <div class="panel-title">Connexion</div>
          <div class="panel-sub">Entrez vos identifiants d'accès</div>
          <div class="panel-line"></div>
        </div>
        <form method="POST" action="<?= lp_h(BASE_URL) ?>/login.php?role=<?= lp_h($selectedRole) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="role" value="<?= lp_h($selectedRole) ?>">
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">Code d'accès</label>
            <input type="text" name="code" class="form-input form-input--mono"
                   placeholder="<?= lp_h($cfg['hint']) ?>" required
                   autocomplete="username"
                   value="<?= lp_h($_POST['code'] ?? '') ?>"
                   style="font-size:14px;letter-spacing:.5px"
                   autofocus>
          </div>
          <div class="form-group" style="margin-bottom:22px">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-input"
                   placeholder="••••••••" required
                   autocomplete="current-password"
                   style="font-size:14px">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;font-size:13px">
            Accéder au système →
          </button>
        </form>
        <div style="margin-top:14px;padding:10px 12px;background:var(--green-faint);border-radius:var(--radius);font-size:10px;color:var(--hint);line-height:1.6">
          <strong>Accès restreint</strong> — Toute utilisation non autorisée est un délit passible de poursuites selon la loi congolaise.
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- ══════════════════════════ PAGE SÉLECTION RÔLE ════════════════ -->
    <div class="login-wrap" style="max-width:900px">
      <div class="login-header">
        <div class="login-eyebrow">République Démocratique du Congo</div>
        <span class="login-logo">LOPANGO</span>
        <div class="login-tagline">Système Numérique de Gouvernance Locative</div>
        <div class="login-sep"></div>
        <div class="login-stamp">
          <span class="login-stamp-dot"></span>
          Impôt sur les Revenus Locatifs · Hôtel de Ville de Kinshasa
        </div>
      </div>

      <div class="roles-grid">
        <?php
        $roleCards = [
            ['role'=>'agent',   'icon'=>'👮', 'title'=>'Agent de Terrain',  'sub'=>'Recensement & Collecte',
             'desc'=>'Recensez les biens locatifs, collectez l\'IRL et synchronisez vos données vers le cloud.'],
            ['role'=>'habitat', 'icon'=>'🏢', 'title'=>'Service Habitat',   'sub'=>'Gestion Communale',
             'desc'=>'Supervisez les biens, validez les dossiers et suivez les recettes de votre commune.'],
            ['role'=>'hvk',     'icon'=>'🏛️', 'title'=>'Hôtel de Ville',    'sub'=>'Pilotage Stratégique · HVK',
             'desc'=>'Vision globale sur les 24 communes, projections budgétaires et alertes fraude.'],
        ];
        ?>
        <?php foreach ($roleCards as $card): ?>
        <a href="<?= lp_h(BASE_URL) ?>/login.php?role=<?= lp_h($card['role']) ?>"
           class="role-card <?= $card['role'] ?>"
           style="text-decoration:none;display:block">
          <div class="role-icon-wrap"><?= $card['icon'] ?></div>
          <div class="role-title"><?= lp_h($card['title']) ?></div>
          <div class="role-sub-<?= $card['role'] ?>"><?= lp_h($card['sub']) ?></div>
          <div class="role-divider"></div>
          <div class="role-desc"><?= lp_h($card['desc']) ?></div>
          <span class="role-btn role-btn-<?= $card['role'] ?>">Sélectionner →</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Footer -->
  <div class="login-footer">
    <span>AmeriKin LLC — Kinshasa, DRC · © <?= date('Y') ?></span>
    <span>Accès restreint aux parties autorisées</span>
    <span>lopango.kin.cd</span>
  </div>

</div>
</body>
</html>

