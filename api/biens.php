<?php
/**
 * LOPANGO — API REST Biens
 * Endpoints :
 *   GET    /api/biens.php              → liste des biens
 *   GET    /api/biens.php?id=XXX       → détail d'un bien
 *   POST   /api/biens.php              → créer un bien
 *   PUT    /api/biens.php?id=XXX       → mettre à jour
 *   DELETE /api/biens.php?id=XXX       → supprimer
 */

require_once dirname(__DIR__) . '/config/config.php';

// ── CORS (adapter en prod) ────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── AUTH API (clé ou session) ──────────────────────────────────────────────
function api_auth(): bool {
    // Clé API header (pour intégrations externes)
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!empty($apiKey)) {
        // En prod : vérifier contre la DB
        return $apiKey === 'LOPANGO_DEV_KEY_2025';
    }
    // Session navigateur (pour appels AJAX internes)
    return auth_check();
}

// ── RÉPONSES ──────────────────────────────────────────────────────────────
function api_ok(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'ts'      => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_error(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error'   => $message,
        'ts'      => date('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── VALIDATION ────────────────────────────────────────────────────────────
if (!api_auth()) api_error('Non autorisé. Fournissez une clé API ou connectez-vous.', 401);

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

// Corps JSON pour POST/PUT
$body = [];
if (in_array($method, ['POST', 'PUT'])) {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? $_POST;
}

// ──────────────────────────────────────────────────────────────────────────
switch ($method) {

    // ── GET ── liste ou détail ────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $bien = db_get_bien($id);
            if (!$bien) api_error("Bien introuvable : {$id}", 404);
            // Enrichir avec paiements et statistiques
            $bien['paiements']       = db_get_paiements_bien($id);
            $bien['irl_theorique']   = lp_calc_irl($bien['loyer'] ?? 0, $bien['type']);
            $bien['score_conformite']= lp_score_conformite($id);
            api_ok($bien);
        } else {
            $commune = $_GET['commune'] ?? null;
            $statut  = $_GET['statut']  ?? null;
            $q       = $_GET['q']       ?? null;
            $page    = max(1, (int)($_GET['page'] ?? 1));
            $per     = min(100, max(1, (int)($_GET['per_page'] ?? 25)));

            $biens = $q ? db_search_biens($q) : db_get_biens($commune, $statut);

            // Filtres supplémentaires
            if ($commune && $q) {
                $biens = array_values(array_filter($biens, fn($b) => $b['commune'] === $commune));
            }

            $paginated = lp_paginate($biens, $page, $per);
            api_ok([
                'biens'    => $paginated['items'],
                'total'    => $paginated['total'],
                'page'     => $paginated['page'],
                'pages'    => $paginated['pages'],
                'per_page' => $paginated['per_page'],
            ]);
        }
        break;

    // ── POST ── créer un bien ─────────────────────────────────────────────
    case 'POST':
        $required = ['commune', 'avenue', 'parcelle', 'proprio', 'type', 'loyer'];
        foreach ($required as $field) {
            if (empty($body[$field])) api_error("Champ obligatoire manquant : {$field}");
        }

        if (!in_array($body['type'], TYPES_BIEN)) {
            api_error('Type invalide. Valeurs acceptées : ' . implode(', ', TYPES_BIEN));
        }

        $code = lp_gen_code(
            $body['commune'],
            $body['avenue'],
            $body['parcelle'],
            $body['unite'] ?? 'U01'
        );

        if (db_get_bien($code)) {
            api_error("Bien déjà existant : {$code}", 409);
        }

        $bien = [
            'id'            => $code,
            'adresse'       => trim($body['adresse'] ?? ($body['avenue'] . ' ' . $body['parcelle'])),
            'commune'       => strtoupper(trim($body['commune'])),
            'quartier'      => trim($body['quartier'] ?? ''),
            'avenue'        => strtoupper(substr(preg_replace('/[^A-Z0-9]/i','',trim($body['avenue'])),0,4)),
            'parcelle'      => strtoupper(trim($body['parcelle'])),
            'unite'         => strtoupper($body['unite'] ?? 'U01'),
            'type'          => $body['type'],
            'proprio'       => trim($body['proprio']),
            'proprio_tel'   => trim($body['proprio_tel'] ?? ''),
            'loyer'         => (int)$body['loyer'],
            'statut'        => in_array($body['statut'] ?? '', STATUTS_BIEN) ? $body['statut'] : 'libre',
            'locataire'     => trim($body['locataire'] ?? ''),
            'locataire_tel' => trim($body['locataire_tel'] ?? ''),
            'date_creation' => date('Y-m-d'),
            'agent_recenseur' => auth_code() ?? ($body['agent_code'] ?? 'API'),
            'observations'  => trim($body['observations'] ?? ''),
            'score_conformite' => 50,
        ];

        if (!db_create_bien($bien)) api_error('Erreur lors de l\'enregistrement.', 500);
        api_ok($bien, 201);
        break;

    // ── PUT ── mettre à jour ──────────────────────────────────────────────
    case 'PUT':
        if (!$id) api_error('Paramètre id obligatoire.');
        $bien = db_get_bien($id);
        if (!$bien) api_error("Bien introuvable : {$id}", 404);

        // Champs modifiables
        $allowed = ['statut','locataire','locataire_tel','proprio','proprio_tel','loyer','observations','adresse','quartier'];
        $updates = array_intersect_key($body, array_flip($allowed));

        if (isset($updates['statut']) && !in_array($updates['statut'], STATUTS_BIEN)) {
            api_error('Statut invalide.');
        }
        if (isset($updates['loyer'])) $updates['loyer'] = (int)$updates['loyer'];

        if (!db_update_bien($id, $updates)) api_error('Erreur lors de la mise à jour.', 500);
        api_ok(array_merge($bien, $updates));
        break;

    // ── DELETE ── supprimer ───────────────────────────────────────────────
    case 'DELETE':
        if (!$id) api_error('Paramètre id obligatoire.');
        // Seul le HVK peut supprimer
        if (!auth_is_hvk()) api_error('Suppression réservée au niveau HVK.', 403);
        $bien = db_get_bien($id);
        if (!$bien) api_error("Bien introuvable : {$id}", 404);
        if (!db_delete_bien($id)) api_error('Erreur lors de la suppression.', 500);
        api_ok(['deleted' => $id]);
        break;

    default:
        api_error('Méthode non supportée.', 405);
}
