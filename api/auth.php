<?php
/**
 * LOPANGO — API Auth
 * POST /api/auth.php          → login (retourne token session)
 * DELETE /api/auth.php        → logout
 * GET /api/auth.php?me=1      → utilisateur courant
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!auth_check()) api_error('Non connecté.', 401);
        $user = auth_user();
        unset($user['password_hash'], $user['password_plain']);
        api_ok(['user' => $user, 'role' => auth_role(), 'commune' => auth_commune()]);
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $code = trim($body['code'] ?? '');
        $pass = $body['password'] ?? '';
        if (empty($code) || empty($pass)) api_error('Code et mot de passe obligatoires.');
        if (!auth_login($code, $pass)) api_error('Identifiants incorrects.', 401);
        $user = auth_user();
        unset($user['password_hash'], $user['password_plain']);
        api_ok([
            'user'       => $user,
            'role'       => auth_role(),
            'commune'    => auth_commune(),
            'session_id' => session_id(),
            'expires_at' => date('c', time() + SESSION_LIFETIME),
        ], 200);
        break;

    case 'DELETE':
        if (!auth_check()) api_error('Non connecté.', 401);
        $_SESSION = [];
        session_destroy();
        api_ok(['logged_out' => true]);
        break;

    default:
        api_error('Méthode non supportée.', 405);
}
