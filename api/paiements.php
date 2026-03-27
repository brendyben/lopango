<?php
/**
 * LOPANGO — API REST Paiements / Quittances IRL
 * GET    /api/paiements.php              → liste paiements
 * GET    /api/paiements.php?id=XXX       → détail paiement
 * POST   /api/paiements.php              → créer paiement
 * POST   /api/paiements.php?action=sync  → synchroniser buffer
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id']     ?? null;
$action = $_GET['action'] ?? null;
$body   = [];
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $paiements = db_get_paiements();
            $p = array_values(array_filter($paiements, fn($x) => $x['id'] === $id));
            if (empty($p)) api_error("Paiement introuvable : {$id}", 404);
            api_ok($p[0]);
        }
        $commune = $_GET['commune'] ?? null;
        $periode = $_GET['periode'] ?? null;
        $statut  = $_GET['statut']  ?? null;
        $paiements = db_get_paiements($commune, $periode);
        if ($statut) $paiements = array_values(array_filter($paiements, fn($p) => $p['statut'] === $statut));
        api_ok([
            'paiements' => $paiements,
            'total'     => count($paiements),
            'montant_total' => array_sum(array_column($paiements, 'montant')),
        ]);
        break;

    case 'POST':
        if ($action === 'sync') {
            $commune = $body['commune'] ?? auth_commune();
            if (!$commune) api_error('Commune obligatoire pour la synchronisation.');
            $count = db_sync_paiements($commune);
            api_ok(['synced' => $count, 'commune' => $commune]);
        }

        // Créer paiement
        foreach (['bien_id','montant','periode'] as $f) {
            if (empty($body[$f])) api_error("Champ obligatoire : {$f}");
        }
        $bien = db_get_bien($body['bien_id']);
        if (!$bien) api_error("Bien introuvable : {$body['bien_id']}", 404);

        $montant = (int)$body['montant'];
        if ($montant <= 0) api_error('Montant invalide.');

        $num = lp_gen_quittance_num($bien['commune']);
        $paiement = [
            'id'            => 'PAY-' . str_pad(db_next_id('paiements'), 4, '0', STR_PAD_LEFT),
            'num_quittance' => $num,
            'bien_id'       => $bien['id'],
            'agent_code'    => auth_code() ?? ($body['agent_code'] ?? 'API'),
            'commune'       => $bien['commune'],
            'montant'       => $montant,
            'periode'       => trim($body['periode']),
            'mode_paiement' => $body['mode_paiement'] ?? 'Espèces',
            'reference'     => $body['reference'] ?? '',
            'statut'        => 'pending',
            'date'          => date('Y-m-d'),
            'heure'         => date('H:i'),
            'synced_at'     => null,
        ];
        if (!db_create_paiement($paiement)) api_error('Erreur enregistrement.', 500);
        api_ok($paiement, 201);
        break;

    default:
        api_error('Méthode non supportée.', 405);
}
