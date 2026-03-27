<?php
/**
 * LOPANGO — Configuration Centrale
 * Ville de Kinshasa · Gouvernance Locative
 */

// ── ENVIRONNEMENT ──────────────────────────────────────────────────────────
define('APP_NAME',    'LOPANGO');
define('APP_VERSION', '1.0.0');
// Lire depuis variable d'environnement (Coolify) ou fallback
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// ── CHEMINS ───────────────────────────────────────────────────────────────
define('ROOT_PATH',    dirname(__DIR__));
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('DATA_PATH',    ROOT_PATH . '/data');
define('INCLUDES_PATH',ROOT_PATH . '/includes');
define('VIEWS_PATH',   ROOT_PATH . '/views');
define('API_PATH',     ROOT_PATH . '/api');
define('PUBLIC_PATH',  ROOT_PATH . '/public');

// ── URL DE BASE ───────────────────────────────────────────────────────────
// Coolify : définir APP_URL=https://lopango.bakapdatalabs.com
// XAMPP   : APP_URL=http://localhost/lopango/public  (ou non défini)
$_appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/lopango/public', '/');
define('BASE_URL',   $_appUrl);
define('ASSETS_URL', BASE_URL . '/assets');

// ── BASE DE DONNÉES ───────────────────────────────────────────────────────
// Coolify génère ces variables automatiquement quand on ajoute un service MySQL
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'lopango');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── STOCKAGE : JSON (dev) ou MySQL (prod) ─────────────────────────────────
// Si DB_HOST est défini dans l'environnement → MySQL activé automatiquement
define('USE_JSON', !getenv('DB_HOST'));

// ── SESSION ───────────────────────────────────────────────────────────────
define('SESSION_NAME',     'LOPANGO_SESSION');
define('SESSION_LIFETIME', 3600);

// ── RÔLES ─────────────────────────────────────────────────────────────────
define('ROLE_AGENT',   'agent');
define('ROLE_HABITAT', 'habitat');
define('ROLE_HVK',     'hvk');

define('ROLES', [
    ROLE_AGENT   => ['label' => 'Agent de Terrain',  'color' => '#16a34a'],
    ROLE_HABITAT => ['label' => 'Service Habitat',    'color' => '#1A5FAB'],
    ROLE_HVK     => ['label' => 'Hôtel de Ville',     'color' => '#c9a227'],
]);

// ── STATUTS & TYPES ───────────────────────────────────────────────────────
define('STATUTS_BIEN', ['occupé', 'libre', 'litige', 'travaux']);
define('TYPES_BIEN',   ['Habitation', 'Commerce', 'Bureau', 'Entrepôt']);

// ── IRL : TAUX PAR TYPE (%) ───────────────────────────────────────────────
define('TAUX_IRL', [
    'Habitation' => 15,
    'Commerce'   => 15,
    'Bureau'     => 15,
    'Entrepôt'   => 15,
]);

// ── PAGINATION ────────────────────────────────────────────────────────────
define('PAGE_SIZE', 25);

// ── TIMEZONE ──────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Kinshasa');

// ── ERREURS ───────────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── AUTOLOAD ──────────────────────────────────────────────────────────────
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';

// ── SESSION ───────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
