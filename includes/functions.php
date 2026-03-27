<?php
/**
 * LOPANGO — Fonctions Métier
 */

// ══════════════════════════════════════════════════════════════ FORMATAGE

function lp_fc(int|float|null $n): string {
    return number_format((int)round((float)($n ?? 0)), 0, ',', ' ') . ' FC';
}

function lp_usd(int|float|null $n): string {
    return '$' . number_format((int)round((float)($n ?? 0)), 0, ',', ' ');
}

function lp_pct(int|float $collecte, int|float $attendu): int {
    if ($attendu == 0) return 0;
    return (int)round(($collecte / $attendu) * 100);
}

function lp_date(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function lp_datetime(string $dt): string {
    return date('d/m/Y à H:i', strtotime($dt));
}

// ══════════════════════════════════════════════════════════════ GÉNÉRATION CODE LOPANGO

/**
 * Génère l'identifiant unique Lopango
 * Format : KIN-[COMMUNE]-[AVENUE4]-[PARCELLE]-[UNITE]
 * Exemple : KIN-GOM-TLCM-070C-U01
 */
function lp_gen_code(
    string $commune,
    string $avenue,
    string $parcelle,
    string $unite = 'U01'
): string {
    $ville    = 'KIN';
    $com      = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $commune));
    $avCode   = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $avenue), 0, 4));
    $avCode   = str_pad($avCode, 4, 'X');
    $par      = strtoupper(substr($parcelle, 0, 5));
    $uni      = strtoupper($unite);
    return "{$ville}-{$com}-{$avCode}-{$par}-{$uni}";
}

/**
 * Valide un code Lopango
 */
function lp_validate_code(string $code): bool {
    return (bool)preg_match('/^KIN-[A-Z0-9]{2,6}-[A-Z0-9]{4}-[A-Z0-9]{3,5}-U\d{2}$/', $code);
}

/**
 * Génère le numéro de quittance unique
 * Format : YYYYMMDD-COMMUNE-NNN
 * Exemple : 20250326-GOM-047
 */
function lp_gen_quittance_num(string $commune): string {
    return db_next_quittance_num($commune);
}

// ══════════════════════════════════════════════════════════════ CALCUL IRL

/**
 * Calcule l'IRL théorique basé sur le loyer et le type
 */
function lp_calc_irl(int|float $loyer_usd, string $type = 'Habitation'): int {
    $taux    = TAUX_IRL[$type] ?? 15;
    $taux_fc = 2750; // Taux de change USD → FC (approximatif)
    return (int)round($loyer_usd * $taux_fc * $taux / 100);
}

/**
 * Score de conformité d'un bailleur (0–100)
 * Basé sur l'historique de paiements
 */
function lp_score_conformite(string $bien_id): int {
    $paiements = db_get_paiements_bien($bien_id);
    if (empty($paiements)) return 30; // Pas d'historique = score bas
    $synced = array_filter($paiements, fn($p) => $p['statut'] === 'synced');
    $ratio  = count($synced) / count($paiements);
    return (int)min(100, max(0, round($ratio * 100)));
}

// ══════════════════════════════════════════════════════════════ BADGES HTML

function lp_badge_statut(string $statut): string {
    $map = [
        'occupé'  => ['class' => 'badge-ok',     'label' => 'Occupé'],
        'libre'   => ['class' => 'badge-warn',    'label' => 'Libre'],
        'litige'  => ['class' => 'badge-danger',  'label' => 'Litige'],
        'travaux' => ['class' => 'badge-info',    'label' => 'Travaux'],
        'synced'  => ['class' => 'badge-ok',      'label' => 'Synchronisé'],
        'pending' => ['class' => 'badge-warn',    'label' => 'En attente'],
    ];
    $cfg = $map[$statut] ?? ['class' => 'badge-gray', 'label' => $statut];
    return "<span class=\"badge {$cfg['class']}\">{$cfg['label']}</span>";
}

function lp_badge_role(string $role): string {
    $map = [
        ROLE_AGENT   => ['class' => 'badge-ok',   'label' => 'Agent'],
        ROLE_HABITAT => ['class' => 'badge-info',  'label' => 'Habitat'],
        ROLE_HVK     => ['class' => 'badge-warn',  'label' => 'HVK'],
    ];
    $cfg = $map[$role] ?? ['class' => 'badge-gray', 'label' => $role];
    return "<span class=\"badge {$cfg['class']}\">{$cfg['label']}</span>";
}

function lp_badge_niveau(string $niveau): string {
    return match($niveau) {
        'danger' => '<span class="badge badge-danger">Critique</span>',
        'warn'   => '<span class="badge badge-warn">Avertissement</span>',
        'ok'     => '<span class="badge badge-ok">Normal</span>',
        default  => "<span class=\"badge badge-gray\">{$niveau}</span>",
    };
}

// ══════════════════════════════════════════════════════════════ CODE PILL

function lp_code_pill(string $code): string {
    return "<span class=\"code-pill\">{$code}</span>";
}

// ══════════════════════════════════════════════════════════════ PROGRESS BAR

function lp_progress(int $pct, string $class = 'prog-green'): string {
    $color = match(true) {
        $pct >= 80 => 'prog-green',
        $pct >= 60 => 'prog-gold',
        default    => 'prog-red',
    };
    $cls = $class !== 'auto' ? $class : $color;
    return "<div class=\"prog-bar\"><div class=\"prog-fill {$cls}\" style=\"width:{$pct}%\"></div></div>";
}

// ══════════════════════════════════════════════════════════════ SÉCURITÉ

function lp_h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function lp_json(array $data): string {
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

// ══════════════════════════════════════════════════════════════ CSRF

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ══════════════════════════════════════════════════════════════ FLASH MESSAGES

function flash_set(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ══════════════════════════════════════════════════════════════ EXPORT CSV

function lp_export_csv(array $data, string $filename, array $headers): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ';');
    foreach ($data as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// ══════════════════════════════════════════════════════════════ PAGINATION

function lp_paginate(array $items, int $page, int $per_page = PAGE_SIZE): array {
    $total = count($items);
    $pages = (int)ceil($total / $per_page);
    $page  = max(1, min($page, $pages));
    $slice = array_slice($items, ($page - 1) * $per_page, $per_page);
    return [
        'items'    => $slice,
        'total'    => $total,
        'page'     => $page,
        'pages'    => $pages,
        'per_page' => $per_page,
        'has_prev' => $page > 1,
        'has_next' => $page < $pages,
    ];
}

// ══════════════════════════════════════════════════════════════ DÉTECTION FRAUDE

function lp_detect_fraude(string $commune): array {
    $alertes  = [];
    $paiements = db_get_paiements($commune);
    // Détecter double paiement même bien même période
    $seen = [];
    foreach ($paiements as $p) {
        $key = $p['bien_id'] . '|' . $p['periode'];
        if (isset($seen[$key])) {
            $alertes[] = [
                'type'    => 'fraude',
                'niveau'  => 'danger',
                'titre'   => 'Double paiement détecté',
                'msg'     => "Bien {$p['bien_id']} — deux quittances pour {$p['periode']}.",
                'commune' => $commune,
                'bien_id' => $p['bien_id'],
                'date'    => date('d/m/Y'),
            ];
        }
        $seen[$key] = true;
    }
    return $alertes;
}

// ══════════════════════════════════════════════════════════════ URL HELPERS

function url(string $page, array $params = []): string {
    $base = BASE_URL . '/index.php?page=' . $page;
    foreach ($params as $k => $v) {
        $base .= '&' . urlencode($k) . '=' . urlencode($v);
    }
    return $base;
}

function current_page(): string {
    return $_GET['page'] ?? auth_default_page();
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function redirect_page(string $page, array $params = []): void {
    redirect(url($page, $params));
}
