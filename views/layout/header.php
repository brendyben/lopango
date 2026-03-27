<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= lp_h($pageTitle ?? 'LOPANGO') ?> — Gouvernance Locative · Kinshasa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/lopango.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
</head>
<body class="app-body">

<?php
/* ── SPLASH SCREEN (première visite uniquement) ── */
$showSplash = empty($_SESSION['splash_shown']);
if ($showSplash) $_SESSION['splash_shown'] = true;
?>

<?php if ($showSplash): ?>
<div id="splash">
  <div class="sp-corner sp-corner-tl"></div>
  <div class="sp-corner sp-corner-tr"></div>
  <div class="sp-corner sp-corner-bl"></div>
  <div class="sp-corner sp-corner-br"></div>
  <div class="sp-inner">
    <div class="sp-emblem">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
        <rect x="7" y="7" width="5" height="22" rx="1.5" fill="#0f4c35"/>
        <rect x="7" y="24" width="15" height="5" rx="1.5" fill="#0f4c35"/>
        <rect x="21" y="7" width="5" height="5" rx="1" fill="#c9a227" opacity=".9"/>
        <rect x="21" y="15" width="5" height="5" rx="1" fill="#c9a227" opacity=".5"/>
        <rect x="28" y="7" width="2" height="22" rx="1" fill="#0f4c35" opacity=".15"/>
      </svg>
    </div>
    <div class="sp-word-wrap"><div class="sp-word" id="sp-wordmark"></div></div>
    <div class="sp-tag">Gouvernance Locative</div>
    <div class="sp-city">République Démocratique du Congo · Kinshasa</div>
    <div class="sp-stats">
      <div class="sp-stat"><span class="sp-stat-n" id="sp-biens">0</span><span class="sp-stat-l">Biens recensés</span></div>
      <div class="sp-stat"><span class="sp-stat-n" id="sp-communes">0</span><span class="sp-stat-l">Communes</span></div>
      <div class="sp-stat"><span class="sp-stat-n" id="sp-irl">0</span><span class="sp-stat-l">IRL collecté (M FC)</span></div>
    </div>
    <div class="sp-prog-wrap">
      <div class="sp-track"><div class="sp-fill" id="sp-fill"></div></div>
      <div class="sp-prog-row">
        <span class="sp-prog-lbl" id="sp-lbl">Initialisation…</span>
        <span class="sp-prog-pct" id="sp-pct">0%</span>
      </div>
      <div class="sp-dots">
        <?php for ($i=0; $i<5; $i++): ?>
        <div class="sp-dot <?= $i===0?'active':'' ?>" id="sd<?=$i?>"></div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
  <div class="sp-wm">AmeriKin LLC · Kinshasa · V<?= APP_VERSION ?> · © <?= date('Y') ?></div>
</div>
<?php endif; ?>

<?php /* ── FLASH MESSAGES ── */ ?>
<?php $flashMessages = flash_get(); ?>

<div id="app">
  <!-- ══════════════════════════════════════ TOPBAR -->
  <div class="topbar">
    <div class="tb-brand">
      <div>
        <div class="tb-logo-mark">LOPANGO</div>
        <div class="tb-logo-sub">Gouvernance Locative · Kinshasa</div>
      </div>
      <div class="tb-divider"></div>
      <div class="tb-project">
        <div class="tb-project-name"><?= lp_h(auth_user()['nom'] ?? '—') ?></div>
        <div class="tb-project-sub"><?= lp_h(auth_user()['commune'] ? ('Commune ' . auth_user()['commune']) : 'Hôtel de Ville') ?></div>
      </div>
    </div>
    <div class="tb-right">
      <div class="tb-badge">
        <div class="tb-dot <?= auth_role() ?>"></div>
        <span><?= lp_h(ROLES[auth_role()]['label'] ?? '—') ?></span>
      </div>
      <div class="tb-user"><?= lp_h(auth_code() ?? '—') ?></div>
      <?php if (!empty($flashMessages)): ?>
      <div class="tb-flash-btn" onclick="document.getElementById('flash-zone').classList.toggle('visible')" title="<?= count($flashMessages) ?> notification(s)">
        <span class="flash-badge"><?= count($flashMessages) ?></span>
      </div>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">⏻ Déconnexion</a>
    </div>
  </div>

  <?php /* ── NOTIFICATIONS FLASH ── */ ?>
  <?php if (!empty($flashMessages)): ?>
  <div id="flash-zone" class="flash-zone">
    <?php foreach ($flashMessages as $flash): ?>
    <div class="flash-item flash-<?= lp_h($flash['type']) ?>">
      <span class="flash-icon"><?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ') ?></span>
      <span><?= lp_h($flash['message']) ?></span>
      <button class="flash-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- MAIN BODY -->
  <div class="main-body">
