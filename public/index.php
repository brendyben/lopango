<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * LOPANGO — Point d'Entrée Principal (Router)
 * public/index.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Vérifier authentification
if (!auth_check()) {
    redirect(BASE_URL . '/login.php');
}

// Toucher la session
auth_touch();

// Page courante
$page = $_GET['page'] ?? auth_default_page();

// Vérifier permissions
if (!auth_can_access($page)) {
    flash_set('error', 'Vous n\'avez pas accès à cette section.');
    redirect_page(auth_default_page());
}

// Titre par défaut
$pageTitle = 'LOPANGO';
$pageScripts = '';

// ── CHARGER LA VUE ────────────────────────────────────────────────────────
$viewMap = [
    // Agent
    'recensement' => 'recensement',
    'collecte'    => 'collecte',
    'buffer'      => 'buffer',
    'mes_biens'   => 'mes_biens',
    // Habitat
    'dashboard'   => 'dashboard_habitat',
    'biens'       => 'biens',
    'bien_detail' => 'bien_detail',
    'validation'  => 'validation',
    'agents'      => 'agents',
    'rapports'    => 'rapports',
    // HVK
    'vue_globale' => 'dashboard_hvk',
    'communes'    => 'communes',
    'projections' => 'projections',
    'alertes'     => 'alertes',
    'rapport_hvk' => 'rapport_hvk',
];

$viewFile = $viewMap[$page] ?? null;
$viewPath = $viewFile ? VIEWS_PATH . '/pages/' . $viewFile . '.php' : null;

// ── RENDU ─────────────────────────────────────────────────────────────────
if ($viewPath && file_exists($viewPath)) {
    // Pré-charger la page pour capturer $pageTitle et $pageScripts
    ob_start();
    include $viewPath;
    $pageContent = ob_get_clean();

    // Layout complet
    include VIEWS_PATH . '/layout/header.php';
    include VIEWS_PATH . '/layout/sidebar.php';
    echo $pageContent;
    include VIEWS_PATH . '/layout/footer.php';
} else {
    // Page 404
    include VIEWS_PATH . '/layout/header.php';
    include VIEWS_PATH . '/layout/sidebar.php';
    echo '
    <div class="page-hdr">
      <div class="page-title">Page introuvable</div>
      <div class="page-sub">La section « ' . lp_h($page) . ' » n\'existe pas.</div>
    </div>
    <div class="content">
      <div class="alert alert-warn">
        <span class="alert-icon">⚠</span>
        <div class="alert-body">
          <div class="alert-title">Page non trouvée</div>
          <a href="' . url(auth_default_page()) . '" class="btn btn-primary btn-sm" style="margin-top:8px;display:inline-flex">← Retour au tableau de bord</a>
        </div>
      </div>
    </div>';
    include VIEWS_PATH . '/layout/footer.php';
}
