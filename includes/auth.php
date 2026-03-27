<?php
/**
 * LOPANGO — Gestion Authentification & Rôles
 */

// ── VÉRIFIER SI CONNECTÉ ──────────────────────────────────────────────────
function auth_check(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['code']);
}

// ── UTILISATEUR COURANT ───────────────────────────────────────────────────
function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function auth_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

function auth_commune(): ?string {
    return $_SESSION['user']['commune'] ?? null;
}

function auth_code(): ?string {
    return $_SESSION['user']['code'] ?? null;
}

// ── VÉRIFIER RÔLE ─────────────────────────────────────────────────────────
function auth_is(string $role): bool {
    return auth_role() === $role;
}

function auth_is_agent(): bool   { return auth_is(ROLE_AGENT); }
function auth_is_habitat(): bool { return auth_is(ROLE_HABITAT); }
function auth_is_hvk(): bool     { return auth_is(ROLE_HVK); }

function auth_is_admin(): bool {
    return auth_is(ROLE_HVK) || auth_is(ROLE_HABITAT);
}

// ── FORCER CONNEXION ──────────────────────────────────────────────────────
function auth_require(?string $role = null): void {
    if (!auth_check()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if ($role && !auth_is($role)) {
        header('Location: ' . BASE_URL . '/?error=forbidden');
        exit;
    }
}

// ── CONNEXION ─────────────────────────────────────────────────────────────
function auth_login(string $code, string $password): bool {
    $user = db_auth_utilisateur($code, $password);
    if (!$user) return false;
    // Créer la session
    $_SESSION['user']        = $user;
    $_SESSION['login_at']    = time();
    $_SESSION['last_active'] = time();
    // Régénérer l'ID de session (sécurité)
    session_regenerate_id(true);
    return true;
}

// ── DÉCONNEXION ───────────────────────────────────────────────────────────
function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// ── MISE À JOUR ACTIVITÉ ──────────────────────────────────────────────────
function auth_touch(): void {
    if (auth_check()) {
        // Expiration après inactivité
        if (time() - ($_SESSION['last_active'] ?? 0) > SESSION_LIFETIME) {
            auth_logout();
        }
        $_SESSION['last_active'] = time();
    }
}

// ── NAVIGATION PAR RÔLE ───────────────────────────────────────────────────
function auth_nav_items(): array {
    $role = auth_role();
    $nav = [
        ROLE_AGENT => [
            ['id' => 'recensement', 'icon' => '🏠', 'label' => 'Recenser un Bien',   'url' => '?page=recensement'],
            ['id' => 'collecte',    'icon' => '🎫', 'label' => 'Collecte IRL',        'url' => '?page=collecte'],
            ['id' => 'buffer',      'icon' => '🔄', 'label' => 'Synchronisation',     'url' => '?page=buffer', 'badge' => 'pending'],
            ['id' => 'mes_biens',   'icon' => '📋', 'label' => 'Mes Collectes',       'url' => '?page=mes_biens'],
        ],
        ROLE_HABITAT => [
            ['id' => 'dashboard',   'icon' => '📊', 'label' => 'Tableau de Bord',     'url' => '?page=dashboard'],
            ['id' => 'biens',       'icon' => '🏠', 'label' => 'Biens Locatifs',      'url' => '?page=biens'],
            ['id' => 'validation',  'icon' => '✅', 'label' => 'Validation',          'url' => '?page=validation', 'badge' => 2],
            ['id' => 'agents',      'icon' => '👮', 'label' => 'Agents',              'url' => '?page=agents'],
            ['id' => 'rapports',    'icon' => '📄', 'label' => 'Rapports & Export',   'url' => '?page=rapports'],
        ],
        ROLE_HVK => [
            ['id' => 'vue_globale', 'icon' => '🏛️', 'label' => 'Vue Globale',         'url' => '?page=vue_globale'],
            ['id' => 'communes',    'icon' => '🗺️', 'label' => 'Communes',            'url' => '?page=communes'],
            ['id' => 'projections', 'icon' => '📈', 'label' => 'Projections',         'url' => '?page=projections'],
            ['id' => 'alertes',     'icon' => '🔔', 'label' => 'Alertes',             'url' => '?page=alertes', 'badge' => 'danger'],
            ['id' => 'rapport_hvk', 'icon' => '📄', 'label' => 'Rapport Mensuel',     'url' => '?page=rapport_hvk'],
        ],
    ];
    return $nav[$role] ?? [];
}

// ── PAGE PAR DÉFAUT SELON RÔLE ────────────────────────────────────────────
function auth_default_page(): string {
    return match(auth_role()) {
        ROLE_AGENT   => 'recensement',
        ROLE_HABITAT => 'dashboard',
        ROLE_HVK     => 'vue_globale',
        default      => 'login',
    };
}

// ── PERMISSION PAR PAGE ───────────────────────────────────────────────────
function auth_can_access(string $page): bool {
    $perms = [
        // Agent uniquement
        'recensement' => [ROLE_AGENT],
        'collecte'    => [ROLE_AGENT],
        'buffer'      => [ROLE_AGENT],
        'mes_biens'   => [ROLE_AGENT],
        // Habitat uniquement
        'validation'  => [ROLE_HABITAT],
        'rapports'    => [ROLE_HABITAT],
        // HVK uniquement
        'vue_globale' => [ROLE_HVK],
        'communes'    => [ROLE_HVK],
        'projections' => [ROLE_HVK],
        'alertes'     => [ROLE_HVK],
        'rapport_hvk' => [ROLE_HVK],
        // Partagées
        'dashboard'   => [ROLE_HABITAT, ROLE_HVK],
        'biens'       => [ROLE_AGENT, ROLE_HABITAT, ROLE_HVK],
        'agents'      => [ROLE_HABITAT, ROLE_HVK],
    ];
    if (!isset($perms[$page])) return true; // Page non restreinte
    return in_array(auth_role(), $perms[$page]);
}
