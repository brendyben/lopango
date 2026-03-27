<?php
/* ── SIDEBAR ─────────────────────────────────────────────────────────────── */
$navItems   = auth_nav_items();
$currentPg  = current_page();
$pendingCount = count(array_filter(db_get_paiements(auth_commune()), fn($p) => $p['statut'] === 'pending'));
$litiquesCount = count(db_get_biens(auth_commune(), 'litige'));
?>
    <!-- ══════════════════════════════════════ SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="sb-section">
        <?php foreach ($navItems as $item): ?>
        <?php
          $isActive = ($currentPg === $item['id']);
          $badge = '';
          if (isset($item['badge'])) {
              if ($item['badge'] === 'pending') {
                  if ($pendingCount > 0) {
                      $badge = "<span class=\"sb-count warn\">{$pendingCount}</span>";
                  }
              } elseif ($item['badge'] === 'danger') {
                  if ($litiquesCount > 0) {
                      $badge = "<span class=\"sb-count danger\">{$litiquesCount}</span>";
                  }
              } elseif (is_int($item['badge']) && $item['badge'] > 0) {
                  $badge = "<span class=\"sb-count warn\">{$item['badge']}</span>";
              }
          }
        ?>
        <a href="<?= lp_h(BASE_URL . '/index.php' . $item['url']) ?>"
           class="sb-item <?= $isActive ? 'active' : '' ?>">
          <span class="sb-icon"><?= $item['icon'] ?></span>
          <span><?= lp_h($item['label']) ?></span>
          <?= $badge ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Séparateur -->
      <div style="margin:0 14px;height:1px;background:var(--border-s)"></div>

      <!-- Actions rapides -->
      <div class="sb-section">
        <div class="sb-section-label">Actions Rapides</div>
        <?php if (auth_is_agent()): ?>
        <a href="<?= url('recensement') ?>" class="sb-item">
          <span class="sb-icon">+</span>
          <span>Nouveau Bien</span>
        </a>
        <a href="<?= url('collecte') ?>" class="sb-item">
          <span class="sb-icon">🎫</span>
          <span>Nouvelle Quittance</span>
        </a>
        <?php elseif (auth_is_habitat()): ?>
        <a href="<?= url('biens') . '&export=1' ?>" class="sb-item">
          <span class="sb-icon">↓</span>
          <span>Exporter CSV</span>
        </a>
        <?php elseif (auth_is_hvk()): ?>
        <a href="<?= url('rapport_hvk') ?>" class="sb-item">
          <span class="sb-icon">📄</span>
          <span>Rapport Mensuel</span>
        </a>
        <?php endif; ?>
      </div>

      <div class="sb-footer">
        <div class="sb-footer-name"><?= lp_h(auth_user()['nom'] ?? '—') ?></div>
        <div class="sb-footer-code"><?= lp_h(auth_code() ?? '—') ?></div>
        <?php if (auth_is_agent()): ?>
        <div style="margin-top:8px;font-size:9px;color:var(--hint)">
          Score: <strong style="color:var(--green)"><?= lp_h((string)(auth_user()['score'] ?? 0)) ?>%</strong>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="content-area">
      <div id="main-content">
