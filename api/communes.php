<?php
/**
 * LOPANGO — API REST Communes
 * GET /api/communes.php           → liste toutes les communes
 * GET /api/communes.php?code=GOM  → détail commune + stats
 * GET /api/communes.php?stats=1   → statistiques globales ville
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function api_ok(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success'=>true,'data'=>$data,'ts'=>date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function api_error(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'error'=>$msg,'ts'=>date('c')], JSON_UNESCAPED_UNICODE);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!auth_check() && $apiKey !== 'LOPANGO_DEV_KEY_2025') api_error('Non autorisé.', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') api_error('Méthode non supportée.', 405);

// Stats globales ville
if (!empty($_GET['stats'])) {
    api_ok(db_stats_ville());
}

// Détail commune
$code = $_GET['code'] ?? null;
if ($code) {
    $commune = db_get_commune(strtoupper($code));
    if (!$commune) api_error("Commune introuvable : {$code}", 404);
    $stats = db_stats_commune(strtoupper($code));
    api_ok([
        'commune'       => $commune,
        'stats'         => [
            'nb_biens'   => $stats['nb_biens'],
            'nb_occupes' => $stats['nb_occupes'],
            'nb_libres'  => $stats['nb_libres'],
            'nb_litiges' => $stats['nb_litiges'],
            'nb_travaux' => $stats['nb_travaux'],
            'taux_recouvrement' => lp_pct($commune['collecte'], $commune['attendu']),
        ],
        'agents'        => $stats['agents'],
        'nb_paiements'  => $stats['nb_paiements'],
    ]);
}

// Liste complète avec tri
$communes = db_get_communes();
$sort     = $_GET['sort'] ?? 'nom';
$order    = $_GET['order'] ?? 'asc';

usort($communes, function($a, $b) use ($sort, $order) {
    $av = $a[$sort] ?? 0;
    $bv = $b[$sort] ?? 0;
    $cmp = is_string($av) ? strcmp($av, $bv) : ($av <=> $bv);
    return $order === 'desc' ? -$cmp : $cmp;
});

// Enrichir avec taux
$communes = array_map(function($c) {
    $c['taux_recouvrement'] = lp_pct($c['collecte'], $c['attendu']);
    return $c;
}, $communes);

api_ok([
    'communes'       => $communes,
    'total'          => count($communes),
    'total_biens'    => array_sum(array_column($communes, 'biens')),
    'total_collecte' => array_sum(array_column($communes, 'collecte')),
    'total_attendu'  => array_sum(array_column($communes, 'attendu')),
]);
